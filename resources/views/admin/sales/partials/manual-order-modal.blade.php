@php
// 貨道/商品搜尋下拉的 Preline 設定（沿用全站 searchable-select 樣式）
$manualSlotSelectConfig = [
    "placeholder" => __('Select slot / product'),
    "hasSearch" => true,
    "searchPlaceholder" => __('Search...'),
    "isHidePlaceholder" => false,
    "searchClasses" => "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
    "searchWrapperClasses" => "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
    "toggleClasses" => "hs-select-toggle luxury-select-toggle",
    "toggleTemplate" => '<button type="button" aria-expanded="false"><span class="me-2" data-icon></span><span class="text-slate-800 dark:text-slate-200" data-title></span><div class="ms-auto"><svg class="size-4 text-slate-400 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg></div></button>',
    "dropdownClasses" => "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[200] animate-luxury-in",
    "optionClasses" => "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
    "optionTemplate" => '<div class="flex items-center justify-between w-full"><span data-title></span><span class="hs-select-active-indicator hidden text-cyan-500"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></div>',
];
@endphp

{{-- 手動補單彈窗（機台斷線/漏報時人工補登銷售紀錄）— 僅系統管理員 --}}
<div x-data="manualOrderForm(@js($manualSlotSelectConfig))" x-cloak
     @open-manual-order.window="open()"
     @keydown.window.escape="show = false">
    <div class="fixed inset-0 z-[110] overflow-y-auto" x-show="show">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
             x-show="show"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="show = false"></div>

        {{-- Dialog --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl transform transition-all"
                 x-show="show"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-6 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="luxury-card rounded-3xl bg-white dark:bg-slate-900 shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">

                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-4 px-6 sm:px-8 pt-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 tracking-tight flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                                {{ __('Manual Order Entry') }}
                            </h3>
                            <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 mt-1 tracking-wide">
                                {{ __('For sales not reported due to disconnection. System administrators only.') }}
                            </p>
                        </div>
                        <button type="button" @click="show = false"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="px-6 sm:px-8 py-6 space-y-5 max-h-[68vh] overflow-y-auto">

                        {{-- 機台 + 發生時間 --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Machine') }} <span class="text-rose-500">*</span></label>
                                <x-searchable-select name="manual_machine_id" id="manual-machine"
                                    :placeholder="__('Select a machine')"
                                    x-model="machineId" @change="machineId = $event.target.value; onMachineChange()">
                                    @foreach($machines as $m)
                                    <option value="{{ $m->id }}" data-title="{{ $m->name }} ({{ $m->serial_no }})">{{ $m->name }} ({{ $m->serial_no }})</option>
                                    @endforeach
                                </x-searchable-select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Transaction Time') }}</label>
                                <input type="text" x-ref="occurredAt" x-model="occurredAt"
                                    placeholder="{{ __('Defaults to now if empty') }}"
                                    class="luxury-input w-full text-sm py-2.5 px-4 cursor-pointer">
                            </div>
                        </div>

                        {{-- 貨道/商品（搜尋式，依機台動態載入） --}}
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Select slot / product') }} <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div id="manual-slot-wrapper" class="relative">{{-- Rebuilt by Alpine --}}</div>
                                <div x-show="loadingSlots" class="absolute right-12 top-1/2 -translate-y-1/2 z-10">
                                    <x-luxury-spinner show="loadingSlots" />
                                </div>
                            </div>
                        </div>

                        {{-- 付款方式 + 價格 --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Payment Type') }} <span class="text-rose-500">*</span></label>
                                <x-searchable-select name="manual_payment_type" id="manual-payment"
                                    :placeholder="__('Select payment type')"
                                    x-model="paymentType" @change="paymentType = $event.target.value">
                                    @foreach($paymentTypes as $ptVal => $ptLabel)
                                    <option value="{{ $ptVal }}" data-title="{{ $ptLabel }}">{{ $ptLabel }}</option>
                                    @endforeach
                                </x-searchable-select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Price') }} <span class="text-rose-500">*</span></label>
                                <div class="flex items-center h-12 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden w-full group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                    <button type="button" @click="price = Math.max(0, (Number(price)||0) - 1)"
                                        class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                    </button>
                                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                    <div class="flex-1 min-w-0 flex items-center justify-center">
                                        <span class="text-slate-400 font-black mr-0.5">$</span>
                                        <input type="text" inputmode="decimal" x-model.number="price"
                                            class="w-full bg-transparent border-none text-center text-lg font-black text-slate-800 dark:text-white focus:ring-0 p-0">
                                    </div>
                                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                    <button type="button" @click="price = (Number(price)||0) + 1"
                                        class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- 數量（Luxury Counter +/-） --}}
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Qty') }}</label>
                            <div class="flex flex-col sm:flex-row items-center gap-6">
                                <div class="flex items-center h-12 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden flex-1 min-w-[200px] w-full group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                    <button type="button" @click="quantity > 1 ? quantity-- : 1"
                                        class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                    </button>
                                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                    <div class="flex-1 min-w-[60px]">
                                        <input type="text" x-model.number="quantity" readonly
                                            class="w-full bg-transparent border-none text-center text-lg font-black text-slate-800 dark:text-white focus:ring-0 p-0">
                                    </div>
                                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                    <button type="button" @click="quantity < 999 ? quantity++ : 999"
                                        class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                    </button>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <template x-for="val in [1, 2, 3, 5]" :key="val">
                                        <button type="button" @click="quantity = val"
                                            class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[11px] font-black transition-all shrink-0"
                                            :class="quantity == val ? 'bg-cyan-500 text-white border-cyan-500 shadow-lg shadow-cyan-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 hover:border-cyan-500/50'">
                                            <span x-text="val"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- 小計 --}}
                        <div class="flex items-center justify-end gap-2 pt-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Total') }}</span>
                            <span class="text-lg font-black text-slate-800 dark:text-white" x-text="`$${total}`"></span>
                        </div>

                        {{-- 扣庫存 --}}
                        <label class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 cursor-pointer">
                            <input type="checkbox" x-model="deductStock" class="mt-0.5 rounded border-slate-300 text-cyan-500 focus:ring-cyan-500">
                            <span>
                                <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('Deduct stock') }}</span>
                                <span class="block text-[11px] text-slate-400 mt-0.5">{{ __('Recommended when the machine physically dispensed but did not report, to reconcile cloud stock.') }}</span>
                            </span>
                        </label>

                        {{-- 開立電子發票（僅機台啟用電子發票時顯示） --}}
                        <div x-show="taxInvoiceEnabled" x-cloak>
                            <label class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 cursor-pointer">
                                <input type="checkbox" x-model="issueInvoice" class="mt-0.5 rounded border-slate-300 text-cyan-500 focus:ring-cyan-500">
                                <span class="flex-1">
                                    <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('Issue e-invoice') }}</span>
                                    <span class="block text-[11px] text-slate-400 mt-0.5">{{ __('Issued via ECPay in the background after submission.') }}</span>
                                </span>
                            </label>

                            <div x-show="issueInvoice" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 pl-1">
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Invoice Type') }}</label>
                                    <x-searchable-select name="manual_invoice_type" id="manual-invoice-type"
                                        :has-search="false" x-model="invoiceType" @change="invoiceType = $event.target.value">
                                        <option value="b2c" data-title="{{ __('B2C (member carrier)') }}">{{ __('B2C (member carrier)') }}</option>
                                        <option value="taxid" data-title="{{ __('Company Tax ID') }}">{{ __('Company Tax ID') }}</option>
                                        <option value="mobile" data-title="{{ __('Mobile barcode carrier') }}">{{ __('Mobile barcode carrier') }}</option>
                                        <option value="citizen" data-title="{{ __('Citizen certificate carrier') }}">{{ __('Citizen certificate carrier') }}</option>
                                        <option value="donation" data-title="{{ __('Donation') }}">{{ __('Donation') }}</option>
                                    </x-searchable-select>
                                </div>
                                <div class="space-y-2" x-show="invoiceType !== 'b2c'">
                                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1" x-text="invoiceValueLabel()"></label>
                                    <input type="text" x-model="invoiceValue" class="luxury-input w-full text-sm py-2.5 px-4">
                                </div>
                            </div>
                        </div>

                        {{-- 備註 --}}
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Remark') }}</label>
                            <textarea x-model="remark" rows="2" maxlength="1000" placeholder="{{ __('Enter remark (optional)') }}"
                                class="luxury-input w-full text-sm py-2.5 px-4 resize-none"></textarea>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-3 px-6 sm:px-8 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/40">
                        <button type="button" @click="show = false"
                            class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" @click="submit()" :disabled="submitting"
                            class="px-6 py-2.5 rounded-xl text-sm font-black text-white bg-cyan-500 hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100 flex items-center gap-2">
                            <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="submitting ? '{{ __('Submitting...') }}' : '{{ __('Create Order') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
