<?php

namespace App\Http\Controllers\Admin\BasicSettings;

use App\Http\Controllers\Admin\AdminController;
use App\Models\System\PaymentConfig;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PaymentConfigController extends AdminController
{
    /**
     * 顯示金流配置列表
     */
    public function index(Request $request): View
    {
        $per_page = $request->input('per_page', 20);
        $configs = PaymentConfig::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->with(['company', 'creator'])
            ->latest()
            ->paginate($per_page)
            ->withQueryString();

        return view('admin.basic-settings.payment-configs.index', [
            'paymentConfigs' => $configs
        ]);
    }

    /**
     * 顯示新增頁面
     */
    public function create(): View
    {
        $companies = \App\Models\System\Company::select('id', 'name', 'code')->get();
        return view('admin.basic-settings.payment-configs.create', compact('companies'));
    }

    /**
     * 儲存金流配置
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'nullable|exists:companies,id',
            'settings' => 'required|array',
        ] + $this->ecpayInvoiceRules(), $this->ecpayInvoiceMessages());

        PaymentConfig::create([
            'name' => $request->name,
            'company_id' => $request->company_id,
            'settings' => $request->settings,
            'creator_id' => auth()->id(),
            'updater_id' => auth()->id(),
        ]);

        return redirect()->route('admin.basic-settings.payment-configs.index')
            ->with('success', __('Payment Configuration created successfully.'));
    }

    /**
     * 顯示編輯頁面
     */
    public function edit(PaymentConfig $paymentConfig): View
    {
        $companies = \App\Models\System\Company::select('id', 'name')->get();
        return view('admin.basic-settings.payment-configs.edit', compact('paymentConfig', 'companies'));
    }

    /**
     * 更新金流配置
     */
    public function update(Request $request, PaymentConfig $paymentConfig): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'nullable|exists:companies,id',
            'settings' => 'required|array',
        ] + $this->ecpayInvoiceRules(), $this->ecpayInvoiceMessages());

        $paymentConfig->update([
            'name' => $request->name,
            'company_id' => $request->company_id,
            'settings' => $request->settings,
            'updater_id' => auth()->id(),
        ]);

        return redirect()->route('admin.basic-settings.payment-configs.index')
            ->with('success', __('Payment Configuration updated successfully.'));
    }

    /**
     * 刪除金流配置
     */
    public function destroy(PaymentConfig $paymentConfig): RedirectResponse
    {
        $paymentConfig->delete();
        return redirect()->route('admin.basic-settings.payment-configs.index')
            ->with('success', __('Payment Configuration deleted successfully.'));
    }

    /**
     * 綠界電子發票群組驗證規則：整組選填，但只要填了任一欄，
     * store_id / hash_key / hash_iv / email 即全部必填（綠界開立發票需金鑰齊備，
     * 且 email 為「電話／信箱擇一」的唯一來源，缺一即無法開立）。
     * 依賴 ConvertEmptyStringsToNull middleware：空欄送出為 null，required_with 不會誤觸發。
     */
    private function ecpayInvoiceRules(): array
    {
        $g = 'settings.ecpay_invoice';
        return [
            "{$g}.store_id" => "nullable|string|max:20|required_with:{$g}.hash_key,{$g}.hash_iv,{$g}.email",
            "{$g}.hash_key" => "nullable|string|max:64|required_with:{$g}.store_id,{$g}.hash_iv,{$g}.email",
            "{$g}.hash_iv" => "nullable|string|max:64|required_with:{$g}.store_id,{$g}.hash_key,{$g}.email",
            "{$g}.email" => "nullable|email|max:100|required_with:{$g}.store_id,{$g}.hash_key,{$g}.hash_iv",
        ];
    }

    /** 綠界電子發票群組的驗證訊息（三語系）。 */
    private function ecpayInvoiceMessages(): array
    {
        return [
            'settings.ecpay_invoice.*.required_with' => __('ECPay e-invoice is optional, but once any field is filled, Store ID / Hash Key / Hash IV / Email are all required.'),
            'settings.ecpay_invoice.email.email' => __('Please enter a valid ECPay e-invoice notification email.'),
        ];
    }
}
