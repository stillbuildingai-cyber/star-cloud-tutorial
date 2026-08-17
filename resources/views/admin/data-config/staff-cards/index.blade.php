@extends('layouts.admin')

@section('content')
<div class="space-y-2 pb-20" x-data="staffCardManager" data-cards="{{ json_encode($cards->items()) }}"
    data-logs="{{ json_encode($logs->items()) }}" data-index-url="{{ route('admin.data-config.staff-cards.index') }}">

    {{-- Page Header --}}
    <x-page-header :title="__('Staff Identification Management')"
        :subtitle="__('Manage employee IC cards and track usage logs.')">
        <template x-if="activeTab === 'staff_list'">
            <div class="flex items-center gap-3">
                <button @click="isImportModalOpen = true" type="button"
                    class="btn-luxury-secondary whitespace-nowrap flex items-center gap-2 transition-all duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span class="text-sm sm:text-base">{{ __('Import Excel') }}</span>
                </button>
                <button @click="openStaffModal()" type="button"
                    class="btn-luxury-primary whitespace-nowrap flex items-center gap-2 transition-all duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="text-sm sm:text-base">{{ __('Add Staff Card') }}</span>
                </button>
            </div>
        </template>
    </x-page-header>

    {{-- Tab Navigation --}}
    <x-tab-nav>
        <x-tab-nav-item value="staff_list" :label="__('Staff List')" />
        <x-tab-nav-item value="usage_logs" :label="__('Usage Logs')" />
    </x-tab-nav>

    {{-- Tab Contents --}}

    {{-- Staff List Tab --}}
    <div x-show="activeTab === 'staff_list'"
        class="luxury-card rounded-3xl p-6 animate-luxury-in relative min-h-[300px]"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

        {{-- Loading Overlay --}}
        <x-luxury-spinner show="tabLoading === 'staff_list'" z-index="z-20">
            <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </x-luxury-spinner>

        <div id="tab-staff_list-container" class="relative">
            @include('admin.data-config.staff-cards.partials.tab-staff-list')
        </div>
    </div>

    {{-- Usage Logs Tab --}}
    <div x-show="activeTab === 'usage_logs'"
        class="luxury-card rounded-3xl p-6 animate-luxury-in relative min-h-[300px]"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

        {{-- Loading Overlay --}}
        <x-luxury-spinner show="tabLoading === 'usage_logs'" z-index="z-20">
            <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-luxury-spinner>

        <div id="tab-usage_logs-container" class="relative">
            @include('admin.data-config.staff-cards.partials.tab-usage-logs')
        </div>
    </div>

    {{-- Modals & Slide-overs --}}
    {{-- Import Staff Modal --}}
    <div x-show="isImportModalOpen" class="fixed inset-0 z-[110] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isImportModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                @click="isImportModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="isImportModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="inline-block w-full max-w-lg p-8 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-900 shadow-2xl rounded-3xl border border-slate-100 dark:border-white/10">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight">
                        {{ __('Import Staff Cards') }}
                    </h3>
                    <button @click="isImportModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitImportForm" class="space-y-6" enctype="multipart/form-data">
                    <div class="space-y-5 px-1">
                        @if(auth()->user()->isSystemAdmin())
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('Affiliated Company') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select name="import_company_id" id="import-company-id" :options="$companies"
                                :placeholder="__('Select Company')"
                                @change="importFormFields.company_id = $event.target.value" required />
                        </div>
                        @endif

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('Excel File') }} <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative group">
                                <input type="file" id="import-file" @change="importFormFields.file = $event.target.files[0]"
                                    accept=".xlsx,.xls,.csv" class="luxury-input w-full pr-12 cursor-pointer" required>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ __('Only .xlsx, .xls, .csv files are supported (Max 5MB)') }}
                            </p>
                        </div>

                        <div class="bg-slate-50 dark:bg-white/5 rounded-2xl p-4 border border-slate-100 dark:border-white/5">
                            <h4 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                                {{ __('Download Template') }}
                            </h4>
                            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                                {{ __('Please use our standard template to ensure data compatibility.') }}
                            </p>
                            <a href="{{ route('admin.data-config.staff-cards.template') }}" download
                                @click="setTimeout(() => { const bar = document.getElementById('top-loading-bar'); if(bar) bar.classList.remove('loading'); }, 1000);"
                                class="inline-flex items-center gap-2 text-sm font-bold text-cyan-600 hover:text-cyan-700 transition-colors group">
                                <span>{{ __('Download Template') }}</span>
                                <svg class="w-4 h-4 transition-transform group-hover:translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-12 pt-6 border-t border-slate-100 dark:border-white/5">
                        <button type="button" @click="isImportModalOpen = false"
                            class="px-6 py-2.5 text-sm font-black text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 uppercase tracking-widest transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn-luxury-primary px-10 py-3 shadow-lg bg-cyan-600 hover:bg-cyan-700 shadow-cyan-500/20"
                            :disabled="loading">
                            <template x-if="!loading">
                                <span>{{ __('Start Import') }}</span>
                            </template>
                            <template x-if="loading">
                                <div class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>{{ __('Processing...') }}</span>
                                </div>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div x-show="isStaffModalOpen" class="fixed inset-0 z-[110] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isStaffModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                @click="isStaffModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="isStaffModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="inline-block w-full max-w-lg p-8 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-900 shadow-2xl rounded-3xl border border-slate-100 dark:border-white/10">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight"
                        x-text="staffModalMode === 'create' ? '{{ __('Add Staff Card') }}' : '{{ __('Edit Staff Card') }}'">
                    </h3>
                    <button @click="isStaffModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitStaffForm" class="space-y-6">
                    <div class="space-y-5 px-1">
                        @if(auth()->user()->isSystemAdmin())
                        <div class="space-y-2">
                            <label
                                class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('Affiliated Company') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select name="company_id" id="modal-company-id" :options="$companies"
                                :placeholder="__('Select Company')"
                                @change="staffFormFields.company_id = $event.target.value" required />
                        </div>
                        @endif

                        <div class="space-y-2">
                            <label
                                class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('Employee ID') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="staffFormFields.employee_id"
                                class="luxury-input w-full focus:ring-emerald-500/20 focus:border-emerald-500"
                                placeholder="{{ __('e.g., S001') }}" required>
                        </div>

                        <div class="space-y-2">
                            <label
                                class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('Staff Name') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="staffFormFields.name" class="luxury-input w-full"
                                placeholder="{{ __('e.g., John Doe') }}" required>
                        </div>

                        <div class="space-y-2">
                            <label
                                class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('IC Card UID') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="staffFormFields.card_uid" class="luxury-input w-full"
                                placeholder="{{ __('e.g., 12345678') }}" required>
                        </div>

                        <div class="space-y-2">
                            <label
                                class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('Notes') }}
                            </label>
                            <textarea x-model="staffFormFields.notes" class="luxury-input w-full h-24 resize-none"
                                placeholder="{{ __('Optional notes...') }}"></textarea>
                        </div>

                        <div class="space-y-2">
                            <label
                                class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('Status') }}
                            </label>
                            <div class="flex items-center gap-6 p-1">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" x-model="staffFormFields.status" value="active"
                                        class="w-4 h-4 text-emerald-600 bg-slate-100 border-slate-300 focus:ring-emerald-500 focus:ring-2 dark:bg-slate-800 dark:border-slate-700">
                                    <span
                                        class="text-sm font-bold text-slate-600 dark:text-slate-400 group-hover:text-emerald-500 transition-colors">{{
                                        __('Active') }}</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" x-model="staffFormFields.status" value="inactive"
                                        class="w-4 h-4 text-rose-600 bg-slate-100 border-slate-300 focus:ring-rose-500 focus:ring-2 dark:bg-slate-800 dark:border-slate-700">
                                    <span
                                        class="text-sm font-bold text-slate-600 dark:text-slate-400 group-hover:text-rose-500 transition-colors">{{
                                        __('Disabled') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-4 mt-12 pt-6 border-t border-slate-100 dark:border-white/5">
                        <button type="button" @click="isStaffModalOpen = false"
                            class="px-6 py-2.5 text-sm font-black text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 uppercase tracking-widest transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn-luxury-primary px-10 py-3 shadow-lg"
                            :class="staffModalMode === 'create' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20' : 'bg-cyan-600 hover:bg-cyan-700 shadow-cyan-500/20'">
                            <span
                                x-text="staffModalMode === 'create' ? '{{ __('Create') }}' : '{{ __('Save Changes') }}'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Confirm Modal --}}
    <x-delete-confirm-modal :title="__('Confirm Deletion')"
        :message="__('Are you sure you want to delete this staff card? This action cannot be undone.')" />

    {{-- Status Toggle Modal --}}
    <x-status-confirm-modal :title="__('Confirm Status Change')"
        :message="__('Are you sure you want to change the status of this card? Disabled cards cannot be used for login.')" />

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('staffCardManager', () => ({
            activeTab: '{{ $tab }}',
            tabLoading: null,
            loading: false,
            isStaffModalOpen: false,
            isImportModalOpen: false,
            isDeleteConfirmOpen: false,
            isStatusConfirmOpen: false,
            staffModalMode: 'create',
            staffFormFields: { id: null, company_id: '', employee_id: '', name: '', card_uid: '', status: 'active', notes: '' },
            importFormFields: { company_id: '{{ auth()->user()->company_id ?? "" }}', file: null },
            deleteFormAction: '',
            toggleFormAction: '',

            init() {
                this.bindPaginationLinks('#tab-staff_list-container', 'staff_list');
                this.bindPaginationLinks('#tab-usage_logs-container', 'usage_logs');

                this.$watch('activeTab', (val) => {
                    const url = new URL(window.location.origin + window.location.pathname);
                    url.searchParams.set('tab', val);
                    window.history.pushState({}, '', url);
                });

                this.$watch('loading', (val) => {
                    const bar = document.getElementById('top-loading-bar');
                    if (bar) {
                        if (val) bar.classList.add('loading');
                        else bar.classList.remove('loading');
                    }
                });
            },

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
                            if (value.trim() !== '') params.set(key, value);
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
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const data = await response.json();
                    if (data.success) {
                        if (container) {
                            container.innerHTML = data.html;
                            if (window.Alpine) window.Alpine.initTree(container);
                            this.bindPaginationLinks('#tab-' + tab + '-container', tab);
                        }
                        const historyUrl = new URL(url, window.location.origin);
                        historyUrl.searchParams.delete('_ajax');
                        window.history.pushState({}, '', historyUrl);
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    if (window.showToast) window.showToast('{{ __("Failed to load data") }}', 'error');
                } finally {
                    this.tabLoading = null;
                }
            },

            bindPaginationLinks(containerSelector, tab) {
                this.$nextTick(() => {
                    const container = document.querySelector(containerSelector);
                    if (!container) return;
                    
                    if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                        window.HSStaticMethods.autoInit();
                    }

                    // Remove old listener if exists to prevent duplicates
                    if (container._ajaxNavigateHandler) {
                        container.removeEventListener('ajax:navigate', container._ajaxNavigateHandler);
                    }

                    // Create new handler
                    container._ajaxNavigateHandler = (e) => {
                        e.preventDefault();
                        const url = e.detail.url;
                        if (url) {
                            this.fetchTabData(tab, url);
                        }
                    };

                    // Listen for the custom event emitted by luxury pagination
                    container.addEventListener('ajax:navigate', container._ajaxNavigateHandler);
                });
            },

            handleFilterSubmit(tab) {
                this.fetchTabData(tab);
            },

            openStaffModal(card = null) {
                if (card) {
                    this.staffModalMode = 'edit';
                    this.staffFormFields = {
                        id: card.id,
                        company_id: card.company_id || '',
                        employee_id: card.employee_id,
                        name: card.name,
                        card_uid: card.card_uid,
                        status: card.status || 'active',
                        notes: card.notes
                    };
                } else {
                    this.staffModalMode = 'create';
                    this.staffFormFields = {
                        id: null,
                        company_id: '{{ auth()->user()->company_id ?? "" }}',
                        employee_id: '',
                        name: '',
                        card_uid: '',
                        status: 'active',
                        notes: ''
                    };
                }
                this.isStaffModalOpen = true;

                // 回填所屬公司下拉：
                // 1. Preline v3 getInstance(target, true) 回傳的是 collection wrapper 而非實例，
                //    其上沒有 setValue 會丟錯導致回填失靈，故不可帶第二個參數。
                // 2. Preline 的 option value 為字串，company_id 為數字時嚴格比對會失配，統一轉字串；空值用 ' ' 對應 placeholder。
                // 同步先設原生 <select>.value 再呼叫 setValue（對齊 welcome-gifts / pickup-codes 既有可運作寫法）。
                this.$nextTick(() => {
                    const el = document.getElementById('modal-company-id');
                    if (el) {
                        const cid = this.staffFormFields.company_id;
                        const valStr = (cid !== undefined && cid !== null && cid.toString().trim() !== '') ? cid.toString() : ' ';
                        el.value = valStr;
                        const inst = window.HSSelect && window.HSSelect.getInstance(el);
                        if (inst) inst.setValue(valStr);
                    }
                });
            },

            async submitStaffForm() {
                const url = this.staffModalMode === 'create'
                    ? `{{ route('admin.data-config.staff-cards.store') }}`
                    : `{{ url('admin/data-config/staff-cards') }}/${this.staffFormFields.id}`;

                const method = this.staffModalMode === 'create' ? 'POST' : 'PUT';

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ ...this.staffFormFields, _method: method })
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.isStaffModalOpen = false;
                        await this.fetchTabData('staff_list');
                        if (window.showToast) window.showToast(data.message, 'success');
                    } else {
                        if (window.showToast) window.showToast(data.message || 'Error', 'error');
                    }
                } catch (error) {
                    console.error('Submit error:', error);
                    if (window.showToast) window.showToast('{{ __("Operation failed") }}', 'error');
                }
            },

            confirmDelete(actionUrl) {
                this.deleteFormAction = actionUrl;
                this.isDeleteConfirmOpen = true;
            },

            async deleteItem() {
                // This is called by the modal form if we want to use AJAX
                // But the component has a standard form submit.
                // We'll intercept it if we want, or just let it reload.
                // Given the products example, they use fetch in submitConfirmedForm.
                // Wait, delete-confirm-modal in components uses a FORM.
                // I'll just let it submit and reload for now, or update it to AJAX.
            },

            toggleStatus(actionUrl) {
                this.toggleFormAction = actionUrl;
                this.isStatusConfirmOpen = true;
            },

            async submitConfirmedForm() {
                if (this.isStatusConfirmOpen) {
                    this.isStatusConfirmOpen = false;
                    this.loading = true;
                    try {
                        const response = await fetch(this.toggleFormAction, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new URLSearchParams({ '_method': 'PATCH' })
                        });
                        const data = await response.json();
                        if (data.success) {
                            await this.fetchTabData(this.activeTab);
                            if (window.showToast) window.showToast(data.message, 'success');
                        }
                    } catch (error) {
                        console.error('Status toggle error:', error);
                    } finally {
                        this.loading = false;
                    }
                }
            },

            async submitImportForm() {
                if (!this.importFormFields.file) return;

                this.loading = true;
                const formData = new FormData();
                formData.append('file', this.importFormFields.file);
                if (this.importFormFields.company_id) {
                    formData.append('company_id', this.importFormFields.company_id);
                }

                try {
                    const response = await fetch(`{{ route('admin.data-config.staff-cards.import') }}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.isImportModalOpen = false;
                        this.importFormFields.file = null;
                        document.getElementById('import-file').value = '';
                        await this.fetchTabData('staff_list');
                        if (window.showToast) window.showToast(data.message, 'success');
                        
                        if (data.results.error_count > 0) {
                            console.error('Import partially failed:', data.results.errors);
                            if (window.showToast) window.showToast(`Imported with ${data.results.error_count} errors. Check console.`, 'warning');
                        }
                    } else {
                        if (window.showToast) window.showToast(data.message || 'Error', 'error');
                    }
                } catch (error) {
                    console.error('Import error:', error);
                    if (window.showToast) window.showToast('{{ __("Import failed") }}', 'error');
                } finally {
                    this.loading = false;
                }
            }
        }));
    });
</script>
@endsection