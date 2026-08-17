<?php

return [
    'title' => 'Star Cloud IoT 通訊協議',
    'version' => 'v1.0.0',
    'description' => '此文件提供 Star Cloud 智能販賣機 IoT 端點通訊協議說明，包含主動拉取 (HTTP REST) 與被動接收 (MQTT) 雙軌通訊機制，供硬體端與前端開發者調研與串接使用。',

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
                    'description' => '透過此介面下載金流金鑰、電子發票設定與機台專屬通訊 Token。金流密鑰與發票設定均取自機台關聯的「金流配置 (payment_configs.settings)」，未設值欄位回傳空字串。',
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
                            'description' => '配置物件，下列各欄位皆位於 data 之下。金鑰／發票來源為機台關聯的「金流配置 (payment_configs.settings)」；DevSet / CashSet / FunctionSet / ShoppingMode 來源為「機台系統設定 (machines.settings)」，OperationSet / HardwareSet 來源為 machines 實體欄位，皆採機台端 *Set + PascalCase 風格。',
                        ],
                        'data.t050v01' => [
                            'type' => 'string',
                            'description' => '機台序號 ← machines.serial_no',
                        ],
                        'data.api_token' => [
                            'type' => 'string',
                            'description' => '機台正式通訊 Token ← machines.api_token（初始化後存本地，後續 API 認證用）',
                        ],
                        'data.t050v41' => [
                            'type' => 'string',
                            'description' => '玉山掃碼 StoreID ← 金流配置 esun_scan.store_id',
                        ],
                        'data.t050v42' => [
                            'type' => 'string',
                            'description' => '玉山掃碼 TermID ← 金流配置 esun_scan.term_id',
                        ],
                        'data.t050v43' => [
                            'type' => 'string',
                            'description' => '玉山掃碼 Key ← 金流配置 esun_scan.key',
                        ],
                        'data.t050v34' => [
                            'type' => 'string',
                            'description' => '綠界發票 商店代號 ← 金流配置 ecpay_invoice.store_id',
                        ],
                        'data.t050v35' => [
                            'type' => 'string',
                            'description' => '綠界發票 HashKey ← 金流配置 ecpay_invoice.hash_key',
                        ],
                        'data.t050v36' => [
                            'type' => 'string',
                            'description' => '綠界發票 HashIV ← 金流配置 ecpay_invoice.hash_iv',
                        ],
                        'data.t050v38' => [
                            'type' => 'string',
                            'description' => '綠界發票 通知 Email ← 金流配置 ecpay_invoice.email',
                        ],
                        'data.TP_APP_ID' => [
                            'type' => 'string',
                            'description' => 'TapPay App ID ← 金流配置 tappay.app_id',
                        ],
                        'data.TP_APP_KEY' => [
                            'type' => 'string',
                            'description' => 'TapPay App Key ← 金流配置 tappay.app_key',
                        ],
                        'data.TP_PARTNER_KEY' => [
                            'type' => 'string',
                            'description' => 'TapPay Partner Key ← 金流配置 tappay.partner_key',
                        ],
                        'data.TP_LINE_MERCHANT_ID' => [
                            'type' => 'string',
                            'description' => 'LINE Pay 特店 ID ← 金流配置 tappay.line_merchant_id',
                        ],
                        'data.TP_JKO_MERCHANT_ID' => [
                            'type' => 'string',
                            'description' => '街口支付 特店 ID ← 金流配置 tappay.jko_merchant_id',
                        ],
                        'data.TP_PI_MERCHANT_ID' => [
                            'type' => 'string',
                            'description' => 'Pi 拍錢包 特店 ID ← 金流配置 tappay.pi_merchant_id',
                        ],
                        'data.TP_PS_MERCHANT_ID' => [
                            'type' => 'string',
                            'description' => '全盈+Pay 特店 ID ← 金流配置 tappay.ps_merchant_id',
                        ],
                        'data.TP_EASY_MERCHANT_ID' => [
                            'type' => 'string',
                            'description' => '悠遊付 特店 ID ← 金流配置 tappay.easy_merchant_id',
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
                            't050v41' => '80812345',
                            't050v42' => '9001',
                            't050v43' => 'hash_key',
                            't050v34' => '2000132',
                            't050v35' => 'invoice_hash_key',
                            't050v36' => 'invoice_hash_iv',
                            't050v38' => 'invoice@example.com',
                            'TP_APP_ID' => 'GP_001',
                            'TP_APP_KEY' => 'app_key_...',
                            'TP_PARTNER_KEY' => 'partner_key_...',
                            'TP_LINE_MERCHANT_ID' => 'LINE_M_001',
                            'TP_JKO_MERCHANT_ID' => 'JKO_M_001',
                            'TP_PI_MERCHANT_ID' => 'PI_M_001',
                            'TP_PS_MERCHANT_ID' => 'PS_M_001',
                            'TP_EASY_MERCHANT_ID' => 'EASY_M_001',
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
                    'notes' => '此 API 為機台初始化引導用，目前不強制驗證 User Token。金流密鑰與發票設定來源為機台關聯的金流配置 (payment_configs)；DevSet / CashSet / FunctionSet / ShoppingMode 來源為 machines.settings、OperationSet / HardwareSet 來源為 machines 實體欄位，全部採機台端 *Set + PascalCase 風格（DevSet/CashSet 對齊機台既有結構，其餘為新定義，機台 App 端需另案實作）。'
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
                [
                    'name' => 'B005: 廣告清單同步 (Ad Sync)',
                    'slug' => 'b005-ad-sync',
                    'method' => 'GET',
                    'path' => '/api/v1/app/machine/ad/B005',
                    'description' => '用於機台端獲取目前應播放的廣告檔案 URL 清單。此介面無需 Request Body。',
                    'headers' => [
                        'Authorization' => 'Bearer <api_token>',
                        'Content-Type' => 'application/json',
                    ],
                    'parameters' => [],
                    'response_parameters' => [
                        'success' => [
                            'type' => 'boolean',
                            'description' => '請求是否成功',
                            'example' => true
                        ],
                        'code' => [
                            'type' => 'integer',
                            'description' => '內部業務狀態碼',
                            'example' => 200
                        ],
                        'data' => [
                            'type' => 'array',
                            'description' => '廣告物件陣列。內部欄位包含：t070v01 (名稱), t070v02 (秒數), t070v03 (位置：1:販賣頁, 2:來店禮, 3:待機廣告), t070v04 (URL), t070v05 (順位)',
                            'example' => [
                                [
                                    't070v01' => '測試機台廣告',
                                    't070v02' => 15,
                                    't070v03' => 3,
                                    't070v04' => 'https://example.com/ad1.mp4',
                                    't070v05' => 1
                                ]
                            ]
                        ],
                    ],
                    'request' => [],
                    'response' => [
                        'success' => true,
                        'code' => 200,
                        'data' => [
                            [
                                't070v01' => '測試機台廣告',
                                't070v02' => 15,
                                't070v03' => 3,
                                't070v04' => 'https://example.com/ad1.mp4',
                                't070v05' => 1
                            ]
                        ]
                    ],
                ],
                [
                    'name' => 'B009: 貨道庫存即時回報 (Inventory Report)',
                    'slug' => 'b009-inventory-report',
                    'method' => 'PUT',
                    'path' => '/api/v1/app/products/supplementary/B009',
                    'description' => '當人員在機台端完成操作後，將目前的貨道實體狀態同步回雲端。需進行 RBAC 權限核查。',
                    'headers' => [
                        'Authorization' => 'Bearer <api_token>',
                        'Content-Type' => 'application/json',
                    ],
                    'parameters' => [
                        'account' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => '操作人員帳號',
                            'example' => '0999123456'
                        ],
                        'data' => [
                            'type' => 'array',
                            'required' => true,
                            'description' => '貨道數據陣列。tid: 貨道號, t060v00: 商品 ID, num: 庫存量, type: 貨道類型(1:履帶, 2:彈簧)',
                            'example' => [
                                ['tid' => '1', 't060v00' => '1', 'num' => 10, 'type' => 1]
                            ]
                        ],
                    ],
                    'response_parameters' => [
                        'success' => [
                            'type' => 'boolean',
                            'description' => '同步是否成功',
                            'example' => true
                        ],
                        'code' => [
                            'type' => 'integer',
                            'description' => '內部業務狀態碼',
                            'example' => 200
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => '回應訊息',
                            'example' => 'Slot report synchronized success'
                        ],
                    ],
                    'request' => [
                        'account' => '0999123456',
                        'data' => [
                            ['tid' => '1', 't060v00' => '1', 'num' => 10, 'type' => 1]
                        ]
                    ],
                    'response' => [
                        'success' => true,
                        'code' => 200,
                        'message' => 'Slot report synchronized success',
                    ],
                ],
                [
                    'name' => 'B012: 商品配置與商品主檔同步 (Unified Sync)',
                    'slug' => 'b012-unified-sync',
                    'method' => 'GET',
                    'path' => '/api/v1/app/machine/products/B012',
                    'description' => '用於機台端獲取目前所有可販售商品的詳細配置 (全量同步)。',
                    'headers' => [
                        'Authorization' => 'Bearer <api_token>',
                        'Content-Type' => 'application/json',
                    ],
                    'parameters' => [],
                    'response_parameters' => [
                        'success' => [
                            'type' => 'boolean',
                            'description' => '請求是否處理成功',
                            'example' => true
                        ],
                        'code' => [
                            'type' => 'integer',
                            'description' => '內部業務狀態碼',
                            'example' => 200
                        ],
                        'data' => [
                            'type' => 'array',
                            'description' => '商品明細物件陣列。欄位包含：t060v00(ID), t060v01(名稱), t060v01_en/jp(既有英日名稱，保留相容), t060v01_i18n(名稱多語系 map), t060v03(規格), t060v03_i18n(規格多語系 map), t060v06(圖片), t060v09(售價), t060v11(預設上限), spring_limit(彈簧上限), track_limit(履帶上限)。多語系 map 的 key 集合=公司機台語系聯集，缺翻譯回退 zh_TW；既有欄位一律保留以相容線上舊 App。',
                            'example' => [
                                [
                                    't060v00' => '1',
                                    't060v01' => '可口可樂 330ml',
                                    't060v01_en' => 'Coca Cola',
                                    't060v01_jp' => 'コカコーラ',
                                    't060v01_i18n' => ['zh_TW' => '可口可樂 330ml', 'en' => 'Coca Cola', 'ja' => 'コカコーラ'],
                                    't060v03' => 'Cold Drink',
                                    't060v03_i18n' => ['zh_TW' => 'Cold Drink', 'en' => 'Cold Drink', 'ja' => 'コールドドリンク'],
                                    't060v06' => 'https://.../coke.png',
                                    't060v09' => 25.0,
                                    't060v11' => 10,
                                    't060v30' => 20.0,
                                    't063v03' => 25.0,
                                    't060v40' => 'Buy 1 Get 1',
                                    't060v41' => 'SKU-COKE-001',
                                    'spring_limit' => 10,
                                    'track_limit' => 15
                                ]
                            ]
                        ]
                    ],
                    'request' => [],
                    'response' => [
                        'success' => true,
                        'code' => 200,
                        'data' => [
                            [
                                't060v00' => '1',
                                't060v01' => '可口可樂 330ml',
                                't060v01_en' => 'Coca Cola 330ml',
                                't060v01_jp' => 'コカコーラ 330ml',
                                't060v01_i18n' => ['zh_TW' => '可口可樂 330ml', 'en' => 'Coca Cola 330ml', 'ja' => 'コカコーラ 330ml'],
                                't060v03' => '經典原味，冰涼好滋味',
                                't060v03_i18n' => ['zh_TW' => '經典原味，冰涼好滋味', 'en' => 'Classic taste, ice cold', 'ja' => 'クラシックな味わい'],
                                't060v06' => 'https://cloud.star.com/storage/products/coke.png',
                                't060v09' => 25.0,
                                't060v11' => 10,
                                't060v30' => 20.0,
                                't063v03' => 25.0,
                                't060v40' => '買一送一活動中',
                                't060v41' => 'SKU-COKE-001',
                                'spring_limit' => 10,
                                'track_limit' => 15
                            ]
                        ]
                    ],
                ],
                [
                    'name' => 'B650: 會員身分驗證 (Member Verification)',
                    'slug' => 'b650-member-verify',
                    'method' => 'POST',
                    'path' => '/api/v1/app/machine/member/verify/B650',
                    'description' => '機台端掃碼後驗證會員身分。',
                    'headers' => [
                        'Authorization' => 'Bearer <api_token>',
                        'Content-Type' => 'application/json',
                    ],
                    'parameters' => [
                        'code' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => '會員代碼',
                            'example' => 'MEMBER12345'
                        ]
                    ],
                    'response' => [
                        'success' => true,
                        'data' => [
                            'member_id' => 123,
                            'name' => '王小明',
                            'points' => 500
                        ]
                    ],
                ],
                [
                    'name' => 'B660: 取貨碼驗證 (Pickup Code)',
                    'slug' => 'b660-pickup-code',
                    'method' => 'POST',
                    'path' => '/api/v1/app/machine/pickup/verify/B660',
                    'description' => '處理代碼取貨驗證。成功後回傳對應資訊，正式消耗改由 MQTT finalize 處理。',
                    'headers' => [
                        'Authorization' => 'Bearer <api_token>',
                        'Content-Type' => 'application/json',
                    ],
                    'parameters' => [
                        'code' => [
                            'type' => 'string',
                            'description' => '8 位數取貨碼',
                            'example' => '12345678'
                        ],
                    ],
                    'response' => [
                        'success' => true,
                        'data' => [
                            'slot_no' => '1',
                            'pickup_code_id' => 45,
                            'status' => 'active'
                        ]
                    ],
                    'notes' => '安全性：連續錯誤 5 次將鎖定該機台驗證功能 1 分鐘。消耗流程已遷移至 MQTT。'
                ],
                [
                    'name' => 'B670: 通行碼驗證 (Pass Code)',
                    'slug' => 'b670-pass-code',
                    'method' => 'POST',
                    'path' => '/api/v1/app/machine/passcode/verify/B670',
                    'description' => '專供維修/工程人員使用的通行碼驗證。僅做身份確認，不涉及商品消耗。',
                    'headers' => [
                        'Authorization' => 'Bearer <api_token>',
                        'Content-Type' => 'application/json',
                    ],
                    'parameters' => [
                        'code' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => '8 位數通行碼',
                            'example' => '88888888'
                        ]
                    ],
                    'response' => [
                        'success' => true,
                        'data' => [
                            'name' => '工程師 A',
                            'status' => 'active'
                        ]
                    ],
                    'notes' => '僅供現場測試使用，錯誤過多同樣會觸發鎖定。'
                ],
                [
                    'name' => 'B680: 員工卡身分驗證 (Staff Card Verification)',
                    'slug' => 'b680-staff-card-verify',
                    'method' => 'POST',
                    'path' => '/api/v1/app/machine/staff/verify/B680',
                    'description' => '機台端感應員工卡 (RFID/NFC) 後驗證卡片 UID。',
                    'headers' => [
                        'Authorization' => 'Bearer <api_token>',
                        'Content-Type' => 'application/json',
                    ],
                    'parameters' => [
                        'code' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => '卡片 UID 碼',
                            'example' => 'A1B2C3D4'
                        ]
                    ],
                    'response' => [
                        'success' => true,
                        'data' => [
                            'name' => '員工姓名',
                            'code_id' => 12,
                            'status' => 'active'
                        ]
                    ],
                    'notes' => '驗證成功後會建立刷卡日誌。連續錯誤同樣會觸發鎖定。'
                ],
                [
                    'name' => 'B690: 來店禮驗證 (Welcome Gift Verify)',
                    'slug' => 'b690-welcome-gift-verify',
                    'method' => 'POST',
                    'path' => '/api/v1/app/machine/welcome-gift/verify/B690',
                    'description' => '處理機台端的 8 位數來店禮代碼驗證。成功後回傳折扣資訊（含台灣中文折數標籤），供機台端套用折扣。正式消耗改由 MQTT finalize 處理 (payment_type=7)。',
                    'headers' => [
                        'Authorization' => 'Bearer <api_token>',
                        'Content-Type' => 'application/json',
                    ],
                    'parameters' => [
                        'code' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => '8 位數來店禮代碼',
                            'example' => '12345678'
                        ]
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
                        'data.name' => [
                            'type' => 'string',
                            'description' => '來店禮名稱',
                            'example' => '新客八五折'
                        ],
                        'data.code_id' => [
                            'type' => 'integer',
                            'description' => '來店禮 ID (用於 MQTT 交易關聯，payment_type=7 時帶入)',
                            'example' => 12
                        ],
                        'data.discount_type' => [
                            'type' => 'string',
                            'description' => '折扣類型：percentage (趴數折扣) 或 amount (金額折扣)',
                            'example' => 'percentage'
                        ],
                        'data.discount_value' => [
                            'type' => 'integer',
                            'description' => '折扣數值。percentage 時為趴數 (如 15 代表 15% off)；amount 時為折扣金額 (如 50)',
                            'example' => 15
                        ],
                        'data.discount_label' => [
                            'type' => 'string',
                            'description' => '台灣中文折數標籤 (如「八五折」、「折 50 元」)，供 UI 直接顯示',
                            'example' => '八五折'
                        ],
                        'data.usage_type' => [
                            'type' => 'string',
                            'description' => '使用類型：once (單次) 或 unlimited (無限制)',
                            'example' => 'once'
                        ],
                        'data.status' => [
                            'type' => 'string',
                            'description' => '來店禮狀態',
                            'example' => 'active'
                        ],
                    ],
                    'request' => [
                        'code' => '12345678'
                    ],
                    'response' => [
                        'success' => true,
                        'code' => 200,
                        'data' => [
                            'name' => '新客八五折',
                            'code_id' => 12,
                            'discount_type' => 'percentage',
                            'discount_value' => 15,
                            'discount_label' => '八五折',
                            'usage_type' => 'once',
                            'status' => 'active'
                        ]
                    ],
                    'error_responses' => [
                        [
                            'code' => 400,
                            'message' => 'Invalid format',
                            'description' => 'code 非 8 位數或缺少必填欄位。'
                        ],
                        [
                            'code' => 404,
                            'message' => 'Invalid or expired code',
                            'description' => '代碼不存在、已失效或已過期。回應另含 remaining_attempts (剩餘嘗試次數)。'
                        ],
                        [
                            'code' => 429,
                            'message' => 'Too many attempts. Locked.',
                            'description' => '連續錯誤達 5 次，該機台驗證功能於鎖定期間 (60 秒) 內皆回傳此回應。'
                        ],
                    ],
                    'notes' => '安全性：連續錯誤 5 次將鎖定該機台驗證功能 1 分鐘。消耗流程已遷移至 MQTT (payment_type=7)。'
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
                    'name' => '交易階段性上報 (Transaction Steps)',
                    'slug' => 'mqtt-transaction-steps',
                    'action' => 'PUB',
                    'topic' => 'machine/{serial_no}/transaction',
                    'qos' => 1,
                    'description' => '用於傳統的分段式上報：交易建立 (create)、發票紀錄 (invoice) 或單次出貨結果 (dispense)。',
                    'payload_parameters' => [
                        'action' => ['type' => 'string', 'description' => '動作：create, invoice, dispense'],
                        'flow_id' => ['type' => 'string', 'description' => '唯一流水號'],
                    ],
                    'payload_example' => [
                        'action' => 'create',
                        'flow_id' => 'FLOW123',
                        'total_amount' => 25.0,
                        'payment_type' => 1,
                        'items' => [['product_id' => 5, 'price' => 25.0, 'quantity' => 1]]
                    ],
                ],
                [
                    'name' => '交易完成統整上報 (Transaction Finalized - 推薦)',
                    'slug' => 'mqtt-transaction-finalize',
                    'action' => 'PUB',
                    'topic' => 'machine/{serial_no}/transaction',
                    'qos' => 1,
                    'description' => '【推薦使用】將交易、發票與出貨結果統整為單一動作上報，確保資料庫寫入的原子性與一致性。',
                    'payload_parameters' => [
                        'action' => ['type' => 'string', 'description' => '(必填) 固定為 "finalize"'],
                        'order' => ['type' => 'object', 'description' => '(必填) 訂單主檔資訊'],
                        'order.order_no' => ['type' => 'string', 'description' => '(必填) 訂單編號'],
                        'order.flow_id' => ['type' => 'string', 'description' => '(必填) 機台端交易流水號'],
                        'order.total_amount' => ['type' => 'numeric', 'description' => '(必填) 應付總金額'],
                        'order.pay_amount' => ['type' => 'numeric', 'description' => '(必填) 實際支付金額'],
                        'order.change_amount' => ['type' => 'numeric', 'description' => '(選填) 找零金額，預設 0', 'required' => false],
                        'order.original_amount' => ['type' => 'numeric', 'description' => '(選填) 原始標價金額', 'required' => false],
                        'order.discount_amount' => ['type' => 'numeric', 'description' => '(選填) 折扣金額，預設 0', 'required' => false],
                        'order.points_used' => ['type' => 'integer', 'description' => '(選填) 使用點數，預設 0', 'required' => false],
                        'order.payment_type' => ['type' => 'integer', 'description' => "(必填) 支付類型代碼：\n1:信用卡, 2:電子票證(悠遊卡/一卡通/iCash 等), 3:掃碼支付, 4:紙鈔機, 5:通行碼, 6:取貨碼, 7:來店禮, 8:問卷, 9:零錢(硬幣), 10:手機支付(行動支付/NFC 手機感應), 41:員工卡, 42:取物單。\n※ 1(信用卡)、2(電子票證)、10(手機支付) 走同一台實體刷卡機(Nexsys 終端機)。\n21~25:線下付款+基本類型 (21:線下+1 信用卡, 22:線下+2 電子票證, 23:線下+3 掃碼, 24:線下+4 紙鈔, 25:線下+9 零錢)。\n30:LINE Pay(TapPay), 31:街口, 32:悠遊付, 33:Pi 拍錢包, 34:全盈+PAY。\n70:LINE Pay(官方直連，非 TapPay 底下的 30)。\n40:會員驗證取貨商品。\n50~54:線下付款+TapPay (50:線下+30, 51:線下+31, 52:線下+32, 53:線下+33, 54:線下+34)。\n60:點數/優惠券全額折抵。61~69:會員+基本類型 (61:會員+1...69:會員+9)。\n90~94:會員+TapPay (90:會員+30...94:會員+34)。\n100:遠端出貨。"],
                        'order.payment_status' => ['type' => 'integer', 'description' => '(必填) 支付狀態 (1:成功)'],
                        'order.code_id' => ['type' => 'string', 'description' => '(選填) 閉環勾稽用的代碼 ID (payment_type 為 5, 6, 41 時必填)', 'required' => false],
                        'order.payment_request' => ['type' => 'string', 'description' => '(選填) 金流請求原始字串', 'required' => false],
                        'order.payment_response' => ['type' => 'string', 'description' => '(選填) 金流回應原始字串', 'required' => false],
                        'order.member_barcode' => ['type' => 'string', 'description' => '(選填) 會員條碼', 'required' => false],
                        'order.invoice_info' => ['type' => 'string', 'description' => '(選填) 預設發票歸戶字串 (如 統編-電話)', 'required' => false],
                        'order.machine_time' => ['type' => 'datetime', 'description' => '(必填) 機台交易時間 (Y-m-d H:i:s)'],
                        'order.items' => ['type' => 'array', 'description' => '(必填) 訂單明細陣列'],
                        'order.items[].product_id' => ['type' => 'integer', 'description' => '(必填) 商品 ID'],
                        'order.items[].price' => ['type' => 'numeric', 'description' => '(必填) 商品單價'],
                        'order.items[].quantity' => ['type' => 'integer', 'description' => '(必填) 數量'],

                        'invoice' => ['type' => 'object', 'description' => '(選填) 發票開立資訊 (若無發票可省略)', 'required' => false],
                        'invoice.invoice_no' => ['type' => 'string', 'description' => '(必填，若有invoice) 發票號碼', 'required' => false],
                        'invoice.invoice_date' => ['type' => 'string', 'description' => '(必填，若有invoice) 發票日期 (Y-m-d)', 'required' => false],
                        'invoice.machine_time' => ['type' => 'datetime', 'description' => '(必填，若有invoice) 機台開立發票時間 (Y-m-d H:i:s)', 'required' => false],
                        'invoice.amount' => ['type' => 'numeric', 'description' => '(必填，若有invoice) 發票金額', 'required' => false],
                        'invoice.random_number' => ['type' => 'string', 'description' => '(必填，若有invoice) 發票隨機碼 (4位)', 'required' => false],
                        'invoice.love_code' => ['type' => 'string', 'description' => '(選填) 捐贈碼', 'required' => false],
                        'invoice.carrier_id' => ['type' => 'string', 'description' => '(選填) 載具編號', 'required' => false],
                        'invoice.carrier_type' => ['type' => 'string', 'description' => '(選填) 載具類型 (如 3J0002)', 'required' => false],
                        'invoice.business_tax_id' => ['type' => 'string', 'description' => '(選填) 買受人統編', 'required' => false],
                        'invoice.rtn_code' => ['type' => 'string', 'description' => '(選填) 電子發票回傳代碼', 'required' => false],
                        'invoice.rtn_msg' => ['type' => 'string', 'description' => '(選填) 電子發票回傳訊息', 'required' => false],

                        'dispense' => ['type' => 'array', 'description' => '(必填) 出貨結果清單'],
                        'dispense[].slot_no' => ['type' => 'string', 'description' => '(必填) 貨道編號'],
                        'dispense[].product_id' => ['type' => 'integer', 'description' => '(必填) 商品 ID'],
                        'dispense[].amount' => ['type' => 'numeric', 'description' => '(必填) 實際扣款額 (單項)'],
                        'dispense[].points_used' => ['type' => 'integer', 'description' => '(選填) 該商品花費點數，預設 0', 'required' => false],
                        'dispense[].dispense_status' => ['type' => 'integer', 'description' => '(必填) 出貨狀態 (1:成功, 0:失敗)'],
                        'dispense[].remaining_stock' => ['type' => 'integer', 'description' => '(必填) 出貨後剩餘庫存'],
                        'dispense[].member_barcode' => ['type' => 'string', 'description' => '(選填) 會員條碼', 'required' => false],
                        'dispense[].machine_time' => ['type' => 'datetime', 'description' => '(選填) 機台出貨時間 (Y-m-d H:i:s)', 'required' => false],
                    ],
                    'payload_example' => [
                        'action' => 'finalize',
                        'order' => [
                            'order_no' => 'T202508080001',
                            'flow_id' => 'FLOW123',
                            'total_amount' => 100.0,
                            'original_amount' => 100.0,
                            'discount_amount' => 0.0,
                            'pay_amount' => 100.0,
                            'change_amount' => 0.0,
                            'points_used' => 0,
                            'payment_type' => 1,
                            'payment_status' => 1,
                            'code_id' => '對應指令的ID或取貨碼(如適用)',
                            'payment_request' => '{"req": "data"}',
                            'payment_response' => '{"res": "ok"}',
                            'member_barcode' => 'MBR999',
                            'invoice_info' => '12345678-0912345678',
                            'machine_time' => '2025-08-08 10:00:00',
                            'items' => [
                                ['product_id' => 5, 'price' => 100.0, 'quantity' => 1]
                            ]
                        ],
                        'invoice' => [
                            'invoice_no' => 'AB12345678',
                            'invoice_date' => '2025-08-08',
                            'amount' => 100.0,
                            'random_number' => '1234',
                            'love_code' => '',
                            'carrier_id' => '/ABC1234',
                            'carrier_type' => '3J0002',
                            'business_tax_id' => '12345678',
                            'rtn_code' => '1',
                            'rtn_msg' => '成功'
                        ],
                        'dispense' => [
                            [
                                'slot_no' => '3',
                                'product_id' => 5,
                                'amount' => 100.0,
                                'points_used' => 0,
                                'dispense_status' => 1,
                                'remaining_stock' => 9,
                                'member_barcode' => 'MBR999',
                                'machine_time' => '2025-08-08 10:01:00'
                            ]
                        ]
                    ],
                ],
                [
                    'name' => '指令下發：遠端出貨 (Dispense)',
                    'slug' => 'mqtt-command-dispense',
                    'action' => 'SUB',
                    'topic' => 'machine/{serial_no}/command',
                    'qos' => 1,
                    'description' => '雲端主動下發「遠端出貨」指令。機台收到後應立即驅動馬達進行出貨，出貨完成或失敗後回報 ACK。',
                    'payload_parameters' => [
                        'command' => ['type' => 'string', 'description' => '固定為 "dispense"'],
                        'command_id' => ['type' => 'string', 'description' => '指令唯一 ID'],
                        'payload' => ['type' => 'object', 'description' => '出貨參數'],
                        'payload.slot_no' => ['type' => 'string', 'description' => '欲出貨的貨道號碼'],
                        'payload.old_stock' => ['type' => 'integer', 'description' => '出貨前的庫存量'],
                        'payload.new_stock' => ['type' => 'integer', 'description' => '出貨後應剩餘的庫存量 (樂觀扣減結果)'],
                    ],
                    'payload_example' => [
                        'command' => 'dispense',
                        'command_id' => '9988776655',
                        'payload' => [
                            'slot_no' => '1',
                            'old_stock' => 10,
                            'new_stock' => 9
                        ]
                    ],
                ],
                [
                    'name' => '指令下發：庫存同步 (Update Inventory)',
                    'slug' => 'mqtt-command-update-inventory',
                    'action' => 'SUB',
                    'topic' => 'machine/{serial_no}/command',
                    'qos' => 1,
                    'description' => '雲端主動下發「庫存更新」指令 (如從後台修改庫存、效期時觸發)。',
                    'payload_parameters' => [
                        'command' => ['type' => 'string', 'description' => '固定為 "update_inventory"'],
                        'command_id' => ['type' => 'string', 'description' => '指令唯一 ID'],
                        'payload' => ['type' => 'object', 'description' => '更新參數'],
                        'payload.slot_no' => ['type' => 'string', 'description' => '貨道號碼'],
                        'payload.stock' => ['type' => 'integer', 'description' => '最新庫存量'],
                        'payload.expiry_date' => ['type' => 'string', 'description' => '最新效期 (YYYY-MM-DD)'],
                        'payload.batch_no' => ['type' => 'string', 'description' => '批號 (可為 null)'],
                    ],
                    'payload_example' => [
                        'command' => 'update_inventory',
                        'command_id' => '5566778899',
                        'payload' => [
                            'slot_no' => '1',
                            'stock' => 10,
                            'expiry_date' => '2026-12-31',
                            'batch_no' => null
                        ]
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
                    'description' => '機台執行完雲端指令後的主動回報。此回報極為重要，雲端將依此決定交易是否完成或需進行庫存回滾。',
                    'payload_parameters' => [
                        'command_id' => ['type' => 'string', 'description' => '對應下發時的指令 ID'],
                        'result' => ['type' => 'string', 'description' => '執行結果，僅接受: success 或 failed'],
                        'stock' => ['type' => 'integer', 'description' => '執行指令後的最終剩餘庫存 (選填，通常出貨指令需帶上)'],
                        'message' => ['type' => 'string', 'description' => '額外的錯誤訊息 (選填)'],
                    ],
                    'payload_example' => [
                        'command_id' => '12345',
                        'result' => 'success',
                        'stock' => 9
                    ],
                ]
            ]
        ]
    ]
];