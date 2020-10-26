<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/libs/helpers/autoload.php');
require_once(__ROOT__ . '/libs/TLS/autoloader.php');

use PTLS\TLSContext;
use PTLS\Exceptions\TLSAlertException;

class HomeConnectDevice extends Module
{
    use InstanceHelper,
        BufferHelper,
        HomeConnectHelper;

    const guid_parent = '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}';
    const guid_socket = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';

    private $io;
    private $haId;

    private $tls_loop = 0;

    /**
     * create instance
     * @return bool|void
     */
    public function Create()
    {
        parent::Create();

        // register private properties
        $this->RegisterPropertyString('haId', '');
        $this->RegisterPropertyString('settings', '[]');
        $this->RegisterPropertyInteger('io', 0);

        // register update timer
        $this->RegisterTimer('CheckVariables', 0, $this->_getPrefix() . '_CheckVariables($_IPS[\'TARGET\']);');
        $this->RegisterTimer('KeepAlive', 0, $this->_getPrefix() . '_ConnectEvent($_IPS[\'TARGET\'], false);');

        // set default properties
        $this->State = TLSState::Init;
    }

    /**
     * delete instance
     * @return bool|void
     */
    public function Destroy()
    {
        // delete parent socket
        $this->DestroyInstanceByModuleAndIdent(self::guid_parent, 'HomeConnectSocket_' . $this->InstanceID);

        parent::Destroy();
    }

    /**
     * execute, when kernel is ready
     */
    protected function onKernelReady()
    {
        // update timer
        $this->SetTimerInterval('CheckVariables', 60000);
        $this->SetTimerInterval('KeepAlive', 60000);

        // connect parent i/o device
        if (!$this->HasActiveParent()) {
            $this->RequireParent(self::guid_parent);
        }

        // reconnect socket
        $this->ReconnectParentSocket();

        // register parent socket
        $this->RegisterParentSocket();

        // check variables
        $this->CheckVariables();
    }

    /**
     * Handle state changes of parent io instance
     * @param int $State
     */
    protected function IOChangeState(int $State)
    {
        if ($State == IS_ACTIVE) {
            $this->State = TLSState::Init;
            $this->ConnectEvent(true);
        } else if ($State == IS_INACTIVE) {
            $this->State = TLSState::Init;
            $this->Multi_TLS = NULL;
        }
    }

