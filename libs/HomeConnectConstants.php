<?php

class HomeConnectConstants
{
    /**
     * Common Settings & States
     */
    // common
    const BSH_Common_Setting_PowerState = [
        'name' => 'Power State',
        'values' => [
            'BSH.Common.EnumType.PowerState.Off' => [
                'value' => [
                    'name' => 'Off',
                    'value' => 0
                ],
                'availability' => [
                    'Dishwasher',
                    'Top',
                    'Hood',
                    'Hob'
                ]
            ],
            'BSH.Common.EnumType.PowerState.On' => [
                'value' => [
                    'name' => 'On',
                    'value' => 1
                ]
            ],
            'BSH.Common.EnumType.PowerState.Standby' => [
                'value' => [
                    'name' => 'Standby',
                    'value' => 2
                ],
                'availability' => [
                    'Oven',
                    'CoffeeMaker'
                ]
            ]
        ]
    ];

    // door state
    const BSH_Common_Status_DoorState = [
        'name' => 'Door',
        'values' => [
            'BSH.Common.EnumType.DoorState.Open' => [
                'value' => [
                    'name' => 'open',
                    'value' => 1
                ]
            ],
            'BSH.Common.EnumType.DoorState.Closed' => [
                'value' => [
                    'name' => 'closed',
                    'value' => 0
                ]
            ],
            'BSH.Common.EnumType.DoorState.Locked' => [
                'value' => [
                    'name' => 'locked',
                    'value' => -1
                ],
                'availability' => [
                    'Oven',
                    'Washer'
                ]
            ]
        ]
    ];

    // operation state
    const BSH_Common_Status_OperationState = [
        'name' => 'Operation State',
        'values' => [
            'BSH.Common.EnumType.OperationState.Inactive' => [
                'value' => [
                    'name' => 'Inactive',
                    'value' => 0
                ],
                'availability' => [
                    'Oven',
                    'Dishwasher',
                    'CoffeeMaker',
                    'Top',
                    'Hood'
                ]
            ],
            'BSH.Common.EnumType.OperationState.Ready' => [
                'value' => [
                    'name' => 'Ready',
                    'value' => 1
                ],
                'availability' => [
                    'Oven',
                    'Dishwasher',
                    'Washer',
                    'Dryer',
                    'CoffeeMaker',
                    'Top'
                ]
            ],
            'BSH.Common.EnumType.OperationState.DelayedStart' => [
                'value' => [
                    'name' => 'Delayed Start',
                    'value' => 2
                ],
                'availability' => [
                    'Oven',
                    'Dishwasher',
                    'Washer',
                    'Dryer'
                ]
            ],
            'BSH.Common.EnumType.OperationState.Run' => [
                'value' => [
                    'name' => 'Run',
                    'value' => 3
                ],
                'availability' => [
                    'Oven',
                    'Dishwasher',
                    'Washer',
                    'Dryer',
                    'CoffeeMaker',
                    'Top',
                    'Hood'
                ]
            ],
            'BSH.Common.EnumType.OperationState.Pause' => [
                'value' => [
                    'name' => 'Pause',
                    'value' => 4
                ],
                'availability' => [
                    'Oven',
                    'Washer',
                    'Dryer',
                    'Top'
                ]
            ],
            'BSH.Common.EnumType.OperationState.ActionRequired' => [
                'value' => [
                    'name' => 'Action Required',
                    'value' => 5
                ],
                'availability' => [
                    'Oven',
                    'Washer',
                    'Dryer',
                    'CoffeeMaker',
                    'Top'
                ]
            ],
            'BSH.Common.EnumType.OperationState.Finished' => [
                'value' => [
                    'name' => 'Finished',
                    'value' => 6
                ],
                'availability' => [
                    'Oven',
                    'Dishwasher',
                    'Washer',
                    'Dryer',
                    'CoffeeMaker',
                    'Top'
                ]
            ],
            'BSH.Common.EnumType.OperationState.Error' => [
                'value' => [
                    'name' => 'Error',
                    'value' => 7
                ],
                'availability' => [
                    'Oven',
                    'Washer',
                    'Dryer',
                    'CoffeeMaker',
                    'Top'
                ]
            ],
            'BSH.Common.EnumType.OperationState.Aborting' => [
                'value' => [
                    'name' => 'Aborting',
                    'value' => 8
                ],
                'availability' => [
                    'Oven',
                    'Dishwasher',
                    'CoffeeMaker',
                    'Top'
                ]
            ]
        ]
    ];

