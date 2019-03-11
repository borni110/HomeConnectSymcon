<?php

trait HomeConnectHelper
{
    private $endpoint;
    private $endpoint_host;
    private $client_id;
    private $client_secret;
    private $redirect;

    private $access_token;
    private $oauth_code;
    private $refresh_token;

    private $error;

    /**
     * init function
     * @param bool $simulator
     */
    private function initHomeConnect()
    {
        $this->endpoint = 'https://api.home-connect.com/';
        $this->endpoint_host = parse_url($this->endpoint, PHP_URL_HOST);
    }

    /**
     * Login
     */
    public function Login()
    {
        $this->_log('HomeConnect', 'Login...');

        // unset current tokens
        $this->access_token = NULL;
        $this->refresh_token = NULL;

        // get access token
        $this->GetAccessToken();

        // check login
        if (!$devices = $this->Api('homeappliances')) {
            $this->access_token = NULL;
        }

        // save valid token
        if (!$this->access_token || !$this->refresh_token) {
            IPS_SetProperty($this->InstanceID, 'access_token', '');
            IPS_SetProperty($this->InstanceID, 'refresh_token', '');
            IPS_ApplyChanges($this->InstanceID);

            $this->SetStatus(201);
            return false;
        }

        return true;
    }

    /**
     * API Wrapper
     * @param string $endpoint
     * @param array $params
     * @param bool $reauth
     * @return bool|mixed
     */
    private function Api($endpoint = NULL, $params = [], $reauth = false)
    {
        // build api url
        $uri = $this->endpoint;
        if (!strstr($endpoint, 'oauth')) {
            $uri .= 'api/';
        }
        $uri .= $endpoint;

        // get method by uri
        $method = basename($uri);
        if (in_array($method, ['active', 'available'])) {
            $method_uri = str_replace('/' . $method, '', $uri);
            $method = basename($method_uri);
        }

        // set default curl options
        $curlOptions = [
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => NULL,
            CURLOPT_HTTPHEADER => [],
            CURLOPT_CUSTOMREQUEST => 'GET'
        ];

        // set options by method
        switch ($method):
            case 'token':
                if ($this->refresh_token) {
                    $params = [
                        'client_secret' => $this->client_secret,
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $this->refresh_token,
                        'state' => $this->InstanceID
                    ];
                } else {
                    $params = [
                        'client_id' => $this->client_id,
                        'client_secret' => $this->client_secret,
                        'redirect_uri' => $this->redirect,
                        'grant_type' => 'authorization_code',
                        'code' => $this->oauth_code,
                        'state' => $this->InstanceID
                    ];
                }

                $curlOptions[CURLOPT_CUSTOMREQUEST] = 'POST';
                $curlOptions[CURLOPT_POSTFIELDS] = http_build_query($params);
                $curlOptions[CURLOPT_HTTPHEADER] = [
                    'Content-Type: application/x-www-form-urlencoded'
                ];
                $this->_log('HomeConnect uri', $uri);
                $this->_log('HomeConnect request', $curlOptions[CURLOPT_CUSTOMREQUEST] . ': ' . $curlOptions[CURLOPT_POSTFIELDS]);
                break;
            default:
                if ($params) {
                    if (isset($params['request'])) {
                        $request = $params['request'];
                        unset($params['request']);
                    } else {
                        $request = 'PUT';
                    }
                    $curlOptions[CURLOPT_CUSTOMREQUEST] = $request;
                    $curlOptions[CURLOPT_POSTFIELDS] = json_encode([
                        'data' => $params
                    ]);

                    $this->_log('HomeConnect uri', $uri);
                    $this->_log('HomeConnect request', $curlOptions[CURLOPT_CUSTOMREQUEST] . ': ' . $curlOptions[CURLOPT_POSTFIELDS]);
                }

                $curlOptions[CURLOPT_HTTPHEADER] = [
                    'Content-Type: application/vnd.bsh.sdk.v1+json',
                    'Accept: application/vnd.bsh.sdk.v1+json',
                    'Accept-Language: de-DE',
                    'Authorization: Bearer ' . $this->access_token
                ];
                break;
        endswitch;

        // init curl or set new uri
        $ch = curl_init($uri);

        // set curl options
        curl_setopt_array($ch, $curlOptions);

        // exec curl
        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

        $this->_log('HomeConnect result', $result ? $result : $status_code);

        // try to convert json to array
        if ($result) {
            $json = json_decode($result, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                $result = $json;
            } // otherwise, convert query to array
            else {
                parse_str($result, $result);
            }
        } // otherwise, parse redirect parameters
        else {
            $parsed_url = parse_url($redirect_url);
            $query = isset($parsed_url['query']) ? $parsed_url['query'] : '';
            parse_str($query, $result);
        }

        // simple error handling
        if (is_array($result) && isset($result['error'])) {
            $this->error = isset($result['error']['description'])
                ? $result['error']['description']
                : $result['error_description'];

            // reauth
            if ($this->refresh_token && isset($result['error']['key']) && $result['error']['key'] == 'invalid_token' && !$reauth) {
                if ($this->GetAccessToken()) {
                    // retry call
                    return $this->Api($endpoint, $params, true);
                }
            }

            $this->_log('HomeConnect', 'Error: ' . $this->error);
            if (!in_array($status_code, [409, 403])) {
                $this->SetStatus(201);
            }
            $this->SetStatus(102);

            return false;
        } else {
            $this->SetStatus(102);
        }

        // strip result
        if (is_array($result) && isset($result['data'][$method])) {
            $result = $result['data'][$method];
        }

        // return result
        return $result;
    }

