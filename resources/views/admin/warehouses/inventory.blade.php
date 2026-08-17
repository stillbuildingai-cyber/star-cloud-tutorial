@extends('layouts.admin')

@section('content')
<script>
    function inventoryManager() {
        return {
            activeTab: '{{ $tab }}',
            tabLoading: null,
            loading: false,
            showStockInModal: false,
            showConfirmModal: false,
            showDeleteModal: false,
            deleteUrl: '',
            stockInWarehouseId: '',
            note: '',
            pendingForm: null,
            items: [{ product_id: '', quantity: 1 }],

            // Order Details (Renamed to match index.blade.php style)
            showOrderDetails: false,
            detailsLoading: false,
            activeOrder: null,
            activeItems: [],

            openStockInModal() {
                this.items = [{ product_id: '', quantity: 1 }];
                this.showStockInModal = true;
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
                    const response = await fetch(`/admin/warehouses/inventory/stock-in/${id}/details`, {
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

            init() {
                this.$watch('activeTab', (newTab) => {
                    // Update URL
                    const url = new URL(window.location.origin + window.location.pathname);
                    url.searchParams.set('tab', newTab);
                    window.history.pushState({}, '', url);

                    // Re-init HSSelect if needed when tab changes
                    this.$nextTick(() => {
                        if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
                    });
                });
            },

            addItem() {
                this.items.push({ product_id: '', quantity: 1 });
                this.$nextTick(() => {
                    if (window.HSStaticMethods) window.HSStaticMethods.autoInit('select');
                });
            },
            removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },

            async fetchTabData(tab, url = null) {
                this.tabLoading = tab;
                const container = document.getElementById('tab-' + tab + '-container');

                if (!url) {
                    const form = container?.querySelector('form');
                    let params = new URLSearchParams();
                    params.set('tab', tab);
                    params.set('_ajax', '1');

                    if (form) {
                        const formData = new FormData(form);
                        formData.forEach((value, key) => {
                            if (value.trim() !== '') params.append(key, value);
                        });
                    }
                    url = `${window.location.pathname}?${params.toString()}`;
                } else {
                    const urlObj = new URL(url, window.location.origin);
                    urlObj.searchParams.set('tab', tab);
                    urlObj.searchParams.set('_ajax', '1');
                    url = urlObj.toString();
                }

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        if (container) {
                            container.innerHTML = data.html;
                            this.$nextTick(() => {
                                if (window.Alpine) Alpine.initTree(container);
                                if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
                            });
                        }

                        const historyUrl = new URL(url, window.location.origin);
                        historyUrl.searchParams.delete('_ajax');
                        window.history.pushState({}, '', historyUrl.toString());
                    }
                } catch (e) {
                    console.error(e);
                    window.showToast?.('{{ __("Loading failed") }}', 'error');
                } finally {
                    this.tabLoading = null;
                }
            },

            async submitStockIn() {
                console.log('submitStockIn triggered');

                const form = document.getElementById('stockInForm');
                if (!form) {
                    console.error('Form not found');
                    return;
                }

                const formData = new FormData(form);
                const warehouseId = formData.get('warehouse_id');

                if (!warehouseId || warehouseId.trim() === '') {
                    window.showToast?.('{{ __("Please select target warehouse") }}', 'error');
                    return;
                }

                let hasItem = false;
                let allValid = true;

                // 抓取所有商品選擇器和數量框
                const productSelects = form.querySelectorAll('select[name^="items["][name$="][product_id]"]');
                const quantityInputs = form.querySelectorAll('input[name^="items["][name$="][quantity]"]');

                if (productSelects.length === 0) {
                    window.showToast?.('{{ __("Please add at least one item") }}', 'error');
                    return;
                }

                productSelects.forEach((select, index) => {
                    hasItem = true;
                    const pId = select.value;
                    const qty = quantityInputs[index]?.value;

                    if (!pId || pId.trim() === '' || pId === ' ') {
                        allValid = false;
                        window.showToast?.('{{ __("Please select product for item :num") }}'.replace(':num', index + 1), 'error');
                    } else if (!qty || parseInt(qty) < 1) {
                        allValid = false;
                        window.showToast?.('{{ __("Please enter valid quantity for item :num") }}'.replace(':num', index + 1), 'error');
                    }
                });

                if (!allValid) return;

                this.loading = true;
                console.log('Validation passed, submitting to:', form.action);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const result = await response.json();
                    console.log('Submit result:', result);

                    if (result.success) {
                        this.showStockInModal = false;

                        // 跳轉至進貨單管理頁面 (Inbound Tab) - 恢復整頁刷新
                        window.location.href = '{{ route('admin.warehouses.inventory') }}?tab=stock-in';
                    } else {
                        const firstError = result.errors ? Object.values(result.errors).flat()[0] : result.message;
                        window.showToast?.(firstError || '{{ __("Validation failed") }}', 'error');
                    }
                } catch (error) {
                    console.error('Submit error:', error);
                    window.showToast?.('{{ __("Operation failed") }}', 'error');
                } finally {
                    this.loading = false;
                }
            },

            handleFilterSubmit(tab) {
                console.log('handleFilterSubmit called for tab:', tab);
                const url = new URL(window.location.href);
                url.searchParams.delete('page');
                this.fetchTabData(tab);
            },
            confirmStockIn(form) {
                this.pendingForm = form;
                this.showConfirmModal = true;
            },

            doConfirmStockIn() {
                if (!this.pendingForm) return;
                this.showConfirmModal = false;
                this.loading = true;
                this.pendingForm.submit();
            },

            confirmDelete(url) {
                this.deleteUrl = url;
                this.showDeleteModal = true;
            },

            doDeleteStockIn() {
                if (!this.deleteUrl) return;
                this.showDeleteModal = false;
                this.loading = true;

                // 建立臨時表單以執行 DELETE 請求（整頁刷新）
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.deleteUrl;

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name="csrf-token"]').content;

                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';

                form.appendChild(csrf);
                form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            }
        };
    }