    // remote control activation
    const BSH_Common_Status_RemoteControlActive = [
        'name' => 'Remote control activation',
        'values' => [
            true => [
                'value' => [
                    'name' => 'Yes',
                    'value' => 1
                ]
            ],
            false => [
                'value' => [
                    'name' => 'No',
                    'value' => 0
                ]
            ]
        ]
    ];

    // remote control start allowance
    const BSH_Common_Status_RemoteControlStartAllowed = [
        'name' => 'Remote control start allowance',
        'values' => [
            true => [
                'value' => [
                    'name' => 'Yes',
                    'value' => 1
                ]
            ],
            false => [
                'value' => [
                    'name' => 'No',
                    'value' => 0
                ]
            ]
        ]
    ];

    // local control state
    const BSH_Common_Status_LocalControlActive = [
        'name' => 'Local Control',
        'values' => [
            true => [
                'value' => [
                    'name' => 'Yes',
                    'value' => 1
                ]
            ],
            false => [
                'value' => [
                    'name' => 'No',
                    'value' => 0
                ]
            ]
        ]
    ];

    /**
     * Common options
     */

    // duration
    const BSH_Common_Option_FinishInRelative = [
        'name' => 'Finish',
        'convert' => 'minute'
    ];

    const BSH_Common_Option_Duration = [
        'name' => 'Duration',
        'convert' => 'minute'
    ];

    // remaining program time
    const BSH_Common_Option_RemainingProgramTime = [
        'name' => 'Remaining',
        'convert' => 'minute'
    ];

    // elapsed program Time
    const BSH_Common_Option_ElapsedProgramTime = [
        'name' => 'Elapsed',
        'convert' => 'minute'
    ];

    // program progress
    const BSH_Common_Option_ProgramProgress = 'Progress';

    // programs
    const BSH_Common_Root_SelectedProgram = [
        'name' => 'Program',
        'alias' => true
    ];

    const BSH_Common_Root_ActiveProgram = [
        'name' => 'Program',
        'alias' => true
    ];

    // timer
    const BSH_Common_Option_StartInRelative = [
        'name' => 'Start in',
        'convert' => 'seconds_to_minutes'
    ];
    // Energie Forecast
    const BSH_Common_Option_EnergyForecast = 'Energy Forecast';

    //Water Forecast
    const BSH_Common_Option_WaterForecast = 'Water Forecast';

    /**
     * Freezer
     */
    // target temperature
    const Refrigeration_FridgeFreezer_Setting_SetpointTemperatureFreezer = 'Target Temperature Freezer';

    // super mode
    const Refrigeration_FridgeFreezer_Setting_SuperModeFreezer = 'Super Mode Freezer';

    /**
     * Fridge
     */
    // target temperature
    const Refrigeration_FridgeFreezer_Setting_SetpointTemperatureRefrigerator = 'Target Temperature Refrigerator';

    // super mode
    const Refrigeration_FridgeFreezer_Setting_SuperModeRefrigerator = 'Super Mode Refrigerator';

    /**
     * Oven
     */
    // temperatures
    const Cooking_Oven_Status_CurrentCavityTemperature = 'Current cavity temperature change';
    const Cooking_Oven_Option_SetpointTemperature = 'Target Temperature';

