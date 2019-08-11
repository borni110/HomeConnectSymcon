<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/libs/helpers/autoload.php');

class HomeConnect extends Module
{
    use InstanceHelper,
        BufferHelper,
        HomeConnectHelper;

    const guid_device = '{A86BAC56-7DD0-4232-A13D-6CDE5FDD0708}';

    private $ip;
    private $category_id;
    private $redirect;
    private $client_secret;
    private $devices = [];
    private $device_settings = [];
    private $devices_found = 0;

    protected $profile_mappings = [
        'Power State' => 'Power.Switch',
        'Power' => 'Power.Standby',
        'Start Device' => 'StartProgram',
        'Target Temperature Freezer' => 'TargetTemperature.Freezer',
        'Target Temperature Refrigerator' => 'TargetTemperature.Fridge',
        'Super Mode Refrigerator' => '~Switch',
        'Super Mode Freezer' => '~Switch',
        'Program' => 'Program',
        'Current cavity temperature change' => 'Temperature',
        'Target Temperature' => 'TargetTemperature.Oven',
        'Duration' => 'Duration.Fixed',
        'Elapsed' => 'Duration.Elapsed',
        'Start in' => 'Duration.StartIn',
        'Remaining' => 'Duration.Remaining',
        'Progress' => 'Progress',
        'Fill Quantity' => 'FillQuantity',
        'Energy Forecast' => 'Energy.Forecast',
        'Water Forecast' => 'Water.Forecast'
    ];

    protected $icon_mappings = [
        'Brand' => 'Factory',
        'Door' => 'Window',
        'Operation State' => 'Flag',
        'Remote control start allowance' => 'Flag',
        'Remote control activation' => 'Flag',
        'Type' => 'Title',
        'ID' => 'Information',
        'Coffee Temperature' => 'Temperature',
        'Bean Amount' => 'Gauge',
        'Fill Quantity' => 'Gauge',
        'Power' => 'Power',
        'Drying Target' => 'WindSpeed'
    ];

    /**
     * create instance
     */
    public function Create()
    {
        parent::Create();

        // register public properties
        $this->RegisterPropertyString('access_token', '');
        $this->RegisterPropertyString('refresh_token', '');
        $this->RegisterPropertyInteger('category_id', 0);
        $this->RegisterPropertyString('my_client_secret', '');
        $this->RegisterPropertyString('my_redirect', 'https://');
        $this->RegisterPropertyString('my_client_id', '');

        // register update timer
        $this->RegisterTimer('UpdateAccessToken', 0, $this->_getPrefix() . '_UpdateAccessToken($_IPS[\'TARGET\']);');
    }

    /**
     * execute, when kernel is ready
     */
    protected function onKernelReady()
    {
        // check configuration data
        $this->ReadConfig();

        // update timer
        $timer = $this->access_token ? 60000 * 1000 : 0;
        $this->SetTimerInterval('UpdateAccessToken', $timer);

        // register webhook
        $this->RegisterWebhook('/hook/homeconnect');
    }

    /**
     * Read config
     * @return void
     */
    private function ReadConfig()
    {
        // get settings
        $this->access_token = $this->ReadPropertyString('access_token');
        $this->refresh_token = $this->ReadPropertyString('refresh_token');
        $this->redirect = $this->ReadPropertyString('my_redirect');
        $this->category_id = $this->ReadPropertyInteger('category_id');
        // get api uri
        $this->initHomeConnect(FALSE);
        $this->client_id = $this->ReadPropertyString('my_client_id');
        $this->client_secret = $this->ReadPropertyString('my_client_secret');

    }

    /**
     * Read and create devices
     * @param bool $dump_devices
     */
    public function CreateDevices(bool $dump_devices)
    {
        // get devices
        $this->GetDevices();

        // dump devices, if requested
        if ($dump_devices) {
            var_dump($this->devices);
            exit;
        }

        // save / update devices
        $this->SaveDevices();

        // output device info
        echo sprintf($this->Translate($this->devices_found == 1 ? '%d device found!' : '%d devices found!'), $this->devices_found);
    }