</script>

<div class="relative space-y-2 pb-20" x-data="inventoryManager()" data-tab="{{ $tab }}"
    data-index-url="{{ route('admin.warehouses.inventory') }}"
    @ajax:navigate.window.prevent="fetchTabData(activeTab, $event.detail.url)">


    <!-- Header -->
    <x-page-header 
        :title="__('Inventory Management')"
        :subtitle="__('Track stock-in orders and movement history')"
    >
        <template x-if="activeTab === 'stock'">
            <button type="button" @click="openStockInModal()" class="btn-luxury-primary flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="text-sm sm:text-base">{{ __('Stock-In Order') }}</span>
            </button>
        </template>
    </x-page-header>

    <x-tab-nav model="activeTab">
        <x-tab-nav-item value="stock"     :label="__('Adjust Inventory')" model="activeTab" />
        <x-tab-nav-item value="stock-in"  :label="__('Stock-In Orders')" model="activeTab" />
        <x-tab-nav-item value="movements" :label="__('Operation Records')" model="activeTab" />
    </x-tab-nav>

    <!-- Tab Contents -->
    <div class="mt-6">
        <!-- Stock List Tab -->
        <div x-show="activeTab === 'stock'" class="relative min-h-[300px]"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

            <!-- Loading Overlay -->
            <div x-show="tabLoading === 'stock'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 z-20 bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] flex flex-col items-center justify-center rounded-3xl"
                x-cloak>
                <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                    <div
                        class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin">
                    </div>
                    <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin"
                        style="animation-duration: 3s; direction: reverse;"></div>
                    <div class="relative w-8 h-8 flex items-center justify-center">
                        <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                </div>
                <p
                    class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">
                    {{ __('Loading Data') }}...</p>
            </div>

            <div id="tab-stock-container" class="relative">
                @include('admin.warehouses.partials.tab-stock')
            </div>
        </div>


        <!-- Stock-In Orders Tab -->
        <div x-show="activeTab === 'stock-in'" class="relative min-h-[300px]"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

            <!-- Loading Overlay -->
            <div x-show="tabLoading === 'stock-in'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 z-20 bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] flex flex-col items-center justify-center rounded-3xl"
                x-cloak>
                <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                    <div
                        class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin">
                    </div>
                    <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin"
                        style="animation-duration: 3s; direction: reverse;"></div>
                    <div class="relative w-8 h-8 flex items-center justify-center">
                        <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                </div>
                <p
                    class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">
                    {{ __('Loading Data') }}...</p>
            </div>

            <div id="tab-stock-in-container" class="relative">
                @include('admin.warehouses.partials.tab-stock-in')
            </div>
        </div>

        <!-- Movements Tab -->
        <div x-show="activeTab === 'movements'" class="relative min-h-[300px]"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

            <!-- Loading Overlay -->
            <div x-show="tabLoading === 'movements'" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 z-20 bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] flex flex-col items-center justify-center rounded-3xl"
                x-cloak>
                <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                    <div
                        class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin">
                    </div>
                    <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin"
                        style="animation-duration: 3s; direction: reverse;"></div>
                    <div class="relative w-8 h-8 flex items-center justify-center">
                        <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                </div>
                <p
                    class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">
                    {{ __('Loading Data') }}...</p>
            </div>

            <div id="tab-movements-container" class="relative">
                @include('admin.warehouses.partials.tab-movements')
            </div>
        </div>
    </div>

    <!-- Stock-In Modal -->
    <div x-show="showStockInModal" class="fixed inset-0 z-[110] overflow-y-auto" style="display: none;" role="dialog"
        aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen p-4">
            <!-- Background Backdrop -->
            <div x-show="showStockInModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="showStockInModal = false"></div>

            <!-- Modal Panel -->
            <div x-show="showStockInModal" x-transition:enter="ease-out duration-300"
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
                        <h3
                            class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight leading-none mb-3">
                            {{ __('New Stock-In Order') }}
                        </h3>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            {{ __('Add new inventory items to your warehouse') }}
                        </p>
                    </div>
                    <button @click="showStockInModal = false"
                        class="p-2.5 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-all border border-slate-100 dark:border-slate-700 shadow-sm">
                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-10 py-2 custom-scrollbar overflow-x-visible">
                    <form id="stockInForm" action="{{ route('admin.warehouses.inventory.stock-in.store') }}"
                        method="POST" class="space-y-6 pb-20">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <label
                                    class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] pl-1">
                                    {{ __('Target Warehouse') }} <span class="text-rose-500">*</span>
                                </label>
                                <x-searchable-select name="warehouse_id" :options="$warehouses ?? []"
                                    :selected="request('warehouse_id')" :placeholder="__('Select Warehouse')" />
                            </div>
                            <div class="space-y-3">
                                <label
                                    class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] pl-1">
                                    {{ __('Note') }}
                                </label>
                                <input type="text" name="note" class="luxury-input py-3"
                                    placeholder="{{ __('Optional remarks') }}">
                            </div>
                        </div>

                        <!-- Products Section -->
                        <div class="space-y-4 pt-4">
                            <div class="flex items-center justify-between pl-1">
                                <label
                                    class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em]">
                                    {{ __('Product List') }} <span class="text-rose-500">*</span>
                                </label>
                                <button type="button" @click="addItem()"
                                    class="text-xs font-black text-cyan-500 hover:text-cyan-400 uppercase tracking-widest flex items-center gap-1.5 transition-colors">
                                    <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    {{ __('Add Product') }}
                                </button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(item, index) in items" :key="index">
                                    <div
                                        class="group flex flex-wrap items-center gap-3 p-3 bg-slate-50/50 dark:bg-slate-900/30 rounded-2xl border border-slate-100 dark:border-slate-800/50 hover:border-cyan-500/30 transition-all duration-300">
                                        <div class="flex-1 min-w-[200px]">
                                            <x-searchable-select x-bind:name="'items['+index+'][product_id]'"
                                                :options="$stock_in_products ?? []"
                                                placeholder="{{ __('Select Product') }}"
                                                x-on:change="item.product_id = $event.target.value" />
                                        </div>
                                        <div class="hidden sm:block w-px h-6 bg-slate-200 dark:bg-slate-800/50"></div>
                                        <div
                                            class="flex items-center gap-1 bg-slate-100/50 dark:bg-slate-900/50 p-1 rounded-xl border border-slate-200/50 dark:border-slate-800/50">
                                            <button type="button" @click="item.quantity > 1 ? item.quantity-- : null"
                                                class="p-2 text-slate-400 hover:text-cyan-500 transition-colors">
                                                <svg class="w-3.5 h-3.5 stroke-[3]" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                                </svg>
                                            </button>
                                            <input type="number" :name="'items['+index+'][quantity]'"
                                                x-model.number="item.quantity" min="1" required
                                                class="w-12 bg-transparent border-none p-0 text-center font-mono font-bold text-slate-800 dark:text-slate-200 focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                placeholder="0">
                                            <button type="button" @click="item.quantity++"
                                                class="p-2 text-slate-400 hover:text-cyan-500 transition-colors">
                                                <svg class="w-3.5 h-3.5 stroke-[3]" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 4.5v15m7.5-7.5h-15" />
                                                </svg>
                                            </button>
                                        </div>
                                        <button type="button" @click="removeItem(index)"
                                            class="p-2 text-slate-300 hover:text-rose-500 transition-all transform hover:scale-110"
                                            x-show="items.length > 1">
                                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div
                    class="px-10 py-6 border-t border-slate-100 dark:border-slate-800/50 flex items-center justify-end gap-4">
                    <button type="button" @click="showStockInModal = false" class="btn-luxury-ghost px-8">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" @click="submitStockIn()" :disabled="loading"
                        class="btn-luxury-primary px-12 relative flex items-center justify-center">
                        <span :class="loading ? 'opacity-0' : ''">{{ __('Confirm Stock-In') }}</span>
                        <template x-if="loading">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </div>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <x-confirm-modal alpine-var="showConfirmModal" confirm-action="doConfirmStockIn()" icon-type="info"
        confirm-color="emerald" :title="__('Confirm Stock-in Order')"
        :message="__('Are you sure you want to confirm this stock-in order? This action will update your inventory levels.')"
        :confirm-text="__('Confirm Stock-In')" />

    <!-- Order Details Slide-over (Rewritten to match index.blade.php structure) -->
    <div x-show="showOrderDetails" class="fixed inset-0 z-[100] overflow-hidden" style="display: none;" x-cloak>
        <div class="absolute inset-0 overflow-hidden">
            <!-- Backdrop -->
            <div x-show="showOrderDetails" x-transition:enter="ease-in-out duration-500"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in-out duration-500" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="showOrderDetails = false"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex">
                <div x-show="showOrderDetails"
                    x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                    x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                    class="w-screen max-w-2xl">

                    <div
                        class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-100 dark:border-slate-800">
                        <!-- Header -->
                        <div
                            class="px-5 py-6 sm:px-8 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <h2
                                        class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white font-display flex items-center gap-2 sm:gap-3">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-cyan-500 flex-shrink-0"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="truncate">{{ __('Order Details') }}</span>
                                    </h2>
                                    <template x-if="activeOrder">
                                        <div
                                            class="mt-2 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest">
                                            <span x-text="activeOrder.order_no"
                                                class="text-cyan-600 dark:text-cyan-400 font-mono tracking-tighter text-lg"></span>
                                            <span class="mx-1">•</span>
                                            <span x-text="activeOrder.warehouse_name"></span>
                                        </div>
                                    </template>
                                </div>
                                <button type="button" @click="showOrderDetails = false"
                                    class="bg-white dark:bg-slate-800 rounded-full p-2 text-slate-400 hover:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none transition duration-300 shadow-sm border border-slate-200 dark:border-slate-700">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto p-6 sm:p-8">
                            <div class="relative min-h-[200px]">
                                <!-- Loading State -->
                                <div x-show="detailsLoading"
                                    class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px]">
                                    <div
                                        class="w-10 h-10 border-2 border-cyan-500 border-t-transparent rounded-full animate-spin">
                                    </div>
                                </div>

                                <template x-if="activeOrder">
                                    <div class="space-y-10">
                                        <!-- Info Grid -->
                                        <div
                                            class="grid grid-cols-2 gap-8 p-8 bg-slate-50/50 dark:bg-slate-800/30 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                                            <div class="space-y-2">
                                                <p
                                                    class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                                    {{ __('Status') }}</p>
                                                <div>
                                                    <template x-if="activeOrder.status === 'draft'">
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black bg-amber-500/10 text-amber-500 border border-amber-500/20 uppercase tracking-widest">{{
                                                            __('Draft') }}</span>
                                                    </template>
                                                    <template x-if="activeOrder.status === 'completed'">
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 uppercase tracking-widest">{{
                                                            __('Completed') }}</span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <p
                                                    class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                                    {{ __('Created By') }}</p>
                                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300"
                                                    x-text="activeOrder.creator_name"></p>
                                            </div>
                                            <div class="space-y-2">
                                                <p
                                                    class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                                    {{ __('Created At') }}</p>
                                                <p class="text-sm font-mono font-bold text-slate-600 dark:text-slate-400"
                                                    x-text="activeOrder.created_at"></p>
                                            </div>
                                            <div class="space-y-2">
                                                <p
                                                    class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                                    {{ __('Approved At') }}</p>
                                                <p class="text-sm font-mono font-bold text-slate-600 dark:text-slate-400"
                                                    x-text="activeOrder.completed_at || '-'"></p>
                                            </div>
                                            <template x-if="activeOrder.note">
                                                <div
                                                    class="col-span-2 space-y-1 pt-2 border-t border-slate-200/50 dark:border-slate-700/50">
                                                    <p
                                                        class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                                        {{ __('Note') }}</p>
                                                    <p class="text-xs font-bold text-slate-600 dark:text-slate-400 italic"
                                                        x-text="activeOrder.note"></p>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Items List -->
                                        <div class="space-y-5">
                                            <div class="flex items-center justify-between px-1">
                                                <h3
                                                    class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">
                                                    {{ __('Product Details') }}</h3>
                                                <span
                                                    class="text-[10px] font-black text-cyan-500 bg-cyan-500/10 px-2 py-0.5 rounded-md uppercase"
                                                    x-text="activeItems.length + ' {{ __('Items') }}'"></span>
                                            </div>

                                            <div class="space-y-3">
                                                <template x-for="(item, idx) in activeItems" :key="idx">
                                                    <div
                                                        class="luxury-card p-4 rounded-2xl flex items-center gap-4 group/item transition-all hover:border-cyan-500/30">
                                                        <div
                                                            class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-800 flex-shrink-0 overflow-hidden border border-slate-200 dark:border-slate-700 group-hover:scale-105 transition-transform">
                                                            <template x-if="item.image_url">
                                                                <img :src="item.image_url"
                                                                    class="w-full h-full object-cover">
                                                            </template>
                                                            <template x-if="!item.image_url">
                                                                <div
                                                                    class="w-full h-full flex items-center justify-center text-slate-400">
                                                                    <svg class="w-8 h-8" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="1.5"
                                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                    </svg>
                                                                </div>
                                                            </template>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-base font-extrabold text-slate-800 dark:text-white truncate"
                                                                x-text="item.product_name"></p>
                                                            <div class="flex items-center gap-2 mt-1">
                                                                <span
                                                                    class="text-xs font-black text-slate-400 uppercase tracking-widest">{{
                                                                    __('Product ID') }}:</span>
                                                                <span
                                                                    class="text-xs font-mono font-bold text-cyan-600 dark:text-cyan-400"
                                                                    x-text="item.product_id"></span>
                                                            </div>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="text-2xl font-black text-cyan-600 dark:text-cyan-400 font-mono tracking-tighter"
                                                                x-text="'x' + item.quantity"></p>
                                                            <p
                                                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                                                {{ __('Quantity') }}</p>
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

    <x-confirm-modal alpine-var="showDeleteModal" confirm-action="doDeleteStockIn()" icon-type="danger"
        confirm-color="rose" :title="__('Confirm Deletion')"
        :message="__('Are you sure you want to delete this draft order? This action cannot be undone.')"
        :confirm-text="__('Delete Permanently')" />

</div>
@endsection
