@extends('layouts.admin')

@section('content')
<script>
    function replenishmentManager() {
        return {
            showReplenishmentModal: false,
            showAutoModal: false,
            showCancelModal: false,
            showAssignModal: false,
            showStatusModal: false,
            fromId: '',
            availableProducts: [],
            allProducts: @json($products ?? []),
            isLoadingProducts: false,
            items: [{ product_id: '', slot_no: '', quantity: 1, current_stock: 0, max_stock: 0 }],
            loading: false,
            cancelOrderId: null,
            assignOrderId: null,
            assignUserId: '',
            statusTargetId: null,
            statusTargetValue: null,
            statusModalTitle: '',
            statusModalMessage: '',

            // Auto replenishment
            autoWarehouseId: '',
            autoMachineId: '',
            autoNote: '',
            autoSlots: [],
            autoHasWarning: false,
            autoInsufficient: {},
            autoWarehouseStocks: {},
            autoPreviewLoaded: false,
            autoLoading: false,

            // Details Panel
            showOrderDetails: false,
            detailsLoading: false,
            activeOrder: null,
            activeItems: [],

            // Machine Slots for create modal
            targetMachineId: '',
            machineSlots: [],
            isLoadingSlots: false,
            modalOpenCount: 0,

            init() {
                // 偵測 URL 參數自動開啟自動補貨單 Modal
                const params = new URLSearchParams(window.location.search);
                const autoMachineId = params.get('auto_machine_id');
                if (autoMachineId) {
                    this.$nextTick(() => {
                        this.autoMachineId = autoMachineId;
                        this.showAutoModal = true;
                        this.autoPreviewLoaded = false;
                        this.autoSlots = [];
                        this.autoNote = '';

                        // 同步 HSSelect UI
                        this.$nextTick(() => {
                            const select = document.querySelector('[name="auto_machine_id"]');
                            if (select && window.HSSelect) {
                                const inst = window.HSSelect.getInstance(select);
                                if (inst) inst.setValue(autoMachineId);
                            }
                        });
                    });
                    // 清除 URL 參數避免重複觸發
                    const url = new URL(window.location);
                    url.searchParams.delete('auto_machine_id');
                    window.history.replaceState({}, '', url);
                }
            },

            async openOrderDetails(id) {
                this.showOrderDetails = true;
                this.detailsLoading = true;
                this.activeOrder = null;
                this.activeItems = [];
                try {
                    const res = await fetch(`{{ url('/admin/warehouses/replenishments') }}/${id}/details`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) { this.activeOrder = data.order; this.activeItems = data.items; }
                } catch (e) { console.error(e); window.showToast?.('{{ __("Failed to load details") }}', 'error'); }
                finally { this.detailsLoading = false; }
            },

            openCreateModal() {
                // 遞增計數器，強制 x-for 重建 DOM 元素並觸發 x-init
                this.modalOpenCount++;
                this.showReplenishmentModal = true;
                this.$nextTick(() => { if (window.HSStaticMethods) window.HSStaticMethods.autoInit('select'); });
            },

            addItem() {
                this.items.push({ product_id: '', slot_no: '', quantity: 1, current_stock: 0, max_stock: 0 });
                const lastIdx = this.items.length - 1;
                this.$nextTick(() => {
                    this.updateProductDisplay(lastIdx);
                    this.updateSlotSelects(lastIdx);
                });
            },
            removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },

            async fetchStock() {
                this.availableProducts = [];
                if (!this.fromId) { this.updateProductDisplay(); return; }
                this.isLoadingProducts = true;
                try {
                    const res = await fetch('{{ route("admin.warehouses.ajax.stock") }}?warehouse_id=' + this.fromId);
                    const json = await res.json();
                    if (json.success) {
                        this.availableProducts = json.data;
                        this.updateProductDisplay();
                    }
                } catch (e) { console.error(e); }
                finally { this.isLoadingProducts = false; }
            },

            async fetchMachineSlots() {
                this.machineSlots = [];
                if (!this.targetMachineId) {
                    this.items.forEach((_, idx) => this.updateSlotSelects(idx));
                    return;
                }
                this.isLoadingSlots = true;
                try {
                    const res = await fetch(`/admin/machines/${this.targetMachineId}/slots-ajax`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.machineSlots = json.slots;
                        this.items.forEach((_, idx) => this.updateSlotSelects(idx));
                    }
                } catch (e) { console.error(e); }
                finally { this.isLoadingSlots = false; }
            },

            updateSlotSelects(index = null) {
                const indices = index !== null ? [index] : this.items.map((_, i) => i);

                indices.forEach(idx => {
                    const item = this.items[idx];
                    const wrapper = document.getElementById(`slot-select-wrapper-${idx}`);
                    if (!wrapper) return;

                    const oldSelect = wrapper.querySelector('select');
                    if (oldSelect && window.HSSelect?.getInstance(oldSelect)) {
                        try { window.HSSelect.getInstance(oldSelect).destroy(); } catch (e) { }
                    }
                    wrapper.innerHTML = '';

                    const selectEl = document.createElement('select');
                    selectEl.id = `slot-select-${idx}-${Date.now()}`;
                    selectEl.name = `items[${idx}][slot_no]`;
                    selectEl.required = true;
                    selectEl.className = 'hidden';

                    const placeholderOpt = document.createElement('option');
                    placeholderOpt.value = '';
                    placeholderOpt.textContent = '{{ __("Slot") }}';
                    selectEl.appendChild(placeholderOpt);

                    this.machineSlots.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.slot_no;
                        const stockInfo = ` [${s.stock || 0}/${s.max_stock || 0}]`;
                        opt.textContent = s.slot_no + (s.product ? ` (${s.product.name})` : '') + stockInfo;

                        // Enhanced style for HSSelect
                        opt.setAttribute('data-title', s.slot_no + (s.product ? ` (${s.product.name})` : ''));
                        const stockHtml = `<div class="flex items-baseline gap-0.5"><span class="text-xs font-black text-slate-800 dark:text-slate-100 tracking-tighter">${s.stock || 0}</span><span class="text-[9px] font-bold text-slate-400 opacity-50">/</span><span class="text-[10px] font-bold text-slate-500 opacity-80">${s.max_stock || 0}</span></div>`;
                        opt.setAttribute('data-description', stockHtml);

                        if (item.slot_no == s.slot_no) opt.selected = true;
                        selectEl.appendChild(opt);
                    });

                    selectEl.setAttribute('data-hs-select', JSON.stringify({
                        "placeholder": "{{ __('Slot') }}",
                        "toggleClasses": "hs-select-toggle luxury-select-toggle w-full text-left text-xs font-mono font-bold",
                        "toggleTemplate": "<button type=\"button\"><span class=\"text-slate-800 dark:text-slate-200\" data-title></span><div class=\"ms-auto\"><svg class=\"size-3 text-slate-400\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m6 9 6 6 6-6\"/></svg></div></button>",
                        "dropdownClasses": "hs-select-menu w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-2xl mt-2 z-[150] max-h-48 overflow-y-auto custom-scrollbar-thin",
                        "optionClasses": "hs-select-option py-2 px-3 text-xs text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 rounded-lg",
                        "optionTemplate": "<div class=\"flex items-center justify-between w-full\"><span class=\"truncate max-w-[140px]\" data-title></span><div data-description></div></div>",
                        "hasSearch": false,
                        "strategy": "fixed"
                    }));

                    wrapper.appendChild(selectEl);
                    selectEl.addEventListener('change', (e) => {
                        item.slot_no = e.target.value;
                        // Auto-select product based on slot
                        const slotData = this.machineSlots.find(s => s.slot_no == item.slot_no);
                        if (slotData) {
                            item.product_id = slotData.product_id;
                            item.current_stock = slotData.stock || 0;
                            item.max_stock = slotData.max_stock || 0;
                            // Update the product display for this row
                            this.updateProductDisplay(idx);
                        }
                    });
                    if (window.HSStaticMethods) window.HSStaticMethods.autoInit('select');
                });
            },

            updateProductDisplay(index = null) {
                const indices = index !== null ? [index] : this.items.map((_, i) => i);

                indices.forEach(idx => {
                    const item = this.items[idx];
                    const wrapper = document.getElementById(`product-select-wrapper-${idx}`);
                    if (!wrapper) return;

                    wrapper.innerHTML = '';

                    if (!item.product_id) {
                        wrapper.innerHTML = `
                            <div class="luxury-input w-full px-4 py-2.5 bg-slate-50/50 dark:bg-slate-900/50 text-slate-400 text-sm flex items-center">
                                <span class="opacity-50 italic">{{ __('Please Select Slot first') }}</span>
                            </div>
                        `;
                        return;
                    }

                    const productInWarehouse = this.availableProducts.find(p => p.id == item.product_id);
                    const productInfo = this.allProducts.find(p => p.id == item.product_id);
                    const productName = productInfo ? productInfo.name : (productInWarehouse ? productInWarehouse.name : 'Unknown');

                    // Priority: stock from selected warehouse, otherwise N/A or 0
                    const stock = productInWarehouse ? productInWarehouse.quantity : 0;
                    const stockText = this.fromId ? `{{ __('Stock') }}: ${stock}` : '{{ __('Select Warehouse') }}';

                    wrapper.innerHTML = `
                        <div class="luxury-input w-full px-6 py-2.5 bg-slate-50/50 dark:bg-slate-900/30 flex items-center justify-between border-dashed border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-8">
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">{{ __('Product Name') }}</span>
                                    <span class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate max-w-[180px]">${productName}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">{{ __('Machine Stock') }}</span>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-xl font-black text-slate-800 dark:text-white tracking-tighter leading-none">${item.current_stock}</span>
                                        <span class="text-xs font-black text-slate-400 opacity-30">/</span>
                                        <span class="text-[11px] font-bold text-slate-500 opacity-50">${item.max_stock}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end shrink-0">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">{{ __('Source') }}</span>
                                <span class="text-xs font-mono font-bold ${stock <= 0 ? 'text-rose-500' : 'text-cyan-500'} tracking-tighter">${stockText}</span>
                            </div>
                            <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id}">
                        </div>
                    `;
                });
            },

            async submitReplenishment() {
                const form = document.getElementById('replenishmentForm');
                const warehouseId = this.fromId;
                const machineId = form.querySelector('[name="machine_id"]')?.value;

                if (!warehouseId || warehouseId === ' ') {
                    window.showToast?.('{{ __("Please select source warehouse") }}', 'error'); return;
                }
                if (!machineId || machineId === ' ') {
                    window.showToast?.('{{ __("Please select target machine") }}', 'error'); return;
                }
                if (this.items.length === 0) {
                    window.showToast?.('{{ __("Please add at least one item") }}', 'error'); return;
                }

                for (let i = 0; i < this.items.length; i++) {
                    if (!this.items[i].product_id) {
                        window.showToast?.('{{ __("Please select product for item :num") }}'.replace(':num', i + 1), 'error'); return;
                    }
                    if (!this.items[i].quantity || this.items[i].quantity < 1) {
                        window.showToast?.('{{ __("Please enter valid quantity for item :num") }}'.replace(':num', i + 1), 'error'); return;
                    }
                }

                this.loading = true;
                form.submit();
            },

            // 自動補貨單預覽
            async loadAutoPreview() {
                if (!this.autoWarehouseId || this.autoWarehouseId === ' ' || !this.autoMachineId || this.autoMachineId === ' ') {
                    window.showToast?.('{{ __("Please select warehouse and machine") }}', 'error'); return;
                }
                this.autoLoading = true;
                try {
                    const res = await fetch('{{ route("admin.warehouses.replenishments.auto") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ warehouse_id: this.autoWarehouseId, machine_id: this.autoMachineId })
                    });
                    const data = await res.json();
                    if (data.success && data.preview) {
                        this.autoSlots = data.slots;
                        this.autoHasWarning = data.has_warning;
                        this.autoInsufficient = data.insufficient || {};
                        this.autoWarehouseStocks = data.warehouse_stocks || {};
                        this.autoPreviewLoaded = true;
                    } else {
                        window.showToast?.(data.message || '{{ __("Failed to load preview") }}', 'error');
                    }
                } catch (e) { console.error(e); window.showToast?.('{{ __("System Error") }}', 'error'); }
                finally { this.autoLoading = false; }
            },

            getActualFillable(index) {
                const slot = this.autoSlots[index];
                if (!slot) return 0;

                let available = this.autoWarehouseStocks[slot.product_id] || 0;
                for (let i = 0; i < index; i++) {
                    if (this.autoSlots[i].product_id === slot.product_id) {
                        available -= this.autoSlots[i].quantity;
                    }
                }
                return available;
            },

            removeAutoSlot(idx) {
                this.autoSlots.splice(idx, 1);
            },

            async submitAutoReplenishment() {
                if (!this.autoSlots || this.autoSlots.length === 0) {
                    window.showToast?.('{{ __("No items to replenish") }}', 'error'); return;
                }

                this.autoLoading = true;
                const items = this.autoSlots.map(s => ({
                    slot_no: s.slot_no,
                    product_id: s.product_id,
                    quantity: s.quantity,
                    current_stock: s.current_stock,
                    max_stock: s.max_stock,
                }));

                try {
                    const res = await fetch('{{ route("admin.warehouses.replenishments.auto") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({
                            warehouse_id: this.autoWarehouseId,
                            machine_id: this.autoMachineId,
                            note: this.autoNote,
                            items
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showAutoModal = false;
                        window.location.reload();
                    } else {
                        window.showToast?.(data.message || '{{ __("Creation failed") }}', 'error');
                    }
                } catch (e) { console.error(e); window.showToast?.('{{ __("System Error") }}', 'error'); }
                finally { this.autoLoading = false; }
            },

            // 狀態推進 (顯示 Modal)
            async advanceStatus(orderId, newStatus, currentAssigneeId = '') {
                this.statusTargetId = orderId;
                this.statusTargetValue = newStatus;
                this.assignUserId = currentAssigneeId || '';

                const labels = {
                    prepared: '{{ __("Confirm Prepare") }}',
                    delivering: '{{ __("Start Delivery") }}',
                    completed: '{{ __("Confirm Complete") }}'
                };
                const messages = {
                    prepared: '{{ __("Are you sure you want to confirm preparation? This will deduct stock from the warehouse.") }}',
                    delivering: '{{ __("Are you sure you want to start delivery?") }}',
                    completed: '{{ __("Are you sure you want to confirm completion? This will update the machine stock.") }}'
                };

                this.statusModalTitle = labels[newStatus];
                this.statusModalMessage = messages[newStatus];
                this.showStatusModal = true;

                this.$nextTick(() => {
                    const select = window.HSSelect.getInstance('#status-assignee-select');
                    if (select) {
                        select.setValue(currentAssigneeId || ' ');
                    }
                });
            },

            // 執行狀態更新
            async executeStatusUpdate() {
                this.loading = true;
                try {
                    const res = await fetch(`{{ url('/admin/warehouses/replenishments') }}/${this.statusTargetId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ 
                            status: this.statusTargetValue,
                            assigned_to: this.assignUserId || null
                        })
                    });
                    if (res.ok) {
                        this.showStatusModal = false;
                        window.location.reload();
                    } else {
                        const data = await res.json();
                        window.showToast?.(data.message || '{{ __("Operation failed") }}', 'error');
                    }
                } catch (e) {
                    console.error(e);
                    window.showToast?.('{{ __("System Error") }}', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // 取消
            confirmCancel(orderId) { this.cancelOrderId = orderId; this.showCancelModal = true; },
            async executeCancel() {
                this.loading = true;
                try {
                    const res = await fetch(`{{ url('/admin/warehouses/replenishments') }}/${this.cancelOrderId}/cancel`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    if (res.ok) {
                        this.showCancelModal = false;
                        window.location.reload();
                    } else {
                        const data = await res.json();
                        window.showToast?.(data.message, 'error');
                    }
                } catch (e) { console.error(e); window.showToast?.('{{ __("System Error") }}', 'error'); }
                finally { this.loading = false; }
            },

            // 指派
            openAssignModal(orderId) { 
                this.assignOrderId = orderId; 
                this.assignUserId = ''; 
                this.showAssignModal = true; 

                this.$nextTick(() => {
                    const select = window.HSSelect.getInstance('#assign-personnel-select');
                    if (select) {
                        select.setValue(' ');
                    }
                });
            },
            async executeAssign() {
                if (!this.assignUserId) { window.showToast?.('{{ __("Select Personnel") }}', 'error'); return; }
                this.loading = true;
                try {
                    const res = await fetch(`{{ url('/admin/warehouses/replenishments') }}/${this.assignOrderId}/assign`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ assigned_to: this.assignUserId })
                    });
                    if (res.ok) {
                        this.showAssignModal = false;
                        window.location.reload();
                    } else {
                        const data = await res.json();
                        window.showToast?.(data.message, 'error');
                    }
                } catch (e) { console.error(e); }
                finally { this.loading = false; }
            },

            async fetchTabData(url = null) {
                this.loading = true;
                const container = document.getElementById('replenishments-table-container');
                let targetUrl = url;

                if (!targetUrl) {
                    const form = document.getElementById('replenishment-filter-form');
                    const formData = new FormData(form);
                    const params = new URLSearchParams(formData);
                    targetUrl = `${form.action}?${params.toString()}`;
                } else {
                    try {
                        const urlObj = new URL(targetUrl, window.location.origin);
                        const perPage = urlObj.searchParams.get('per_page');
                        if (perPage) {
                            const hiddenInput = document.querySelector('#replenishment-filter-form input[name="per_page"]');
                            if (hiddenInput) hiddenInput.value = perPage;
                        }
                    } catch (e) {
                        console.error('URL sync error:', e);
                    }
                }

                try {
                    const response = await fetch(targetUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success && container) {
                        container.outerHTML = data.html;
                        this.$nextTick(() => {
                            const newContainer = document.getElementById('replenishments-table-container');
                            if (newContainer && window.Alpine) Alpine.initTree(newContainer);
                            if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
                        });
                        window.history.pushState({}, '', targetUrl);
                    }
                } catch (e) {
                    console.error(e);
                    window.showToast?.('{{ __("Loading failed") }}', 'error');
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>

<div class="space-y-4 pb-10" x-data="replenishmentManager()" @open-details.window="openOrderDetails($event.detail.id)"
    @ajax:filter.window="fetchTabData()"
    @ajax:navigate:replenishments.window.prevent="fetchTabData($event.detail.url)">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Machine Replenishment') }}</h1>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">{{ __('Create and manage replenishment orders from warehouse to machines') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" @click="
                autoWarehouseId = ''; 
                autoMachineId = ''; 
                autoNote = ''; 
                autoPreviewLoaded = false; 
                autoSlots = []; 
                showAutoModal = true;
                $nextTick(() => {
                    document.querySelectorAll('[name^=\'auto_\']').forEach(s => {
                        if (window.HSSelect) {
                            const inst = window.HSSelect.getInstance(s);
                            if (inst) inst.setValue(' ');
                        }
                    });
                });
            " class="btn-luxury-ghost border-cyan-500/30 text-cyan-600 hover:bg-cyan-50 dark:hover:bg-cyan-500/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
                <span>{{ __('Auto Replenishment') }}</span>
            </button>
            <button type="button" @click="openCreateModal()" class="btn-luxury-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>{{ __('New Replenishment') }}</span>
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative min-h-[400px]">
        <!-- Loading Overlay -->
        <div x-show="loading" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 z-20 bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] flex flex-col items-center justify-center rounded-3xl"
            x-cloak>
            <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin"></div>
                <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin" style="animation-duration: 3s; direction: reverse;"></div>
                <div class="relative w-8 h-8 flex items-center justify-center">
                    <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
            </div>
            <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">{{ __('Loading Data') }}...</p>
        </div>
        <form id="replenishment-filter-form" action="{{ route('admin.warehouses.replenishments') }}" method="GET" class="flex flex-wrap items-center gap-4 mb-8">
            <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
            
            <div class="relative group w-full sm:w-72">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                    <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" name="search_order" value="{{ request('search_order') }}"
                    @keydown.enter.prevent="fetchTabData()"
                    class="py-2.5 pl-12 pr-6 block w-full luxury-input" placeholder="{{ __('Search order number...') }}">
            </div>

            <div class="w-full sm:w-44">
                <x-searchable-select 
                    name="status" 
                    :selected="request('status')"
                    :placeholder="__('All Statuses')"
                    onchange="this.dispatchEvent(new CustomEvent('ajax:filter', { bubbles: true }))"
                >
                    <option value="" data-title="{{ __('All Statuses') }}">{{ __('All Statuses') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }} data-title="{{ __('Pending') }}">{{ __('Pending') }}</option>
                    <option value="prepared" {{ request('status') === 'prepared' ? 'selected' : '' }} data-title="{{ __('Prepared') }}">{{ __('Prepared') }}</option>
                    <option value="delivering" {{ request('status') === 'delivering' ? 'selected' : '' }} data-title="{{ __('Delivering') }}">{{ __('Delivering') }}</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }} data-title="{{ __('Completed') }}">{{ __('Completed') }}</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }} data-title="{{ __('Cancelled') }}">{{ __('Cancelled') }}</option>
                </x-searchable-select>
            </div>

            @if(auth()->user()->isSystemAdmin())
            <div class="w-full sm:w-72">
                <x-searchable-select
                    name="company_id"
                    :options="$companies"
                    :selected="request('company_id')"
                    :placeholder="__('All Companies')"
                    onchange="this.dispatchEvent(new CustomEvent('ajax:filter', { bubbles: true }))"
                />
            </div>
            @endif

            {{-- 開始時間 --}}
            <div class="relative group w-full sm:w-56 sm:flex-none" 
                 x-data="{ fp: null }" 
                 x-init="fp = flatpickr($refs.startDate, { 
                    dateFormat: 'Y-m-d H:i',
                    enableTime: true,
                    time_24hr: true,
                    disableMobile: true,
                    defaultHour: 0,
                    defaultMinute: 0,
                    locale: 'zh_tw',
                    position: 'auto right',
                    defaultDate: '{{ request('start_date', $defaultStart ?? '') }}'
                 })">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </span>
                <input type="text" name="start_date" x-ref="startDate" 
                    value="{{ request('start_date', $defaultStart ?? '') }}"
                    placeholder="{{ __('Start Time') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer">
                <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none">{{ __('Start Time') }}</div>
            </div>

            {{-- 結束時間 --}}
            <div class="relative group w-full sm:w-56 sm:flex-none" 
                 x-data="{ fp: null }" 
                 x-init="fp = flatpickr($refs.endDate, { 
                    dateFormat: 'Y-m-d H:i',
                    enableTime: true,
                    time_24hr: true,
                    disableMobile: true,
                    defaultHour: 23,
                    defaultMinute: 59,
                    locale: 'zh_tw',
                    position: 'auto right',
                    defaultDate: '{{ request('end_date', $defaultEnd ?? '') }}'
                 })">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </span>
                <input type="text" name="end_date" x-ref="endDate" 
                    value="{{ request('end_date', $defaultEnd ?? '') }}"
                    placeholder="{{ __('End Time') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer">
                <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none">{{ __('End Time') }}</div>
            </div>

            <div class="flex items-center gap-2 ml-auto sm:ml-0">
                <button type="button" @click="fetchTabData()" class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 group transition-all active:scale-95" title="{{ __('Search') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                <button type="button" 
                    @click="
                        $el.closest('form').querySelectorAll('input').forEach(i => {
                            if (i.name === 'start_date' && i._flatpickr) i._flatpickr.setDate('{{ $defaultStart }}');
                            else if (i.name === 'end_date' && i._flatpickr) i._flatpickr.setDate('{{ $defaultEnd }}');
                            else if (i._flatpickr) i._flatpickr.clear();
                            else if (i.name !== 'per_page') i.value = '';
                        });
                        $el.closest('form').querySelectorAll('select').forEach(s => {
                            s.value = ' ';
                            const instance = window.HSSelect?.getInstance(s);
                            if (instance) instance.setValue(' ');
                        });
                        fetchTabData();
                    "
                    class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 group transition-all active:scale-95" title="{{ __('Reset') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </form>
        <div class="flex items-center gap-2 px-1 mb-6 -mt-4">
            <div class="w-1.5 h-1.5 rounded-full bg-cyan-500/50"></div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                {{ __('Showing last 6 months by default when no date range is selected') }}
            </span>
        </div>
        @include('admin.warehouses.partials.table-replenishments')
    </div>

    @include('admin.warehouses.partials.modal-replenishment-create')
    @include('admin.warehouses.partials.modal-replenishment-auto')
    @include('admin.warehouses.partials.slideover-replenishment-details')
    @include('admin.warehouses.partials.modal-replenishment-cancel')
    @include('admin.warehouses.partials.modal-replenishment-assign')
    @include('admin.warehouses.partials.modal-replenishment-status')
</div>
@endsection