    /**
     * get all devices from HomeConnect
     */
    public function GetDevices()
    {
        // read config
        $this->ReadConfig();

        // get devices
        if ($devices = $this->Api('homeappliances')) {
            // parse devices
            if (isset($devices) && is_array($devices)) {
                // loop devices & attach them
                //print_r($devices);
                foreach ($devices AS $device) {
                    // attach device
                    $this->devices[$device['haId']] = [
                        'ID' => $device['haId'],
                        'Brand' => $device['brand'],
                        'Type' => $this->Translate($device['type']),
                        'Settings' => []
                    ];

                    // get default settings
                    $settings = HomeConnectConstants::settings($device['type']);
                    /*
                    echo "settings:\n";
                    print_r($settings);
                    */
                    // attach all available options
                    if (!in_array($device['type'], ['FridgeFreezer'])) {
                        if ($options = $this->Api('homeappliances/' . $device['haId'] . '/programs/selected/options')) {
                            /*
                            echo "options:\n";
                            print_r($options);
                            */
                            $this->_log('HomeConnect Options', $options);
                            foreach ($options AS $option) {
                                if (isset($option['name'])){
                                    $name = $option['name'];
                                }else{
                                     $name="";
                                }
                                $map = $this->_map($device['type'], $option);

                                // append settings
                                $this->devices[$device['haId']]['Settings'][] = [
                                    'key' => $map['key'],
                                    'value' => is_string($map['value']) ? $this->Translate($map['value']) : $map['value'],
                                    'custom_profile' => isset($map['custom_profile']) ? [
                                        'values' => $map['custom_profile']
                                    ] : false,
                                    'name' => $name
                                ];
                            }
                        }
                    }
                    $this->_log('HomeConnect Options', $this->devices[$device['haId']]['Settings']);
                    // merge with current settings
                    if ($current_settings = $this->Api('homeappliances/' . $device['haId'] . '/settings')) {
                        $settings = $this->_mergeSettings($settings, $current_settings);
                    }

                    // attach settings
                    foreach ($settings AS $setting) {
                        $map = $this->_map($device['type'], $setting);

                        // append settings
                        $this->devices[$device['haId']]['Settings'][] = [
                            'key' => $map['key'],
                            'value' => is_string($map['value']) ? $this->Translate($map['value']) : $map['value'],
                            'custom_profile' => isset($map['custom_profile']) ? [
                                'values' => $map['custom_profile']
                            ] : false
                        ];
                    }

                    // attach status
                    if ($states = $this->Api('homeappliances/' . $device['haId'] . '/status')) {
                        if (isset($states) && is_array($states)) {
                            foreach ($states AS $state) {
                                $name = $state['name'];
                                $map = $this->_map($device['type'], $state);
                                    $this->devices[$device['haId']]['Settings'][] = [
                                    'key' => $map['key'],
                                    'value' => is_string($map['value']) ? $this->Translate($map['value']) : $map['value'],
                                    'custom_profile' => isset($map['custom_profile']) ? [
                                        'values' => $map['custom_profile']
                                    ] : false,
                                    'name' => $name
                                ];
                            }
                        }
                    }

                    // attach current program
                    if (!in_array($device['type'], ['FridgeFreezer'])) {
                        if ($programs = $this->Api('homeappliances/' . $device['haId'] . '/programs/available')) {
                            $device_programs = [];
                            if (isset($programs) && is_array($programs)) {
                                $current_program = -1;
                                $selected_program = $this->Api('homeappliances/' . $device['haId'] . '/programs/selected');
                                foreach ($programs AS $program) {
                                    $program_key = $program['key'];
                                    $program_value = $this->_map($program_key);

                                    if ($selected_program && $selected_program['data']['key'] == $program_key) {
                                        $current_program = count($device_programs);
                                    }

                                    $this->device_settings['program'][] = $program_key;
                                    $device_programs[] = $program_value;
                                }
                                $this->devices[$device['haId']]['Settings'][] = [
                                    'key' => 'Program',
                                    'value' => $current_program,
                                    'custom_profile' => [
                                        'icon' => 'Script',
                                        'values' => $device_programs
                                    ],
                                    'name' => $this->Translate('Program')
                                ];
                            }
                        }
                    }

                    // increase devices found
                    $this->devices_found++;
                }
            }
        }
    }