    /**
     * Get access token from oauth or refresh token
     * @param bool
     * @return bool|array
     */
    private function GetAccessToken($return_tokens = false)
    {
        IPS_LogMessage('TOKEN', $this->refresh_token);
        if (!$this->oauth_code && !$this->refresh_token) {
            return false;
        }


        // get request token
        $endpoint = 'security/oauth/token';

        // get request token data
        if ($data = $this->Api($endpoint)) {
            // set tokens
            $this->access_token = isset($data['access_token']) ? $data['access_token'] : (isset($data['id_token']) ? $data['id_token'] : false);
            $this->refresh_token = isset($data['refresh_token']) ? $data['refresh_token'] : false;

            // return tokens, if requested
            if ($return_tokens) {
                return [
                    'access_token' => $this->access_token,
                    'refresh_token' => $this->refresh_token
                ];
            }

            // save access token
            if ($this->access_token) {
                IPS_SetProperty($this->InstanceID, 'access_token', $this->access_token);
                IPS_SetProperty($this->InstanceID, 'refresh_token', $this->refresh_token);
                IPS_ApplyChanges($this->InstanceID);
            }

            // return bool, if consumer request token was set
            return !!($this->access_token);
        }

        // fallback: return false
        return false;
    }

    /**
     * Callback: Update program
     * @param $data
     * @return mixed
     */
    private function UpdateProgram($data)
    {
        // update data
        $update = $this->Api('homeappliances/' . $data['haId'] . '/programs/selected', [
            'key' => $data['value']
        ]);

        // get current program settings
        if ($update !== false) {
            $program_settings = $this->Api('homeappliances/' . $data['haId'] . '/programs/selected');
            if (isset($program_settings['data']['options'])) {
                foreach ($program_settings['data']['options'] AS $option) {
                    $map = $this->_map('dummy', $option);

                    if ($idents = $this->_getIdentifierByNeedle($map['key'])) {
                        foreach ($idents AS $ident) {
                            $value = is_string($map['value']) ? $this->Translate($map['value']) : $map['value'];
                            if (!is_null($value)) {
                                SetValue($this->GetIDForIdent($ident), $value);
                            }
                        }
                    }
                }
            }
        }

        // return update state
        return $update;
    }

