<?php

namespace App\Http\Controllers\Admin\BasicSettings;

use App\Http\Controllers\Admin\AdminController;
use App\Models\System\Company;
use App\Services\Notification\DiscordWebhookService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class DiscordNotificationController extends AdminController
{
    /**
     * 顯示 Discord 告警設定頁面 (雙 Tab 支援)
     */
     public function index(Request $request): View
     {
         $user = auth()->user();
         $activeTab = $request->input('tab', 'company');
 
         // ── Tab 1: 公司全域設定 ──
         $companyQuery = Company::query();
         if ($user->isSystemAdmin()) {
             if ($searchCompany = $request->input('search_company')) {
                 $companyQuery->where(function ($q) use ($searchCompany) {
                     $q->where('name', 'like', "%{$searchCompany}%")
                       ->orWhere('code', 'like', "%{$searchCompany}%");
                 });
             }
         } else {
             $companyQuery->where('id', $user->company_id);
         }
         $companies = $companyQuery->paginate($request->input('company_per_page', 10), ['*'], 'company_page')
             ->withQueryString();
 
         // ── Tab 2: 機台個別設定 ──
         $machineQuery = \App\Models\Machine\Machine::query()->with(['company', 'machineModel']);
         if ($user->isSystemAdmin()) {
             if ($filterCompanyId = trim($request->input('filter_company_id', ''))) {
                 $machineQuery->where('company_id', $filterCompanyId);
             }
             if ($searchMachine = $request->input('search_machine')) {
                 $machineQuery->where(function ($q) use ($searchMachine) {
                     $q->where('name', 'like', "%{$searchMachine}%")
                       ->orWhere('serial_no', 'like', "%{$searchMachine}%");
                 });
             }
         } else {
             $machineQuery->where('company_id', $user->company_id);
             if ($searchMachine = $request->input('search_machine')) {
                 $machineQuery->where(function ($q) use ($searchMachine) {
                     $q->where('name', 'like', "%{$searchMachine}%")
                       ->orWhere('serial_no', 'like', "%{$searchMachine}%");
                 });
             }
         }
         $machines = $machineQuery->paginate($request->input('machine_per_page', 10), ['*'], 'machine_page')
             ->withQueryString();
 
         // ── 輔助資料 ──
         // 所有公司列表 (僅 System Admin 用於 Tab 2 篩選下拉)
         $allCompanies = [];
         if ($user->isSystemAdmin()) {
             $allCompanies = Company::select('id', 'name')->get();
         }
 
         // 用於前端 Modal「拉式複製」的同公司機台 settings 列表
         $copyableQuery = \App\Models\Machine\Machine::select('id', 'name', 'serial_no', 'company_id', 'settings');
         if (!$user->isSystemAdmin()) {
             $copyableQuery->where('company_id', $user->company_id);
         }
         $copyableMachines = $copyableQuery->get();
 
         return view('admin.basic-settings.discord-notifications.index', [
             'companies' => $companies,
             'machines' => $machines,
             'allCompanies' => $allCompanies,
             'copyableMachines' => $copyableMachines,
             'activeTab' => $activeTab,
         ]);
     }
 
     /**
      * 更新公司 Discord 告警設定
      */
     public function updateCompany(Request $request, $id)
     {
         $user = auth()->user();
         if (!$user->isSystemAdmin() && (int)$id !== (int)$user->company_id) {
             abort(403, __('Unauthorized action.'));
         }
 
         $company = Company::findOrFail($id);
 
         $request->validate([
             'discord_webhook_url' => 'nullable|url|max:500',
             'discord_notify_enabled' => 'boolean',
             'discord_notify_types' => 'array',
             'discord_notify_types.*' => 'string|in:submachine,status,temperature',
             'discord_notify_locale' => 'required|string|in:zh_TW,en,ja',
         ]);
 
         $settings = $company->settings ?? [];
         $settings['discord_webhook_url'] = $request->input('discord_webhook_url');
         $settings['discord_notify_enabled'] = (bool) $request->input('discord_notify_enabled', false);
         $settings['discord_notify_types'] = $request->input('discord_notify_types', []);
         $settings['locale'] = $request->input('discord_notify_locale', 'zh_TW');
 
         $company->settings = $settings;
         $company->save();
 
         return redirect()->route('admin.basic-settings.discord-notifications.index', ['tab' => 'company'])
             ->with('success', __('Company Discord notification settings updated successfully.'));
     }
 
     /**
      * 更新機台 Discord 與溫度告警設定
      */
     public function updateMachine(Request $request, $id)
     {
         $user = auth()->user();
         $machine = \App\Models\Machine\Machine::findOrFail($id);
 
         if (!$user->isSystemAdmin() && (int)$machine->company_id !== (int)$user->company_id) {
             abort(403, __('Unauthorized action.'));
         }
 
         $request->validate([
             'discord_webhook_url' => 'nullable|url|max:500',
             'notify_types_mode' => 'required|string|in:inherit,custom',
             'discord_notify_types' => 'nullable|array',
             'discord_notify_types.*' => 'string|in:submachine,status,temperature',
             'temp_upper_limit' => 'nullable|numeric|min:-50|max:100',
             'temp_lower_limit' => 'nullable|numeric|min:-50|max:100',
         ]);
 
         $settings = $machine->settings ?? [];
         $settings['discord_webhook_url'] = $request->input('discord_webhook_url');
 
         $notifyTypesMode = $request->input('notify_types_mode', 'inherit');
         $notifyTypes = $request->input('discord_notify_types', []);
         $hasTemperature = in_array('temperature', $notifyTypes, true);
 
         if ($notifyTypesMode === 'custom') {
             $settings['discord_notify_types'] = array_values($notifyTypes);
             if ($hasTemperature) {
                 $settings['temp_alert_enabled'] = 'enabled';
                 $settings['temp_upper_limit'] = $request->filled('temp_upper_limit') ? (float)$request->temp_upper_limit : null;
                 $settings['temp_lower_limit'] = $request->filled('temp_lower_limit') ? (float)$request->temp_lower_limit : null;
             } else {
                 $settings['temp_alert_enabled'] = 'disabled';
                 $settings['temp_upper_limit'] = null;
                 $settings['temp_lower_limit'] = null;
             }
         } else {
             unset($settings['discord_notify_types']);
             $settings['temp_alert_enabled'] = 'inherit';
             $settings['temp_upper_limit'] = null;
             $settings['temp_lower_limit'] = null;
         }
 
         $machine->settings = $settings;
         $machine->save();
 
         return redirect()->route('admin.basic-settings.discord-notifications.index', ['tab' => 'machine'])
             ->with('success', __('Machine Discord notification settings updated successfully.'));
     }

    /**
     * 測試發送 Discord Webhook
     */
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'discord_webhook_url' => 'required|url|max:500',
        ]);

        $webhookUrl = $request->input('discord_webhook_url');
        
        try {
            $service = app(DiscordWebhookService::class);
            $success = $service->sendTestAlert($webhookUrl);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => __('Test notification sent successfully.'),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to send test notification. Please verify the Webhook URL.'),
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred: ') . $e->getMessage(),
            ], 500);
        }
    }
}