    // heating modes
    const Cooking_Oven_Program_HeatingMode_HotAir = 'Hot Air';
    const Cooking_Oven_Program_HeatingMode_TopBottomHeating = 'Top Bottom Heating';
    const Cooking_Oven_Program_HeatingMode_PizzaSetting = 'Pizza';

    /**
     * Coffee Machine
     */

    // programs
    const ConsumerProducts_CoffeeMaker_Program_Beverage_Espresso = 'Espresso';
    const ConsumerProducts_CoffeeMaker_Program_Beverage_EspressoMacchiato = 'Espresso Macchiato';
    const ConsumerProducts_CoffeeMaker_Program_Beverage_Coffee = 'Coffee';
    const ConsumerProducts_CoffeeMaker_Program_Beverage_Cappuccino = 'Cappuchino';
    const ConsumerProducts_CoffeeMaker_Program_Beverage_LatteMacchiato = 'Latte Macchiato';
    const ConsumerProducts_CoffeeMaker_Program_Beverage_CaffeLatte = 'Caffe Latte';

    // temperature
    const ConsumerProducts_CoffeeMaker_Option_CoffeeTemperature = [
        'name' => 'Coffee Temperature',
        'values' => [
            'ConsumerProducts.CoffeeMaker.EnumType.CoffeeTemperature.Normal' => [
                'value' => [
                    'name' => 'Normal',
                    'value' => 0
                ]
            ],
            'ConsumerProducts.CoffeeMaker.EnumType.CoffeeTemperature.High' => [
                'value' => [
                    'name' => 'High',
                    'value' => 1
                ]
            ],
            'ConsumerProducts.CoffeeMaker.EnumType.CoffeeTemperature.VeryHigh' => [
                'value' => [
                    'name' => 'Very High',
                    'value' => 2
                ]
            ]
        ]
    ];

    // bean amount
    const ConsumerProducts_CoffeeMaker_Option_BeanAmount = [
        'name' => 'Bean Amount',
        'values' => [
            'ConsumerProducts.CoffeeMaker.EnumType.BeanAmount.Mild' => [
                'value' => [
                    'name' => 'Mild',
                    'value' => 0
                ]
            ],
            'ConsumerProducts.CoffeeMaker.EnumType.BeanAmount.Normal' => [
                'value' => [
                    'name' => 'Normal',
                    'value' => 1
                ]
            ],
            'ConsumerProducts.CoffeeMaker.EnumType.BeanAmount.Strong' => [
                'value' => [
                    'name' => 'Strong',
                    'value' => 2
                ]
            ],
            'ConsumerProducts.CoffeeMaker.EnumType.BeanAmount.VeryStrong' => [
                'value' => [
                    'name' => 'Very Strong',
                    'value' => 3
                ]
            ],
            'ConsumerProducts.CoffeeMaker.EnumType.BeanAmount.DoubleShot' => [
                'value' => [
                    'name' => 'Double Shot',
                    'value' => 4
                ]
            ],
            'ConsumerProducts.CoffeeMaker.EnumType.BeanAmount.DoubleShotPlus' => [
                'value' => [
                    'name' => 'Double Shot Plus',
                    'value' => 5
                ]
            ],
            'ConsumerProducts.CoffeeMaker.EnumType.BeanAmount.DoubleShotPlusPlus' => [
                'value' => [
                    'name' => 'Double Shot Plus Plus',
                    'value' => 6
                ]
            ]
        ]
    ];

    // fill quantity
    const ConsumerProducts_CoffeeMaker_Option_FillQuantity = 'Fill Quantity';

    /**
     * Dishwasher
     */