    /**
     * Callback: Update options
     * @param $data
     * @return mixed
     */
    private function UpdateOptions($data)
    {
        // update options
        $update = $this->Api('homeappliances/' . $data['haId'] . '/programs/active/options', [
            'key' => $data['value']
        ]);

        // get current active options
        if ($update !== false) {
            $active_options = $this->Api('homeappliances/' . $data['haId'] . '/programs/active/options');
            $this->_log('UpdateOption', $active_options);
            if (isset($active_options['data']['options'])) {
                foreach ($active_options['data']['options'] AS $option) {
                    $map = $this->_map('dummy', $option);
                    if ($idents = $this->_getIdentifierByNeedle($map['key'])) {
                        foreach ($idents AS $ident) {
                            $value = is_string($map['value']) ? $this->Translate($map['value']) : $map['value'];
                            if (!is_null($value)) {
                                SetValue($this->GetIDForIdent($ident), $value);
                                $this->_log('UpdateOption ' . $this->GetIDForIdent($ident), $value);
                            }
                        }
                    }
                }
            }
        }
        return false;
    }
    /**
     * Callback: Start / Stop device with current program
     * @param $data
     * @return mixed
     */
    private function UpdateStartDevice($data)
    {
        $options = [];

        // get current program settings
        if ($current_program = $this->Api('homeappliances/' . $data['haId'] . '/programs/selected')) {
            $current_program['data']['request'] = $data['value'] ? 'PUT' : 'DELETE';

            // start / stop program
            return $this->Api('homeappliances/' . $data['haId'] . '/programs/active', $current_program['data']);
        } else {
            $this->error = 'Please select a program first.';
        }

        // fallback
        return false;
    }

    /**
     * Callback: Update refrigerator temperature
     * @param $data
     * @return mixed
     */
    private function UpdateTargetTemperatureRefrigerator($data)
    {
        // update data
        return $this->Api('homeappliances/' . $data['haId'] . '/settings/Refrigeration.FridgeFreezer.Setting.SetpointTemperatureRefrigerator', [
            'key' => 'Refrigeration.FridgeFreezer.Setting.SetpointTemperatureRefrigerator',
            'value' => $data['value'],
            'unit' => '°C'
        ]);
    }

    /**
     * Callback: Update freezer temperature
     * @param $data
     * @return mixed
     */
    private function UpdateTargetTemperatureFreezer($data)
    {
        // update data
        return $this->Api('homeappliances/' . $data['haId'] . '/settings/Refrigeration.FridgeFreezer.Setting.SetpointTemperatureFreezer', [
            'key' => 'Refrigeration.FridgeFreezer.Setting.SetpointTemperatureFreezer',
            'value' => $data['value'],
            'unit' => '°C'
        ]);
    }

    /**
     * Callback: Update freezer super mode
     * @param $data
     * @return mixed
     */
    private function UpdateSuperModeFreezer($data)
    {
        // update data
        return $this->Api('homeappliances/' . $data['haId'] . '/settings/Refrigeration.FridgeFreezer.Setting.SuperModeFreezer', [
            'key' => 'Refrigeration.FridgeFreezer.Setting.SuperModeFreezer',
            'value' => (bool)$data['value']
        ]);
    }

    /**
     * Callback: Update refrigerator super mode
     * @param $data
     * @return mixed
     */
    private function UpdateSuperModeRefrigerator($data)
    {
        // update data
        return $this->Api('homeappliances/' . $data['haId'] . '/settings/Refrigeration.FridgeFreezer.Setting.SuperModeRefrigerator', [
            'key' => 'Refrigeration.FridgeFreezer.Setting.SuperModeRefrigerator',
            'value' => (bool)$data['value']
        ]);
    }

    /**
     * Callback: Update power
     * @param $data
     * @return mixed
     */
    private function UpdatePower($data)
    {
        // update data
        return $this->Api('homeappliances/' . $data['haId'] . '/settings/BSH.Common.Setting.PowerState', [
            'key' => 'BSH.Common.Setting.PowerState',
            'value' => 'BSH.Common.EnumType.PowerState.' . ucfirst(strtolower($data['value']))
        ]);
    }

    /**
     * Callback: Update power (alias)
     * @param $data
     * @return mixed
     */
    private function UpdatePowerState($data)
    {
        return $this->UpdatePower($data);
    }