    /**
     * Save devices
     */
    public function SaveDevices()
    {
        foreach ($this->devices AS $name => $variables) {
            // create device instance
            $instance_id = $this->CreateInstanceByIdentifier(self::guid_device, $this->category_id, $name);
            IPS_SetProperty($instance_id, 'haId', $variables['ID']);
            IPS_SetProperty($instance_id, 'io', $this->InstanceID);
            if ($this->device_settings) {
                IPS_SetProperty($instance_id, 'settings', json_encode($this->device_settings));
                $this->_log('HomeConnect ' . $variables['ID'], json_encode($variables));

            }

            // create device variables
            $position = 0;
            //rbo
            echo "SaveDevices, Array variables:";
            print_r($variables);
            echo "\n";
            foreach ($variables AS $key => $value) {
                $identifier = $instance_id . '_' . $key;

                // settings
                if (is_array($value)) {
                    foreach ($value AS $v) {
                        $ident = $instance_id . '_' . $v['key'];
                        $custom_profile = isset($v['custom_profile']) && $v['custom_profile'] ? $v['custom_profile'] : false;

                        $this->CreateVariableByIdentifier([
                            'parent_id' => $instance_id,
                            'name' => $v['key'],
                            'value' => $v['value'],
                            'identifier' => $ident,
                            'position' => $position,
                            'custom_profile' => $custom_profile
                        ]);
                        $position++;
                    }
                } // variable
                else {
                    $this->CreateVariableByIdentifier([
                        'parent_id' => $instance_id,
                        'name' => $key,
                        'value' => $value,
                        'identifier' => $identifier,
                        'position' => $position
                    ]);
                    $position++;
                }
            }

            // apply instance changes
            IPS_ApplyChanges($instance_id);
        }
    }

    /**
     * Update access token by timer
     */
    public function UpdateAccessToken()
    {
        // read config
        $this->ReadConfig();

        // get new access token
        $this->GetAccessToken();
    }

    /**
     * This method will be called by the register button on the property page!
     */
    public function Register()
    {
        // read config
        $this->ReadConfig();

        // build params
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect,
            'response_type' => 'code',
            'state' => $this->InstanceID
        ];