    // programs
    const Dishcare_Dishwasher_Program_Auto1 = 'Auto1';
    const Dishcare_Dishwasher_Program_Auto2 = 'Auto2';
    const Dishcare_Dishwasher_Program_Auto3 = 'Auto3';
    const Dishcare_Dishwasher_Program_Eco50 = 'Eco 50°';
    const Dishcare_Dishwasher_Program_Quick45 = 'Quick 45°';
    const Dishcare_Dishwasher_Program_Intensiv70 = 'Intensive 70°';
    const Dishcare_Dishwasher_Program_NightWash = 'Silent';
    const Dishcare_Dishwasher_Program_Kurz60 = 'Short 60°';
    const Dishcare_Dishwasher_Program_Glas40 = 'Glass 40°';
    const Dishcare_Dishwasher_Program_PreRinse = 'Pre Rinse';
    const Dishcare_Dishwasher_Program_MachineCare = 'Machine Care';

    // options
    const Dishcare_Dishwasher_Option_IntensivZone = [
        'name' => 'Intensiv Zone',
        'values' => [
            true => [
                'value' => [
                    'name' => 'Yes',
                    'value' => 1
                ]
            ],
            false => [
                'value' => [
                    'name' => 'No',
                    'value' => 0
                ]
            ]
        ]
    ];

    const Dishcare_Dishwasher_Option_VarioSpeedPlus = [
        'name' => 'varioSpeed Plus',
        'values' => [
            true => [
                'value' => [
                    'name' => 'Yes',
                    'value' => 1
                ]
            ],
            false => [
                'value' => [
                    'name' => 'No',
                    'value' => 0
                ]
            ]
        ]
    ];

    const Dishcare_Dishwasher_Option_BrillianceDry = [
        'name' => 'Brilliance Dry',
        'values' => [
            true => [
                'value' => [
                    'name' => 'Yes',
                    'value' => 1
                ]
            ],
            false => [
                'value' => [
                    'name' => 'No',
                    'value' => 0
                ]
            ]
        ]
    ];

    const Dishcare_Dishwasher_Option_HalfLoad = [
        'name' => 'Half Load',
        'values' => [
            true => [
                'value' => [
                    'name' => 'Yes',
                    'value' => 1
                ]
            ],
            false => [
                'value' => [
                    'name' => 'No',
                    'value' => 0
                ]
            ]
        ]
    ];

    const Dishcare_Dishwasher_Option_HygienePlus = [
        'name' => 'Hygiene Plus',
        'values' => [
            true => [
                'value' => [
                    'name' => 'Yes',
                    'value' => 1
                ]
            ],
            false => [
                'value' => [
                    'name' => 'No',
                    'value' => 0
                ]
            ]
        ]
    ];

    /**
     * Dryer
     */

    // programs
    const LaundryCare_Dryer_Program_Cotton = 'Cotton';
    const LaundryCare_Dryer_Program_Synthetic = 'Synthetic';
    const LaundryCare_Dryer_Program_Mix = 'Mix';
    const LaundryCare_Dryer_Program_Hygiene = 'Hygiene';
    const LaundryCare_Dryer_Program_Super40 = 'Super 40';
    const LaundryCare_Dryer_Program_Outdoor = 'Outdoor';
    const LaundryCare_Dryer_Program_Pillow = 'Pillow';
    const LaundryCare_Dryer_Program_Blankets = 'Blankets';
    const LaundryCare_Dryer_Program_BusinessShirts = 'Business Shirts';
    const LaundryCare_Dryer_Program_Towels = 'Towels';

    // drying target
    const LaundryCare_Dryer_Option_DryingTarget = [
        'name' => 'Drying Target',
        'values' => [
            'LaundryCare.Dryer.EnumType.DryingTarget.IronDry' => [
                'value' => [
                    'name' => 'Iron Dry',
                    'value' => 0
                ]
            ],
            'LaundryCare.Dryer.EnumType.DryingTarget.CupboardDry' => [
                'value' => [
                    'name' => 'Cupboard Dry',
                    'value' => 1
                ]
            ],
            'LaundryCare.Dryer.EnumType.DryingTarget.CupboardDryPlus' => [
                'value' => [
                    'name' => 'Cupboard Dry Plus',
                    'value' => 2
                ]
            ]
        ]
    ];

    const LaundryCare_Dryer_Option_Gentle = 'Gentle';

