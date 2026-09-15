<?php

return [
    'title' => 'Star Cloud IoT 通訊協議',
    'version' => 'v1.0.0',
    'description' => '此文件提供 Star Cloud 智慧插座 IoT 端點通訊協議說明，包含主動拉取 (HTTP REST) 與被動接收 (MQTT) 雙軌通訊機制，供硬體端與前端開發者調研與串接使用。',

    'http_apis' => [
        [
            'name' => '機台核心通訊 (IoT Core)',
            'apis' => [
                [
                    'name' => 'B000: 維運人員登入認證 (Technician Login)',
                    'slug' => 'b000-tech-login',
                    'method' => 'POST',
                    'path' => '/api/v1/app/admin/login/B000',
                    'description' => '維運人員輸入個人帳密進行認證。此 API 需帶入機台 API Token。驗證成功後核發臨時 User Token 供後續擴充使用。',
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer <machine_api_token>',
                    ],
                    'parameters' => [
                        'Su_Account' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => '維運人員帳號 (username/email)',
                            'example' => 'admin_test'
                        ],
                        'Su_Password' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => '維運人員密碼',
                            'example' => 'password123'
                        ],
                        'ip' => [
                            'type' => 'string',
                            'required' => false,
                            'description' => '機台本地 IP',
                            'example' => '192.168.1.100'
                        ],
                    ],
                    'response_parameters' => [
                        'success' => [
                            'type' => 'boolean',
                            'description' => '請求是否成功',
                            'example' => true
                        ],
                        'code' => [
                            'type' => 'integer',
                            'description' => '業務狀態碼',
                            'example' => 200
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => '回應訊息',
                            'example' => 'Success'
                        ],
                        'identity' => [
                            'type' => 'string',
                            'description' => '登入者身分：system 系統登入者 / company 公司帳號 / staff 一般人員 (僅驗證成功時回傳)',
                            'example' => 'system'
                        ],
                        'token' => [
                            'type' => 'string',
                            'description' => '臨時身份認證 Token',
                            'example' => '1|abcdefg...'
                        ],
                    ],
                    'request' => [
                        'Su_Account' => 'admin_test',
                        'Su_Password' => 'password123',
                        'ip' => '192.168.1.100'
                    ],
                    'response' => [
                        'success' => true,
                        'code' => 200,
                        'message' => 'Success',
                        'identity' => 'system',
                        'token' => '1|abcdefg...'
                    ],
                ],
                [
                    'name' => 'B014: 機台參數與金鑰下載 (Config Download)',
                    'slug' => 'b014-config-download',
                    'method' => 'GET',
                    'path' => '/api/v1/app/machine/setting/B014',
                    'description' => '透過此介面下載機台專屬通訊 Token 與系統設定。教學版智慧插座已移除販賣機金流/發票配置 (payment_configs)，僅保留裝置系統設定 (machines.settings) 與硬體實體欄位。',
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'parameters' => [
                        'machine' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => '機台序號',
                            'example' => 'SN202604130001'
                        ],
                    ],
                    'response_parameters' => [
                        'success' => [
                            'type' => 'boolean',
                            'description' => '是否成功',
                        ],
                        'data' => [
                            'type' => 'object',
                            'description' => '配置物件，下列各欄位皆位於 data 之下。DevSet / CashSet / FunctionSet / ShoppingMode 來源為「機台系統設定 (machines.settings)」，OperationSet / HardwareSet 來源為 machines 實體欄位，皆採機台端 *Set + PascalCase 風格。',
                        ],
                        'data.t050v01' => [
                            'type' => 'string',
                            'description' => '機台序號 ← machines.serial_no',
                        ],
                        'data.api_token' => [
                            'type' => 'string',
                            'description' => '機台正式通訊 Token ← machines.api_token（初始化後存本地，後續 API 認證用）',
                        ],
                        'data.DevSet' => [
                            'type' => 'object',
                            'description' => "支付旗標（命名沿用機台 DevSetStructure；布林）。機台既有欄位：\n• ShoppingCar ← shopping_cart_enabled（購物車）\n• Invoice ← tax_invoice_enabled（電子發票）\n• DevNFCPay ← card_terminal_enabled（刷卡機總開關）\n• DevEsunPay ← scan_pay_esun_enabled（玉山掃碼）\n• DevTapPay ← scan_pay_tappay_enabled（TapPay 掃碼）\n• DevCash ← cash_module_enabled（現金）\n• DevLinePay ← scan_pay_linepay_enabled（LINE Pay 官方直連）\n• TapPay30 ← tappay_linepay（TapPay 底下的 LINE Pay）\n• TapPay31 ← tappay_jkopay（街口支付）\n• TapPay32 ← tappay_easywallet（悠遊付）\n• TapPay33 ← tappay_pipay（Pi 支付）\n• TapPay34 ← tappay_pluspay（全盈+支付）\n新定義（機台端待新增欄位）：\n• DevCreditCard ← credit_card_enabled（信用卡）\n• DevMobilePay ← mobile_pay_enabled（手機支付）\n• DevCardPay ← card_pay_enabled（卡片支付）\n• DevScanPay ← scan_pay_enabled（掃碼總開關）\n（VMC/Electic 為機台硬體類型，雲端不下發）",
                        ],
                        'data.CashSet' => [
                            'type' => 'object',
                            'description' => "現金面額旗標（命名對齊機台端 CashSetStructure；布林）：\n• BillF1000 / BillE500 / BillD100 ← cash_bill_1000 / 500 / 100\n• CoinF50 / CoinE10 / CoinD5 / CoinC1 ← cash_coin_50 / 10 / 5 / 1",
                        ],
                        'data.FunctionSet' => [
                            'type' => 'object',
                            'description' => "非支付功能模組旗標（新定義，機台端待實作對應結構；布林）：\n• PickupModule ← pickup_module_enabled（取貨模組）\n• PickupCode ← pickup_code_enabled（取貨碼）\n• PassCode ← pass_code_enabled（通行碼）\n• WelcomeGift ← welcome_gift_enabled（來店禮）\n• MemberSystem ← member_system_enabled（會員系統）\n• AmbientTemp ← ambient_temp_monitoring_enabled（環境溫度監控）\n• PharmacyPickup ← pharmacy_pickup_enabled（領藥單；雲端建單、掃 QR 出貨。僅在 ShoppingMode=pickup_sheet 取物單模式下有效）",
                        ],
                        'data.ShoppingMode' => [
                            'type' => 'string',
                            'description' => '購物方式（新定義）← machines.settings.shopping_mode：basic / employee_card / pickup_sheet',
                        ],
                        'data.LangSet' => [
                            'type' => 'object',
                            'description' => "機台顯示語系（新定義，最多 5 種）← machines.settings.languages：\n• Languages ← 有序語系陣列（如 [\"zh_TW\",\"en\",\"ja\"]，順序即切換順序）\n• Default ← 預設語系（清單第一個，開機/idle 顯示）\n未設定時退化為 [fallback]。機台據此渲染語系切換 UI；可選語系池子見 config/locales.php。商品名稱/規格的對應翻譯由 B012 的 t060v01_i18n / t060v03_i18n 提供。",
                        ],
                        'data.OperationSet' => [
                            'type' => 'object',
                            'description' => "運作參數（新定義；來源 machines 實體欄位）：\n• CardReaderSeconds ← card_reader_seconds（整數，刷卡機秒數）\n• PaymentBufferSeconds ← payment_buffer_seconds（整數，金流緩衝秒數）\n• CheckoutTime1 ← card_reader_checkout_time_1（時間字串）\n• CheckoutTime2 ← card_reader_checkout_time_2\n• HeatingStartTime ← heating_start_time（加熱開始）\n• HeatingEndTime ← heating_end_time（加熱結束）",
                        ],
                        'data.HardwareSet' => [
                            'type' => 'object',
                            'description' => "硬體與貨道（新定義；來源 machines 實體欄位）：\n• CardReaderNo ← card_reader_no（字串，刷卡機編號）\n• SpringSlot1_10 / 11_20 / 21_30 / 31_40 / 41_50 / 51_60 ← is_spring_slot_*（布林，true=彈簧 / false=履帶）",
                        ],
                    ],
                    'request' => [],
                    'response' => [
                        'success' => true,
                        'code' => 200,
                        'data' => [
                            't050v01' => 'SN202604130001',
                            'api_token' => 'mac_token_...',
                            'DevSet' => [
                                'ShoppingCar' => true,
                                'Invoice' => true,
                                'DevNFCPay' => true,
                                'DevCreditCard' => true,
                                'DevMobilePay' => true,
                                'DevCardPay' => false,
                                'DevScanPay' => true,
                                'DevEsunPay' => true,
                                'DevTapPay' => false,
                                'DevCash' => true,
                                'DevLinePay' => true,
                                'TapPay30' => false,
                                'TapPay31' => true,
                                'TapPay32' => false,
                                'TapPay33' => false,
                                'TapPay34' => false,
                            ],
                            'CashSet' => [
                                'BillF1000' => true,
                                'BillE500' => true,
                                'BillD100' => true,
                                'CoinF50' => true,
                                'CoinE10' => true,
                                'CoinD5' => true,
                                'CoinC1' => true,
                            ],
                            'FunctionSet' => [
                                'PickupModule' => false,
                                'PickupCode' => false,
                                'PassCode' => false,
                                'WelcomeGift' => false,
                                'MemberSystem' => false,
                                'AmbientTemp' => false,
                                'PharmacyPickup' => false,
                            ],
                            'ShoppingMode' => 'basic',
                            'LangSet' => [
                                'Languages' => ['zh_TW', 'en', 'ja'],
                                'Default' => 'zh_TW',
                            ],
                            'OperationSet' => [
                                'CardReaderSeconds' => 30,
                                'PaymentBufferSeconds' => 5,
                                'CheckoutTime1' => '22:30:00',
                                'CheckoutTime2' => '23:45:00',
                                'HeatingStartTime' => '00:00:00',
                                'HeatingEndTime' => '00:00:00',
                            ],
                            'HardwareSet' => [
                                'CardReaderNo' => 'CR-001',
                                'SpringSlot1_10' => true,
                                'SpringSlot11_20' => false,
                                'SpringSlot21_30' => false,
                                'SpringSlot31_40' => false,
                                'SpringSlot41_50' => false,
                                'SpringSlot51_60' => false,
                            ],
                        ]
                    ],
                    'notes' => '此 API 為機台初始化引導用，目前不強制驗證 User Token。DevSet / CashSet / FunctionSet / ShoppingMode 來源為 machines.settings、OperationSet / HardwareSet 來源為 machines 實體欄位，全部採機台端 *Set + PascalCase 風格（DevSet/CashSet 對齊機台既有結構，其餘為新定義，機台 App 端需另案實作）。教學版智慧插座已移除販賣機金流配置 (payment_configs)。'
                ],
                [
                    'name' => 'B016: 機台系統設定回寫 (Settings Write-back)',
                    'slug' => 'b016-settings-writeback',
                    'method' => 'PATCH',
                    'path' => '/api/v1/app/machine/setting/B016',
                    'description' => '機台主控台由系統方設定硬體貨道類型後回寫雲端，為 B014 下載的反向操作。需帶 B000 核發之 User Token，且僅系統管理員可操作。機台端現在只回寫硬體貨道類型 (is_spring_slot_*)，其餘系統設定純由後台單向決定，B016 不再接收。',
                    'headers' => [
                        'Authorization' => 'Bearer <user_token>',
                        'Content-Type' => 'application/json',
                    ],
                    'parameters' => [
                        'machine' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => '機台序號 (serial_no)',
                            'example' => 'SN202604130001'
                        ],
                        'settings' => [
                            'type' => 'object',
                            'required' => true,
                            'description' => "貨道類型鍵值物件（皆為布林），鍵名採後端原始 snake_case，只覆寫有送來的鍵、其餘保留。白名單外鍵一律忽略。可回寫鍵：\n• 貨道類型（machines 實體欄位）：is_spring_slot_1_10 / 11_20 / 21_30 / 31_40 / 41_50 / 51_60（true=彈簧 / false=履帶）",
                            'example' => ['is_spring_slot_1_10' => false]
                        ],
                    ],
                    'response_parameters' => [
                        'success' => [
                            'type' => 'boolean',
                            'description' => '是否成功',
                        ],
                        'code' => [
                            'type' => 'integer',
                            'description' => '業務狀態碼',
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => '回應訊息',
                        ],
                    ],
                    'request' => [
                        'machine' => 'SN202604130001',
                        'settings' => [
                            'is_spring_slot_1_10' => true,
                            'is_spring_slot_11_20' => false,
                        ],
                    ],
                    'response' => [
                        'success' => true,
                        'code' => 200,
                        'message' => 'Settings updated successfully.',
                    ],
                    'notes' => '僅系統管理員 (identity=system) 可操作，非系統方回 403 Forbidden；查無序號回 404 Machine not found；settings 非物件或未含任何有效貨道鍵回 422 Invalid settings payload。寫回時更新 is_spring_slot_* 實體欄位並記錄 updater_id，套用 withoutGlobalScopes 跨租戶定位機台。'
                ],
            ],
        ],
    ],

    'mqtt_connection_specs' => [
        'description' => '機台連線 MQTT Broker 時的必要憑據。',
        'specs' => [
            'Host' => 'your-mqtt-broker.example.com',
            'Port' => '443 (WSS / 公網) 或 1883 (TCP / 內部)',
            'Username' => '機台序號 (serial_no)',
            'Password' => 'API Token 的 SHA256 雜湊值 (由 B014 取得)',
            'ClientID' => 'SC_{serial_no} (固定格式，確保單機唯一連線)',
        ]
    ],

    'mqtt_topics' => [
        [
            'name' => '指令與通訊 (Commands & Flow)',
            'topics' => [
                [
                    'name' => '機台心跳上報 (Heartbeat)',
                    'slug' => 'mqtt-heartbeat',
                    'action' => 'PUB',
                    'topic' => 'machine/{serial_no}/heartbeat',
                    'qos' => 0,
                    'description' => '機台每分鐘固定上報的狀態大包裝。整合了溫度與韌體版本。雲端記錄規則：溫度只要有變動 (current !== last) 即記錄日誌。',
                    'payload_parameters' => [
                        'temperature' => ['type' => 'integer', 'description' => '目前機台溫度'],
                        'firmware_version' => ['type' => 'string', 'description' => 'APP 韌體版本'],
                    ],
                    'payload_example' => [
                        'temperature' => 20,
                        'firmware_version' => '2.1.6'
                    ],
                ],
                [
                    'name' => '機台連線狀態上報 (Status/LWT)',
                    'slug' => 'mqtt-status',
                    'action' => 'PUB',
                    'topic' => 'machine/{serial_no}/status',
                    'qos' => 0,
                    'description' => '用於機台連線與斷線的即時狀態同步。通常用於 MQTT 的遺囑訊息 (LWT) 設定，或由 Broker 連線事件觸發。',
                    'payload_parameters' => [
                        'status' => ['type' => 'string', 'description' => '連線狀態。接受值：online, offline, restarting'],
                    ],
                    'payload_example' => [
                        'status' => 'online'
                    ],
                ],
                [
                    'name' => '機台環境溫度上報 (Ambient Temperature Report)',
                    'slug' => 'mqtt-ambient-temp-report',
                    'action' => 'PUB',
                    'topic' => 'machine/{serial_no}/ambient_temp',
                    'qos' => 1,
                    'description' => '機台主動上報當前環境溫度。支援 temperature 或 ambient_temp 欄位上報。',
                    'payload_parameters' => [
                        'temperature' => ['type' => 'integer', 'description' => '當前環境溫度值 (例如 28)'],
                        'ambient_temp' => ['type' => 'integer', 'description' => '當前環境溫度值 (相容相應硬體參數)'],
                    ],
                    'payload_example' => [
                        'temperature' => 28
                    ],
                ],

                [
                    'name' => '機台硬體事件上報 (Event)',
                    'slug' => 'mqtt-event',
                    'action' => 'PUB',
                    'topic' => 'machine/{serial_no}/event',
                    'qos' => 1,
                    'description' => '用於機台上報各種獨立的硬體運行與物理事件。後台收到後會直接寫入機台狀態日誌中並進行多語系翻譯。',
                    'payload_parameters' => [
                        'event' => ['type' => 'string', 'description' => '事件識別碼。例如：fanon (風扇開), fanoff (風扇關) 等。'],
                    ],
                    'payload_example' => [
                        'event' => 'fanon'
                    ],
                ],
                [
                    'name' => '機台異常上報 (Error)',
                    'slug' => 'mqtt-error',
                    'action' => 'PUB',
                    'topic' => 'machine/{serial_no}/error',
                    'qos' => 1,
                    'description' => '當機台發生硬體故障（如卡貨、通訊中斷）時即時上報。',
                    'payload_parameters' => [
                        'tid' => ['type' => 'integer', 'description' => '貨道編號或任務 ID'],
                        'error_code' => ['type' => 'string', 'description' => '硬體錯誤代碼 (如 0403 代表卡貨)'],
                    ],
                    'payload_example' => [
                        'tid' => 12,
                        'error_code' => '0403'
                    ],
                ],
                [
                    'name' => '指令下發：遠端找零 (Change)',
                    'slug' => 'mqtt-command-change',
                    'action' => 'SUB',
                    'topic' => 'machine/{serial_no}/command',
                    'qos' => 1,
                    'description' => '雲端主動下發「找零」指令。指示機台退還指定金額的現金。',
                    'payload_parameters' => [
                        'command' => ['type' => 'string', 'description' => '固定為 "change"'],
                        'command_id' => ['type' => 'string', 'description' => '指令唯一 ID'],
                        'payload' => ['type' => 'object', 'description' => '找零參數'],
                        'payload.amount' => ['type' => 'integer', 'description' => '欲找零/退款的金額'],
                    ],
                    'payload_example' => [
                        'command' => 'change',
                        'command_id' => '1122334455',
                        'payload' => [
                            'amount' => 50
                        ]
                    ],
                ],
                [
                    'name' => '指令下發：系統控制 (System Control)',
                    'slug' => 'mqtt-command-system',
                    'action' => 'SUB',
                    'topic' => 'machine/{serial_no}/command',
                    'qos' => 1,
                    'description' => '雲端主動下發的系統級別控制指令。包含重啟、設備鎖定解鎖、購物車結帳等無額外參數的指令。',
                    'payload_parameters' => [
                        'command' => ['type' => 'string', 'description' => '系統指令類型。支援：reboot (重啟機台), reboot_card (重啟刷卡機), checkout (購物車結帳), lock (設備鎖定), unlock (設備解鎖), update_ads (廣告同步), update_products (商品資料同步), fanon (開啟風扇), fanoff (關閉風扇), fanauto (風扇自動控制)'],
                        'command_id' => ['type' => 'string', 'description' => '指令唯一 ID'],
                        'payload' => ['type' => 'object', 'description' => '空物件 (無額外參數)'],
                    ],
                    'payload_example' => [
                        'command' => 'reboot',
                        'command_id' => '9999999999',
                        'payload' => new \stdClass()
                    ],
                ],

                [
                    'name' => '指令下發：環境溫度上限 (Ambient Temperature Upper Limit)',
                    'slug' => 'mqtt-command-ambient-temp-limit',
                    'action' => 'SUB',
                    'topic' => 'machine/{serial_no}/command',
                    'qos' => 1,
                    'description' => '雲端主動下發「環境溫度上限」指令。指示機台更新環境溫度開啟風扇的閾值設定。',
                    'payload_parameters' => [
                        'command' => ['type' => 'string', 'description' => '固定為 "ambient_temp_limit"'],
                        'command_id' => ['type' => 'string', 'description' => '指令唯一 ID'],
                        'payload' => ['type' => 'object', 'description' => '溫度設定參數'],
                        'payload.temperature' => ['type' => 'integer', 'description' => '環境溫度上限閾值（可為 null 表示停用監測）'],
                    ],
                    'payload_example' => [
                        'command' => 'ambient_temp_limit',
                        'command_id' => '1122334455',
                        'payload' => [
                            'temperature' => 28
                        ]
                    ],
                ],

                [
                    'name' => '指令執行回報 (Machine to Cloud)',
                    'slug' => 'mqtt-command-ack',
                    'action' => 'PUB',
                    'topic' => 'machine/{serial_no}/command/ack',
                    'qos' => 1,
                    'description' => '機台執行完雲端指令後的主動回報。此回報極為重要，雲端將依此更新指令狀態並記錄機台日誌。',
                    'payload_parameters' => [
                        'command_id' => ['type' => 'string', 'description' => '對應下發時的指令 ID'],
                        'result' => ['type' => 'string', 'description' => '執行結果，僅接受: success 或 failed'],
                        'message' => ['type' => 'string', 'description' => '額外的錯誤訊息 (選填)'],
                    ],
                    'payload_example' => [
                        'command_id' => '12345',
                        'result' => 'success'
                    ],
                ]
            ]
        ]
    ]
];