        // build oauth uri
        $oauth_uri = $this->endpoint . 'security/oauth/authorize?' . http_build_query($params);
        echo $oauth_uri;
    }

    /**
     * process webhook data
     */
    public function ProcessHookData()
    {
        // read config
        $this->ReadConfig();

        // retrieve authentication code and login
        if (isset($_GET['code']) && $_GET['state'] == $this->InstanceID) {
            $this->oauth_code = $_GET['code'];
            $this->Login();

            // update timer
            $timer = $this->access_token ? 86000 * 1000 : 0;
            $this->SetTimerInterval('UpdateAccessToken', $timer);

            // show information
            echo $this->Translate('Done! Please close this window now and proceed the instance configuration.');
        }
    }

    /**
     * merge device settings by key
     * @param array $old_data
     * @param array $new_data
     * @return array
     */
    private function _mergeSettings(array $old_data, array $new_data)
    {
        $data = [];
        // replace new data
        foreach ($old_data AS $o_data) {
            foreach ($new_data AS $k => $n_data) {
                if ($o_data['key'] == $n_data['key']) {
                    $o_data = $n_data;
                    unset($new_data[$k]);
                    break;
                }
            }

            $data[] = $o_data;
        }

        // append new data
        foreach ($new_data AS $d) {
            $data[] = $d;
        }

        return $data;
    }

    /**
     * create custom variable profile
     * @param string $profile_id
     * @param string $name
     */
    protected function CreateCustomVariableProfile(string $profile_id, string $name)
    {
        switch ($name):
            case 'StartProgram':
                IPS_CreateVariableProfile($profile_id, 0); // boolean
                IPS_SetVariableProfileAssociation($profile_id, false, $this->Translate('Stop'), 'Power', -1);
                IPS_SetVariableProfileAssociation($profile_id, true, $this->Translate('Start'), 'Power', 0x3ADF00);
                IPS_SetVariableProfileIcon($profile_id, 'Power');
                break;
            case 'Power.Switch':
                IPS_CreateVariableProfile($profile_id, 0); // boolean
                IPS_SetVariableProfileAssociation($profile_id, false, $this->Translate('Off'), 'Power', -1);
                IPS_SetVariableProfileAssociation($profile_id, true, $this->Translate('On'), 'Power', 0x3ADF00);
                IPS_SetVariableProfileIcon($profile_id, 'Power');
                break;
            case 'Power.Standby':
                IPS_CreateVariableProfile($profile_id, 0); // boolean
                IPS_SetVariableProfileAssociation($profile_id, false, $this->Translate('Standby'), 'Power', -1);
                IPS_SetVariableProfileAssociation($profile_id, true, $this->Translate('On'), 'Power', 0x3ADF00);
                IPS_SetVariableProfileIcon($profile_id, 'Power');
                break;
            case 'TargetTemperature.Freezer':
                IPS_CreateVariableProfile($profile_id, 1); // integer
                IPS_SetVariableProfileText($profile_id, '', '°C');
                IPS_SetVariableProfileIcon($profile_id, 'Temperature');
                IPS_SetVariableProfileValues($profile_id, -24, -16, 1);
                break;
            case 'TargetTemperature.Fridge':
                IPS_CreateVariableProfile($profile_id, 1); // integer
                IPS_SetVariableProfileText($profile_id, '', '°C');
                IPS_SetVariableProfileIcon($profile_id, 'Temperature');
                IPS_SetVariableProfileValues($profile_id, 2, 8, 1);
                break;
            case 'Temperature':
                IPS_CreateVariableProfile($profile_id, 1); // integer
                IPS_SetVariableProfileText($profile_id, '', '°C');
                IPS_SetVariableProfileIcon($profile_id, 'Temperature');
                IPS_SetVariableProfileValues($profile_id, 0, 300, 1);
                break;
            case 'TargetTemperature.Oven':
                IPS_CreateVariableProfile($profile_id, 1); // integer
                IPS_SetVariableProfileText($profile_id, '', '°C');
                IPS_SetVariableProfileIcon($profile_id, 'Temperature');
                IPS_SetVariableProfileValues($profile_id, 30, 250, 1);
                break;
            case 'Duration.Elapsed':
                IPS_CreateVariableProfile($profile_id, 2); // float
                IPS_SetVariableProfileDigits($profile_id, 2);
                IPS_SetVariableProfileText($profile_id, '', ' Min.');
                IPS_SetVariableProfileIcon($profile_id, 'Hourglass-30');
                IPS_SetVariableProfileValues($profile_id, 30, 250, 1);
                break;
            case 'Duration.StartIn':
                IPS_CreateVariableProfile($profile_id, 1); // integer
                IPS_SetVariableProfileText($profile_id, '', ' Min.');
                IPS_SetVariableProfileIcon($profile_id, 'Clock');
                IPS_SetVariableProfileValues($profile_id, 0, 1439 , 1);
                break;
            case 'Duration.Remaining':
                IPS_CreateVariableProfile($profile_id, 2); // float
                IPS_SetVariableProfileDigits($profile_id, 2);
                IPS_SetVariableProfileText($profile_id, '', ' Min.');
                IPS_SetVariableProfileIcon($profile_id, 'Hourglass');
                IPS_SetVariableProfileValues($profile_id, 30, 250, 1);
                break;
            case 'Duration.Fixed':
                IPS_CreateVariableProfile($profile_id, 1); // integer
                IPS_SetVariableProfileText($profile_id, '', ' Min.');
                IPS_SetVariableProfileIcon($profile_id, 'Clock');
                IPS_SetVariableProfileValues($profile_id, 30, 250, 1);
                break;
            case 'Progress':
                IPS_CreateVariableProfile($profile_id, 1); // integer
                IPS_SetVariableProfileText($profile_id, '', '%');
                IPS_SetVariableProfileIcon($profile_id, 'Distance');
                IPS_SetVariableProfileValues($profile_id, 0, 100, 1);
                break;
            case 'Energy.Forecast':
                IPS_CreateVariableProfile($profile_id, 1); // integer
                IPS_SetVariableProfileText($profile_id, '', '%');
                IPS_SetVariableProfileIcon($profile_id, 'Plug');
                IPS_SetVariableProfileValues($profile_id, 0, 100, 1);
                break;
            case 'Water.Forecast':
                IPS_CreateVariableProfile($profile_id, 1); // integer
                IPS_SetVariableProfileText($profile_id, '', '%');
                IPS_SetVariableProfileIcon($profile_id, 'Tap');
                IPS_SetVariableProfileValues($profile_id, 0, 100, 1);
                break;
            case 'FillQuantity':
                IPS_CreateVariableProfile($profile_id, 1); // integer
                IPS_SetVariableProfileText($profile_id, '', 'ml');
                IPS_SetVariableProfileIcon($profile_id, 'Gauge');
                IPS_SetVariableProfileValues($profile_id, 35, 480, 5);
                break;
        endswitch;
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

        $formHead = [
        ];

        // show forms, depending on current configuration
        if ($this->access_token) {
            $formHead = array_merge(
                $formHead,
                [
                    [
                        'type' => 'Label',
                        'label' => '___ [ Note ] ___________________________________________________________________________________________'
                    ],
                    [
                        'type' => 'Label',
                        'label' => 'Please turn your HomeConnect devices at least into standby mode, otherwise they won\'t found.'
                    ],
                    [
                        'type' => 'Label',
                        'label' => '___ [ Device Category ] ________________________________________________________________________________'
                    ],
                    [
                        'name' => 'category_id',
                        'type' => 'SelectCategory',
                        'caption' => 'Category'
                    ],
                    [
                        'name' => 'access_token',
                        'type' => 'ValidationTextBox',
                        'caption' => 'Access Token'
                    ],
                ]
            );

        } else {
            $formHead = array_merge(
                $formHead,
                [
                    [
                        'type' => 'Label',
                        'label' => '___ [ Settings ] _______________________________________________________________________________________'
                    ]
                ]
            );

            $formHead[] = [
                'name' => 'my_client_id',
                'type' => 'ValidationTextBox',
                'caption' => 'API Key'
            ];
            $formHead[] = [
                'name' => 'my_client_secret',
                'type' => 'ValidationTextBox',
                'caption' => 'API Secret'
            ];
            $formHead[] = [
                'name' => 'my_redirect',
                'type' => 'ValidationTextBox',
                'caption' => 'Redirect URI'
            ];
        }


        $formHead = array_merge(
            $formHead,
            [
                [
                    'type' => 'Label',
                    'label' => '___ [ Logging ] ________________________________________________________________________________________'
                ],
                [
                    'name' => 'log',
                    'type' => 'CheckBox',
                    'caption' => 'enable logging'
                ]
            ]
        );

        return $formHead;
    }

    /**
     * return form actions by token
     * @return array
     */
    protected function FormActions()
    {
        // read config
        $this->ReadConfig();

        // show buttons, depending on current configuration
        if ($this->access_token) {
            return [
                [
                    'type' => 'Button',
                    'label' => 'Dump Devices',
                    'onClick' => $this->_getPrefix() . '_CreateDevices($id, true);'
                ],
                [
                    'type' => 'Button',
                    'label' => 'Create Devices',
                    'onClick' => $this->_getPrefix() . '_CreateDevices($id, false);'
                ]
            ];
        } else {
            return [
                [
                    'type' => 'Button',
                    'label' => 'Login',
                    'onClick' => $this->_getPrefix() . '_Register($id);'
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
                'caption' => 'Please login to your HomeConnect account to proceed.'
            ],
            [
                'code' => 102,
                'icon' => 'active',
                'caption' => 'HomeConnect connection has been established!'
            ],
            [
                'code' => 201,
                'icon' => 'inactive',
                'caption' => 'Error: Could not connect to api. Please check your connection details!'
            ]
        ];
    }
}