    const LaundryCare_Dryer_Option_WrinkleGuard = [
        'name' => 'Wrinkle Guard',
        'values' => [
            'LaundryCare.Dryer.EnumType.WrinkleGuard.Min60' => [
                'value' => [
                    'name' => '60 min',
                    'value' => 0
                ]
            ],
            'LaundryCare.Dryer.EnumType.WrinkleGuard.Min120' => [
                'value' => [
                    'name' => '120 min',
                    'value' => 1
                ]
            ],
            'LaundryCare.Dryer.EnumType.WrinkleGuard.Min240' => [
                'value' => [
                    'name' => '240 min',
                    'value' => 2
                ]
            ]
        ]
    ];

    /**
     * Washer
     */

    // programs
    const LaundryCare_Washer_Program_Cotton = 'Cotton';
    const LaundryCare_Washer_Program_EasyCare = 'Easy Care';
    const LaundryCare_Washer_Program_Mix = 'Mix';
    const LaundryCare_Washer_Program_DelicatesSilk = 'Delicates Silk';
    const LaundryCare_Washer_Program_Wool = 'Wool';
    const LaundryCare_Washer_Program_DarkWash = 'Dark Wash';
    const LaundryCare_Washer_Program_ShirtsBlouses = 'Shirts Blouses';
    const LaundryCare_Washer_Program_WaterProof = 'Water Proof';
    const LaundryCare_Washer_Program_Auto30 = 'Auto 30';
    const LaundryCare_Washer_Program_Auto40 = 'Auto 40';
    const LaundryCare_Washer_Program_Auto60 = 'Auto 60';
    const LaundryCare_Washer_Program_Sensitive = 'Sensitive';
    const LaundryCare_Washer_Program_Curtains = 'Curtains';

    // temperature
    const LaundryCare_Washer_Option_Temperature = [
        'name' => 'Temperature',
        'values' => [
            'LaundryCare.Washer.EnumType.Temperature.Cold' => [
                'value' => [
                    'name' => 'Cold Water',
                    'value' => 0
                ]
            ],
            'LaundryCare.Washer.EnumType.Temperature.GC20' => [
                'value' => [
                    'name' => '20°',
                    'value' => 1
                ]
            ],
            'LaundryCare.Washer.EnumType.Temperature.GC30' => [
                'value' => [
                    'name' => '30°',
                    'value' => 2
                ]
            ],
            'LaundryCare.Washer.EnumType.Temperature.GC40' => [
                'value' => [
                    'name' => '40°',
                    'value' => 3
                ]
            ],
            'LaundryCare.Washer.EnumType.Temperature.GC50' => [
                'value' => [
                    'name' => '50°',
                    'value' => 4
                ]
            ],
            'LaundryCare.Washer.EnumType.Temperature.GC60' => [
                'value' => [
                    'name' => '60°',
                    'value' => 5
                ]
            ],
            'LaundryCare.Washer.EnumType.Temperature.GC70' => [
                'value' => [
                    'name' => '70°',
                    'value' => 6
                ]
            ],
            'LaundryCare.Washer.EnumType.Temperature.GC80' => [
                'value' => [
                    'name' => '80°',
                    'value' => 7
                ]
            ],
            'LaundryCare.Washer.EnumType.Temperature.GC90' => [
                'value' => [
                    'name' => '90°',
                    'value' => 8
                ]
            ]
        ]
    ];

