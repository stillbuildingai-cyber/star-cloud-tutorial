@extends('layouts.admin')

@section('content')
<div class="relative space-y-4 pb-20" x-data="{
    showModal: false,
    editing: false,
    isDeleteConfirmOpen: false,
    isStatusConfirmOpen: false,
    deleteFormAction: '',
    toggleFormAction: '',
    isLoading: false,
    isSubmitting: false,
    currentWarehouse: { id: '', company_id: '', name: '', type: 'branch', address: '', is_active: 1 },
    openCreateModal() {
        this.editing = false;
        this.currentWarehouse = { id: '', company_id: '{{ auth()->user()->company_id }}', name: '', type: 'branch', address: '', is_active: 1 };
        this.showModal = true;
    },
    openEditModal(wh) {
        this.editing = true;
        this.currentWarehouse = { ...wh };
        // Ensure searchable-select updates for company_id if present
        this.$nextTick(() => {
            if (window.HSSelect && document.getElementById('company_select')) {
                window.HSSelect.getInstance('#company_select')?.setValue(wh.company_id?.toString() || '');
            }
        });
        this.showModal = true;
    },
    submitConfirmedForm() {
        this.$refs.statusToggleForm.submit();
    },
    async submitForm() {
        if (this.isSubmitting) return;
        this.isSubmitting = true;
        
        const form = this.$refs.warehouseForm;
        const formData = new FormData(form);
        const action = form.action;
        const method = formData.get('_method') || 'POST';

        try {
            const response = await fetch(action, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                window.Alpine.store('toast').show(data.message || '{{ __('Operation successful') }}', 'success');
                this.showModal = false;
                this.fetchPage(window.location.href);
            } else if (response.status === 422) {
                const errors = data.errors;
                Object.values(errors).forEach(errGroup => {
                    errGroup.forEach(msg => {
                        window.Alpine.store('toast').show(msg, 'error');
                    });
                });
            } else {
                window.Alpine.store('toast').show(data.message || '{{ __('Operation failed') }}', 'error');
            }
        } catch (error) {
            console.error('Submit failed:', error);
            window.Alpine.store('toast').show('{{ __('Network error') }}', 'error');
        } finally {
            this.isSubmitting = false;
        }
    },
    async fetchPage(url) {
        if (!url) return;
        this.isLoading = true;
        
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });
            
            if (!response.ok) throw new Error('Network response was not ok');
            
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            const newContent = doc.querySelector('#ajax-content-container');
            const currentContent = document.querySelector('#ajax-content-container');
            if (newContent && currentContent) {
                currentContent.innerHTML = newContent.innerHTML;
                if (window.Alpine) {
                    Alpine.initTree(currentContent);
                }
            }

            const newStats = doc.querySelector('#warehouse-stats-container');
            const currentStats = document.querySelector('#warehouse-stats-container');
            if (newStats && currentStats) {
                currentStats.innerHTML = newStats.innerHTML;
            }
            
            window.history.pushState({}, '', url);
            this.$nextTick(() => {
                if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
            });
        } catch (error) {
            console.error('Error fetching page:', error);
            window.showToast?.('載入失敗，請重試', 'error');
        } finally {
            this.isLoading = false;
        }
    },
    showInventoryPanel: false,
    inventoryLoading: false,
    inventoryStocks: [],
    inventorySearch: '',
    activeWarehouse: { id: '', name: '' },
    async openInventoryPanel(wh) {
        this.activeWarehouse = wh;
        this.showInventoryPanel = true;
        this.inventorySearch = '';
        await this.fetchInventory();
    },
    async fetchInventory() {
        this.inventoryLoading = true;
        try {
            const url = `/admin/warehouses/${this.activeWarehouse.id}/inventory-ajax?search=${this.inventorySearch}`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                this.inventoryStocks = data.stocks;
            }
        } catch (e) { console.error(e); }
        finally { this.inventoryLoading = false; }
    }
}" @ajax:navigate.window.prevent="fetchPage($event.detail.url); $event.stopImmediatePropagation();">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Warehouse Overview') }}</h1>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">{{ __('Manage your warehouses, including main and branch warehouses') }}</p>
        </div>
        <button @click="openCreateModal()" class="btn-luxury-primary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span class="text-sm sm:text-base">{{ __('Add Warehouse') }}</span>
        </button>
    </div>

    {{-- Stats Cards --}}
    <div id="warehouse-stats-container" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="luxury-card p-6 rounded-2xl animate-luxury-in">
            <p class="text-sm font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ __('Total Warehouses') }}</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white mt-2">{{ $stats['total'] }}</p>
        </div>
        <div class="luxury-card p-6 rounded-2xl animate-luxury-in" style="animation-delay: 100ms">
            <p class="text-sm font-black text-cyan-500 uppercase tracking-widest">{{ __('Main Warehouses') }}</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white mt-2">{{ $stats['main'] }}</p>
        </div>
        <div class="luxury-card p-6 rounded-2xl animate-luxury-in" style="animation-delay: 200ms">
            <p class="text-sm font-black text-indigo-500 uppercase tracking-widest">{{ __('Branch Warehouses') }}</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white mt-2">{{ $stats['branch'] }}</p>
        </div>
        <div class="luxury-card p-6 rounded-2xl animate-luxury-in" style="animation-delay: 300ms">
            <p class="text-sm font-black text-rose-500 uppercase tracking-widest">{{ __('Low Stock Alerts') }}</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white mt-2">{{ $stats['low_stock'] }}</p>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="luxury-card rounded-3xl p-8 animate-luxury-in" style="animation-delay: 400ms"
        id="ajax-content-container"
        :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': isLoading }">
        {{-- Filters --}}
        <div class="flex flex-wrap items-center justify-between mb-8 gap-4">
            <form action="{{ route('admin.warehouses.index') }}" method="GET" class="flex flex-wrap items-center gap-4"
                @submit.prevent="
                    const url = new URL($el.action, window.location.origin);
                    new FormData($el).forEach((value, key) => {
                        if (value.trim()) url.searchParams.set(key, value);
                        else url.searchParams.delete(key);
                    });
                    fetchPage(url.pathname + url.search);
                ">
                <div class="flex items-center gap-3 flex-1 sm:flex-none">
                    <div class="relative group flex-1 sm:flex-none">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="py-2.5 pl-12 pr-6 block w-full sm:w-64 luxury-input text-sm font-bold" placeholder="{{ __('Search warehouses...') }}">
                        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                        <input type="hidden" name="type" value="{{ request('type') }}">
                    </div>

                    @if(auth()->user()->isSystemAdmin())
                    <div class="w-full sm:w-72">
                        <x-searchable-select name="company_id" :options="$companies" :selected="request('company_id')"
                            :placeholder="__('All Companies')"
                            x-on:change="$el.closest('form').dispatchEvent(new Event('submit', { cancelable: true }))" />
                    </div>
                    @endif

                    <div class="flex items-center gap-2 flex-none">
                        <button type="submit" class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 group transition-all active:scale-95" title="{{ __('Search') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>

                        <button type="button" 
                            @click="
                                $el.closest('form').querySelectorAll('input[type=text]').forEach(i => i.value = '');
                                $el.closest('form').querySelectorAll('input[type=hidden][name=type]').forEach(i => i.value = '');
                                $el.closest('form').querySelectorAll('select').forEach(s => {
                                    s.value = ' ';
                                    const instance = window.HSSelect?.getInstance(s);
                                    if (instance) instance.setValue(' ');
                                });
                                $el.closest('form').dispatchEvent(new Event('submit', { cancelable: true }));
                            "
                            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 group transition-all active:scale-95" title="{{ __('Reset') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                </div>
            </form>

            <div
                class="flex items-center p-1 bg-slate-100/50 dark:bg-slate-900/50 backdrop-blur-md rounded-2xl border border-slate-200/50 dark:border-slate-700/50">
                <a href="{{ route('admin.warehouses.index', array_filter(['search' => request('search'), 'company_id' => request('company_id'), 'per_page' => request('per_page')])) }}" @click.prevent="fetchPage($el.getAttribute('href'))"
                    class="px-5 py-2 text-xs font-black tracking-widest uppercase transition-all duration-300 rounded-xl {{ !request()->filled('type') && !request()->filled('status') ? 'bg-white dark:bg-slate-800 text-cyan-600 dark:text-cyan-400 shadow-lg shadow-cyan-500/10' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300' }}">
                    {{ __('All') }}
                </a>
                <a href="{{ route('admin.warehouses.index', array_filter(['type' => 'main', 'search' => request('search'), 'company_id' => request('company_id'), 'per_page' => request('per_page')])) }}"
                    @click.prevent="fetchPage($el.getAttribute('href'))"
                    class="px-5 py-2 text-xs font-black tracking-widest uppercase transition-all duration-300 rounded-xl {{ request('type') === 'main' ? 'bg-white dark:bg-cyan-500/10 text-cyan-500 shadow-lg shadow-cyan-500/10' : 'text-slate-400 dark:text-slate-500 hover:text-cyan-500/80' }}">
                    {{ __('Main') }}
                </a>
                <a href="{{ route('admin.warehouses.index', array_filter(['type' => 'branch', 'search' => request('search'), 'company_id' => request('company_id'), 'per_page' => request('per_page')])) }}"
                    @click.prevent="fetchPage($el.getAttribute('href'))"
                    class="px-5 py-2 text-xs font-black tracking-widest uppercase transition-all duration-300 rounded-xl {{ request('type') === 'branch' ? 'bg-white dark:bg-indigo-500/10 text-indigo-500 shadow-lg shadow-indigo-500/10' : 'text-slate-400 dark:text-slate-500 hover:text-indigo-500/80' }}">
                    {{ __('Branch') }}
                </a>
            </div>
        </div>

        {{-- Table View (Desktop) --}}
        <div class="hidden xl:block overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-y-0">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                            {{ __('Warehouse Info') }}</th>
                        @if(auth()->user()->isSystemAdmin())
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-left">
                            {{ __('Company Name') }}</th>
                        @endif
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                            {{ __('Type') }}</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                            {{ __('Products / Stock') }}</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                            {{ __('Status') }}</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                            {{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                    @forelse($warehouses as $warehouse)
                    <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                        <td class="px-6 py-5 cursor-pointer group/item" @click="openInventoryPanel({ id: '{{ $warehouse->id }}', name: '{{ addslashes($warehouse->name) }}' })">
                            <div class="flex items-center gap-x-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{
                                        $warehouse->name }}</span>
                                    @if($warehouse->address)
                                    <span
                                        class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-0.5 tracking-wide line-clamp-1">{{
                                        $warehouse->address }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @if(auth()->user()->isSystemAdmin())
                        <td class="px-6 py-5 whitespace-nowrap">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">
                                {{ $warehouse->company->name ?? __('System Default') }}
                            </span>
                        </td>
                        @endif
                        <td class="px-6 py-5 text-center">
                            @if($warehouse->type === 'main')
                            <div class="mt-1 flex flex-col items-center gap-1.5">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black bg-cyan-400/20 text-cyan-400 border border-cyan-400/30 uppercase tracking-widest shadow-sm shadow-cyan-400/10">{{
                                    __('Main') }}</span>
                            </div>
                            @else
                            <div class="mt-1 flex flex-col items-center gap-1.5">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black bg-indigo-400/20 text-indigo-400 border border-indigo-400/30 uppercase tracking-widest shadow-sm shadow-indigo-400/10">{{
                                    __('Branch') }}</span>
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="flex items-center justify-center gap-x-3">
                                <div class="flex flex-col items-center">
                                    <span class="text-base font-extrabold text-slate-800 dark:text-white">{{
                                        $warehouse->products_count ?? 0 }}</span>
                                    <span
                                        class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{
                                        __('Products') }}</span>
                                </div>
                                <div class="w-px h-6 bg-slate-100 dark:bg-slate-800"></div>
                                <div class="flex flex-col items-center">
                                    <span class="text-base font-extrabold text-slate-800 dark:text-white">{{
                                        (int)($warehouse->total_stock ?? 0) }}</span>
                                    <span
                                        class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{
                                        __('Stock') }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($warehouse->is_active)
                            <span
                                class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 tracking-widest uppercase shadow-sm shadow-emerald-500/5">{{
                                __('Active') }}</span>
                            @else
                            <span
                                class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black bg-slate-400/10 text-slate-400 border border-slate-400/20 tracking-widest uppercase">{{
                                __('Disabled') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex justify-end items-center gap-2">
                                @if($warehouse->is_active)
                                <button type="button"
                                    @click="toggleFormAction = '{{ route('admin.warehouses.toggle-status', $warehouse->id) }}'; isStatusConfirmOpen = true"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-amber-500 hover:bg-amber-500/5 transition-all border border-transparent hover:border-amber-500/20"
                                    title="{{ __('Disable') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                    </svg>
                                </button>
                                @else
                                <button type="button"
                                    @click="toggleFormAction = '{{ route('admin.warehouses.toggle-status', $warehouse->id) }}'; $nextTick(() => $refs.statusToggleForm.submit())"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all border border-transparent hover:border-emerald-500/20"
                                    title="{{ __('Enable') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z" />
                                    </svg>
                                </button>
                                @endif
                                <button @click="openEditModal({{ json_encode($warehouse) }})"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                    title="{{ __('Edit') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </button>
                                <button type="button"
                                    @click="deleteFormAction = '{{ route('admin.warehouses.destroy', $warehouse->id) }}'; isDeleteConfirmOpen = true"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all border border-transparent hover:border-rose-500/20"
                                    title="{{ __('Delete') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                                <button type="button"
                                    @click="openInventoryPanel({ id: '{{ $warehouse->id }}', name: '{{ addslashes($warehouse->name) }}' })"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                    title="{{ __('View Inventory') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSystemAdmin() ? 6 : 5 }}" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819" />
                                </svg>
                                <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No warehouses found') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Card View (Mobile/Tablet) --}}
        <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($warehouses as $warehouse)
            <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group relative">
                
                <!-- Card Header -->
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1 cursor-pointer" @click="openInventoryPanel({ id: '{{ $warehouse->id }}', name: '{{ addslashes($warehouse->name) }}' })">
                            <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 truncate group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">
                                {{ $warehouse->name }}
                            </h3>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate mt-0.5">
                                {{ $warehouse->address ?: __('No address provided') }}
                            </p>
                        </div>
                    </div>
                    <div class="shrink-0">
                        @if($warehouse->type === 'main')
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-cyan-500/10 border border-cyan-500/20 w-fit">
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-cyan-500"></span>
                            <span class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 tracking-widest uppercase">
                                {{ __('Main') }}
                            </span>
                        </div>
                        @else
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 w-fit">
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-indigo-500"></span>
                            <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 tracking-widest uppercase">
                                {{ __('Branch') }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Products') }}</p>
                        <p class="text-xl font-black text-slate-800 dark:text-white">{{ $warehouse->products_count ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Stock') }}</p>
                        <p class="text-xl font-black text-slate-800 dark:text-white">{{ (int)($warehouse->total_stock ?? 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Status') }}</p>
                        @if($warehouse->is_active)
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 w-fit">
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500 animate-pulse"></span>
                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 tracking-widest uppercase">
                                {{ __('Active') }}
                            </span>
                        </div>
                        @else
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-500/10 border border-slate-500/20 w-fit">
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-slate-400"></span>
                            <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 tracking-widest uppercase">
                                {{ __('Disabled') }}
                            </span>
                        </div>
                        @endif
                    </div>
                    @if(auth()->user()->isSystemAdmin())
                    <div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Company') }}</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300 leading-tight">
                            {{ $warehouse->company->name ?? __('System') }}
                        </p>
                    </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <!-- Status Toggle -->
                    @if($warehouse->is_active)
                    <button type="button"
                        @click="toggleFormAction = '{{ route('admin.warehouses.toggle-status', $warehouse->id) }}'; isStatusConfirmOpen = true"
                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-amber-500 transition-all border border-slate-200/50 dark:border-slate-700/50 shadow-sm"
                        title="{{ __('Disable') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                        </svg>
                    </button>
                    @else
                    <button type="button"
                        @click="toggleFormAction = '{{ route('admin.warehouses.toggle-status', $warehouse->id) }}'; $nextTick(() => $refs.statusToggleForm.submit())"
                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 transition-all border border-slate-200/50 dark:border-slate-700/50 shadow-sm"
                        title="{{ __('Enable') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z" />
                        </svg>
                    </button>
                    @endif

                    <!-- Edit -->
                    <button @click="openEditModal({{ json_encode($warehouse) }})"
                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 transition-all border border-slate-200/50 dark:border-slate-700/50 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                    </button>

                    <!-- Delete -->
                    <button type="button"
                        @click="deleteFormAction = '{{ route('admin.warehouses.destroy', $warehouse->id) }}'; isDeleteConfirmOpen = true"
                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 transition-all border border-slate-200/50 dark:border-slate-700/50 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>

                    <!-- View Inventory (Primary Action) -->
                    <button @click="openInventoryPanel({ id: '{{ $warehouse->id }}', name: '{{ addslashes($warehouse->name) }}' })"
                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-cyan-500 text-white font-black text-xs uppercase tracking-widest hover:bg-cyan-600 transition-all duration-300 shadow-lg shadow-cyan-500/25">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        {{ __('View Inventory') }}
                    </button>
                </div>
            </div>
            @empty
            <div class="col-span-full py-20 text-center flex flex-col items-center gap-4 bg-white/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800/50 luxury-card">
                <div class="flex flex-col items-center gap-3">
                    <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819" />
                    </svg>
                    <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No warehouses found') }}</p>
                </div>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6 py-6 border-t border-slate-50 dark:border-slate-800/50">
            {{ $warehouses->links('vendor.pagination.luxury') }}
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    <div x-show="showModal" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="showModal = false">
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block px-8 py-10 overflow-visible text-left align-bottom transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight"
                        x-text="editing ? '{{ __('Edit Warehouse') }}' : '{{ __('Add Warehouse') }}'"></h3>
                    <button @click="showModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form x-ref="warehouseForm"
                    :action="editing ? '{{ url('admin/warehouses') }}/' + currentWarehouse.id : '{{ route('admin.warehouses.store') }}'"
                    method="POST" @submit.prevent="submitForm()" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_method" :value="editing ? 'PUT' : 'POST'">
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                __('Warehouse Name') }} <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" x-model="currentWarehouse.name"
                                class="luxury-input w-full" placeholder="{{ __('e.g. Main Warehouse') }}">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                __('Warehouse Type') }} <span class="text-rose-500">*</span></label>
                            <div
                                class="flex p-1.5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 w-fit">
                                <button type="button" @click="currentWarehouse.type = 'main'"
                                    :class="currentWarehouse.type === 'main' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/20' : 'text-slate-400 hover:text-slate-600'"
                                    class="px-5 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">
                                    {{ __('Main') }}
                                </button>
                                <button type="button" @click="currentWarehouse.type = 'branch'"
                                    :class="currentWarehouse.type === 'branch' ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/20' : 'text-slate-400 hover:text-slate-600'"
                                    class="px-5 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">
                                    {{ __('Branch') }}
                                </button>
                            </div>
                            <input type="hidden" name="type" :value="currentWarehouse.type">
                        </div>

                        @if(auth()->user()->isSystemAdmin())
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                __('Company Name') }}</label>
                            <x-searchable-select id="company_select" name="company_id" :options="$companies"
                                x-model="currentWarehouse.company_id" :placeholder="__('Select Company')"
                                class="w-full" />
                        </div>
                        @else
                        <input type="hidden" name="company_id" :value="currentWarehouse.company_id">
                        @endif

                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                __('Address') }}</label>
                            <input type="text" name="address" x-model="currentWarehouse.address"
                                class="luxury-input w-full" placeholder="{{ __('Optional') }}">
                        </div>
                        <template x-if="editing">
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Status') }}</label>
                                <div
                                    class="flex p-1.5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 w-fit">
                                    <button type="button" @click="currentWarehouse.is_active = 1"
                                        :class="currentWarehouse.is_active == 1 ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'text-slate-400 hover:text-slate-600'"
                                        class="px-5 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">{{
                                        __('Active') }}</button>
                                    <button type="button" @click="currentWarehouse.is_active = 0"
                                        :class="currentWarehouse.is_active == 0 ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/20' : 'text-slate-400 hover:text-slate-600'"
                                        class="px-5 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">{{
                                        __('Disabled') }}</button>
                                </div>
                                <input type="hidden" name="is_active" :value="currentWarehouse.is_active ? 1 : 0">
                            </div>
                        </template>
                    </div>
                    <div class="flex justify-end gap-x-4 pt-8">
                        <button type="button" @click="showModal = false" class="btn-luxury-ghost px-8">{{ __('Cancel')
                            }}</button>
                        <button type="submit" class="btn-luxury-primary px-12" :disabled="isSubmitting">
                            <template x-if="!isSubmitting">
                                <span x-text="editing ? '{{ __('Update') }}' : '{{ __('Create') }}'"></span>
                            </template>
                            <template x-if="isSubmitting">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    <span>{{ __('Processing...') }}</span>
                                </div>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Confirm Modals --}}
    <x-delete-confirm-modal :message="__('Are you sure to delete this warehouse? This action cannot be undone.')" />
    <x-status-confirm-modal :title="__('Disable Warehouse')" :message="__('Are you sure to disable this warehouse?')" />
    <form x-ref="statusToggleForm" :action="toggleFormAction" method="POST" class="hidden">
        @csrf
        @method('PATCH')
    </form>

    <!-- Inventory Offcanvas Panel -->
    <div x-show="showInventoryPanel" class="fixed inset-0 z-[100] overflow-hidden" style="display: none;"
        aria-labelledby="inventory-panel-title" role="dialog" aria-modal="true" x-cloak>

        <!-- Background backdrop -->
        <div x-show="showInventoryPanel" x-transition:enter="ease-in-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in-out duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
            @click="showInventoryPanel = false">
        </div>

        <div class="fixed inset-y-0 right-0 max-w-full flex">
            <!-- Sliding panel -->
            <div x-show="showInventoryPanel"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                class="w-screen max-w-2xl">

                <div class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl">
                    <!-- Header -->
                    <div class="px-5 py-6 sm:px-8 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <h2 id="inventory-panel-title"
                                    class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white font-display flex items-center gap-2 sm:gap-3">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-cyan-500 flex-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span class="truncate">{{ __('Warehouse Inventory') }}</span>
                                </h2>
                                <div class="mt-2 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest">
                                    <span x-text="activeWarehouse.name" class="text-cyan-600 dark:text-cyan-400"></span>
                                </div>
                            </div>
                            <button type="button" @click="showInventoryPanel = false"
                                class="bg-white dark:bg-slate-800 rounded-full p-2 text-slate-400 hover:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none transition duration-300 shadow-sm border border-slate-200 dark:border-slate-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Search -->
                        <div class="mt-6 relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </span>
                            <input type="text" x-model="inventorySearch" @input.debounce.300ms="fetchInventory()"
                                placeholder="{{ __('Search products...') }}" 
                                class="luxury-input py-2.5 pl-12 pr-6 block w-full bg-white dark:bg-slate-800">
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto p-6 sm:p-8">
                        <div class="relative min-h-[200px]">
                            <!-- Loading State -->
                            <div x-show="inventoryLoading" 
                                 class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px]">
                                <div class="w-10 h-10 border-2 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
                            </div>

                            <!-- Inventory List -->
                            <div class="space-y-4">
                                <template x-for="item in inventoryStocks" :key="item.id">
                                    <div class="luxury-card p-4 rounded-2xl flex items-center gap-4 group/item transition-all hover:border-cyan-500/30">
                                        <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-800 flex-shrink-0 overflow-hidden border border-slate-200 dark:border-slate-700">
                                            <template x-if="item.image_url">
                                                <img :src="item.image_url" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!item.image_url">
                                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-base font-extrabold text-slate-800 dark:text-white truncate" x-text="item.product_name"></p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ __('Product ID') }}:</span>
                                                <span class="text-xs font-mono font-bold text-cyan-600 dark:text-cyan-400" x-text="item.product_id"></span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-black text-slate-800 dark:text-white" x-text="item.quantity"></p>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('In Stock') }}</p>
                                        </div>
                                    </div>
                                </template>

                                <!-- Empty State -->
                                <template x-if="inventoryStocks.length === 0 && !inventoryLoading">
                                    <div class="py-12 text-center">
                                        <p class="text-slate-400 font-bold uppercase tracking-widest">{{ __('No products found in this warehouse') }}</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Global Loading Overlay -->
    <div x-show="isLoading"
        class="absolute inset-0 z-[60] flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] rounded-3xl"
        x-cloak>
        <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
            <div
                class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin">
            </div>
            <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin"
                style="animation-duration: 3s; direction: reverse;"></div>
            <div class="relative w-8 h-8 flex items-center justify-center">
                <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            </div>
        </div>
        <p class="text-[12px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">{{
            __('Loading Data') }}...</p>
    </div>
    </div>
@endsection