    /**
     * Check variables
     */
    public function CheckVariables()
    {
        // check, if remote start is enabled
        if ($remote_start_ident = $this->_getIdentifierByNeedle('Remote control start allowance')) {
            if ($operation_state_ident = $this->_getIdentifierByNeedle('Operation State')) {
                if ($power_state_ident = $this->_getIdentifierByNeedle('Operation State')) {
                    $remote_start_enabled = (bool)GetValue($this->GetIDForIdent($remote_start_ident[0]));
                    $current_power_state = (int)GetValue($this->GetIDForIdent($power_state_ident[0]));
                    $current_operation_state = (int)GetValue($this->GetIDForIdent($operation_state_ident[0]));

                    // set control idents
                    $control_idents = ['Start Device'];
                    if (in_array($current_operation_state, [1, 4])) { // 1 = Ready, 2 = Delayed Start, 4 = Pause
                        $control_idents[] = 'Program';
                    }

                    // enable / disable actions, depending on remote start allowance
                    foreach (['Start Device', 'Program'] AS $ident) {
                        if ($idents = $this->_getIdentifierByNeedle($ident)) {
                            foreach ($idents AS $id) {
                                if (
                                    ($ident == 'Program' && $current_power_state && $remote_start_enabled)
                                    || $remote_start_enabled
                                ) {
                                    $this->force_ident = true;
                                    $this->EnableAction($id);
                                } else {
                                    $this->force_ident = true;
                                    $this->DisableAction($id);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Read config & init home connect settings
     */
    protected function ReadConfig()
    {
        $this->haId = $this->ReadPropertyString('haId');
        $this->settings = $this->ReadPropertyString('settings');
        if ($this->io = $this->ReadPropertyInteger('io')) {
            $this->initHomeConnect();
            $this->client_id = IPS_GetProperty($this->io, 'my_client_id');
            // set tokens
            $this->access_token = IPS_GetProperty($this->io, 'access_token');
            $this->refresh_token = IPS_GetProperty($this->io, 'refresh_token');
        }
    }

    /**
     * Register parent client socket
     */
    public function RegisterParentSocket()
    {
        // read config
        $this->ReadConfig();

        // register & configure parent socket
        $ParentID = $this->RegisterParent();
        if ($this->haId && $ParentID > 0) {
            IPS_SetName($ParentID, 'HomeConnect Socket ' . $this->haId);
            IPS_SetIdent($ParentID, 'HomeConnectSocket_' . $this->InstanceID);
            IPS_SetProperty($ParentID, 'Host', $this->endpoint_host);
            IPS_SetProperty($ParentID, 'Port', 443);
            IPS_SetProperty($ParentID, 'Open', true);

            if (IPS_HasChanges($ParentID)) {
                IPS_ApplyChanges($ParentID);
            }
        }
    }

    /**
     * Reconnect parent socket
     * @param bool $force
     */
    public function ReconnectParentSocket($force = false)
    {
        $ParentID = $this->GetParentId();
        if (($this->HasActiveParent() || $force) && $ParentID > 0) {
            IPS_SetProperty($ParentID, 'Open', true);
            IPS_ApplyChanges($ParentID);
        }
    }

    /**
     * Receive and update current data
     * @param string $JSONString
     * @return bool|void
     */
    public function ReceiveData($JSONString)
    {
        // convert json data to array
        $data = json_decode($JSONString, true);

        // append socket buffer to tls buffer
        $Data = utf8_decode($data['Buffer']);

        // check for TLS handshake
        if ($this->State == TLSState::TLSisSend || $this->State == TLSState::TLSisReceived) {
            $this->WaitForResponse(TLSState::TLSisSend);
            $this->SendDebug('Receive TLS Handshake', $Data, 0);
            $this->Handshake = $Data;

            $this->State = TLSState::TLSisReceived;
            return;
        }

        if (!$this->Multi_TLS || $this->State != TLSState::Connected) {
            return;
        }

        // decrypt TLS data
        if ((ord($Data[0]) >= 0x14) && (ord($Data[0]) <= 0x18) && (substr($Data, 1, 2) == "\x03\x03")) {
            $TLSData = $Data;
            $Data = '';
            $TLS = $this->Multi_TLS;
            while (strlen($TLSData) > 0) {
                $len = unpack("n", substr($TLSData, 3, 2))[1] + 5;
                if (strlen($TLSData) >= $len) {
                    try {
                        $Part = substr($TLSData, 0, $len);
                        $TLSData = substr($TLSData, $len);
                        $TLS->encode($Part);
                        $Data .= $TLS->input();
                    } catch (Exception $e) {
                        $this->SendDebug('TLS Error', $e->getMessage(), 0);
                        $this->ReconnectParentSocket();
                        return;
                    }
                } else {
                    break;
                }
            }

            $this->Multi_TLS = $TLS;
            if (strlen($TLSData) > 0) {
                $this->SendDebug('Receive TLS Part', $TLSData, 0);
            }
        } else { // buffer does not match
            return;
        }
        
        // return, if response is empty
        if (empty($Data)) {
            return;
        }

        // event received, parse it!
        if (strstr($Data, 'event:')) {
            $this->reauth = false;
            $Data = $this->_parseEventData($Data);
            $this->SendDebug('HomeConnect Event', json_encode($Data), 1);

            // handle events
            $this->_handleEvents($Data);
        } // reauth
        else if (strstr($Data, '401 Unauthorized') && !$this->reauth) {
            $this->reauth = true;
            $this->ReadConfig();

            if ($this->io) {
                if ($tokens = $this->GetAccessToken(true)) {
                    $this->reauth = false;
                    IPS_SetProperty($this->io, 'access_token', $tokens['access_token']);
                    IPS_SetProperty($this->io, 'refresh_token', $tokens['refresh_token']);
                    IPS_ApplyChanges($this->io);
                }
            }
        } // log data
        else {
            $this->_log('HomeConnect Socket', $Data);
        }
    }

    /**
     * Event parser
     * @param string $data
     * @return array
     */
    private function _parseEventData(string $data)
    {
        $events = [];
        foreach (explode("\r\n", $data) AS $line) {
            if (strstr($line, 'event:')) {
                $event = [
                    'haId' => NULL,
                    'event' => NULL,
                    'data' => []
                ];

                foreach (explode("\n", $line) AS $event_data) {
                    if (strstr($event_data, 'event:')) {
                        $event['event'] = trim(strtolower(substr($event_data, 6)));
                    } else if (strstr($event_data, 'id:')) {
                        $event['haId'] = trim(substr($event_data, 3));
                    } else if (strstr($event_data, 'data:')) {
                        if ($json = json_decode(trim(substr($event_data, 5)), true)) {
                            $event['data'] = $json;
                        }
                    }
                }

                if ($event['haId']) {
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * Event handler
     * @uses ProcessFinishedCallback
     * @uses ProcessAbortedCallback
     *
     * @param array $events
     */
    private function _handleEvents(array $events)
    {
        // read config
        $this->ReadConfig();

        // handle events
        foreach ($events AS $event) {
            if ($event['data'] && isset($event['data']['items'])) {
                foreach ($event['data']['items'] AS $item) {
                    $map = $this->_map('dummy', $item);
                    $map['event'] = $event['event'];

                    $this->_log('HomeConnect ' . $this->haId, json_encode([$item['key'] => $map]));

                    // update variable
                    if ($idents = $this->_getIdentifierByNeedle($map['key'])) {
                        foreach ($idents AS $ident) {
                            $value = is_string($map['value']) ? $this->Translate($map['value']) : $map['value'];

                            // handle device profile settings
                            // custom value on specific methods
                            $action_key = str_replace('', '', strtolower($map['key']));
                            if (in_array($action_key, ['program'])) {
                                $device_settings = json_decode($this->ReadPropertyString('settings'), true);
                                $actions = array_flip($device_settings[$action_key]);
                                $value = isset($actions[$item['value']]) ? $actions[$item['value']] : 0;
                            }
                            // set value
                            if (!is_null($value)) {
                                //LogMessage_Wert($this->GetIDForIdent($ident),$value);
                                $this->_log('HomeConnect Daten', "Variable: ".$this->GetIDForIdent($ident)." Value: ".$value);
                                SetValue($this->GetIDForIdent($ident), $value);
                            }
                        }
                    }
                    // update item event key on finished value '0'
                    if ($item['key'] == 'BSH.Common.Option.StartInRelative' && $item['value'] == 0) {
                        $this->ProcessStartCallback();
                    }

                    // update item event key on finished / aborted state
                    if ($item['key'] == 'BSH.Common.Status.OperationState') {
                        if ($item['value'] == 'BSH.Common.EnumType.OperationState.Finished') {
                            $item['key'] = 'BSH.Common.Event.ProgramFinished';
                        } else if (in_array($item['value'], [
                            'BSH.Common.EnumType.OperationState.Aborting',
                            'BSH.Common.EnumType.OperationState.Ready'
                        ])) {
                            $item['key'] = 'BSH.Common.Event.ProgramAborted';
                        }
                    }
                    switch ($item['key']):
                        // enable / disable webfront action
                        case 'BSH.Common.EnumType.OperationState':
                        case 'BSH.Common.Status.RemoteControlStartAllowed':
                        case 'BSH.Common.Setting.PowerState';
                            $this->CheckVariables();
                            break;
                        // update Option variables
                        case 'BSH.Common.Option.RemainingProgramTime';
                            if ($operation_state_ident = $this->_getIdentifierByNeedle('Operation State')) {
                                if((int)GetValue($this->GetIDForIdent($operation_state_ident[0])) <> 3){
                                    //Seems that a programm is running but we have missed the state Change -> get it
                                    $this->CheckStatus($this->haId);
                                }
                            }
                            break;
                        case 'BSH.Common.Root.ActiveProgram';
                            $this->CheckStatus($this->haId);
                            $this->CheckOptions($this->haId);
                        default:
                            $callbacks = HomeConnectConstants::callbacks;
                            if (isset($callbacks[$item['key']])) {
                                $callback = $callbacks[$item['key']] . 'Callback';
                                if (method_exists($this, $callback)) {
                                    call_user_func([$this, $callback]);
                                }
                            }
                            break;
                    endswitch;
                }
            }
        }
    }

    public function LogMessage_Wert($name, $daten)
	{
	    // Fehlermeldung nach logs schreiben
	    $log = IPS_GetKernelDir()."logs\\Homeconnect.txt";
	    $fp = fopen($log, "a");
	    fwrite($fp, date("H:i:s ").$name.": ".$daten."\r\n");
	    fclose($fp);
	}

    /**
     * Connect to HomeConnect's event channel stream
     * @param bool $force
     * @return bool
     */
    public function ConnectEvent($force = false)
    {
        // check open port
        if (!$this->HasActiveParent()) {
            $this->ReconnectParentSocket(true);
            return false;
        }

        // force reset
        if ($force) {
            $this->State = TLSState::Init;
            $this->Multi_TLS = NULL;
            $this->reauth = false;
        }

        // if event is still connected or connecting, do nothing
        if (($this->State == TLSState::Connected && !is_null($this->Multi_TLS))
            || $this->State == TLSState::TLSisSend) {
            return true;
        }
        
        // read config
        $this->ReadConfig();

        // reset state
        // $this->State = TLSState::Connecting;

        // if parent i/o instance exists, proceed
        if ($this->io) {
            // get access token from parent i/o instance
            $access_token = IPS_GetProperty($this->io, 'access_token');

            // if access token is present, connect to event channel
            if ($access_token && $this->CreateTLSConnection()) {
                // build event channel data
                $Header[] = 'GET /api/homeappliances/' . $this->haId . '/events HTTP/1.1';
                $Header[] = 'Host: ' . $this->endpoint_host;
                $Header[] = 'Accept: text/event-stream';
                $Header[] = 'Connection: keep-alive';
                $Header[] = 'Authorization: Bearer ' . $access_token;
                $Header[] = "\r\n";

                $Data = implode("\r\n", $Header);

                // encrypt data
                $TLS = $this->Multi_TLS;
                $Data = $TLS->output($Data)->decode();
                $this->Multi_TLS = $TLS;
                $this->SendDebug('Send TLS', $Data, 0);

                // send data
                $JSON['DataID'] = self::guid_socket;
                $JSON['Buffer'] = utf8_encode($Data);
                $JSON['InstanceID'] = $this->InstanceID;
                $JSON['Method'] = 'socket';
                $JsonString = json_encode($JSON);

                $this->SendDataToParent($JsonString);

                return true;
            }
        }

        return false;
    }

    /**
     * Init TLS connection to client socket
     * @return bool
     */
    private function CreateTLSConnection()
    {
        // return true, if event channel is still connected
        if ($this->State == TLSState::Connected && $this->Multi_TLS) {
            return true;
        }

        // reset state
        $this->State = TLSState::TLSisSend;

        // init tls config
        $TLSconfig = TLSContext::getClientConfig([]);
        $TLS = TLSContext::createTLS($TLSconfig);

        $this->SendDebug('TLS start', '', 0);
        $loop = 1;
        $SendData = $TLS->decode();
        $this->SendDebug('Send TLS Handshake ' . $loop, $SendData, 0);

        // send handshake data
        $JSON['DataID'] = self::guid_socket;
        $JSON['Buffer'] = utf8_encode($SendData);
        $JSON['Method'] = 'socket';

        $JsonString = json_encode($JSON);
        parent::SendDataToParent($JsonString);

        // check TLS handshake
        while (!$TLS->isHandshaked() && ($loop < 10)) {
            $loop++;
            $Result = $this->WaitForResponse(TLSState::TLSisReceived);
            if ($Result === false) {
                $this->SendDebug('TLS no answer', '', 0);

                if ($this->tls_loop < 2) {
                    $this->tls_loop++;
                    $this->ReconnectParentSocket();
                } else {
                    $this->State = TLSState::Init;
                }
                break;
            }

            $this->tls_loop = 0;
            $this->State = TLSState::TLSisSend;
            $this->SendDebug('Get TLS Handshake', $Result, 0);
            try {
                $TLS->encode($Result);
                if ($TLS->isHandshaked()) {
                    break;
                }
            } catch (TLSAlertException $e) {
                $this->SendDebug('TLS Error', $e->getMessage(), 0);

                // retry
                try {
                    if (strlen($out = $e->decode())) {
                        $JSON['DataID'] = self::guid_socket;
                        $JSON['Buffer'] = utf8_encode($SendData);
                        $JsonString = json_encode($JSON);
                        parent::SendDataToParent($JsonString);
                    }
                } catch (Exception $e) {

                }

                return false;
            }

            // loop handshake
            $SendData = $TLS->decode();
            if (strlen($SendData) > 0) {
                $this->SendDebug('TLS loop ' . $loop, $SendData, 0);
                $JSON['DataID'] = self::guid_socket;
                $JSON['Buffer'] = utf8_encode($SendData);
                $JsonString = json_encode($JSON);
                parent::SendDataToParent($JsonString);
            } else {
                $this->SendDebug('TLS waiting loop ' . $loop, $SendData, 0);
            }
        }

        // check if handshake was successfull
        if (!$TLS->isHandshaked()) {
            return false;
        }

        $this->Multi_TLS = $TLS;

        // debug
        $this->SendDebug('TLS ProtocolVersion', $TLS->getDebug()->getProtocolVersion(), 0);
        $UsingCipherSuite = explode("\n", $TLS->getDebug()->getUsingCipherSuite());
        unset($UsingCipherSuite[0]);
        foreach ($UsingCipherSuite as $Line) {
            $this->SendDebug(trim(substr($Line, 0, 14)), trim(substr($Line, 15)), 0);
        }

        // change state
        $this->State = TLSState::Connected;

        // handshake was successful! :)
        return true;
    }

    /**
     * Waits for client socket response
     * @param int $State
     * @return bool|string
     */
    private function WaitForResponse(int $State)
    {
        for ($i = 0; $i < 500; $i++) {
            if ($this->State == $State) {
                $Handshake = $this->Handshake;
                $this->Handshake = '';
                return $Handshake;
            }
            IPS_Sleep(5);
        }
        return false;
    }

    /**
     * Switch device on / off
     * @param bool $Value
     * @return bool|void
     */
    public function SwitchMode(bool $Value)
    {
        $Ident = $this->_getIdentifierByNeedle('Start Device');
        $this->RequestAction($Ident[0], $Value);
    }

    /**
     * Set program
     * @param string $Value
     * @return bool|void
     */
    public function SetProgram(string $Value)
    {
        $Ident = $this->_getIdentifierByNeedle('Program');
        $this->RequestAction($Ident[0], $Value);
    }

    /**
     * Request Actions
     * @uses UpdateProgram
     * @uses UpdateBrillianceDry
     * @uses UpdateVarioSpeedPlus
     * @uses UpdateIntensivZone
     * @uses UpdateTargetTemperatureRefrigerator
     * @uses UpdateTargetTemperatureFreezer
     * @uses UpdateSuperModeFreezer
     * @uses UpdateSuperModeRefrigerator
     * @uses UpdatePowerState
     * @uses UpdateStartDevice
     * @param string $Ident
     * @param $Value
     * @return bool|void
     */
    public function RequestAction($Ident, $Value)
    {
        // set action value
        $action_value = $Value;

        // read config
        $this->ReadConfig();

        // get method from ident
        $method = $this->_getMethodFromIdent($Ident);

        // custom value on specific methods
        $action_key = str_replace('', '', strtolower($method));
        if (in_array($action_key, ['program'])) {
            $device_settings = json_decode($this->ReadPropertyString('settings'), true);
            $actions = $device_settings[$action_key];
            $action_value = $actions[$Value];
        }

        // build callback data
        $callbackData = [
            'haId' => $this->haId,
            'value' => $action_value
        ];

        // update value
        $method = 'Update' . $method;
        if (method_exists($this, $method)) {
            $response = call_user_func([$this, $method], $callbackData);

            // show errors
            if ($response === false) {
                echo $this->Translate($this->error);
            } // update variable
            else {
                SetValue($this->GetIDForIdent($Ident), $Value);
            }
        } else {
            $this->_log('HomeConnect', sprintf('Method "%s" was not found!', $method));
        }
    }

    /**
     * Helper: Get request method from ident
     * @param string $Ident
     * @return mixed
     */
    private function _getMethodFromIdent(string $Ident)
    {
        return str_replace(
            '_',
            '',
            substr($Ident, strpos($Ident, (string)$this->InstanceID) + strlen($this->InstanceID) + 1)
        );
    }

    /***********************************************************
     * Configuration Form
     ***********************************************************/
    /**
     * set configuration for parent
     * @return string
     */
    public function GetConfigurationForParent()
    {
        // read config
        $this->ReadConfig();

        // return config
        return json_encode([
            'Host' => $this->endpoint_host,
            'Port' => 443,
            'Open' => true
        ]);
    }


    /***********************************************************
     * Configuration Form
     ***********************************************************/
    /**
     * build configuration form
     * @return string
     */
    public function GetConfigurationForm()
    {
        // return current form
        return json_encode([
            'elements' => $this->FormHead(),
            'actions' => $this->FormActions(),
            'status' => $this->FormStatus()
        ]);
    }

    /**
     * return form configurations on configuration step
     * @return array
     */
    protected function FormHead()
    {
        // read config
        $this->ReadConfig();

        {
            return [
                [
                    'type' => 'Label',
                    'label' => '___ [ Logging ] ________________________________________________________________________________________'
                ],
                [
                    'name' => 'log',
                    'type' => 'CheckBox',
                    'caption' => 'enable logging'
                ],
                [
                    'type' => 'Label',
                    'label' => '___ [ Settings ] _______________________________________________________________________________________'
                ],
                [
                    'name' => 'settings',
                    'type' => 'ValidationTextBox',
                    'caption' => 'Settings'
                ],
                [
                    'name' => 'io',
                    'type' => 'ValidationTextBox',
                    'caption' => 'HomeConnect Symcon-Id'
                ]
            ];
        }
    }

    /**
     * return form actions
     * @return array
     */
    protected function FormActions()
    {

        // show buttons, depending on current configuration
        {
            return [
                [
                    'type' => 'Button',
                    'label' => 'Connect Event',
                    'onClick' => $this->_getPrefix() . '_ConnectEvent($id, true)',
                ]
            ];
        }
    }

    /**
     * return from status
     * @return array
     */
    protected function FormStatus()
    {
        return [
            [
                'code' => 101,
                'icon' => 'inactive',
                'caption' => 'Please login'
            ],
            [
                'code' => 102,
                'icon' => 'active',
                'caption' => 'HomeConnectDevice connection has been established!'
            ],
            [
                'code' => 201,
                'icon' => 'inactive',
                'caption' => 'Error: Could not connect to HomeConnectModule!'
            ]
        ];
    }


}