    // spin speed
    const LaundryCare_Washer_Option_SpinSpeed = [
        'name' => 'Spin Speed',
        'values' => [
            'LaundryCare.Washer.EnumType.SpinSpeed.Off' => [
                'value' => [
                    'name' => 'Off',
                    'value' => 0
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.UlNo' => [
                'value' => [
                    'name' => 'Off',
                    'value' => 0
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.UlLow' => [
                'value' => [
                    'name' => 'Low',
                    'value' => 1
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.UlMedium' => [
                'value' => [
                    'name' => 'Medium',
                    'value' => 2
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.UlHigh' => [
                'value' => [
                    'name' => 'High',
                    'value' => 3
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.RPM400' => [
                'value' => [
                    'name' => '400 rpm',
                    'value' => 4
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.RPM600' => [
                'value' => [
                    'name' => '600 rpm',
                    'value' => 5
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.RPM800' => [
                'value' => [
                    'name' => '800 rpm',
                    'value' => 6
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.RPM1000' => [
                'value' => [
                    'name' => '1000 rpm',
                    'value' => 7
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.RPM1200' => [
                'value' => [
                    'name' => '1200 rpm',
                    'value' => 8
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.RPM1400' => [
                'value' => [
                    'name' => '1400 rpm',
                    'value' => 9
                ]
            ],
            'LaundryCare.Washer.EnumType.SpinSpeed.RPM1600' => [
                'value' => [
                    'name' => '1600 rpm',
                    'value' => 10
                ]
            ]
        ]
    ];

    const LaundryCare_Washer_Option_Stains = [
        'name' => 'Stains',
        'values' => [
            'LaundryCare.Washer.EnumType.Stains.Off' => [
                'value' => [
                    'name' => 'Off',
                    'value' => 0
                ]
            ],
            'LaundryCare.Washer.EnumType.Stains.On' => [
                'value' => [
                    'name' => 'On',
                    'value' => 1
                ]
            ]
        ]
    ];
    const LaundryCare_Washer_Option_RinsePlus = [
        'name' => 'Rinse Plus',
        'values' => [
            'LaundryCare.Washer.EnumType.RinsePlus.Off' => [
                'value' => [
                    'name' => 'Off',
                    'value' => 0
                ]
            ],
            'LaundryCare.Washer.EnumType.RinsePlus.On' => [
                'value' => [
                    'name' => 'On',
                    'value' => 1
                ]
            ]
        ]
    ];
    const LaundryCare_Washer_Option_Prewash = 'Prewash';
    const LaundryCare_Washer_Option_WaterPlus ='Wasser Plus';
    const LaundryCare_Washer_Option_IDos1DosingLevel = [
        'name' => 'IDos1DosingLevel',
        'values' => [
            'LaundryCare.Washer.EnumType.IDosingLevel.Normal' => [
                'value' => [
                    'name' => 'Normal',
                    'value' => 0
                ]
            ],
            'LaundryCare.Washer.EnumType.IDosingLevel.Middle' => [
                'value' => [
                    'name' => 'Middle',
                    'value' => 1
                ]
            ],
            'LaundryCare.Washer.EnumType.IDosingLevel.Strong' => [
                'value' => [
                    'name' => 'Strong',
                    'value' => 2
                ]
            ]
        ]
    ];
    const LaundryCare_Washer_Option_IDos2DosingLevel = [
        'name' => 'IDos2DosingLevel',
        'values' => [
            'LaundryCare.Washer.EnumType.IDosingLevel.Normal' => [
                'value' => [
                    'name' => 'Normal',
                    'value' => 0
                ]
            ],
            'LaundryCare.Washer.EnumType.IDosingLevel.Middle' => [
                'value' => [
                    'name' => 'Middle',
                    'value' => 1
                ]
            ],
            'LaundryCare.Washer.EnumType.IDosingLevel.Strong' => [
                'value' => [
                    'name' => 'Strong',
                    'value' => 2
                ]
            ]
        ]
    ];

    const LaundryCare_Common_Option_VarioPerfect = [
        'name' => 'VarioPerfect',
        'values' => [
            'LaundryCare.Commmon.EnumType.VarioPerfect.SpeedPerfect' => [
                'value' => [
                    'name' => 'SpeedPerfect',
                    'value' => 0
                ]
            ],
            'LaundryCare.Commmon.EnumType.VarioPerfect.SpeedPerfect1' => [
                'value' => [
                    'name' => 'SpeedPerfect1',
                    'value' => 1
                ]
            ],
            'LaundryCare.Commmon.EnumType.VarioPerfect.SpeedPerfect2' => [
                'value' => [
                    'name' => 'SpeedPerfect2',
                    'value' => 2
                ]
            ]
        ]
    ];
    const LaundryCare_Washer_Option_LessIroning = 'Less Ironing';

    /**
     * Hood
     */

    // options
    const Cooking_Common_Option_Hood_VentingLevel = 'Venting Level';
    const Cooking_Common_Option_Hood_IntensiveLevel = 'Intensive Level';


    /**
     * Hob
     *  
     */
    const Cooking_Hob_Option_ZoneSelector = 'Zonenauswahl';
    const Cooking_Hob_Option_PowerLevel = 'Leistungsstufe';
    const Cooking_Hob_Option_JoinZone = 'Zone zusammenschalten';

    /*
    "key": "Cooking.Hob.Option.ZoneSelector",
    "value": "Cooking.Hob.EnumType.ZoneSelector.FrontLeft",
    "displayvalue": "Vorne links"


    "key": "Cooking.Hob.Option.PowerLevel",
    "value": "Cooking.Hob.EnumType.PowerLevel.Off",
    "name": "Leistungsstufe",
    "displayvalue": "0 "


    "key": "Cooking.Hob.Option.JoinZone",
    "value": false,
    "name": "Zone zusammenschalten"
    */


    /**
     * Default settings by type
     */
    const default_settings = [
        'Dishwasher' => [
            'Start Device' => false,
            'BSH.Common.Setting.PowerState' => 'BSH.Common.EnumType.PowerState.On',
            'BSH.Common.Option.RemainingProgramTime' => 0,
            'BSH.Common.Option.ProgramProgress' => 0,
            'BSH.Common.Option.ElapsedProgramTime' => 0,
            'BSH_Common.Option.StartInRelative' => 0,
            'BSH.Common.Option.EnergyForecast' => 0,
            'BSH.Common.Option.WaterForecast' => 0,
            'Dishcare.Dishwasher.Option.IntensivZone'  => 0,
            'Dishcare.Dishwasher.Option.VarioSpeedPlus'  => 0,
            'Dishcare.Dishwasher.Option.BrillianceDry' => 0,
            'Dishcare.Dishwasher.Option.HalfLoad' => 0,
            'Dishcare.Dishwasher.Option.HygienePlus' => 0
        ],
        'Hood' => [
            'Start Device' => false,
            'BSH.Common.Setting.PowerState' => 'BSH.Common.EnumType.PowerState.On',
            'BSH.Common.Option.Duration' => 0,
            'BSH.Common.Option.ElapsedProgramTime' => 0,
            'BSH.Common.Option.RemainingProgramTime' => 0,
            'BSH.Common.Option.ProgramProgress' => 0,
            'Cooking.Common.Option.Hood.VentingLevel' => 0
        ],
        'Oven' => [
            'BSH.Common.Setting.PowerState' => 'BSH.Common.EnumType.PowerState.On',
            'BSH.Common.Option.Duration' => 0,
            'BSH.Common.Option.ElapsedProgramTime' => 0,
            'BSH.Common.Option.RemainingProgramTime' => 0,
            'BSH.Common.Option.ProgramProgress' => 0,
            'Cooking.Oven.Option.SetpointTemperature' => 200
        ],
        'CoffeeMaker' => [
            'Start Device' => false,
            'BSH.Common.Setting.PowerState' => 'BSH.Common.EnumType.PowerState.On',
            'BSH.Common.Option.RemainingProgramTime' => 0,
            'BSH.Common.Option.ProgramProgress' => 0,
            'ConsumerProducts.CoffeeMaker.Option.CoffeeTemperature' => 'ConsumerProducts.CoffeeMaker.EnumType.CoffeeTemperature.High',
            'ConsumerProducts.CoffeeMaker.Option.BeanAmount' => 'ConsumerProducts.CoffeeMaker.EnumType.BeanAmount.Normal',
            'ConsumerProducts.CoffeeMaker.Option.FillQuantity' => 150
        ],
        'Washer' => [
            'Start Device' => false,
            'BSH.Common.Setting.PowerState' => 'BSH.Common.EnumType.PowerState.On',
            'BSH.Common.Option.RemainingProgramTime' => 0,
            'BSH.Common.Option.ProgramProgress' => 0,
            'LaundryCare.Washer.Option.Temperature' => 'LaundryCare.Washer.EnumType.Temperature.GC40',
            'LaundryCare.Washer.Option.SpinSpeed' => 'LaundryCare.Washer.EnumType.SpinSpeed.RPM1400',
            'LaundryCare.Washer.Option.Stains' => 'LaundryCare.Washer.EnumType.Stains.Off',
            'LaundryCare.Washer.Option.RinsePlus' => 'LaundryCare.Washer.EnumType.RinsePlus.Off',
            'LaundryCare.Washer.Option.Prewash' => 0,
            'LaundryCare.Washer.Option.LessIroning' => 0,
            'LaundryCare.Washer.Option.IDos2DosingLevel' => 'LaundryCare.Washer.EnumType.IDosingLevel.Normal',
            'LaundryCare.Washer.Option.IDos1DosingLevel' => 'LaundryCare.Washer.EnumType.IDosingLevel.Normal',
            'LaundryCare.Common.Option.VarioPerfect' => 'LaundryCare.Commmon.EnumType.VarioPerfect.SpeedPerfect',
            'BSH.Common.Option.FinishInRelative'  => 0
        ],
        'Dryer' => [
            'Start Device' => false,
            'BSH.Common.Setting.PowerState' => 'BSH.Common.EnumType.PowerState.On',
            'BSH.Common.Option.RemainingProgramTime' => 0,
            'BSH.Common.Option.ProgramProgress' => 0,
            'BSH.Common.Option.FinishInRelative'  => 0,
            'LaundryCare.Dryer.Option.DryingTarget' => 'LaundryCare.Dryer.EnumType.DryingTarget.IronDry',
            'LaundryCare.Dryer.Option.Gentle'  => 0,
            'LaundryCare.Dryer.Option.WrinkleGuard'  => 0
        ],
        'FridgeFreezer' => [
            'BSH.Common.Setting.PowerState' => 'BSH.Common.EnumType.PowerState.On',
            'Refrigeration.FridgeFreezer.Setting.SetpointTemperatureFreezer' => 0,
            'Refrigeration.FridgeFreezer.Setting.SetpointTemperatureRefrigerator' => 0,
            'Refrigeration.FridgeFreezer.Setting.SuperModeFreezer' => false,
            'Refrigeration.FridgeFreezer.Setting.SuperModeRefrigerator' => false
        ],
        'Cooktop' => [
            'BSH.Common.Setting.PowerState' => 'BSH.Common.EnumType.PowerState.On'
        ],
        'Hob' => [
            'BSH.Common.Option.Duration' => 0
        ]
    ];

    // callbacks
    const callbacks = [
        'BSH.Common.Event.ProgramFinished' => 'ProcessFinished',
        'BSH.Common.Event.ProgramAborted' => 'ProcessAborted',
        'LaundryCare.Dryer.Event.DryingProcessFinished' => 'ProcessFinished'
    ];

    /**
     * get constant
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        $value = $key;

        $key = str_replace('.', '_', $key);
        if ($constant = @constant('self::' . $key)) {
            $value = $constant;
        }

        return $value;
    }

    /**
     * get default settings by type / device
     * @param $type
     * @return array
     */
    public static function settings($type)
    {
        $settings = [];
        $default_settings = self::default_settings;
        if (isset($default_settings[$type])) {
            foreach ($default_settings[$type] AS $key => $value) {
                $settings[] = [
                    'key' => $key,
                    'value' => $value
                ];
            }
        }
       return $settings;
    }
}
