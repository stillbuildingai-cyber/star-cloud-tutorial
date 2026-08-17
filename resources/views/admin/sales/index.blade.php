@extends('layouts.admin')

@section('content')
<div class="space-y-2 pb-20" x-data="salesCenter()"
    @ajax:navigate.window.prevent="fetchTabData(activeTab, $event.detail.url)"
    @manual-order-created.window="switchTab('orders'); fetchTabData('orders')">

    {{-- Page Header --}}
    <x-page-header :title="$title" :subtitle="$description" />

    {{-- Tab Navigation --}}
    <x-tab-nav model="activeTab">
        <x-tab-nav-item value="orders" :label="__('Transaction Orders')" model="activeTab" />
        <x-tab-nav-item value="invoices" :label="__('Electronic Invoices')" model="activeTab" />
        <x-tab-nav-item value="dispense" :label="__('Dispense Records')" model="activeTab" />
    </x-tab-nav>

    {{-- Tab Contents --}}
    <div class="mt-6">
        <div class="relative min-h-[400px]">
            {{-- Orders Tab --}}
            <div x-show="activeTab === 'orders'" class="relative"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0">
                <div class="luxury-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 lg:p-8 relative overflow-hidden">
                    <x-luxury-spinner show="tabLoading === 'orders'" />
                    <div id="tab-orders-container" :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': tabLoading === 'orders' }">
                        @include('admin.sales.partials.tab-orders')
                    </div>
                </div>
            </div>

            {{-- Invoices Tab --}}
            <div x-show="activeTab === 'invoices'" class="relative"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                <div class="luxury-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 lg:p-8 relative overflow-hidden">
                    <x-luxury-spinner show="tabLoading === 'invoices'" />
                    <div id="tab-invoices-container" :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': tabLoading === 'invoices' }">
                        @include('admin.sales.partials.tab-invoices')
                    </div>
                </div>
            </div>

            {{-- Dispense Records Tab --}}
            <div x-show="activeTab === 'dispense'" class="relative"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                <div class="luxury-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 lg:p-8 relative overflow-hidden">
                    <x-luxury-spinner show="tabLoading === 'dispense'" />
                    <div id="tab-dispense-container" :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': tabLoading === 'dispense' }">
                        @include('admin.sales.partials.tab-dispense')
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Slide-over Panel (Order Details) --}}
    <div 
        class="fixed inset-0 z-[100] overflow-hidden" 
        x-show="showPanel"
        x-cloak
        @keydown.window.escape="showPanel = false"
    >
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
             x-show="showPanel"
             x-transition:enter="ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showPanel = false"
        ></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div 
                class="pointer-events-auto w-screen max-w-2xl transform transition ease-in-out duration-500 sm:duration-700"
                x-show="showPanel"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-100 dark:border-slate-800">
                    <div id="order-detail-content">
                        {{-- AJAX injected content --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 發票作廢 / 補開 確認框（自製 UI，取代瀏覽器原生 confirm） --}}
    <x-confirm-modal alpineVar="showVoidModal" confirmAction="submitInvoiceAction()" :title="__('Void Invoice')"
        :message="__('Void this invoice?')" :confirmText="__('Void Invoice')" :cancelText="__('Cancel')"
        iconType="danger" confirmColor="rose">
        <div>
            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ __('Remark') }}</label>
            <textarea x-model="voidRemark" rows="2" maxlength="1000"
                placeholder="{{ __('Enter remark (optional)') }}"
                class="luxury-input w-full text-sm py-2.5 px-4 resize-none"></textarea>
        </div>
    </x-confirm-modal>
    <x-confirm-modal alpineVar="showReissueModal" confirmAction="submitInvoiceAction()" :title="__('Re-issue')"
        :message="__('Re-issue this invoice?')" :confirmText="__('Re-issue')" :cancelText="__('Cancel')"
        iconType="info" confirmColor="sky" />

    {{-- 手動補單彈窗：僅系統管理員可見 / 使用（後端再次把關） --}}
    @if(auth()->user()->isSystemAdmin())
        @include('admin.sales.partials.manual-order-modal')
    @endif
