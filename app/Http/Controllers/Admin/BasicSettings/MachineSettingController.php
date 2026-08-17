<?php

namespace App\Http\Controllers\Admin\BasicSettings;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Machine\Machine;
use App\Models\Machine\MachineModel;
use App\Models\System\Company;
use App\Models\System\PaymentConfig;
use App\Traits\ImageHandler;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;

class MachineSettingController extends AdminController
{
    use ImageHandler;

    /**
     * 顯示機台與型號設定列表 (採用標籤頁整合)
     */
    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $tab = $request->input('tab', 'machines');
        $per_page = $request->input('per_page', 10);
        $search = $request->input('search');
        $isAjax = $request->boolean('_ajax');
        $currentUser = auth()->user();
        $companyId = trim((string) $request->input('company_id', ''));

        $applyMachineFilters = function ($query) use ($search, $currentUser, $companyId) {
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('serial_no', 'like', "%{$search}%");
                });
            }

            if ($currentUser->isSystemAdmin() && $companyId !== '') {
                $query->where('company_id', $companyId);
            }

            return $query;
        };

        // AJAX 模式：只查當前 Tab 的搜尋/分頁結果
        if ($isAjax) {
            $machines = null;
            $models_list = null;
            $users_list = null;

            switch ($tab) {
                case 'machines':
                    $machineQuery = Machine::query()->with(['machineModel', 'paymentConfig', 'company']);
                    $applyMachineFilters($machineQuery);
                    $machines = $machineQuery->latest()->paginate($per_page)->withQueryString();
                    break;

                case 'system_settings':
                    $machineQuery = Machine::query()->with(['company']);
                    $applyMachineFilters($machineQuery);
                    $machines = $machineQuery->latest()->paginate($per_page)->withQueryString();
                    break;

                case 'models':
                    $modelQuery = MachineModel::query()->withCount('machines');
                    if ($search) {
                        $modelQuery->where('name', 'like', "%{$search}%");
                    }
                    $models_list = $modelQuery->latest()->paginate($per_page)->withQueryString();
                    break;

                case 'permissions':
                    // 套用層級可見範圍：系統管理員看全部；租戶僅看可見範圍內帳號（避免跨租戶洩漏）。
                    $userQuery = \App\Models\System\User::query()
                        ->where('is_admin', true)
                        ->visibleTo($currentUser)
                        ->with(['company', 'machines']);
                    if ($search) {
                        $userQuery->where(function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('username', 'like', "%{$search}%");
                        });
                    }
                    if ($currentUser->isSystemAdmin() && $companyId !== '') {
                        $userQuery->where('company_id', $companyId);
                    }
                    $users_list = $userQuery->latest()->paginate($per_page)->withQueryString();
                    break;

            }

            $companies = Company::select('id', 'name', 'code')->orderBy('name')->get();

            $tabView = match($tab) {
                'models'      => 'admin.basic-settings.machines.partials.tab-models',
                'permissions' => 'admin.basic-settings.machines.partials.tab-permissions',
                'system_settings' => 'admin.basic-settings.machines.partials.tab-system-settings',
                default       => 'admin.basic-settings.machines.partials.tab-machines',
            };
            return response()->view($tabView, compact(
                'machines', 'models_list', 'users_list', 'companies', 'tab'
            ));
        }

        // SSR 模式：一次查好全部三個 Tab 的首頁資料（供 x-show 即時切換）
        $machineQuery = Machine::query()
            ->with(['machineModel', 'paymentConfig', 'company']);

        if (in_array($tab, ['machines', 'system_settings'], true)) {
            $applyMachineFilters($machineQuery);
        }

        $machines = $machineQuery->latest()->paginate($per_page)->withQueryString();

        $models_list = MachineModel::query()
            ->withCount('machines')
            ->latest()->paginate($per_page)->withQueryString();

        $userQuery = \App\Models\System\User::query()
            ->where('is_admin', true)
            ->visibleTo($currentUser)
            ->with(['company', 'machines']);
        $users_list = $userQuery->latest()->paginate($per_page)->withQueryString();

        // 基礎下拉資料 (用於新增/編輯機台的彈窗)
        $models = MachineModel::select('id', 'name')->get();
        $paymentConfigs = PaymentConfig::select('id', 'name')->get();
        $companies = Company::select('id', 'name', 'code')->orderBy('name')->get();

        // 同步系統設定彈窗的機台選擇器清單 (依 TenantScoped 自動租戶隔離)
        $allMachines = Machine::select('id', 'name', 'serial_no')->orderBy('name')->get();

        return view('admin.basic-settings.machines.index', compact(
            'machines',
            'models_list',
            'users_list',
            'models',
            'paymentConfigs',
            'companies',
            'allMachines',
            'tab'
        ));
    }

    /**
     * 儲存新機台 (僅核心欄位)
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'serial_no' => 'required|string|unique:machines,serial_no',
            'company_id' => 'nullable|exists:companies,id',
            'machine_model_id' => 'required|exists:machine_models,id',
            'payment_config_id' => 'nullable|exists:payment_configs,id',
            'key_no' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // Increase to 10MB
        ]);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach (array_slice($request->file('images'), 0, 3) as $image) {
                $imagePaths[] = $this->storeAsWebp($image, 'machines');
            }
        }

        $machine = Machine::create(array_merge($validated, [
            'api_token' => \Illuminate\Support\Str::random(60),
            'creator_id' => auth()->id(),
            'updater_id' => auth()->id(),
            'card_reader_seconds' => 30, // 預設值
            'card_reader_checkout_time_1' => '22:30:00',
            'card_reader_checkout_time_2' => '23:45:00',
            'payment_buffer_seconds' => 5,
            'images' => $imagePaths,
        ]));

        return redirect()->route('admin.basic-settings.machines.index')
            ->with('success', __('Machine created successfully.'));
    }

    /**
     * 顯示詳細編輯頁面
     */
    public function edit(Machine $machine): View
    {
        $models = MachineModel::select('id', 'name')->get();
        $paymentConfigs = PaymentConfig::select('id', 'name')->get();
        $companies = \App\Models\System\Company::select('id', 'name', 'code')->get();

        return view('admin.basic-settings.machines.edit', compact('machine', 'models', 'paymentConfigs', 'companies'));
    }

    /**
     * 更新機台詳細參數
     */
    public function update(Request $request, Machine $machine): RedirectResponse
    {
        Log::info('Machine Update Request', ['machine_id' => $machine->id, 'data' => $request->all()]);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'serial_no' => 'sometimes|required|string|unique:machines,serial_no,' . $machine->id,
                'card_reader_seconds' => 'required|integer|min:0',
                'payment_buffer_seconds' => 'required|integer|min:0',
                'card_reader_checkout_time_1' => 'nullable|string',
                'card_reader_checkout_time_2' => 'nullable|string',
                'heating_start_time' => 'nullable|string',
                'heating_end_time' => 'nullable|string',
                'card_reader_no' => 'nullable|string|max:255',
                'key_no' => 'nullable|string|max:255',
                'is_spring_slot_1_10' => 'boolean',
                'is_spring_slot_11_20' => 'boolean',
                'is_spring_slot_21_30' => 'boolean',
                'is_spring_slot_31_40' => 'boolean',
                'is_spring_slot_41_50' => 'boolean',
                'is_spring_slot_51_60' => 'boolean',
                'tax_invoice_enabled' => 'boolean',
                'card_terminal_enabled' => 'boolean',
                'scan_pay_esun_enabled' => 'boolean',
                'scan_pay_linepay_enabled' => 'boolean',
                'shopping_cart_enabled' => 'boolean',
                'cash_module_enabled' => 'boolean',
                'ambient_temp_monitoring_enabled' => 'boolean',
                'machine_model_id' => 'required|exists:machine_models,id',
                'payment_config_id' => 'nullable|exists:payment_configs,id',
                'location' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'image_0' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'image_1' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'remove_image_0' => 'nullable|boolean',
                'remove_image_1' => 'nullable|boolean',
                'remove_image_2' => 'nullable|boolean',
            ]);

            // 僅限系統管理員可修改公司
            if (auth()->user()->isSystemAdmin()) {
                $companyRule = ['company_id' => 'nullable|exists:companies,id'];
                $companyData = $request->validate($companyRule);
                $validated = array_merge($validated, $companyData);
            }
            
            Log::info('Machine Update Validated Data', ['data' => $validated]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Machine Update Validation Failed', ['errors' => $e->errors()]);
            throw $e;
        }

        // 排除虛擬欄位 (圖片上傳、移除標記)，這些欄位不在資料表內
        $dataToUpdate = \Illuminate\Support\Arr::except($validated, [
            'image_0', 'image_1', 'image_2', 
            'remove_image_0', 'remove_image_1', 'remove_image_2'
        ]);

        // 偵測序號是否變動，若變動則自動重新產生 API Token
        $tokenRegenerated = false;
        if (isset($dataToUpdate['serial_no']) && $dataToUpdate['serial_no'] !== $machine->serial_no) {
            $dataToUpdate['api_token'] = Str::random(60);
            $tokenRegenerated = true;
        }

        $machine->update(array_merge($dataToUpdate, [
            'updater_id' => auth()->id(),
        ]));

        // 處理圖片更新 (支援 3 個獨立槽位: image_0, image_1, image_2)
        $currentImages = $machine->images ?? [];
        $updated = false;

        for ($i = 0; $i < 3; $i++) {
            $inputName = "image_$i";
            $removeName = "remove_image_$i";

            // 如果有新圖片上傳
            if ($request->hasFile($inputName)) {
                // 刪除舊圖
                if (isset($currentImages[$i]) && !empty($currentImages[$i])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($currentImages[$i]);
                }
                // 儲存新圖
                $currentImages[$i] = $this->storeAsWebp($request->file($inputName), 'machines');
                $updated = true;
            } 
            // 否則，如果有刪除標記
            elseif ($request->input($removeName) === '1') {
                if (isset($currentImages[$i]) && !empty($currentImages[$i])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($currentImages[$i]);
                    unset($currentImages[$i]);
                    $updated = true;
                }
            }
        }

        if ($updated) {
            ksort($currentImages);
            $machine->update(['images' => array_values($currentImages)]);
        }

        $successMessage = __('Machine updated successfully.');
        if ($tokenRegenerated) {
            $successMessage .= ' ' . __('API Token has been regenerated due to serial number change.');
        }

        return redirect()->route('admin.basic-settings.machines.index')
            ->with('success', $successMessage);
    }

    public function regenerateToken(Request $request, $serial): \Illuminate\Http\JsonResponse
    {
        $machine = Machine::where('serial_no', $serial)->firstOrFail();
        $newToken = \Illuminate\Support\Str::random(60);
        $machine->update(['api_token' => $newToken]);


        Log::info('Machine API Token Regenerated', [
            'machine_id' => $machine->id,
            'serial_no' => $machine->serial_no,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => __('API Token regenerated successfully.'),
            'api_token' => $newToken
        ]);
    }

    /**
     * 系統設定錯誤回應：依請求型態回 JSON 或 redirect。
     */
    private function settingsError(Request $request, string $message, int $code): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $code);
        }
        return redirect()->back()->with('error', $message)->withInput();
    }

    public function updateSystemSettings(Request $request, Machine $machine): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        \Log::info('Update System Settings Request:', $request->all());
        if ($request->has('settings')) {
            $settings = $request->input('settings');
            $allowedFields = [
                'tax_invoice_enabled',
                'card_terminal_enabled',
                'scan_pay_esun_enabled',
                'scan_pay_linepay_enabled',
                'shopping_cart_enabled',
                'welcome_gift_enabled',
                'cash_module_enabled',
                'pickup_module_enabled',
                'member_system_enabled',
                'ambient_temp_monitoring_enabled',
            ];

            $allowedShoppingModes = ['basic', 'employee_card', 'pickup_sheet'];
            $shoppingMode = $settings['shopping_mode'] ?? 'basic';
            if (!in_array($shoppingMode, $allowedShoppingModes, true)) {
                $shoppingMode = 'basic';
            }

            $data = [];
            foreach ($allowedFields as $field) {
                $data[$field] = isset($settings[$field]) ? (bool)$settings[$field] : false;
            }

            $jsonSettings = $machine->settings ?? [];
            $jsonSettings['shopping_mode'] = $shoppingMode;

            if ($shoppingMode !== 'basic') {
                $data['card_terminal_enabled'] = false;
                $data['scan_pay_esun_enabled'] = false;
                $data['scan_pay_linepay_enabled'] = false;
                $data['shopping_cart_enabled'] = false;
                $data['welcome_gift_enabled'] = false;
                $data['cash_module_enabled'] = false;
                $data['pickup_module_enabled'] = false;

                $jsonSettings['credit_card_enabled'] = false;
                $jsonSettings['mobile_pay_enabled'] = false;
                $jsonSettings['card_pay_enabled'] = false;

                $jsonSettings['scan_pay_enabled'] = false;
                $jsonSettings['scan_pay_tappay_enabled'] = false;
                $jsonSettings['tappay_linepay'] = false;
                $jsonSettings['tappay_jkopay'] = false;
                $jsonSettings['tappay_pipay'] = false;
                $jsonSettings['tappay_pluspay'] = false;
                $jsonSettings['tappay_easywallet'] = false;

                $jsonSettings['cash_bill_1000'] = false;
                $jsonSettings['cash_bill_500'] = false;
                $jsonSettings['cash_bill_100'] = false;
                $jsonSettings['cash_coin_50'] = false;
                $jsonSettings['cash_coin_10'] = false;
                $jsonSettings['cash_coin_5'] = false;
                $jsonSettings['cash_coin_1'] = false;

                $jsonSettings['pickup_code_enabled'] = false;
                $jsonSettings['pass_code_enabled'] = false;

                // 副櫃系統（格子櫃功能）僅基礎版可用，非基礎版一律關閉
                $jsonSettings['subcabinet_enabled'] = false;
            } else {
                $jsonSettings['credit_card_enabled'] = isset($settings['credit_card_enabled']) ? (bool)$settings['credit_card_enabled'] : false;
                $jsonSettings['mobile_pay_enabled'] = isset($settings['mobile_pay_enabled']) ? (bool)$settings['mobile_pay_enabled'] : false;
                $jsonSettings['card_pay_enabled'] = isset($settings['card_pay_enabled']) ? (bool)$settings['card_pay_enabled'] : false;

                $jsonSettings['scan_pay_enabled'] = isset($settings['scan_pay_enabled']) ? (bool)$settings['scan_pay_enabled'] : false;
                $jsonSettings['scan_pay_tappay_enabled'] = isset($settings['scan_pay_tappay_enabled']) ? (bool)$settings['scan_pay_tappay_enabled'] : false;
                $jsonSettings['tappay_linepay'] = isset($settings['tappay_linepay']) ? (bool)$settings['tappay_linepay'] : false;
                $jsonSettings['tappay_jkopay'] = isset($settings['tappay_jkopay']) ? (bool)$settings['tappay_jkopay'] : false;
                $jsonSettings['tappay_pipay'] = isset($settings['tappay_pipay']) ? (bool)$settings['tappay_pipay'] : false;
                $jsonSettings['tappay_pluspay'] = isset($settings['tappay_pluspay']) ? (bool)$settings['tappay_pluspay'] : false;
                $jsonSettings['tappay_easywallet'] = isset($settings['tappay_easywallet']) ? (bool)$settings['tappay_easywallet'] : false;

                $jsonSettings['cash_bill_1000'] = isset($settings['cash_bill_1000']) ? (bool)$settings['cash_bill_1000'] : false;
                $jsonSettings['cash_bill_500'] = isset($settings['cash_bill_500']) ? (bool)$settings['cash_bill_500'] : false;
                $jsonSettings['cash_bill_100'] = isset($settings['cash_bill_100']) ? (bool)$settings['cash_bill_100'] : false;
                $jsonSettings['cash_coin_50'] = isset($settings['cash_coin_50']) ? (bool)$settings['cash_coin_50'] : false;
                $jsonSettings['cash_coin_10'] = isset($settings['cash_coin_10']) ? (bool)$settings['cash_coin_10'] : false;
                $jsonSettings['cash_coin_5'] = isset($settings['cash_coin_5']) ? (bool)$settings['cash_coin_5'] : false;
                $jsonSettings['cash_coin_1'] = isset($settings['cash_coin_1']) ? (bool)$settings['cash_coin_1'] : false;

                $jsonSettings['pickup_code_enabled'] = isset($settings['pickup_code_enabled']) ? (bool)$settings['pickup_code_enabled'] : false;
                $jsonSettings['pass_code_enabled'] = isset($settings['pass_code_enabled']) ? (bool)$settings['pass_code_enabled'] : false;

                // 副櫃系統（格子櫃功能）：基礎版可設定
                $jsonSettings['subcabinet_enabled'] = isset($settings['subcabinet_enabled']) ? (bool)$settings['subcabinet_enabled'] : false;
            }

            foreach ($allowedFields as $field) {
                $jsonSettings[$field] = $data[$field];
            }

            // 領藥單模組：僅在「取物單(pickup_sheet)」模式下可啟用，其他模式一律關閉
            $jsonSettings['pharmacy_pickup_enabled'] = ($shoppingMode === 'pickup_sheet')
                ? (bool) ($settings['pharmacy_pickup_enabled'] ?? false)
                : false;

            // 顯示語系 (languages)：機台螢幕可切換的語系，最多 N 種，僅系統管理員可設定。
            // 商品翻譯範圍由「公司機台語系聯集」反推，故此處異動需失效聯集快取並重建商品目錄。
            // 非系統管理員即使送出 languages 亦一律忽略（不報錯，讓其仍可儲存其他設定）。
            $languagesChanged = false;
            if (array_key_exists('languages', $settings) && auth()->user()->isSystemAdmin()) {
                $whitelist = array_keys(config('locales.supported', []));
                $max = (int) config('locales.max_per_machine', 5);

                // 去重、保留順序、僅留白名單內語系
                $languages = array_values(array_unique(array_filter(
                    (array) $settings['languages'],
                    fn ($l) => in_array($l, $whitelist, true)
                )));

                if (count((array) $settings['languages']) > $max || count($languages) > $max) {
                    return $this->settingsError($request, __('You can select at most :max languages.', ['max' => $max]), 422);
                }

                $jsonSettings['languages'] = $languages;
                $languagesChanged = true;
            }

            $machine->update([
                'settings' => $jsonSettings,
                'updater_id' => auth()->id()
            ]);

            // 同時更新 model 的屬性欄位 (以防 mutator 同步，以及確保 dirty check 正確)
            $machine->update(array_merge($data, ['updater_id' => auth()->id()]));

            // 語系異動：失效公司語系聯集快取並重建商品目錄 i18n（B012 下發內容隨之更新）
            if ($languagesChanged) {
                \App\Models\System\Company::forgetActiveLocales($machine->company_id);
                app(\App\Services\Product\ProductCatalogService::class)->rebuildCache($machine->company_id);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Settings updated successfully.')
                ]);
            }
            return redirect()->back()->with('success', __('Settings updated successfully.'));
        }

        $field = $request->input('field');
        $value = $request->boolean('value');

        $allowedFields = [
            'tax_invoice_enabled',
            'card_terminal_enabled',
            'scan_pay_esun_enabled',
            'scan_pay_linepay_enabled',
            'shopping_cart_enabled',
            'welcome_gift_enabled',
            'cash_module_enabled',
            'member_system_enabled',
            'ambient_temp_monitoring_enabled',
        ];

        if (!in_array($field, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Invalid field'], 400);
        }

        $machine->update([$field => $value, 'updater_id' => auth()->id()]);

        return response()->json([
            'success' => true,
            'message' => __('Setting updated successfully.')
        ]);
    }

    /**
     * 同步系統設定至機台：透過 MQTT 下發 update_settings 指令，
     * 通知機台 APP 主動回抓最新系統設定 (B014)。
     * 參考廣告同步 (AdvertisementController::syncToMachine) 的併行控制模式。
     */
    public function syncSettings(Request $request, Machine $machine, \App\Services\Machine\MqttService $mqttService): \Illuminate\Http\JsonResponse
    {
        // 併行檢查：若有 pending 指令，超過 1 分鐘視為逾時可覆蓋
        $pendingCommand = \App\Models\Machine\RemoteCommand::where('machine_id', $machine->id)
            ->where('command_type', 'update_settings')
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingCommand) {
            $isTimedOut = $pendingCommand->created_at->lt(now()->subMinute());

            if (!$isTimedOut) {
                return response()->json([
                    'success' => false,
                    'message' => __('This machine has a pending command. Please wait.')
                ], 422);
            }

            $pendingCommand->update([
                'status' => 'timeout',
                'note'   => __('Superseded by new command (Timeout)'),
            ]);
        }

        $command = \App\Models\Machine\RemoteCommand::create([
            'machine_id'   => $machine->id,
            'user_id'      => auth()->id(),
            'command_type' => 'update_settings',
            'payload'      => [],
            'status'       => 'pending',
            'remark'       => $request->filled('note') ? $request->input('note') : __('Manual Sync Settings'),
        ]);

        $success = $mqttService->pushCommand(
            $machine->serial_no,
            'update_settings',
            [],
            (string) $command->id
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => __('Sync command sent successfully.')
            ]);
        }

        $command->update(['status' => 'failed', 'note' => 'MQTT push failed']);

        return response()->json([
            'success' => false,
            'message' => __('Failed to send sync command.')
        ], 500);
    }

    /**
     * 刪除機台 (僅限系統管理員)
     */
    public function destroy(Machine $machine): RedirectResponse
    {
        if (!auth()->user()->isSystemAdmin()) {
            abort(403);
        }

        $machine->delete();

        return redirect()->route('admin.basic-settings.machines.index')
            ->with('success', __('Machine deleted successfully.'));
    }

    /**
     * 公開機台分布地圖
     */
    public function distribution(): View
    {
        $machines = Machine::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('machineModel')
            ->get(['id', 'name', 'serial_no', 'address', 'location', 'latitude', 'longitude', 'status', 'machine_model_id']);

        return view('admin.basic-settings.machines.distribution', compact('machines'));
    }
}
