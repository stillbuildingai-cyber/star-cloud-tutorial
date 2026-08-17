@extends('layouts.admin')

@section('content')
<script>
    function transferManager() {
        return {
            showTransferModal: false,
            transferType: 'warehouse_to_warehouse',
            fromId: '',
            availableProducts: [],
            allProducts: @json($products ?? []),
            isLoadingProducts: false,
            items: [{ product_id: '', quantity: 1 }],
            loading: false,

            // Details Panel State
            showOrderDetails: false,
            activeOrder: null,
            activeItems: [],
            detailsLoading: false,
            
            // Delete State
            showDeleteModal: false,
            deleteUrl: '',
            
            // Confirm State
            showConfirmModal: false,
            pendingTransferUrl: '',
            
            openCreateModal() {
                this.items = [{ product_id: '', quantity: 1 }];
                this.showTransferModal = true;
                this.$nextTick(() => {
                    if (window.HSStaticMethods) window.HSStaticMethods.autoInit('select');
                });
            },
            
            async openOrderDetails(id) {
                console.log('openOrderDetails triggered for ID:', id);
                this.showOrderDetails = true;
                this.detailsLoading = true;
                this.activeOrder = null;
                this.activeItems = [];

                try {
                    const response = await fetch(`/admin/warehouses/transfers/${id}/details`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.activeOrder = data.order;
                        this.activeItems = data.items;
                    } else {
                        throw new Error(data.message || 'Failed to load details');
                    }
                } catch (e) {
                    console.error('openOrderDetails error:', e);
                    window.showToast?.('{{ __("Failed to load details") }}', 'error');
                } finally {
                    this.detailsLoading = false;
                }
            },

            confirmTransfer(url) {
                this.confirmUrl = url;
                this.showConfirmModal = true;
            },

            confirmDelete(url) {
                this.deleteUrl = url;
                this.showDeleteModal = true;
            },

            async fetchTabData(url = null) {
                this.loading = true;
                const container = document.getElementById('transfers-table-container');
                let targetUrl = url;

                if (!targetUrl) {
                    const form = document.getElementById('transfer-filter-form');
                    const formData = new FormData(form);
                    const params = new URLSearchParams(formData);
                    targetUrl = `${form.action}?${params.toString()}`;
                } else {
                    try {
                        const urlObj = new URL(targetUrl, window.location.origin);
                        const perPage = urlObj.searchParams.get('per_page');
                        if (perPage) {
                            const hiddenInput = document.querySelector('#transfer-filter-form input[name="per_page"]');
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
                        // Re-init HSSelect or other Preline components if needed
                        this.$nextTick(() => {
                            const newContainer = document.getElementById('transfers-table-container');
                            if (newContainer && window.Alpine) Alpine.initTree(newContainer);
                            if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
                        });
                        
                        // Update URL without reload
                        window.history.pushState({}, '', targetUrl);
                    }
                } catch (e) {
                    console.error(e);
                    window.showToast?.('{{ __("Loading failed") }}', 'error');
                } finally {
                    this.loading = false;
                }
            },

            addItem() { 
                this.items.push({ product_id: '', quantity: 1 }); 
                this.$nextTick(() => {
                    this.updateProductSelects();
                });
            },
            removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },

            async fetchStock() {
                this.availableProducts = [];
                if (!this.fromId || this.fromId.trim() === '') {
                    this.$nextTick(() => this.updateProductSelects());
                    return;
                }
                
                this.isLoadingProducts = true;
                try {
                    const params = this.transferType === 'warehouse_to_warehouse' 
                        ? 'warehouse_id=' + this.fromId 
                        : 'machine_id=' + this.fromId;
                    const res = await fetch('{{ route('admin.warehouses.ajax.stock') }}?' + params);
                    const json = await res.json();
                    if (json.success) {
                        this.availableProducts = json.data;
                        this.$nextTick(() => this.updateProductSelects());
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.isLoadingProducts = false;
                }
            },

            updateProductSelects() {
                this.items.forEach((item, index) => {
                    const wrapper = document.getElementById(`product-select-wrapper-${index}`);
                    if (!wrapper) return;

                    // 銷毀舊實例
                    const oldSelect = wrapper.querySelector('select');
                    if (oldSelect && window.HSSelect && window.HSSelect.getInstance(oldSelect)) {
                        try { window.HSSelect.getInstance(oldSelect).destroy(); } catch (e) {}
                    }
                    wrapper.innerHTML = '';

                    // 構建新 Select
                    const selectEl = document.createElement('select');
                    selectEl.id = `product-select-${index}-${Date.now()}`;
                    selectEl.name = `items[${index}][product_id]`;
                    selectEl.required = true;
                    selectEl.className = 'hidden';
                    
                    const products = this.fromId ? this.availableProducts : this.allProducts;
                    
                    // Placeholder
                    const placeholderOpt = document.createElement('option');
                    placeholderOpt.value = '';
                    placeholderOpt.textContent = '{{ __("Select Product") }}';
                    selectEl.appendChild(placeholderOpt);

                    products.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        const stockText = p.quantity !== undefined ? ` (${'{{ __("Stock") }}'}: ${p.quantity})` : '';
                        opt.textContent = p.name + stockText;
                        opt.setAttribute('data-title', p.name + stockText);
                        if (item.product_id == p.id) opt.selected = true;
                        selectEl.appendChild(opt);
                    });

                    // Preline Config
                    const config = {
                        "placeholder": "{{ __('Select Product') }}",
                        "toggleClasses": "hs-select-toggle luxury-select-toggle w-full text-left",
                        "toggleTemplate": "<button type=\"button\"><span class=\"text-slate-800 dark:text-slate-200\" data-title></span><div class=\"ms-auto\"><svg class=\"size-4 text-slate-400\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m6 9 6 6 6-6\"/></svg></div></button>",
                        "dropdownClasses": "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-2xl mt-2 z-[150] max-h-48 overflow-y-auto custom-scrollbar-thin",
                        "optionClasses": "hs-select-option py-2 px-3 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg",
                        "hasSearch": true,
                        "searchPlaceholder": "{{ __('Search Product') }}",
                        "searchClasses": "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200",
                        "searchWrapperClasses": "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
                        "strategy": "fixed"
                    };
                    selectEl.setAttribute('data-hs-select', JSON.stringify(config));
                    
                    wrapper.appendChild(selectEl);
                    selectEl.addEventListener('change', (e) => {
                        item.product_id = e.target.value;
                    });

                    // Init Preline
                    if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                        window.HSStaticMethods.autoInit('select');
                    }
                });
            },

            resetFrom() {
                this.fromId = '';
                this.availableProducts = [];
                this.items.forEach(item => item.product_id = ''); // 清除已選商品
                this.$nextTick(() => this.updateProductSelects());
            },

            async submitTransfer() {
                const form = document.getElementById('transferForm');
                const type = this.transferType;
                const fromId = this.fromId; // 使用 Alpine 變數更直接
                const toId = form.querySelector('[name="to_warehouse_id"]')?.value;
                
                // 前端必填檢查
                if (!fromId) {
                    const label = type === 'warehouse_to_warehouse' ? '{{ __("Source Warehouse") }}' : '{{ __("Source Machine") }}';
                    window.showToast?.('{{ __("Please select") }} ' + label, 'error');
                    return;
                }
                if (!toId) {
                    window.showToast?.('{{ __("Please select target warehouse") }}', 'error');
                    return;
                }
                if (fromId === toId && type === 'warehouse_to_warehouse') {
                    window.showToast?.('{{ __("Source and target warehouse cannot be the same") }}', 'error');
                    return;
                }

                if (this.items.length === 0) {
                    window.showToast?.('{{ __("Please add at least one item") }}', 'error');
                    return;
                }

                // 檢查每一列商品與數量
                let itemError = null;
                for (let i = 0; i < this.items.length; i++) {
                    const item = this.items[i];
                    if (!item.product_id) {
                        itemError = '{{ __("Please select product for item :num") }}'.replace(':num', i + 1);
                        break;
                    }
                    if (!item.quantity || item.quantity < 1) {
                        itemError = '{{ __("Please enter valid quantity for item :num") }}'.replace(':num', i + 1);
                        break;
                    }
                }

                if (itemError) {
                    window.showToast?.(itemError, 'error');
                    return;
                }

                this.loading = true;
                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    
                    const result = await response.json();
                    if (response.ok) {
                        if (result.success) {
                            this.showTransferModal = false;
                            window.location.reload(); 
                        } else {
                            window.showToast?.(result.message || '{{ __("Creation failed") }}', 'error');
                        }
                    } else if (response.status === 422) {
                        // Laravel 驗證錯誤
                        const errors = result.errors || {};
                        const firstError = Object.values(errors)[0]?.[0] || '{{ __("Validation failed") }}';
                        window.showToast?.(firstError, 'error');
                    } else {
                        window.showToast?.(result.message || '{{ __("System error, please try again later") }}', 'error');
                    }
                } catch (error) {
                    console.error(error);
                    window.showToast?.('{{ __("Connection error or system failure") }}', 'error');
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>

<div class="space-y-4 pb-10" x-data="transferManager()" 
    @open-details.window="openOrderDetails($event.detail.id)"
    @ajax:filter.window="fetchTabData()"
    @ajax:navigate:transfers.window.prevent="fetchTabData($event.detail.url)">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Transfer Orders') }}</h1>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">{{ __('Manage stock transfers between warehouses and machine returns') }}</p>
        </div>
        <button type="button" @click="openCreateModal()" class="btn-luxury-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            <span>{{ __('New Transfer') }}</span>
        </button>
    </div>

    {{-- Table --}}
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

        <form id="transfer-filter-form" action="{{ route('admin.warehouses.transfers') }}" method="GET" class="flex flex-wrap items-center gap-4 mb-8">
            
            <div class="relative group w-full sm:w-72">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                    <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" name="search_order" value="{{ request('search_order') }}"
                    @keydown.enter.prevent="$dispatch('ajax:filter')"
                    class="py-2.5 pl-12 pr-6 block w-full luxury-input" placeholder="{{ __('Search order number...') }}">
            </div>

            <div class="w-full sm:w-60">
                <x-searchable-select 
                    name="type" 
                    :selected="request('type')"
                    :placeholder="__('All Types')"
                    onchange="this.dispatchEvent(new CustomEvent('ajax:filter', { bubbles: true }))"
                >
                    <option value="" data-title="{{ __('All Types') }}">{{ __('All Types') }}</option>
                    <option value="warehouse_to_warehouse" {{ request('type') === 'warehouse_to_warehouse' ? 'selected' : '' }} data-title="{{ __('Warehouse to Warehouse') }}">{{ __('Warehouse to Warehouse') }}</option>
                    <option value="machine_to_warehouse" {{ request('type') === 'machine_to_warehouse' ? 'selected' : '' }} data-title="{{ __('Machine to Warehouse') }}">{{ __('Machine to Warehouse') }}</option>
                </x-searchable-select>
            </div>

            <div class="w-full sm:w-44">
                <x-searchable-select 
                    name="status" 
                    :selected="request('status')"
                    :placeholder="__('All Statuses')"
                    onchange="this.dispatchEvent(new CustomEvent('ajax:filter', { bubbles: true }))"
                >
                    <option value="" data-title="{{ __('All Statuses') }}">{{ __('All Statuses') }}</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }} data-title="{{ __('Draft') }}">{{ __('Draft') }}</option>
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

        @include('admin.warehouses.partials.table-transfers')
    </div>

    {{-- New Transfer Modal --}}
    <div x-show="showTransferModal" class="fixed inset-0 z-[110] overflow-y-auto" style="display: none;" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen p-4">
            <!-- Background Backdrop -->
            <div x-show="showTransferModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="showTransferModal = false"></div>

            <!-- Modal Panel -->
            <div x-show="showTransferModal" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="relative flex flex-col bg-white dark:bg-slate-900 rounded-[2.5rem] text-left shadow-2xl w-full max-w-3xl border border-slate-100 dark:border-slate-800 z-10 max-h-[90vh]"
                @click.stop>
                
                <!-- Modal Header -->
                <div class="px-10 py-8 pb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight leading-none mb-3">
                            {{ __('New Transfer') }}
                        </h3>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            {{ __('Create stock transfers between warehouses and machine returns') }}
                        </p>
                    </div>
                    <button @click="showTransferModal = false"
                        class="p-2.5 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-all border border-slate-100 dark:border-slate-700 shadow-sm">
                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-10 py-2 custom-scrollbar overflow-x-visible">
                    <form id="transferForm" action="{{ route('admin.warehouses.transfers.store') }}" method="POST" class="space-y-6 pb-20">
                    @csrf
                    
                    {{-- Type Toggle --}}
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] pl-1">
                            {{ __('Transfer Type') }}
                        </label>
                        <div class="flex p-1.5 bg-slate-100 dark:bg-slate-900/50 rounded-2xl border border-slate-200/50 dark:border-slate-800/50 w-fit">
                            <button type="button" @click="transferType = 'warehouse_to_warehouse'; resetFrom()"
                                :class="transferType === 'warehouse_to_warehouse' ? 'bg-white dark:bg-slate-800 text-indigo-500 shadow-sm' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300">
                                {{ __('Warehouse to Warehouse') }}
                            </button>
                            <button type="button" @click="transferType = 'machine_to_warehouse'; resetFrom()"
                                :class="transferType === 'machine_to_warehouse' ? 'bg-white dark:bg-slate-800 text-amber-500 shadow-sm' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
                                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300">
                                {{ __('Machine to Warehouse') }}
                            </button>
                        </div>
                        <input type="hidden" name="type" :value="transferType">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- From --}}
                        <div x-show="transferType === 'warehouse_to_warehouse'" class="space-y-3">
                            <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] pl-1">
                                {{ __('From Warehouse') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select 
                                name="from_warehouse_id" 
                                :options="$warehouses ?? []" 
                                :placeholder="__('Select Warehouse')"
                                class="w-full"
                                x-model="fromId"
                                @change="fromId = $event.target.value; fetchStock()"
                            />
                        </div>
                        <div x-show="transferType === 'machine_to_warehouse'" class="space-y-3">
                            <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] pl-1">
                                {{ __('From Machine') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select 
                                name="from_machine_id" 
                                :options="$machines->map(fn($m) => (object)['id' => $m->id, 'name' => $m->name . ' (' . $m->serial_no . ')'])" 
                                :placeholder="__('Select Machine')"
                                class="w-full"
                                x-model="fromId"
                                @change="fromId = $event.target.value; fetchStock()"
                            />
                        </div>

                        {{-- To --}}
                        <div class="space-y-3">
                            <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] pl-1">
                                {{ __('To Warehouse') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select 
                                name="to_warehouse_id" 
                                :options="$warehouses ?? []" 
                                :placeholder="__('Select Warehouse')"
                                class="w-full"
                                required 
                            />
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] pl-1">
                            {{ __('Note') }}
                        </label>
                        <input type="text" name="note" class="luxury-input w-full px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50" 
                            placeholder="{{ __('Optional remarks') }}">
                    </div>

                    {{-- Items --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pl-1">
                            <label class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em]">
                                {{ __('Item List') }} <span class="text-rose-500">*</span>
                            </label>
                            <button type="button" @click="addItem()"
                                class="text-xs font-black text-cyan-500 hover:text-cyan-400 uppercase tracking-widest flex items-center gap-1.5 transition-colors">
                                <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                {{ __('Add Product') }}
                            </button>
                        </div>
                        
                        <div class="space-y-3">
                                <template x-for="(item, index) in items" :key="index">
                                    <div class="group flex flex-wrap items-center gap-3 p-3 bg-slate-50/50 dark:bg-slate-900/30 rounded-2xl border border-slate-100 dark:border-slate-800/50 hover:border-cyan-500/30 transition-all duration-300">
                                        <div class="flex-1 min-w-[200px] relative" 
                                            :id="'product-select-wrapper-' + index"
                                            x-init="$nextTick(() => updateProductSelects())">
                                            <!-- 動態渲染商品 Select -->
                                            <div x-show="isLoadingProducts" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 flex items-center justify-center z-10 rounded-xl">
                                                <div class="w-4 h-4 border-2 border-cyan-500/20 border-t-cyan-500 rounded-full animate-spin"></div>
                                            </div>
                                        </div>
                                        <div class="hidden sm:block w-px h-6 bg-slate-200 dark:bg-slate-800/50"></div>
                                        <div class="flex items-center gap-1 bg-slate-100/50 dark:bg-slate-900/50 p-1 rounded-xl border border-slate-200/50 dark:border-slate-800/50">
                                            <button type="button" @click="item.quantity > 1 ? item.quantity-- : null" 
                                                class="p-2 text-slate-400 hover:text-cyan-500 transition-colors">
                                                <svg class="w-3.5 h-3.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                                            </button>
                                            <input type="number" :name="'items['+index+'][quantity]'" x-model.number="item.quantity"
                                                min="1" required class="w-12 bg-transparent border-none p-0 text-center font-mono font-bold text-slate-800 dark:text-slate-200 focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                placeholder="0">
                                            <button type="button" @click="item.quantity++" 
                                                class="p-2 text-slate-400 hover:text-cyan-500 transition-colors">
                                                <svg class="w-3.5 h-3.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                            </button>
                                        </div>
                                        <button type="button" @click="removeItem(index)"
                                            class="p-2 text-slate-300 hover:text-rose-500 transition-all transform hover:scale-110"
                                            x-show="items.length > 1">
                                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="px-10 py-6 border-t border-slate-100 dark:border-slate-800/50 flex items-center justify-end gap-4">
                <button type="button" @click="showTransferModal = false" class="btn-luxury-ghost px-8">
                    {{ __('Cancel') }}
                </button>
                <button type="button" @click="submitTransfer()" 
                    :disabled="loading"
                    class="btn-luxury-primary px-12 relative flex items-center justify-center">
                    <span :class="loading ? 'opacity-0' : ''">{{ __('Create') }}</span>
                    <template x-if="loading">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </template>
                </button>
            </div>
        </div>
    </div>
    </div>
    {{-- Details Slide-over --}}
    <div x-show="showOrderDetails" class="fixed inset-0 z-[120] overflow-hidden" style="display: none;" x-cloak>
        <div class="absolute inset-0 overflow-hidden">
            <!-- Backdrop -->
            <div x-show="showOrderDetails" 
                x-transition:enter="ease-in-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in-out duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
                @click="showOrderDetails = false"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex">
                <div x-show="showOrderDetails" 
                    x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                    class="w-screen max-w-2xl">
                    
                    <div class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-100 dark:border-slate-800">
                        <!-- Header -->
                        <div class="px-5 py-6 sm:px-8 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white font-display flex items-center gap-2 sm:gap-3">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-cyan-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34" />
                                            <path d="M11 9.66V7a2 2 0 0 0-2-2H5.66" />
                                            <path d="m20 21-5-5" />
                                            <path d="m15 21 5-5" />
                                            <path d="M12 13H7" />
                                            <path d="M12 17H7" />
                                            <path d="M17 12V7a2 2 0 0 0-2-2h-5" />
                                        </svg>
                                        <span class="truncate">{{ __('Transfer Details') }}</span>
                                    </h2>
                                    <template x-if="activeOrder">
                                        <div class="mt-2 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest">
                                            <span x-text="activeOrder.order_no" class="text-cyan-600 dark:text-cyan-400 font-mono tracking-tighter text-lg"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="window.open('/admin/warehouses/transfers/' + activeOrder.id + '/print', '_blank')" class="p-2 text-cyan-500 hover:text-cyan-600 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-full focus:outline-none transition duration-300 shadow-sm border border-slate-200 dark:border-slate-700 flex items-center justify-center" title="{{ __('Print Order') }}">
                                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l-.024-.03c-1.285-1.56-1.93-3.19-1.93-5.166C4.766 4.902 6.713 3 9.12 3c2.407 0 4.354 1.902 4.354 4.624 0 1.977-.645 3.607-1.93 5.167-.004.005-.008.01-.012.015L9.12 15.652l-2.4-1.832z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 12h2m-2 4h2m-2-8h2M3 12h2m-2 4h2m-2-8h2M12 18H8.5c-.83 0-1.5-.67-1.5-1.5V14m8.5 4H16m0 0v-4.5c0-.83-.67-1.5-1.5-1.5H11" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="showOrderDetails = false"
                                        class="bg-white dark:bg-slate-800 rounded-full p-2 text-slate-400 hover:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none transition duration-300 shadow-sm border border-slate-200 dark:border-slate-700">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto p-6 sm:p-8">
                            <div class="relative min-h-[200px]">
                                <!-- Loading State -->
                                <div x-show="detailsLoading" 
                                     class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px]">
                                    <div class="w-10 h-10 border-2 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
                                </div>

                                <template x-if="activeOrder">
                                    <div class="space-y-10">
                                        <!-- Transfer Flow -->
                                        <div class="flex items-center gap-4 justify-center">
                                            <div class="flex-1 text-center p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm transition-all duration-300 hover:shadow-md">
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ __('Source') }}</p>
                                                <p class="text-sm font-black text-slate-800 dark:text-white" x-text="activeOrder.from_name"></p>
                                            </div>
                                            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center bg-cyan-500 text-white rounded-2xl shadow-lg shadow-cyan-500/20 animate-pulse">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                </svg>
                                            </div>
                                            <div class="flex-1 text-center p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm transition-all duration-300 hover:shadow-md">
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ __('Target') }}</p>
                                                <p class="text-sm font-black text-slate-800 dark:text-white" x-text="activeOrder.to_name"></p>
                                            </div>
                                        </div>

                                        <!-- Info Grid -->
                                        <div class="grid grid-cols-2 gap-8 p-8 bg-slate-50/50 dark:bg-slate-800/30 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                                            <div class="space-y-2">
                                                <p class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Status') }}</p>
                                                <div>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black tracking-widest uppercase border"
                                                        :class="{
                                                            'bg-amber-500/10 text-amber-500 border-amber-500/20': activeOrder.status === 'draft',
                                                            'bg-emerald-500/10 text-emerald-500 border-emerald-500/20': activeOrder.status === 'completed',
                                                            'bg-slate-500/10 text-slate-500 border-slate-500/20': activeOrder.status === 'cancelled'
                                                        }"
                                                        x-text="{
                                                            'draft': '{{ __('Draft') }}',
                                                            'completed': '{{ __('Completed') }}',
                                                            'cancelled': '{{ __('Cancelled') }}'
                                                        }[activeOrder.status] || activeOrder.status"></span>
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <p class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Transfer Type') }}</p>
                                                <div>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black tracking-widest uppercase border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                                                        x-text="{
                                                            'warehouse_to_warehouse': '{{ __('Warehouse to Warehouse') }}',
                                                            'machine_to_warehouse': '{{ __('Machine to Warehouse') }}'
                                                        }[activeOrder.type] || activeOrder.type"></span>
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <p class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Created By') }}</p>
                                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="activeOrder.creator_name"></p>
                                            </div>
                                            <div class="space-y-2">
                                                <p class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Created At') }}</p>
                                                <p class="text-sm font-mono font-bold text-slate-600 dark:text-slate-400" x-text="activeOrder.created_at"></p>
                                            </div>
                                            <div class="space-y-2">
                                                <p class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Completed At') }}</p>
                                                <p class="text-sm font-mono font-bold text-slate-600 dark:text-slate-400" x-text="activeOrder.completed_at || '-'"></p>
                                            </div>
                                            <template x-if="activeOrder.note">
                                                <div class="col-span-2 space-y-1 pt-2 border-t border-slate-200/50 dark:border-slate-700/50">
                                                    <p class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Note') }}</p>
                                                    <p class="text-xs font-bold text-slate-600 dark:text-slate-400 italic" x-text="activeOrder.note"></p>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" @click="window.open('/admin/warehouses/transfers/' + activeOrder.id + '/print', '_blank')" class="px-4 py-2 rounded-xl text-xs font-black bg-cyan-500 text-white hover:bg-cyan-600 transition-all shadow-lg shadow-cyan-500/10 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l-.024-.03c-1.285-1.56-1.93-3.19-1.93-5.166C4.766 4.902 6.713 3 9.12 3c2.407 0 4.354 1.902 4.354 4.624 0 1.977-.645 3.607-1.93 5.167-.004.005-.008.01-.012.015L9.12 15.652l-2.4-1.832z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12h2m-2 4h2m-2-8h2M3 12h2m-2 4h2m-2-8h2M12 18H8.5c-.83 0-1.5-.67-1.5-1.5V14m8.5 4H16m0 0v-4.5c0-.83-.67-1.5-1.5-1.5H11" />
                                                </svg>
                                                {{ __('Print Order') }}
                                            </button>
                                        </div>

                                        <!-- Items List -->
                                        <div class="space-y-5">
                                            <div class="flex items-center justify-between px-1">
                                                <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Product Details') }}</h3>
                                                <span class="text-[10px] font-black text-cyan-500 bg-cyan-500/10 px-2 py-0.5 rounded-md uppercase" x-text="activeItems.length + ' {{ __('Items') }}'"></span>
                                            </div>
                                            
                                            <div class="space-y-3">
                                                <template x-for="(item, idx) in activeItems" :key="idx">
                                                    <div class="luxury-card p-4 rounded-2xl flex items-center gap-4 group/item transition-all hover:border-cyan-500/30"
                                                         x-data="{ imgFailed: !item.image_url }">
                                                        <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-800 flex-shrink-0 overflow-hidden border border-slate-200 dark:border-slate-700 group-hover:scale-105 transition-transform flex items-center justify-center relative">
                                                            <template x-if="item.image_url">
                                                                <img :src="item.image_url" class="w-full h-full object-cover" x-show="!imgFailed" x-on:error="imgFailed = true">
                                                            </template>
                                                            <div x-show="imgFailed" class="absolute inset-0 flex items-center justify-center bg-slate-50 dark:bg-slate-800/50">
                                                                <svg class="w-8 h-8 text-slate-300 dark:text-slate-600 group-hover/item:text-cyan-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-base font-extrabold text-slate-800 dark:text-white truncate" x-text="item.product_name"></p>
                                                            <div class="flex items-center gap-2 mt-1">
                                                                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ __('Product ID') }}:</span>
                                                                <span class="text-xs font-mono font-bold text-cyan-600 dark:text-cyan-400" x-text="item.product_id"></span>
                                                            </div>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="text-2xl font-black text-cyan-600 dark:text-cyan-400 font-mono tracking-tighter" x-text="'x' + item.quantity"></p>
                                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Quantity') }}</p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirmation Modal --}}
    <template x-teleport="body">
        <div x-show="showConfirmModal" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div x-show="showConfirmModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                    @click="showConfirmModal = false"></div>

                <div x-show="showConfirmModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                    class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-md p-8 border border-slate-100 dark:border-slate-800">

                    <div class="sm:flex sm:items-start text-center sm:text-left">
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-emerald-100 dark:bg-emerald-500/10 rounded-2xl sm:mx-0 sm:h-12 sm:w-12 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 sm:mt-0 sm:ml-6">
                            <h3 class="text-xl font-black text-slate-800 dark:text-white leading-6 tracking-tight font-display uppercase">
                                {{ __('Confirm Transfer Order') }}
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm font-bold text-slate-500 dark:text-slate-400 leading-relaxed">
                                    {{ __('Are you sure you want to confirm this transfer? This will deduct stock from source and add to target.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 sm:mt-10 sm:flex sm:flex-row-reverse gap-3">
                        <form :action="confirmUrl" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="inline-flex justify-center w-full px-6 py-3 text-sm font-black text-white transition-all bg-emerald-500 rounded-xl hover:bg-emerald-600 shadow-lg shadow-emerald-200 dark:shadow-none hover:scale-[1.02] active:scale-[0.98] sm:w-auto uppercase tracking-widest font-display">
                                {{ __('Confirm Transfer') }}
                            </button>
                        </form>
                        <button type="button" @click="showConfirmModal = false"
                            class="inline-flex justify-center w-full px-6 py-3 mt-3 text-sm font-black text-slate-700 dark:text-slate-200 transition-all bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 sm:mt-0 sm:w-auto uppercase tracking-widest font-display">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Delete Confirmation Modal --}}
    <template x-teleport="body">
        <div x-show="showDeleteModal" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                    @click="showDeleteModal = false"></div>

                <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                    class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-md p-8 border border-slate-100 dark:border-slate-800">

                    <div class="sm:flex sm:items-start text-center sm:text-left">
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-amber-100 dark:bg-amber-500/10 rounded-2xl sm:mx-0 sm:h-12 sm:w-12 text-amber-600 dark:text-amber-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="mt-3 sm:mt-0 sm:ml-6">
                            <h3 class="text-xl font-black text-slate-800 dark:text-white leading-6 tracking-tight font-display uppercase">
                                {{ __('Confirm Deletion') }}
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm font-bold text-slate-500 dark:text-slate-400 leading-relaxed">
                                    {{ __('Are you sure you want to delete this draft order? This action cannot be undone.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 sm:mt-10 sm:flex sm:flex-row-reverse gap-3">
                        <form :action="deleteUrl" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex justify-center w-full px-6 py-3 text-sm font-black text-white transition-all bg-rose-500 rounded-xl hover:bg-rose-600 shadow-lg shadow-rose-200 dark:shadow-none hover:scale-[1.02] active:scale-[0.98] sm:w-auto uppercase tracking-widest font-display">
                                {{ __('Delete Permanently') }}
                            </button>
                        </form>
                        <button type="button" @click="showDeleteModal = false"
                            class="inline-flex justify-center w-full px-6 py-3 mt-3 text-sm font-black text-slate-700 dark:text-slate-200 transition-all bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 sm:mt-0 sm:w-auto uppercase tracking-widest font-display">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