</div>
@endsection

@section('scripts')
<script>
function salesCenter() {
    return {
        showPanel: false,
        activeTab: '{{ $tab }}',
        tabLoading: null,
        showVoidModal: false,
        showReissueModal: false,
        pendingInvoiceForm: '',
        voidRemark: '',

        confirmVoidInvoice(formId) {
            this.pendingInvoiceForm = formId;
            this.voidRemark = '';
            this.showVoidModal = true;
        },

        confirmReissueInvoice(formId) {
            this.pendingInvoiceForm = formId;
            this.showReissueModal = true;
        },

        submitInvoiceAction() {
            const form = this.pendingInvoiceForm ? document.getElementById(this.pendingInvoiceForm) : null;
            if (form) {
                // 作廢時附帶管理者備註（注入隱藏 input，後端寫入 invoice.remark）
                if (this.showVoidModal) {
                    let input = form.querySelector('input[name="remark"]');
                    if (!input) {
                        input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'remark';
                        form.appendChild(input);
                    }
                    input.value = this.voidRemark || '';
                }
                form.submit();
            }
            this.showVoidModal = false;
            this.showReissueModal = false;
        },

        init() {
            const params = new URLSearchParams(window.location.search);
            const urlTab = params.get('tab');
            if (urlTab && ['orders', 'invoices', 'dispense'].includes(urlTab)) {
                this.activeTab = urlTab;
            }

            // 監聽頁籤切換以更新網址列 (不觸發重整)
            this.$watch('activeTab', (newTab) => {
                const url = new URL(window.location.origin + window.location.pathname);
                url.searchParams.set('tab', newTab);
                window.history.pushState({}, '', url);
            });
        },

        switchTab(tab, url = null) {
            if (this.activeTab === tab && !url) return;
            this.activeTab = tab;
            
            // 如果有提供 URL (例如點擊跳轉)，則使用 AJAX 載入新內容
            if (url) {
                this.fetchTabData(tab, url);
                return;
            }
        },

        async fetchTabData(tab, url = null) {
            if (this.tabLoading === tab) return;
            this.tabLoading = tab;
            const container = document.getElementById('tab-' + tab + '-container');

            if (!url) {
                // 從表單建構 URL
                let params = new URLSearchParams();
                params.set('tab', tab);

                if (container) {
                    const form = container.querySelector('form');
                    if (form) {
                        const formData = new FormData(form);
                        formData.forEach((value, key) => {
                            if (value && value.trim() !== '') params.append(key, value);
                        });
                    }
                }
                url = `${window.location.pathname}?${params.toString()}`;
            }

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success && data.html) {
                    if (container) container.innerHTML = data.html;
                    
                    // 更新網址列但不重整頁面
                    window.history.pushState({}, '', url);
                    
                    // 重新初始化 Preline 組件
                    this.$nextTick(() => {
                        if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
                    });
                }
            } catch (err) {
                console.error('Failed to fetch tab data:', err);
            } finally {
                this.tabLoading = null;
            }
        },

        async generatePickupCode(orderId) {
            if (!confirm('{{ __('Generate pickup code? (Valid for pickup within 7 days)') }}')) return;
            try {
                const res = await fetch(`/admin/sales/orders/${orderId}/pickup-code`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                const data = await res.json();
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || '', type: data.success ? 'success' : 'error' } }));
                if (data.success) this.fetchTabData('orders');
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Operation failed') }}', type: 'error' } }));
            }
        },
        openDetail(orderId) {
            this.showPanel = true;
            document.getElementById('order-detail-content').innerHTML = `<div class="flex items-center justify-center h-full py-20"><x-luxury-spinner show="true" /></div>`;
            
            fetch(`/admin/sales/${orderId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('order-detail-content').innerHTML = data.html;
                }
            })
            .catch(err => {
                console.error('Failed to load order detail:', err);
                this.showPanel = false;
            });
        },

        exportData(type) {
            const container = document.getElementById('tab-' + this.activeTab + '-container');
            let params = new URLSearchParams();
            params.set('tab', this.activeTab);
            params.set('export', type);

            if (container) {
                const form = container.querySelector('form');
                if (form) {
                    const formData = new FormData(form);
                    formData.forEach((value, key) => {
                        if (value && value.trim() !== '') {
                            if (key !== 'tab' && key !== 'export') {
                                params.append(key, value);
                            }
                        }
                    });
                }
            }

            // 使用隱藏的 iframe 進行下載，避免觸發主視窗的 beforeunload 事件，
            // 從而防止全域的頂部進度條 (top-loading-bar) 被觸發並卡在 95% 的位置。
            let iframe = document.getElementById('export-download-iframe');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'export-download-iframe';
                iframe.style.display = 'none';
                document.body.appendChild(iframe);
            }
            iframe.src = `${window.location.pathname}?${params.toString()}`;

            // 顯示成功提示訊息，與商品報表保持一致
            if (type === 'csv') {
                window.showToast?.('CSV 報表導出成功', 'success');
            } else if (type === 'excel') {
                window.showToast?.('Excel 報表導出成功', 'success');
            }
        },

        copyTableData() {
            const container = document.getElementById('tab-' + this.activeTab + '-container');
            if (!container) return;
            const table = container.querySelector('table');
            if (!table) return;

            let headers = [];
            table.querySelectorAll('thead th').forEach((th, idx, arr) => {
                if (idx < arr.length - 1) {
                    headers.push(th.innerText.trim().replace(/\s+/g, ' '));
                }
            });

            let rows = [headers.join('\t')];
            table.querySelectorAll('tbody tr').forEach(tr => {
                if (tr.querySelector('td.colspan') || tr.querySelector('td[colspan]')) return;
                let cells = [];
                tr.querySelectorAll('td').forEach((td, idx, arr) => {
                    if (idx < arr.length - 1) {
                        let text = td.innerText.trim().replace(/\r?\n/g, ' ').replace(/\s+/g, ' ');
                        cells.push(text);
                    }
                });
                if (cells.length > 0) {
                    rows.push(cells.join('\t'));
                }
            });

            navigator.clipboard.writeText(rows.join('\n')).then(() => {
                window.showToast?.('已成功將明細複製至剪貼簿', 'success');
            }).catch(err => {
                console.error('Clipboard copy failed:', err);
                window.showToast?.('複製失敗，瀏覽器權限可能不足', 'error');
            });
        },

        printTable() {
            const container = document.getElementById('tab-' + this.activeTab + '-container');
            if (!container) return;
            const table = container.querySelector('table');
            if (!table) return;

            const clonedTable = table.cloneNode(true);
            
            // 移除所有 SVG 圖標，防止列印或 PDF 輸出時因為沒有載入 Tailwind 樣式而顯示巨大黑色區塊
            clonedTable.querySelectorAll('svg').forEach(svg => svg.remove());

            clonedTable.querySelectorAll('tr').forEach(tr => {
                const cells = tr.querySelectorAll('th, td');
                if (cells.length > 0) {
                    cells[cells.length - 1].remove();
                }
            });

            let tabTitle = '';
            if (this.activeTab === 'orders') tabTitle = '交易紀錄';
            else if (this.activeTab === 'invoices') tabTitle = '電子發票';
            else if (this.activeTab === 'dispense') tabTitle = '遠端出貨紀錄';

            const printWindow = window.open('', '_blank');
            let html = `<html><head><title>${tabTitle}</title>`;
            html += `<style>
                body { font-family: system-ui, -apple-system, sans-serif; padding: 30px; color: #1e293b; }
                h1 { font-size: 24px; font-weight: 800; margin-bottom: 5px; }
                .subtitle { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 25px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
                th, td { border: 1px solid #cbd5e1; padding: 10px 12px; text-align: left; }
                th { background-color: #f1f5f9; font-weight: bold; color: #334155; }
                @media print {
                    body { padding: 0; }
                }
            </style></head><body>`;
            html += `<h1>${tabTitle}</h1>`;
            html += `<div class="subtitle">列印時間：${new Date().toLocaleString()}</div>`;
            html += clonedTable.outerHTML;
            html += `</body></html>`;

            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
        }
    }
}

function manualOrderForm(slotSelectConfig) {
    return {
        slotSelectConfig: slotSelectConfig,
        show: false,
        machineId: '',
        taxInvoiceEnabled: false,
        slots: [],
        loadingSlots: false,
        selectedSlot: '',
        quantity: 1,
        price: 0,
        paymentType: '',
        occurredAt: '',
        deductStock: true,
        issueInvoice: false,
        invoiceType: 'b2c',
        invoiceValue: '',
        remark: '',
        submitting: false,
        _fp: null,

        open() {
            this.resetForm();
            this.show = true;
            this.$nextTick(() => {
                this.initPicker();
                // 重置/初始化 Preline 搜尋下拉的顯示值
                this.syncSelect('manual-machine', ' ');
                this.syncSelect('manual-payment', ' ');
                this.syncSelect('manual-invoice-type', 'b2c');
                if (window.HSStaticMethods?.autoInit) window.HSStaticMethods.autoInit();
                this.updateSlotSelect();
            });
        },

        resetForm() {
            this.machineId = '';
            this.taxInvoiceEnabled = false;
            this.slots = [];
            this.selectedSlot = '';
            this.quantity = 1;
            this.price = 0;
            this.paymentType = '';
            this.occurredAt = '';
            this.deductStock = true;
            this.issueInvoice = false;
            this.invoiceType = 'b2c';
            this.invoiceValue = '';
            this.remark = '';
        },

        initPicker() {
            if (this._fp) { this._fp.destroy(); this._fp = null; }
            if (window.flatpickr && this.$refs.occurredAt) {
                this._fp = flatpickr(this.$refs.occurredAt, {
                    dateFormat: 'Y-m-d H:i', enableTime: true, time_24hr: true, disableMobile: true,
                    locale: '{{ app()->getLocale() == 'zh_TW' ? 'zh_tw' : 'en' }}',
                });
            }
        },

        async onMachineChange() {
            this.slots = [];
            this.selectedSlot = '';
            this.price = 0;
            this.taxInvoiceEnabled = false;
            this.issueInvoice = false;
            this.updateSlotSelect();
            if (!this.machineId) return;
            this.loadingSlots = true;
            try {
                const res = await fetch(`{{ route('admin.sales.manual.machine-slots') }}?machine_id=${this.machineId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.slots = data.slots || [];
                    this.taxInvoiceEnabled = !!data.tax_invoice_enabled;
                }
            } catch (e) {
                console.error('load slots failed', e);
                window.showToast?.('{{ __('Failed to load slots') }}', 'error');
            } finally {
                this.loadingSlots = false;
                this.updateSlotSelect();
            }
        },

        // 依 slots 重建貨道/商品搜尋下拉（沿用全站 Preline searchable-select 樣式）
        updateSlotSelect() {
            this.$nextTick(() => {
                const wrapper = document.getElementById('manual-slot-wrapper');
                if (!wrapper) return;

                wrapper.querySelectorAll('select').forEach(s => {
                    try { window.HSSelect?.getInstance(s)?.destroy(); } catch (e) {}
                });
                wrapper.innerHTML = '';

                if (!this.machineId) {
                    const dummy = document.createElement('div');
                    dummy.className = 'luxury-input opacity-50 cursor-not-allowed flex items-center justify-between';
                    dummy.innerHTML = `<span class="text-slate-400">{{ __('Select a machine to choose products') }}</span><svg class="size-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>`;
                    wrapper.appendChild(dummy);
                    return;
                }

                const selectEl = document.createElement('select');
                selectEl.id = 'manual-slot-' + Date.now();
                selectEl.className = 'hidden';

                const cleanConfig = JSON.parse(JSON.stringify(this.slotSelectConfig), (key, value) => {
                    return typeof value === 'string' ? value.replace(/\r?\n|\r/g, ' ').trim() : value;
                });
                selectEl.setAttribute('data-hs-select', JSON.stringify(cleanConfig));

                const ph = document.createElement('option');
                ph.value = '';
                ph.textContent = "{{ __('Select slot / product') }}";
                ph.setAttribute('data-title', "{{ __('Select slot / product') }}");
                selectEl.appendChild(ph);

                this.slots.forEach(slot => {
                    const opt = document.createElement('option');
                    opt.value = slot.slot_no;
                    const text = `${slot.slot_no} · ${slot.product_name} ({{ __('Stock') }}: ${slot.stock})`;
                    opt.textContent = text;
                    opt.setAttribute('data-title', text);
                    if (String(this.selectedSlot) === String(slot.slot_no)) opt.selected = true;
                    selectEl.appendChild(opt);
                });

                wrapper.appendChild(selectEl);
                selectEl.addEventListener('change', e => this.onSlotPicked(e.target.value));

                if (window.HSStaticMethods?.autoInit) window.HSStaticMethods.autoInit(['select']);
            });
        },

        onSlotPicked(slotNo) {
            this.selectedSlot = slotNo;
            const slot = this.slots.find(s => String(s.slot_no) === String(slotNo));
            this.price = slot ? Number(slot.price) || 0 : 0;
        },

        // 同步 Preline 下拉顯示值（沿用取貨碼模式）
        syncSelect(id, value) {
            const el = document.getElementById(id);
            if (!el) return;
            const v = (value !== undefined && value !== null && value.toString().trim() !== '') ? value.toString() : ' ';
            el.value = v;
            try { window.HSSelect?.getInstance(el)?.setValue(v); } catch (e) {}
        },

        get total() {
            return Math.round((Number(this.price) || 0) * (Number(this.quantity) || 0));
        },

        invoiceValueLabel() {
            return {
                taxid: '{{ __('Company Tax ID') }}',
                mobile: '{{ __('Mobile barcode carrier') }}',
                citizen: '{{ __('Citizen certificate carrier') }}',
                donation: '{{ __('Donation love code') }}',
            }[this.invoiceType] || '';
        },

        validate() {
            if (!this.machineId) { window.showToast?.('{{ __('Select a machine') }}', 'error'); return false; }
            if (!this.selectedSlot) { window.showToast?.('{{ __('Select slot / product') }}', 'error'); return false; }
            if (!this.paymentType) { window.showToast?.('{{ __('Select payment type') }}', 'error'); return false; }
            if (!(Number(this.quantity) > 0)) { return false; }
            return true;
        },

        async submit() {
            if (this.submitting || !this.validate()) return;
            const slot = this.slots.find(s => String(s.slot_no) === String(this.selectedSlot));
            if (!slot) { window.showToast?.('{{ __('Select slot / product') }}', 'error'); return; }
            this.submitting = true;
            try {
                const payload = {
                    machine_id: this.machineId,
                    payment_type: this.paymentType,
                    occurred_at: this.occurredAt || null,
                    deduct_stock: this.deductStock,
                    issue_invoice: this.taxInvoiceEnabled && this.issueInvoice,
                    invoice_type: this.invoiceType,
                    invoice_value: this.invoiceValue,
                    remark: this.remark,
                    items: [{
                        product_id: slot.product_id,
                        slot_no: slot.slot_no,
                        price: this.price,
                        quantity: this.quantity,
                    }],
                };
                const res = await fetch('{{ route('admin.sales.manual.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    window.showToast?.(data.message || 'OK', 'success');
                    this.show = false;
                    window.dispatchEvent(new CustomEvent('manual-order-created'));
                } else {
                    window.showToast?.(data.message || '{{ __('Failed to create manual order') }}', 'error');
                }
            } catch (e) {
                console.error('submit manual order failed', e);
                window.showToast?.('{{ __('Failed to create manual order') }}', 'error');
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
@endsection