    /**
     * Callback: Process finished
     * reset timer and update process
     */
    private function ProcessFinishedCallback()
    {
        // set program start to false
        if ($ident = $this->_getIdentifierByNeedle('Start Device')) {
            SetValue($this->GetIDForIdent($ident[0]), false);
        }

        // reset timer
        foreach (['Elapsed', 'Remaining'] AS $needle) {
            if ($ident = $this->_getIdentifierByNeedle($needle)) {
                SetValue($this->GetIDForIdent($ident[0]), 0);
            }
        }

        // set progress to 100%
        if ($ident = $this->_getIdentifierByNeedle('Progress')) {
            SetValue($this->GetIDForIdent($ident[0]), 100);
        }

        $this->_log('HomeConnect Callback', __FUNCTION__ . ' executed');
    }

    /**
     * Callback: Process aborted
     * reset timer and progress
     */
    private function ProcessAbortedCallback()
    {
        // set program start to false
        if ($ident = $this->_getIdentifierByNeedle('Start Device')) {
            SetValue($this->GetIDForIdent($ident[0]), false);
        }

        // reset timer
        foreach (['Elapsed', 'Remaining'] AS $needle) {
            if ($ident = $this->_getIdentifierByNeedle($needle)) {
                SetValue($this->GetIDForIdent($ident[0]), 0);
            }
        }

        // reset progress, when not 100%
        if ($ident = $this->_getIdentifierByNeedle('Progress')) {
            $variable_id = $this->GetIDForIdent($ident[0]);
            if (GetValue($variable_id) < 100) {
                SetValue($variable_id, 0);
            }
        }

        $this->_log('HomeConnect Callback', __FUNCTION__ . ' executed');
    }

    /**
     * Callback: Process start
     * set programmstatus and powerstate
     */
    private function ProcessStartCallback()
    {
        // set program start to true
        if ($ident = $this->_getIdentifierByNeedle('Start Device')) {
            SetValue($this->GetIDForIdent($ident[0]), true);
        }
        if ($ident = $this->_getIdentifierByNeedle('Operation State')) {
            SetValue($this->GetIDForIdent($ident[0]), 3);
        }
        $this->_log('HomeConnect Callback', __FUNCTION__ . ' executed');
    }

    /**
     * API data mapper
     * @param null $type
     * @param null $setting
     * @return array|mixed
     */
    private function _map($type = NULL, $setting = NULL)
    {
        // return single mapping
        if (is_null($setting)) {
            $value = HomeConnectConstants::get($type);
            return $value;
        }

        // defaults
        $data = [
            'key' => $setting['key'],
            'value' => $setting['value']
        ];

        // check for valid mapper
        if ($mapper = HomeConnectConstants::get($setting['key'])) {
            if (!is_array($mapper)) {
                $mapper = [
                    'name' => $mapper
                ];
            }

            // convert key to human readable name
            $data['key'] = $mapper['name'];

            // convert values, if present
            if (isset($mapper['values'])) {
                $values = [];
                foreach ($mapper['values'] AS $value_key => $value_data) {
                    // append option, if available
                    if (!isset($value_data['availability']) || in_array($type, $value_data['availability'])) {
                        $values[$value_data['value']['name']] = $value_data['value']['value'];
                    }

                    // set current value
                    if ($value_key == $setting['value']) {
                        $data['value'] = $value_data['value']['value'];
                    }
                }

                // attach custom profile
                $data['custom_profile'] = array_flip($values);

                // convert boolean values, if needed
                if (count($values) == 2) {
                    foreach ($values AS &$v) {
                        $v = ($v == 1) ? true : false;
                    }

                    $data['key'] = isset($values['Standby']) ? 'Power' : $data['key'];
                    $data['value'] = ($data['value'] == 1) ? true : false;
                }
                // detach custom profile
                // convert value to string
                else if (count($values) == 1) {
                    $data['value'] = $this->Translate($data['custom_profile'][$data['value']]);
                    $data['custom_profile'] = '~String';
                }
            }

            // convert value, if needed
            if (isset($mapper['convert'])) {
                switch ($mapper['convert']):
                    case 'minute':
                        $data['value'] = (float)($data['value'] / 60);
                        break;
                    case 'seconds_to_minutes':
                        $data['value'] = ($data['value'] / 60);
                        break;
                endswitch;
            }

            // get alias, if needed
            if (isset($mapper['alias']) && $mapper['alias']) {
                $data['value'] = $this->_map($data['value']);
            }
        }

        // return mapped data
        return $data;
    }
}
