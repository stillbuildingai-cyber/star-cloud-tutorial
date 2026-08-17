<?php

namespace App\Http\Controllers\Admin\BasicSettings;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Machine\MachineModel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MachineModelController extends AdminController
{
    /**
     * 顯示機台型號列表 (重新導向至機台設定的標籤頁)
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('admin.basic-settings.machines.index', ['tab' => 'models']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'temp_upper_limit' => 'nullable|integer|min:-50|max:100',
            'temp_lower_limit' => 'nullable|integer|min:-50|max:100',
        ]);

        $settings = [
            'temp_upper_limit' => $request->filled('temp_upper_limit') ? (int)$request->temp_upper_limit : null,
            'temp_lower_limit' => $request->filled('temp_lower_limit') ? (int)$request->temp_lower_limit : null,
        ];

        MachineModel::create([
            'name' => $validated['name'],
            'settings' => $settings,
            'company_id' => auth()->user()->company_id,
            'creator_id' => auth()->id(),
            'updater_id' => auth()->id(),
        ]);

        return redirect()->route('admin.basic-settings.machines.index', ['tab' => 'models'])
            ->with('success', __('Machine model created successfully.'));
    }

    /**
     * 顯示編輯頁面 (與 index 共用 Modal 則不需此方法，但 resource 路由建議保留或調整)
     */
    public function edit(MachineModel $machine_model): View
    {
        // 若採用 index Modal 編輯，此處可回傳 JSON 或維持 Blade
        return view('admin.basic-settings.machine-models.edit', compact('machine_model'));
    }

    public function update(Request $request, MachineModel $machine_model): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'temp_upper_limit' => 'nullable|integer|min:-50|max:100',
            'temp_lower_limit' => 'nullable|integer|min:-50|max:100',
        ]);

        $settings = [
            'temp_upper_limit' => $request->filled('temp_upper_limit') ? (int)$request->temp_upper_limit : null,
            'temp_lower_limit' => $request->filled('temp_lower_limit') ? (int)$request->temp_lower_limit : null,
        ];

        $machine_model->update([
            'name' => $validated['name'],
            'settings' => $settings,
            'updater_id' => auth()->id(),
        ]);

        return redirect()->route('admin.basic-settings.machines.index', ['tab' => 'models'])
            ->with('success', __('Machine model updated successfully.'));
    }

    public function destroy(MachineModel $machine_model): RedirectResponse
    {
        if ($machine_model->machines()->count() > 0) {
            return redirect()->back()->with('error', __('Cannot delete model that is currently in use by machines.'));
        }

        $machine_model->delete();

        return redirect()->route('admin.basic-settings.machines.index', ['tab' => 'models'])
            ->with('success', __('Machine model deleted successfully.'));
    }
}
