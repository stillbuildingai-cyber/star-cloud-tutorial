@extends('layouts.admin')

@section('content')
<div class="space-y-2 pb-20" x-data="{
    showModal: false, 
    showHistoryModal: false, 
    showSettingsModal: false,
    editing: false, 
    sidebarView: 'detail', 
    activeTab: '{{ request('tab', 'list') }}',
    currentCompany: {
        id: '{{ $error_company ? $error_company->id : '' }}',
        name: '{{ $error_company ? addslashes($error_company->name) : '' }}',
        code: '{{ $error_company ? addslashes($error_company->code) : '' }}',
        original_type: '{{ $error_company ? $error_company->original_type : 'lease' }}',
        current_type: '{{ $error_company ? $error_company->current_type : 'lease' }}',
        tax_id: '{{ $error_company ? addslashes($error_company->tax_id ?? '') : '' }}',
        contact_name: '{{ $error_company ? addslashes($error_company->contact_name ?? '') : '' }}',
        contact_phone: '{{ $error_company ? addslashes($error_company->contact_phone ?? '') : '' }}',
        contact_email: '{{ $error_company ? addslashes($error_company->contact_email ?? '') : '' }}',
        start_date: '{{ $error_company && $error_company->start_date ? (\Illuminate\Support\Carbon::parse($error_company->start_date)->format('Y-m-d')) : '' }}',
        end_date: '{{ $error_company && $error_company->end_date ? (\Illuminate\Support\Carbon::parse($error_company->end_date)->format('Y-m-d')) : '' }}',
        warranty_start_date: '{{ $error_company && $error_company->warranty_start_date ? (\Illuminate\Support\Carbon::parse($error_company->warranty_start_date)->format('Y-m-d')) : '' }}',
        warranty_end_date: '{{ $error_company && $error_company->warranty_end_date ? (\Illuminate\Support\Carbon::parse($error_company->warranty_end_date)->format('Y-m-d')) : '' }}',
        software_start_date: '{{ $error_company && $error_company->software_start_date ? (\Illuminate\Support\Carbon::parse($error_company->software_start_date)->format('Y-m-d')) : '' }}',
        software_end_date: '{{ $error_company && $error_company->software_end_date ? (\Illuminate\Support\Carbon::parse($error_company->software_end_date)->format('Y-m-d')) : '' }}',
        status: {{ $error_company ? $error_company->status : 1 }},
        note: '{{ $error_company ? addslashes($error_company->note ?? '') : '' }}',
        settings: {
            enable_material_code: {{ $error_company && ($error_company->settings['enable_material_code'] ?? false) ? 'true' : 'false' }},
            enable_points: {{ $error_company && ($error_company->settings['enable_points'] ?? false) ? 'true' : 'false' }},
            enable_custom_branding: {{ $error_company && ($error_company->settings['enable_custom_branding'] ?? false) ? 'true' : 'false' }},
            logo_path: '{{ $error_company ? ($error_company->settings['logo_path'] ?? '') : '' }}',
            login_bg_path: '{{ $error_company ? ($error_company->settings['login_bg_path'] ?? '') : '' }}',
            login_card_bg_path: '{{ $error_company ? ($error_company->settings['login_card_bg_path'] ?? '') : '' }}',
            login_title: '{{ old('login_title', $error_company ? ($error_company->settings['login_title'] ?? '') : '') }}'
        }
    },
    openCreateModal() {
        this.editing = false;
        this.currentCompany = { 
            id: '', name: '', code: '', original_type: 'lease', current_type: 'lease',
            tax_id: '', contact_name: '', contact_phone: '', 
            contact_email: '', start_date: '', end_date: '', 
            warranty_start_date: '', warranty_end_date: '',
            software_start_date: '', software_end_date: '',
            status: 1, note: '',
            settings: { 
                enable_material_code: false, 
                enable_points: false,
                enable_custom_branding: false,
                logo_path: '',
                login_bg_path: '',
                login_card_bg_path: '',
                login_title: ''
            }
        };
        this.showModal = true;
    },
    openEditModal(company) {
        this.editing = true;
        this.currentCompany = { 
            ...company,
            start_date: company.start_date ? company.start_date.substring(0, 10) : '',
            end_date: company.end_date ? company.end_date.substring(0, 10) : '',
            warranty_start_date: company.warranty_start_date ? company.warranty_start_date.substring(0, 10) : '',
            warranty_end_date: company.warranty_end_date ? company.warranty_end_date.substring(0, 10) : '',
            software_start_date: company.software_start_date ? company.software_start_date.substring(0, 10) : '',
            software_end_date: company.software_end_date ? company.software_end_date.substring(0, 10) : '',
            settings: {
                enable_material_code: company.settings?.enable_material_code || false,
                enable_points: company.settings?.enable_points || false,
                enable_custom_branding: company.settings?.enable_custom_branding || false,
                logo_path: company.settings?.logo_path || '',
                login_bg_path: company.settings?.login_bg_path || '',
                login_card_bg_path: company.settings?.login_card_bg_path || '',
                login_title: company.settings?.login_title || ''
            }
        };
        this.originalStatus = company.status;
        this.showModal = true;
    },
    isDeleteConfirmOpen: false,
    deleteFormAction: '',
    isStatusConfirmOpen: false,
    originalStatus: 1,
    toggleFormAction: '',
    statusToggleSource: 'edit',
    showDetail: false,
    detailCompany: {
        id: '', name: '', code: '', original_type: 'lease', current_type: 'lease',
        tax_id: '', contact_name: '', contact_phone: '', contact_email: '', 
        start_date: '', end_date: '', 
        warranty_start_date: '', warranty_end_date: '',
        software_start_date: '', software_end_date: '',
        status: 1, note: '',
        settings: { enable_material_code: false, enable_points: false, enable_custom_branding: false },
        users_count: 0, machines_count: 0,
        contracts: []
    },
    openDetailSidebar(company) {
        this.detailCompany = { 
            ...company,
            settings: {
                enable_material_code: company.settings?.enable_material_code || false,
                enable_points: company.settings?.enable_points || false,
                enable_custom_branding: company.settings?.enable_custom_branding || false
            }
        };
        this.sidebarView = 'detail';
        this.showDetail = true;
    },
    openHistorySidebar(company) {
        this.detailCompany = { 
            ...company,
            settings: {
                enable_material_code: company.settings?.enable_material_code || false,
                enable_points: company.settings?.enable_points || false,
                enable_custom_branding: company.settings?.enable_custom_branding || false
            }
        };
        this.sidebarView = 'history';
        this.showDetail = true;
    },
    openFullHistory() {
        this.showHistoryModal = true;
    },
    openHistory(company) {
        this.detailCompany = { 
            ...company,
            settings: {
                enable_material_code: company.settings?.enable_material_code || false,
                enable_points: company.settings?.enable_points || false,
                enable_custom_branding: company.settings?.enable_custom_branding || false
            }
        };
        this.showHistoryModal = true;
    },
    openSettingsModal(company) {
        this.currentCompany = { 
            ...company,
            settings: {
                enable_material_code: company.settings?.enable_material_code === true || company.settings?.enable_material_code === 1 || company.settings?.enable_material_code === '1',
                enable_points: company.settings?.enable_points === true || company.settings?.enable_points === 1 || company.settings?.enable_points === '1',
                enable_custom_branding: company.settings?.enable_custom_branding === true || company.settings?.enable_custom_branding === 1 || company.settings?.enable_custom_branding === '1'
            }
        };
        this.settingsAction = `{{ route('admin.permission.companies.index') }}/${company.id}/settings`;
        this.showSettingsModal = true;
    },

    submitConfirmedForm() {
        if (this.statusToggleSource === 'list') {
            this.$refs.statusToggleForm.submit();
        } else {
            this.$refs.companyForm.submit();
        }
    },
    init() {
        this.$watch('activeTab', (val) => {
            history.pushState({}, '', '{{ route('admin.permission.companies.index') }}?tab=' + val);
        });
        
        // Listen to popstate to handle browser back/forward correctly
        window.addEventListener('popstate', () => {
            if (window.location.search) {
                this.fetchPage(window.location.href, false);
            }
        });
    },
    isLoadingTable: false,
    async fetchPage(url, pushState = true) {
        this.isLoadingTable = true;
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (response.ok) {
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.querySelector('#ajax-content-container');
                if (newContainer) {
                    document.querySelector('#ajax-content-container').innerHTML = newContainer.innerHTML;
                    if (pushState) {
                        history.pushState({}, '', url);
                    }
                }
            }
        } catch (error) {
            console.error('AJAX fetch error:', error);
            window.location.href = url;
        } finally {
            this.isLoadingTable = false;
        }
    }
}">

    <!-- Header Area -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white tracking-tight font-display transition-all duration-300">
                {{ __('Customer Management') }}
            </h1>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">
                {{ __('Manage all tenant accounts and validity') }}
            </p>
        </div>
        <button @click="openCreateModal()" class="btn-luxury-primary whitespace-nowrap flex items-center gap-2 transition-all duration-300">
            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
            <span class="text-sm sm:text-base">{{ __('Add Customer') }}</span>
        </button>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex items-center gap-1 p-1.5 bg-slate-100 dark:bg-slate-900/50 rounded-2xl w-fit border border-slate-200/50 dark:border-slate-800/50 overflow-x-auto whitespace-nowrap custom-scrollbar" aria-label="Tabs">
        <button type="button" @click="activeTab = 'list'"
            :class="activeTab === 'list' ? 'bg-white dark:bg-slate-800 text-cyan-600 dark:text-cyan-400 shadow-sm shadow-cyan-500/10' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
            class="px-8 py-3 rounded-xl text-sm font-black uppercase tracking-widest transition-all duration-300">
            {{ __('Customer List') }}
        </button>
        <button type="button" @click="activeTab = 'settings'"
            :class="activeTab === 'settings' ? 'bg-white dark:bg-slate-800 text-cyan-600 dark:text-cyan-400 shadow-sm shadow-cyan-500/10' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
            class="px-8 py-3 rounded-xl text-sm font-black uppercase tracking-widest transition-all duration-300">
            {{ __('System Settings') }}
        </button>
        <button type="button" @click="activeTab = 'branding'"
            :class="activeTab === 'branding' ? 'bg-white dark:bg-slate-800 text-cyan-600 dark:text-cyan-400 shadow-sm shadow-cyan-500/10' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
            class="px-8 py-3 rounded-xl text-sm font-black uppercase tracking-widest transition-all duration-300">
            {{ __('Brand Styling') }}
        </button>
    </div>

    <div class="relative mt-6">
        <!-- Loading Spinner Overlay -->
        <x-luxury-spinner :show="'isLoadingTable'" />

        <div id="ajax-content-container" 
            :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': isLoadingTable }" 
            @ajax:navigate.prevent.stop="fetchPage($event.detail.url)"
            @click="if ($event.target.closest('a') && $event.target.closest('a').href && ($event.target.closest('a').href.includes('page=') || $event.target.closest('a').href.includes('tab='))) { $event.preventDefault(); fetchPage($event.target.closest('a').href); }">
    <!-- Customer List Tab -->
    <div x-show="activeTab === 'list'" x-cloak class="space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="luxury-card p-6 rounded-2xl animate-luxury-in">
            <p class="text-sm font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ __('Total Customers') }}</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white mt-2">{{ $companies->total() }}</p>
        </div>
        <div class="luxury-card p-6 rounded-2xl animate-luxury-in" style="animation-delay: 100ms">
            <p class="text-sm font-black text-emerald-500 uppercase tracking-widest">{{ __('Active') }}</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white mt-2">{{ $companies->where('status',
                1)->count() }}</p>
        </div>
        <div class="luxury-card p-6 rounded-2xl animate-luxury-in" style="animation-delay: 200ms">
            <p class="text-sm font-black text-rose-500 uppercase tracking-widest">{{ __('Expired / Disabled') }}</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white mt-2">{{ $companies->where('status',
                0)->count() }}</p>
        </div>
    </div>

    <!-- Table Section -->
    <div class="luxury-card rounded-3xl p-8 animate-luxury-in" style="animation-delay: 300ms">
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <form action="{{ route('admin.permission.companies.index') }}" method="GET" class="flex flex-wrap items-center gap-4 w-full" 
                @submit.prevent="fetchPage($el.action + '?' + new URLSearchParams(new FormData($el)).toString())">
                
                <div class="relative group w-full sm:w-72">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="w-4 h-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors stroke-[2.5]"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="py-2.5 pl-12 pr-6 block w-full luxury-input text-sm font-bold"
                        placeholder="{{ __('Search customers...') }}">
                </div>

                <div class="flex items-center p-1 bg-slate-100/50 dark:bg-slate-900/50 backdrop-blur-md rounded-2xl border border-slate-200/50 dark:border-slate-700/50 w-full sm:w-fit overflow-x-auto whitespace-nowrap">
                    <a href="{{ route('admin.permission.companies.index') }}" @click.prevent="fetchPage($event.currentTarget.href)"
                        class="px-5 py-2 text-xs font-black tracking-widest uppercase transition-all duration-300 rounded-xl {{ !request()->filled('status') ? 'bg-white dark:bg-slate-800 text-cyan-600 dark:text-cyan-400 shadow-lg shadow-cyan-500/10' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300' }}">
                        {{ __('All') }}
                    </a>
                    <a href="{{ route('admin.permission.companies.index', ['status' => 1]) }}" @click.prevent="fetchPage($event.currentTarget.href)"
                        class="px-5 py-2 text-xs font-black tracking-widest uppercase transition-all duration-300 rounded-xl {{ request('status') === '1' ? 'bg-white dark:bg-emerald-500/10 text-emerald-500 shadow-lg shadow-emerald-500/10' : 'text-slate-400 dark:text-slate-500 hover:text-emerald-500/80' }}">
                        {{ __('Active') }}
                    </a>
                    <a href="{{ route('admin.permission.companies.index', ['status' => 0]) }}" @click.prevent="fetchPage($event.currentTarget.href)"
                        class="px-5 py-2 text-xs font-black tracking-widest uppercase transition-all duration-300 rounded-xl {{ request('status') === '0' ? 'bg-white dark:bg-rose-500/10 text-rose-500 shadow-lg shadow-rose-500/10' : 'text-slate-400 dark:text-slate-500 hover:text-rose-500/80' }}">
                        {{ __('Disabled') }}
                    </a>
                </div>

                <div class="flex items-center gap-2 ml-auto sm:ml-0">
                    <button type="submit"
                        class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95"
                        title="{{ __('Search') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    <a href="{{ route('admin.permission.companies.index') }}" @click.prevent="fetchPage($event.currentTarget.href)"
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95 border border-slate-200 dark:border-slate-700"
                        title="{{ __('Reset') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </a>
                </div>

                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                <input type="hidden" name="tab" value="list">
            </form>
        </div>

        <!-- Table View (Desktop) -->
        <div class="hidden xl:block overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-y-0">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-900/30">
                        <th
                            class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                            {{ __('Customer Info') }}</th>
                        <th
                            class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">
                            {{ __('Business Type') }}</th>
                        <th
                            class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">
                            {{ __('Status') }}</th>
                        <th
                            class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">
                            {{ __('Accounts / Machines') }}</th>
                        <th
                            class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">
                            {{ __('Service Terms') }}</th>
                        <th
                            class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-right">
                            {{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                    @forelse($companies as $company)
                    <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                        <td class="px-6 py-6">
                            <div class="flex items-center gap-x-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{
                                            $company->name }}</span>
                                    </div>
                                    <span
                                        class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400 mt-0.5 tracking-widest uppercase">{{
                                        $company->code }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <div class="flex items-center gap-2 min-w-[100px] justify-center">
                                    <span class="text-[12px] font-bold text-slate-400 uppercase tracking-widest w-8 text-right">{{ __('Original:') }}</span>
                                    <span class="px-2 py-0.5 rounded text-xs font-bold {{ $company->original_type === 'buyout' ? 'bg-amber-500/10 text-amber-600' : 'bg-blue-500/10 text-blue-600' }} uppercase tracking-wider">
                                        {{ __($company->original_type === 'buyout' ? 'Buyout' : 'Lease') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 min-w-[100px] justify-center">
                                    <span class="text-[12px] font-bold text-slate-400 uppercase tracking-widest w-8 text-right">{{ __('Current:') }}</span>
                                    <span class="px-2 py-0.5 rounded text-xs font-bold {{ $company->current_type === 'buyout' ? 'bg-amber-500/10 text-amber-600 border border-amber-500/20' : 'bg-blue-500/10 text-blue-600 border border-blue-500/20' }} uppercase tracking-wider">
                                        {{ __($company->current_type === 'buyout' ? 'Buyout' : 'Lease') }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 text-center py-6">
                            @if($company->status)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[12px] font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 tracking-widest uppercase">
                                {{ __('Active') }}
                            </span>
                            @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[12px] font-black bg-rose-500/10 text-rose-500 border border-rose-500/20 tracking-widest uppercase">
                                {{ __('Disabled') }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-6 text-center">
                            <div class="flex items-center justify-center gap-x-3">
                                <div class="flex flex-col items-center">
                                    <span class="text-base font-extrabold text-slate-800 dark:text-white">{{ $company->users_count }}</span>
                                    <span class="text-[12px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ __('Users') }}</span>
                                </div>
                                <div class="w-px h-6 bg-slate-100 dark:bg-slate-800"></div>
                                <div class="flex flex-col items-center">
                                    <span class="text-base font-extrabold text-slate-800 dark:text-white">{{ $company->machines_count }}</span>
                                    <span class="text-[12px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ __('Machines') }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 border-b border-slate-50 dark:border-slate-800/50">
                            <div class="flex flex-col gap-2.5 min-w-[200px] mx-auto w-fit">
                                <!-- Contract Period (Only for Lease) -->
                                @if($company->current_type === 'lease')
                                <div class="flex items-center gap-3 group/term">
                                    <span class="px-2.5 py-1 rounded-md bg-blue-500/10 text-blue-600 text-[12px] font-black border border-blue-500/20 group-hover/term:bg-blue-500 group-hover/term:text-white transition-all tracking-wider whitespace-nowrap min-w-[42px] text-center">
                                        {{ __('Contract') }}
                                    </span>
                                    <div class="flex items-center gap-1.5 font-mono text-[12px] font-bold">
                                        <span class="text-slate-400">{{ $company->start_date ? $company->start_date->format('Y-m-d') : '--' }}</span>
                                        <span class="text-slate-300">~</span>
                                        <span class="{{ $company->end_date && $company->end_date->isPast() ? 'text-rose-500 shadow-sm shadow-rose-500/10' : 'text-slate-600 dark:text-slate-300' }}">
                                            {{ $company->end_date ? $company->end_date->format('Y-m-d') : __('Permanent') }}
                                        </span>
                                    </div>
                                </div>
                                @endif

                                @if($company->current_type === 'buyout')
                                <!-- Warranty Period -->
                                <div class="flex items-center gap-3 group/term">
                                    <span class="px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-600 text-[12px] font-black border border-amber-500/20 group-hover/term:bg-amber-500 group-hover/term:text-white transition-all tracking-wider whitespace-nowrap min-w-[42px] text-center">
                                        {{ __('Warranty') }}
                                    </span>
                                    <div class="flex items-center gap-1.5 font-mono text-[12px] font-bold">
                                        <span class="text-slate-400">{{ $company->warranty_start_date ? $company->warranty_start_date->format('Y-m-d') : '--' }}</span>
                                        <span class="text-slate-300">~</span>
                                        <span class="{{ $company->warranty_end_date && $company->warranty_end_date->isPast() ? 'text-rose-500 shadow-sm shadow-rose-500/10' : 'text-slate-500 dark:text-slate-400' }}">
                                            {{ $company->warranty_end_date ? $company->warranty_end_date->format('Y-m-d') : '--' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Software Period -->
                                <div class="flex items-center gap-3 group/term">
                                    <span class="px-2.5 py-1 rounded-md bg-purple-500/10 text-purple-600 text-[12px] font-black border border-purple-500/20 group-hover/term:bg-purple-500 group-hover/term:text-white transition-all tracking-wider whitespace-nowrap min-w-[42px] text-center">
                                        {{ __('Software') }}
                                    </span>
                                    <div class="flex items-center gap-1.5 font-mono text-[12px] font-bold">
                                        <span class="text-slate-400">{{ $company->software_start_date ? $company->software_start_date->format('Y-m-d') : '--' }}</span>
                                        <span class="text-slate-300">~</span>
                                        <span class="{{ $company->software_end_date && $company->software_end_date->isPast() ? 'text-rose-500 shadow-sm shadow-rose-500/10' : 'text-slate-500 dark:text-slate-400' }}">
                                            {{ $company->software_end_date ? $company->software_end_date->format('Y-m-d') : '--' }}
                                        </span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-6 text-right">
                            <div class="flex items-center justify-end gap-x-2">
                                @if($company->status)
                                <button type="button"
                                    @click="toggleFormAction = '{{ route('admin.permission.companies.status.toggle', $company->id) }}'; statusToggleSource = 'list'; isStatusConfirmOpen = true"
                                    class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-amber-500 hover:bg-amber-500/5 transition-all border border-transparent hover:border-amber-500/20"
                                    title="{{ __('Disable') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                    </svg>
                                </button>
                                @else
                                <button type="button"
                                    @click="toggleFormAction = '{{ route('admin.permission.companies.status.toggle', $company->id) }}'; $nextTick(() => $refs.statusToggleForm.submit())"
                                    class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all border border-transparent hover:border-emerald-500/20"
                                    title="{{ __('Enable') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z" />
                                    </svg>
                                </button>
                                @endif
                                <button @click='statusToggleSource = "edit"; openEditModal({{ json_encode($company) }})'
                                    class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-600 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                    title="{{ __('Edit') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteFormAction = '{{ route('admin.permission.companies.destroy', $company->id) }}'; isDeleteConfirmOpen = true"
                                    class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all border border-transparent hover:border-rose-500/20"
                                    title="{{ __('Delete') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-3.38a2.25 2.25 0 00-2.25-2.25h-3.51a2.25 2.25 0 00-2.25 2.25v3.38" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-20 h-20 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-4">
                                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <p class="text-slate-400 font-bold tracking-widest uppercase text-sm">{{ __('No Customers Found') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Mobile View (Cards) -->
        <div class="xl:hidden space-y-4">
            @forelse($companies as $company)
            <div class="luxury-card p-6 rounded-3xl border border-slate-100 dark:border-slate-800/50 hover:shadow-xl transition-all duration-300">
                <div class="flex justify-between items-start mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center">
                            <svg class="w-6 h-6 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-black text-slate-800 dark:text-white leading-tight">{{ $company->name }}</h4>
                            <p class="text-xs font-mono font-bold text-slate-500 tracking-widest uppercase mt-0.5">{{ $company->code }}</p>
                        </div>
                    </div>
                    @if($company->status)
                    <span class="px-3 py-1 rounded-full text-[10px] font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 tracking-widest uppercase">{{ __('Active') }}</span>
                    @else
                    <span class="px-3 py-1 rounded-full text-[10px] font-black bg-rose-500/10 text-rose-500 border border-rose-500/20 tracking-widest uppercase">{{ __('Disabled') }}</span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="p-3 bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Business') }}</p>
                        <p class="text-xs font-bold {{ $company->current_type === 'buyout' ? 'text-amber-600' : 'text-blue-600' }} uppercase tracking-wider">
                            {{ __($company->current_type === 'buyout' ? 'Buyout' : 'Lease') }}
                        </p>
                    </div>
                    <div class="p-3 bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Scale') }}</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase">
                            {{ $company->users_count }}U / {{ $company->machines_count }}M
                        </p>
                    </div>
                </div>

                <div class="space-y-2.5 mb-6">
                    @if($company->current_type === 'lease')
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-600 text-[10px] font-black border border-blue-500/20 uppercase tracking-wider min-w-[55px] text-center">{{ __('Contract') }}</span>
                        <span class="text-[11px] font-mono font-bold text-slate-600 dark:text-slate-300">
                            {{ $company->start_date ? $company->start_date->format('Y-m-d') : '--' }} ~ {{ $company->end_date ? $company->end_date->format('Y-m-d') : __('Permanent') }}
                        </span>
                    </div>
                    @endif

                    @if($company->current_type === 'buyout')
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 rounded bg-amber-500/10 text-amber-600 text-[10px] font-black border border-amber-500/20 uppercase tracking-wider min-w-[55px] text-center">{{ __('Warranty') }}</span>
                        <span class="text-[11px] font-mono font-bold text-slate-600 dark:text-slate-300">
                            {{ $company->warranty_start_date ? $company->warranty_start_date->format('Y-m-d') : '--' }} ~ {{ $company->warranty_end_date ? $company->warranty_end_date->format('Y-m-d') : '--' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 rounded bg-purple-500/10 text-purple-600 text-[10px] font-black border border-purple-500/20 uppercase tracking-wider min-w-[55px] text-center">{{ __('Software') }}</span>
                        <span class="text-[11px] font-mono font-bold text-slate-600 dark:text-slate-300">
                            {{ $company->software_start_date ? $company->software_start_date->format('Y-m-d') : '--' }} ~ {{ $company->software_end_date ? $company->software_end_date->format('Y-m-d') : '--' }}
                        </span>
                    </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    @if($company->status)
                    <button type="button"
                        @click="toggleFormAction = '{{ route('admin.permission.companies.status.toggle', $company->id) }}'; statusToggleSource = 'list'; isStatusConfirmOpen = true"
                        class="px-4 py-2 rounded-xl bg-amber-500/5 text-amber-500 text-xs font-black uppercase tracking-widest border border-amber-500/20 hover:bg-amber-500 hover:text-white transition-all">
                        {{ __('Disable') }}
                    </button>
                    @else
                    <button type="button"
                        @click="toggleFormAction = '{{ route('admin.permission.companies.status.toggle', $company->id) }}'; $nextTick(() => $refs.statusToggleForm.submit())"
                        class="px-4 py-2 rounded-xl bg-emerald-500/5 text-emerald-500 text-xs font-black uppercase tracking-widest border border-emerald-500/20 hover:bg-emerald-500 hover:text-white transition-all">
                        {{ __('Enable') }}
                    </button>
                    @endif
                    <button @click='statusToggleSource = "edit"; openEditModal({{ json_encode($company) }})'
                        class="px-4 py-2 rounded-xl bg-cyan-500/5 text-cyan-500 text-xs font-black uppercase tracking-widest border border-cyan-500/20 hover:bg-cyan-500 hover:text-white transition-all">
                        {{ __('Edit') }}
                    </button>
                    <button @click="deleteFormAction = '{{ route('admin.permission.companies.destroy', $company->id) }}'; isDeleteConfirmOpen = true"
                        class="p-2 rounded-xl bg-rose-500/5 text-rose-500 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-3.38a2.25 2.25 0 00-2.25-2.25h-3.51a2.25 2.25 0 00-2.25 2.25v3.38" />
                        </svg>
                    </button>
                </div>
            </div>
            @empty
            <div class="py-20 text-center bg-slate-50 dark:bg-slate-900/50 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-800">
                <p class="text-slate-400 font-bold uppercase tracking-widest text-sm">{{ __('No Customers Found') }}</p>
            </div>
            @endforelse
        </div>

        <div class="mt-8 border-t border-slate-100 dark:border-slate-800 pt-6">
            {{ $companies->links('vendor.pagination.luxury') }}
        </div>
        </div>
    </div>

    <!-- System Settings Tab -->
    <div x-show="activeTab === 'settings'" x-cloak class="space-y-3">
        <div class="luxury-card rounded-3xl p-8 animate-luxury-in">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-6">
                <form action="{{ route('admin.permission.companies.index') }}" method="GET" class="relative group" @submit.prevent="fetchPage($el.action + '?' + new URLSearchParams(new FormData($el)).toString())">
                    <input type="hidden" name="tab" value="settings">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" class="py-2.5 pl-12 pr-6 block w-64 luxury-input" placeholder="{{ __('Search customers...') }}">
                </form>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-separate border-spacing-y-0">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/30">
                            <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">{{ __('Customer Info') }}</th>
                            <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">{{ __('Enabled Features') }}</th>
                            <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                        @forelse($companies as $company)
                        <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                            <td class="px-6 py-6 w-1/3">
                                <div class="flex items-center gap-x-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all">
                                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ $company->name }}</span>
                                        </div>
                                        <span class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400 mt-0.5 tracking-widest uppercase">{{ $company->code }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex flex-wrap gap-2">
                                    @php $settings = $company->settings ?? []; @endphp
                                    
                                    @if(empty($settings) || !in_array(true, array_map(fn($v) => (bool)$v, is_array($settings) ? $settings : ($settings->toArray() ?? [])), true))
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-500/10 text-slate-500 dark:text-slate-400 text-[13px] font-bold uppercase tracking-wider">{{ __('None') }}</span>
                                    @else
                                        @if($settings['enable_material_code'] ?? false)
                                            <span class="px-2.5 py-1 rounded-lg bg-cyan-500/10 text-cyan-500 dark:text-cyan-400 text-[13px] font-bold uppercase tracking-wider">{{ __('Material Code') }}</span>
                                        @endif
                                        @if($settings['enable_points'] ?? false)
                                            <span class="px-2.5 py-1 rounded-lg bg-cyan-500/10 text-cyan-500 dark:text-cyan-400 text-[13px] font-bold uppercase tracking-wider">{{ __('Points') }}</span>
                                        @endif
                                        @if($settings['enable_custom_branding'] ?? false)
                                            <span class="px-2.5 py-1 rounded-lg bg-cyan-500/10 text-cyan-500 dark:text-cyan-400 text-[13px] font-bold uppercase tracking-wider">{{ __('Custom Branding') }}</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-6 text-right">
                                <button type="button" @click='openSettingsModal({{ json_encode($company) }})' 
                                    class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20" 
                                    title="{{ __('Edit Settings') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-300 mb-4">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <p class="text-slate-400 font-bold">{{ __('No customers found') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-8 border-t border-slate-100 dark:border-slate-800 pt-6">
                {{ $companies->appends(['tab' => 'settings', 'search' => request('search')])->links('vendor.pagination.luxury') }}
            </div>
        </div>
    </div>

    <!-- Brand Styling Tab -->
    <div x-show="activeTab === 'branding'" x-cloak class="space-y-3">
        <div class="luxury-card rounded-3xl p-8 animate-luxury-in">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-6">
                <form action="{{ route('admin.permission.companies.index') }}" method="GET" class="relative group" @submit.prevent="fetchPage($el.action + '?' + new URLSearchParams(new FormData($el)).toString())">
                    <input type="hidden" name="tab" value="branding">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" class="py-2.5 pl-12 pr-6 block w-64 luxury-input" placeholder="{{ __('Search customers...') }}">
                </form>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-separate border-spacing-y-0">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/30">
                            <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">{{ __('Customer Info') }}</th>
                            <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Enable Status') }}</th>
                            <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">{{ __('Current Brand Visual') }}</th>
                            <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                        @forelse($companies as $company)
                        @php $settings = $company->settings ?? []; @endphp
                        <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                            <td class="px-6 py-6 w-1/4">
                                <div class="flex items-center gap-x-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all">
                                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ $company->name }}</span>
                                        <span class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 mt-0.5 tracking-widest uppercase">{{ $company->code }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 text-center">
                                @if(filter_var($settings['enable_custom_branding'] ?? false, FILTER_VALIDATE_BOOLEAN))
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 tracking-widest uppercase">
                                        {{ __('Enabled') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-slate-100 dark:bg-slate-800 text-slate-400 tracking-widest uppercase">
                                        {{ __('Disabled') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/50 dark:border-slate-700/50 flex items-center justify-center overflow-hidden shrink-0">
                                        @if(!empty($settings['logo_path']))
                                            <img src="{{ Storage::disk('public')->url($settings['logo_path']) }}" class="max-w-full max-h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="hidden w-full h-full items-center justify-center bg-slate-50 dark:bg-slate-800/50">
                                                <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="flex w-full h-full items-center justify-center bg-slate-50/50 dark:bg-slate-900/30">
                                                <svg class="w-5 h-5 text-slate-400 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Welcome Title') }}</span>
                                        <span class="text-sm font-extrabold text-slate-700 dark:text-slate-200 truncate max-w-[200px] mt-0.5" title="{{ $settings['login_title'] ?? __('System Default') }}">
                                            {{ $settings['login_title'] ?? __('System Default') }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 text-right">
                                @if(filter_var($settings['enable_custom_branding'] ?? false, FILTER_VALIDATE_BOOLEAN))
                                    <a href="{{ route('admin.permission.companies.branding.edit', $company->id) }}"
                                        class="px-4 py-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 text-xs font-black tracking-widest uppercase shadow-lg shadow-cyan-500/10 active:scale-95 transition-all inline-block"
                                        title="{{ __('Customize Brand') }}">
                                        {{ __('Setup Brand') }}
                                    </a>
                                @else
                                    <button type="button" @click='openSettingsModal({{ json_encode($company) }})'
                                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 hover:text-slate-600 text-xs font-black tracking-widest uppercase transition-all"
                                        title="{{ __('Enable first') }}">
                                        {{ __('Go Enable') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-300 mb-4">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <p class="text-slate-400 font-bold">{{ __('No customers found') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-8 border-t border-slate-100 dark:border-slate-800 pt-6">
                {{ $companies->appends(['tab' => 'branding', 'search' => request('search')])->links('vendor.pagination.luxury') }}
            </div>
        </div>
    </div>
    </div>
    </div>

    <!-- Create/Edit Modal (Simplified with standard Blade form for reliability) -->
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
                class="inline-block px-8 py-10 overflow-visible text-left align-bottom transition-all transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">

                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight"
                        x-text="editing ? '{{ __('Edit Customer') }}' : '{{ __('Add Customer') }}'"></h3>
                    <button @click="showModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form
                    x-ref="companyForm"
                    :action="editing ? '{{ url('admin/permission/companies') }}/' + currentCompany.id : '{{ route('admin.permission.companies.store') }}'"
                    method="POST" class="space-y-6"
                    @submit.prevent="
                        if (editing && currentCompany.status == '0' && originalStatus == '1') {
                            isStatusConfirmOpen = true;
                        } else {
                            $el.submit();
                        }
                    ">
                    @csrf
                    <input type="hidden" name="_method" :value="editing ? 'PUT' : 'POST'">

                    <!-- Profile Section -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3">
                            <div class="h-6 w-1 bg-cyan-500 rounded-full"></div>
                            <h4 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest">{{
                                __('Company Information') }}</h4>
                        </div>

                        <!-- Business Type Selector -->
                        <div class="p-4 rounded-2xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 space-y-4">
                            <label class="text-[12px] font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Business Type') }}
                                <span class="text-rose-500 ml-0.5">*</span>
                            </label>
                            
                            <!-- 新增模式：顯示切換按鈕 -->
                            <div x-show="!editing" class="flex p-1.5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 w-fit">
                                <button type="button" @click="currentCompany.original_type = 'lease'" 
                                    :class="currentCompany.original_type === 'lease' ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/20' : 'text-slate-400 hover:text-slate-600'"
                                    class="px-4 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">
                                    {{ __('Lease') }}
                                </button>
                                <button type="button" @click="currentCompany.original_type = 'buyout'" 
                                    :class="currentCompany.original_type === 'buyout' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-600'"
                                    class="px-4 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">
                                    {{ __('Buyout') }}
                                </button>
                                <input type="hidden" name="original_type" :value="currentCompany.original_type">
                            </div>

                            <!-- 編輯模式：顯示原始類型固定值 + 當前類型切換 -->
                            <div x-show="editing" class="flex flex-col gap-5">
                                <div class="flex items-center gap-4">
                                    <span class="text-[12px] font-black text-slate-400 uppercase tracking-widest">{{ __('Original Type') }}: <span class="text-rose-500 ml-0.5">*</span></span>
                                    <span class="px-4 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-bold uppercase tracking-widest text-slate-600 dark:text-slate-400" x-text="currentCompany.original_type === 'buyout' ? '{{ __('Buyout') }}' : '{{ __('Lease') }}'"></span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-[12px] font-black text-slate-400 uppercase tracking-widest">{{ __('Current Type') }}: <span class="text-rose-500 ml-0.5">*</span></span>
                                    <div class="flex p-1.5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 w-fit">
                                        <button type="button" @click="currentCompany.current_type = 'lease'" 
                                            :class="currentCompany.current_type === 'lease' ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/20' : 'text-slate-400 hover:text-slate-600'"
                                            class="px-4 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">
                                            {{ __('Lease') }}
                                        </button>
                                        <button type="button" @click="currentCompany.current_type = 'buyout'" 
                                            :class="currentCompany.current_type === 'buyout' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-600'"
                                            class="px-4 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">
                                            {{ __('Buyout') }}
                                        </button>
                                        <input type="hidden" name="current_type" :value="currentCompany.current_type">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Company Name') }} <span class="text-rose-500 ml-0.5">*</span></label>
                                <input type="text" name="name" x-model="currentCompany.name" required
                                    class="luxury-input w-full" placeholder="{{ __('e.g. Taiwan Star') }}">
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Company Code') }}</label>
                                <input type="text" name="code" x-model="currentCompany.code"
                                    class="luxury-input w-full" placeholder="{{ __('e.g. TWSTAR') }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Tax ID (Optional)') }}</label>
                                <input type="text" name="tax_id" x-model="currentCompany.tax_id"
                                    class="luxury-input w-full">
                            </div>
                            <div class="grid grid-cols-2 gap-3" x-show="currentCompany.current_type === 'lease'">
                                <div class="space-y-2">
                                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                        __('Start Date') }}</label>
                                    <input type="date" name="start_date" x-model="currentCompany.start_date"
                                        class="luxury-input w-full px-2">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                        __('End Date') }}</label>
                                    <input type="date" name="end_date" x-model="currentCompany.end_date"
                                        class="luxury-input w-full px-2">
                                </div>
                            </div>
                        </div>

                        <!-- Buyout Specific Dates -->
                        <div x-show="currentCompany.current_type === 'buyout'" x-transition class="space-y-4 pt-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-2">
                                        <label class="text-[12px] font-black text-amber-600 uppercase tracking-widest pl-1">{{ __('Warranty Start') }}</label>
                                        <input type="date" name="warranty_start_date" x-model="currentCompany.warranty_start_date"
                                            class="luxury-input w-full px-2 border-amber-100 dark:border-amber-900/30">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[12px] font-black text-amber-600 uppercase tracking-widest pl-1">{{ __('Warranty End') }}</label>
                                        <input type="date" name="warranty_end_date" x-model="currentCompany.warranty_end_date"
                                            class="luxury-input w-full px-2 border-amber-100 dark:border-amber-900/30">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-2">
                                        <label class="text-[12px] font-black text-indigo-600 uppercase tracking-widest pl-1">{{ __('Software Start') }}</label>
                                        <input type="date" name="software_start_date" x-model="currentCompany.software_start_date"
                                            class="luxury-input w-full px-2 border-indigo-100 dark:border-indigo-900/30">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[12px] font-black text-indigo-600 uppercase tracking-widest pl-1">{{ __('Software End') }}</label>
                                        <input type="date" name="software_end_date" x-model="currentCompany.software_end_date"
                                            class="luxury-input w-full px-2 border-indigo-100 dark:border-indigo-900/30">
                                    </div>
                                </div>
                            </div>
                            <!-- Keep hidden start_date for buyout as well if needed by backend -->
                            <input type="hidden" name="start_date" :value="currentCompany.start_date" x-if="currentCompany.current_type === 'buyout' && !currentCompany.start_date">
                        </div>
                    </div>

                    <!-- Admin Account Section (Account Creation) - Only show when creating -->
                    <div x-show="!editing" class="space-y-6 pt-6 border-t border-slate-100 dark:border-slate-800 relative z-20">
                        <div class="flex items-center gap-3">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest">
                                {{ __('Initial Admin Account') }}
                                <span class="text-[12px] text-slate-400 font-normal normal-case tracking-normal ml-2">({{ __('Optional') }})</span>
                            </h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Username') }}</label>
                                <input type="text" name="admin_username" class="luxury-input w-full"
                                    placeholder="{{ __('Enter login ID') }}">
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Password') }}</label>
                                <div x-data="{ show: false }" class="relative items-center">
                                    <input :type="show ? 'text' : 'password'" name="admin_password" class="luxury-input w-full pr-12"
                                        placeholder="{{ __('Min 8 characters') }}">
                                    <button type="button" @click="show = !show" 
                                        class="absolute inset-y-0 end-0 flex items-center z-20 px-4 cursor-pointer text-slate-400 hover:text-cyan-500 transition-colors">
                                        <svg x-show="!show" class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Admin Name') }}</label>
                                <input type="text" name="admin_name" class="luxury-input w-full" placeholder="{{ __('Admin display name') }}">
                            </div>
                            <div class="space-y-2 relative z-[30]">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Initial Role') }}</label>
                                <x-searchable-select 
                                    name="admin_role" 
                                    :hasSearch="false"
                                >
                                    @foreach($template_roles as $role)
                                        <option value="{{ $role->name }}" {{ $role->name == '客戶管理員角色模板' ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </x-searchable-select>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Section -->
                    <div class="space-y-6 pt-6 border-t border-slate-100 dark:border-slate-800 relative z-20">
                        <div class="flex items-center gap-3">
                            <div class="h-6 w-1 bg-amber-500 rounded-full"></div>
                            <h4 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest">{{
                                __('Contact & Details') }}</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Contact Name') }}</label>
                                <input type="text" name="contact_name" x-model="currentCompany.contact_name"
                                    class="luxury-input w-full">
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Contact Phone') }}</label>
                                <input type="text" name="contact_phone" x-model="currentCompany.contact_phone"
                                    class="luxury-input w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2 relative z-[20]">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Status') }}</label>
                                <x-searchable-select 
                                    id="company-status" 
                                    name="status" 
                                    x-model="currentCompany.status" 
                                    :hasSearch="false"
                                >
                                    <option value="1">{{ __('Active') }}</option>
                                    <option value="0">{{ __('Disabled') }}</option>
                                </x-searchable-select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Notes') }}</label>
                                <textarea name="note" x-model="currentCompany.note" rows="2"
                                    class="luxury-input w-full h-[46px] min-h-[46px] py-3"></textarea>
                            </div>
                        </div>
                    </div>



                    <div class="flex justify-end gap-x-4 pt-8">
                        <button type="button" @click="showModal = false" class="btn-luxury-ghost px-8">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn-luxury-primary px-12">
                            <span x-text="editing ? '{{ __('Update') }}' : '{{ __('Create') }}'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- System Settings Edit Modal -->
    <div x-show="showSettingsModal" class="fixed inset-0 z-[100] flex items-center justify-center" x-cloak>
        <div x-show="showSettingsModal" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showSettingsModal = false"></div>

        <div x-show="showSettingsModal" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 w-full max-w-3xl mx-4 overflow-hidden z-[101] flex flex-col max-h-[90vh]">
            
            <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                <div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{ __('Edit System Settings') }}</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1" x-text="currentCompany.name"></p>
                </div>
                <button type="button" @click="showSettingsModal = false" class="text-slate-400 hover:text-slate-500 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl p-2 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-8 overflow-y-auto custom-scrollbar flex-1">
                <form :action="settingsAction" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="space-y-3">
                        <!-- 商品與系統設定 (Additional Features) -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <div class="md:col-span-1">
                                <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Goods & System Settings') }}</h4>
                            </div>
                            <div class="md:col-span-3 flex flex-wrap gap-x-8 gap-y-4">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative inline-flex items-center">
                                        <input type="hidden" name="settings[enable_material_code]" value="0">
                                        <input type="checkbox" name="settings[enable_material_code]" value="1" x-model="currentCompany.settings.enable_material_code" class="peer sr-only">
                                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                    </div>
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ __('Display Material Code') }}</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative inline-flex items-center">
                                        <input type="hidden" name="settings[enable_points]" value="0">
                                        <input type="checkbox" name="settings[enable_points]" value="1" x-model="currentCompany.settings.enable_points" class="peer sr-only">
                                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                    </div>
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ __('Enable Points Mechanism') }}</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative inline-flex items-center">
                                        <input type="hidden" name="settings[enable_custom_branding]" value="0">
                                        <input type="checkbox" name="settings[enable_custom_branding]" value="1" x-model="currentCompany.settings.enable_custom_branding" class="peer sr-only">
                                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                    </div>
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ __('Enable Custom Branding') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-x-4 pt-8 mt-6 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showSettingsModal = false" class="btn-luxury-ghost px-8">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn-luxury-primary px-12">
                            <span>{{ __('Update Settings') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <x-delete-confirm-modal :message="__('Are you sure to delete this customer?')" />
    <x-status-confirm-modal :title="__('Disable Customer Confirmation')" :message="__('Disabling this customer will also disable all accounts under this customer. Are you sure you want to continue?')" />

    <form x-ref="statusToggleForm" :action="toggleFormAction" method="POST" class="hidden">
        @csrf
        @method('PATCH')
    </form>

    <!-- Details Sidebar -->
    <template x-teleport="body">
        <div x-show="showDetail" class="fixed inset-0 z-[150]" x-cloak>
            <!-- Overlay -->
            <div x-show="showDetail" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showDetail = false"
                 class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>

            <!-- Sidebar Content -->
            <div class="fixed inset-y-0 right-0 max-w-full flex">
                <div class="w-screen max-w-md" 
                     x-show="showDetail"
                     x-transition:enter="transform transition ease-in-out duration-500"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-500"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full">
                    
                    <div class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-100 dark:border-slate-800">
                        <!-- Header -->
                        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900 sticky top-0 z-10">
                            <div>
                                <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight" x-text="sidebarView === 'history' ? '{{ __('Contract History Detail') }}' : '{{ __('Customer Details') }}'"></h2>
                                <div class="flex items-center gap-2 mt-1">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]" x-text="detailCompany.name"></p>
                                    <span class="text-xs font-mono font-black text-cyan-500 px-1.5 py-0.5 bg-cyan-500/10 rounded" x-text="detailCompany.code"></span>
                                </div>
                            </div>
                            <button @click="showDetail = false" class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-slate-400">
                                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="flex-1 overflow-y-auto px-8 pt-4 pb-8 space-y-5 custom-scrollbar">
                          <!-- Tab Switcher -->
                          <div class="flex gap-1 p-1 bg-slate-100 dark:bg-slate-800/60 rounded-xl">
                              <button @click="sidebarView = 'detail'" 
                                  :class="sidebarView === 'detail' ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                                  class="flex-1 py-2.5 px-3 text-xs font-black uppercase tracking-[0.15em] rounded-lg transition-all">{{ __('Customer Details') }}</button>
                              <button @click="sidebarView = 'history'" 
                                  :class="sidebarView === 'history' ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                                  class="flex-1 py-2.5 px-3 text-xs font-black uppercase tracking-[0.15em] rounded-lg transition-all">{{ __('Contract History Detail') }}</button>
                          </div>

                          <!-- Detail View -->
                          <div x-show="sidebarView === 'detail'" class="space-y-8">
                            <!-- Validity & Status Section -->
                            <section class="space-y-4">
                                <h3 class="text-xs font-black text-emerald-500 uppercase tracking-[0.3em]">{{ __('Account Status') }}</h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                                        <div>
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Current Status') }}</span>
                                            <template x-if="detailCompany.status">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></span>
                                                    <span class="text-sm font-black text-emerald-500 uppercase tracking-widest">{{ __('Active') }}</span>
                                                </div>
                                            </template>
                                            <template x-if="!detailCompany.status">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.4)]"></span>
                                                    <span class="text-sm font-black text-rose-500 uppercase tracking-widest">{{ __('Disabled') }}</span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    
                                    <!-- Business Type -->
                                    <div class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 space-y-4">
                                        <div class="flex justify-between items-start pb-3 border-b border-slate-100/50 dark:border-slate-800/50">
                                            <div>
                                                <h4 class="text-[12px] font-black text-indigo-500 uppercase tracking-[0.2em]">{{ __('Contract Model') }}</h4>
                                            </div>
                                            <span :class="detailCompany.current_type === 'lease' ? 'text-blue-500 bg-blue-500/10' : 'text-amber-500 bg-amber-500/10'" 
                                                class="text-[12px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg">
                                                <span x-text="detailCompany.current_type === 'lease' ? '{{ __('Lease') }}' : '{{ __('Buyout') }}'"></span>
                                            </span>
                                        </div>
                                        
                                        <!-- Lease Info Display -->
                                        <template x-if="detailCompany.current_type === 'lease'">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-1">
                                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ __('Start Date') }}</p>
                                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200" x-text="detailCompany.start_date?.substring(0, 10) || '-'"></p>
                                                </div>
                                                <div class="space-y-1">
                                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ __('End Date') }}</p>
                                                    <p class="text-xs font-black text-slate-800 dark:text-white" x-text="detailCompany.end_date?.substring(0, 10) || '{{ __('Unlimited') }}'"></p>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Buyout Info Display -->
                                        <template x-if="detailCompany.current_type === 'buyout'">
                                            <div class="space-y-4">
                                                <div class="grid grid-cols-1 gap-3">
                                                    <div class="p-3 bg-amber-50/30 dark:bg-amber-500/5 rounded-xl border border-amber-100/50 dark:border-amber-500/10">
                                                        <p class="text-[9px] font-black text-amber-600 uppercase tracking-widest">{{ __('Warranty Service') }}</p>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <span class="text-xs font-black text-slate-700 dark:text-slate-200" x-text="detailCompany.warranty_start_date?.substring(0, 10) || '-'"></span>
                                                            <span class="text-slate-400">~</span>
                                                            <span class="text-xs font-black text-slate-800 dark:text-white" x-text="detailCompany.warranty_end_date?.substring(0, 10) || '-'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="p-3 bg-indigo-50/30 dark:bg-indigo-500/5 rounded-xl border border-indigo-100/50 dark:border-indigo-500/10">
                                                        <p class="text-[9px] font-black text-indigo-600 uppercase tracking-widest">{{ __('Software Service') }}</p>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <span class="text-xs font-black text-slate-700 dark:text-slate-200" x-text="detailCompany.software_start_date?.substring(0, 10) || '-'"></span>
                                                            <span class="text-slate-400">~</span>
                                                            <span class="text-xs font-black text-slate-800 dark:text-white" x-text="detailCompany.software_end_date?.substring(0, 10) || '-'"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </section>

                            <!-- Feature Settings Section -->
                            <section class="space-y-4">
                                <h3 class="text-xs font-black text-indigo-500 uppercase tracking-[0.3em]">{{ __('Feature Settings') }}</h3>
                                <div class="space-y-3">
                                    <!-- Material Code -->
                                    <div class="bg-white dark:bg-slate-800/20 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/50 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01" /></svg>
                                            </div>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('Material Code') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-widest"
                                             :class="detailCompany.settings?.enable_material_code ? 'bg-emerald-500/10 text-emerald-500' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                                            <span x-text="detailCompany.settings?.enable_material_code ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}'"></span>
                                        </div>
                                    </div>
                                    <!-- Points Rule -->
                                    <div class="bg-white dark:bg-slate-800/20 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/50 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('Points Rule') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-widest"
                                             :class="detailCompany.settings?.enable_points ? 'bg-emerald-500/10 text-emerald-500' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                                             <span x-text="detailCompany.settings?.enable_points ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}'"></span>
                                        </div>
                                    </div>
                                    <!-- Custom Branding -->
                                    <div class="bg-white dark:bg-slate-800/20 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/50 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 rounded-lg bg-cyan-50 dark:bg-cyan-500/10 text-cyan-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-1.242 2.25 2.25 0 00-.22-2.131zm0 0a3 3 0 015.78-1.128 2.25 2.25 0 012.4-2.245 4.5 4.5 0 00-8.4 1.242 2.25 2.25 0 00.22 2.13z" />
                                                </svg>
                                            </div>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('Custom Branding') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-widest"
                                             :class="detailCompany.settings?.enable_custom_branding ? 'bg-emerald-500/10 text-emerald-500' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                                             <span x-text="detailCompany.settings?.enable_custom_branding ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}'"></span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Basic Info Section -->
                            <section class="space-y-4">
                                <h3 class="text-xs font-black text-cyan-500 uppercase tracking-[0.3em]">{{ __('Basic Information') }}</h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Tax ID') }}</span>
                                        <div class="text-sm font-mono font-bold text-slate-700 dark:text-slate-300" x-text="detailCompany.tax_id || '--'"></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Contact Name') }}</span>
                                            <div class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="detailCompany.contact_name || '--'"></div>
                                        </div>
                                        <div class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Phone') }}</span>
                                            <div class="text-sm font-mono font-bold text-slate-700 dark:text-slate-300" x-text="detailCompany.contact_phone || '--'"></div>
                                        </div>
                                    </div>
                                    <div class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Email') }}</span>
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate" x-text="detailCompany.contact_email || '--'"></div>
                                    </div>
                                </div>
                            </section>

                            <!-- Statistics Section -->
                            <section class="space-y-4">
                                <h3 class="text-xs font-black text-amber-500 uppercase tracking-[0.3em]">{{ __('Statistics') }}</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-amber-50 dark:bg-amber-500/5 p-6 rounded-2xl border border-amber-100 dark:border-amber-500/10 text-center">
                                        <p class="text-2xl font-black text-slate-800 dark:text-white" x-text="detailCompany.users_count || 0"></p>
                                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest mt-1">{{ __('Users') }}</p>
                                    </div>
                                    <div class="bg-cyan-50 dark:bg-cyan-500/5 p-6 rounded-2xl border border-cyan-100 dark:border-cyan-500/10 text-center">
                                        <p class="text-2xl font-black text-slate-800 dark:text-white" x-text="detailCompany.machines_count || 0"></p>
                                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest mt-1">{{ __('Machines') }}</p>
                                    </div>
                                </div>
                            </section>

                            <!-- Notes Section -->
                            <section class="space-y-4 pb-8" x-show="detailCompany.note">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em]">{{ __('Notes') }}</h3>
                                <div class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                    <p class="text-sm font-bold text-slate-600 dark:text-slate-400 leading-relaxed italic" x-text="detailCompany.note"></p>
                                </div>
                            </section>
                          </div>

                          <!-- History View -->
                          <div x-show="sidebarView === 'history'" class="space-y-6">
                            <template x-for="(contract, index) in (detailCompany.contracts || [])" :key="contract.id">
                                <div class="bg-slate-50/50 dark:bg-slate-800/30 rounded-2xl border border-slate-100 dark:border-slate-800/80 overflow-hidden">
                                    <!-- Card Header -->
                                    <div class="px-5 py-3 bg-white dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-[9px] font-black text-slate-500" x-text="'#' + (detailCompany.contracts.length - index)"></div>
                                            <div>
                                                <p class="text-[12px] font-black text-slate-700 dark:text-slate-200" x-text="new Date(contract.created_at).toLocaleString()"></p>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <span :class="contract.type === 'lease' ? 'text-blue-500 bg-blue-500/10' : 'text-amber-500 bg-amber-500/10'" 
                                                        class="text-[8px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded"
                                                        x-text="contract.type === 'lease' ? '{{ __('Lease') }}' : '{{ __('Buyout') }}'"></span>
                                                    <span class="text-[9px] text-slate-400">{{ __('by') }} <span class="text-slate-600 dark:text-slate-300" x-text="contract.creator?.name || 'System'"></span></span>
                                                </div>
                                            </div>
                                        </div>
                                        <template x-if="index === 0">
                                            <span class="px-2 py-0.5 bg-emerald-500 text-white text-[8px] font-black uppercase tracking-widest rounded-full">{{ __('Current') }}</span>
                                        </template>
                                    </div>

                                    <!-- Card Body -->
                                    <div class="p-5 space-y-4">
                                        <!-- Lease dates -->
                                        <template x-if="contract.type === 'lease'">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-1.5">
                                                    <p class="text-[12px] font-black text-slate-400 uppercase tracking-widest">{{ __('Contract Start') }}</p>
                                                    <p class="text-sm font-black text-slate-700 dark:text-white font-mono" x-text="contract.start_date?.substring(0, 10) || '-'"></p>
                                                </div>
                                                <div class="space-y-1.5">
                                                    <p class="text-[12px] font-black text-slate-400 uppercase tracking-widest">{{ __('Contract End') }}</p>
                                                    <p class="text-sm font-black text-slate-700 dark:text-white font-mono" x-text="contract.end_date?.substring(0, 10) || '{{ __('Unlimited') }}'"></p>
                                                </div>
                                            </div>
                                        </template>
                                        <!-- Buyout dates -->
                                        <template x-if="contract.type === 'buyout'">
                                            <div class="grid grid-cols-1 gap-4">
                                                <div class="p-4 bg-amber-50/40 dark:bg-amber-500/5 rounded-2xl border border-amber-100/50 dark:border-amber-500/10">
                                                    <p class="text-[12px] font-black text-amber-600 uppercase tracking-widest mb-2">{{ __('Warranty Service') }}</p>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-black text-slate-700 dark:text-slate-200 font-mono" x-text="contract.warranty_start_date?.substring(0, 10) || '-'"></span>
                                                        <span class="text-slate-400 font-bold">~</span>
                                                        <span class="text-sm font-black text-slate-800 dark:text-white font-mono" x-text="contract.warranty_end_date?.substring(0, 10) || '-'"></span>
                                                    </div>
                                                </div>
                                                <div class="p-4 bg-indigo-50/40 dark:bg-indigo-500/5 rounded-2xl border border-indigo-100/50 dark:border-indigo-500/10">
                                                    <p class="text-[12px] font-black text-indigo-600 uppercase tracking-widest mb-2">{{ __('Software Service') }}</p>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-black text-slate-700 dark:text-slate-200 font-mono" x-text="contract.software_start_date?.substring(0, 10) || '-'"></span>
                                                        <span class="text-slate-400 font-bold">~</span>
                                                        <span class="text-sm font-black text-slate-800 dark:text-white font-mono" x-text="contract.software_end_date?.substring(0, 10) || '-'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <!-- Note -->
                                        <div class="pt-2 border-t border-slate-100/50 dark:border-slate-800/50" x-show="contract.note">
                                            <p class="text-[9px] font-bold text-slate-400 italic" x-text="contract.note"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="!(detailCompany.contracts || []).length" class="py-12 text-center">
                                <div class="w-14 h-14 rounded-2xl bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center mx-auto mb-3 border border-slate-100 dark:border-slate-800">
                                    <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18c-2.305 0-4.408.867-6 2.292m0-14.25v14.25" /></svg>
                                </div>
                                <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('No history records') }}</p>
                            </div>
                          </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-8 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                            <button @click="showDetail = false" class="w-full btn-luxury-ghost py-4 rounded-xl font-black uppercase tracking-[0.2em] text-xs">
                                {{ __('Close Panel') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

</div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #475569;
    }
</style>

@endsection