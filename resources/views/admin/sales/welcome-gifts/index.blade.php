@extends('layouts.admin')

@section('content')
<div class="space-y-2 pb-20" x-data="{
    activeTab: '{{ $tab ?? "list" }}',
    tabLoading: null,
    showCreateModal: false,
    showEditModal: false,
    showDeleteModal: false,
    showQrModal: false,
    deleteTargetForm: '',
    activeQrCode: '',
    activeTicketUrl: '',
    selectedMachine: '',
    giftName: '',
    discountType: 'percentage',
    discountValInput: '',
    usageType: 'once',
    usageLimit: 1,
    customCode: '',
    
    // Create validity/expiry states
    expiryMode: 'permanent',
    expiresDays: 7,
    customExpiry: '',
    
    editFormAction: '',
    editCustomCode: '',
    editGiftName: '',
    editDiscountType: 'percentage',
    editDiscountValInput: '',
    editUsageType: 'once',
    editUsageLimit: 1,
    
    // Edit validity/expiry states
    editExpiryMode: 'permanent',
    editExpiresDays: 7,
    editCustomExpiry: '',

    openEditModal(item) {
        this.editFormAction = '{{ route('admin.sales.store-gifts.update', ':id') }}'.replace(':id', item.id);
        this.editCustomCode = item.code;
        this.editGiftName = item.name;
        this.editDiscountType = item.discount_type;
        this.editDiscountValInput = item.discount_type === 'percentage' ? item.input_fold : item.discount_value;
        this.editUsageType = item.usage_type;
        this.editUsageLimit = item.usage_limit || 1;
        
        if (item.expires_at) {
            this.editExpiryMode = 'date';
            const expDate = new Date(item.expires_at);
            this.editCustomExpiry = this.formatDate(expDate);
            const today = new Date();
            const diffTime = expDate - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            this.editExpiresDays = diffDays > 0 ? diffDays : 7;
        } else {
            this.editExpiryMode = 'permanent';
            this.editCustomExpiry = '';
            this.editExpiresDays = 7;
        }
        this.syncSelect('edit-discount-type', this.editDiscountType);
        this.syncSelect('edit-usage-type', this.editUsageType);
        
        this.showEditModal = true;
    },

    generateRandomCode() {
        this.customCode = Math.floor(Math.random() * 90000000 + 10000000).toString();
    },

    calculateDaysExpiry() {
        const date = new Date();
        const days = parseInt(this.expiresDays) || 0;
        date.setDate(date.getDate() + days);
        return this.formatDate(date);
    },

    getDisplayExpiry() {
        if (this.expiryMode === 'permanent') {
            return '{{ __("Permanent") }}';
        }
        if (this.customExpiry) {
            return this.customExpiry.replace('T', ' ');
        }
        return this.calculateDaysExpiry();
    },

    getSubmittedExpiry() {
        if (this.expiryMode === 'permanent') {
            return '';
        }
        if (this.customExpiry) {
            return this.customExpiry;
        }
        return this.calculateDaysExpiry();
    },

    calculateEditDaysExpiry() {
        const date = new Date();
        const days = parseInt(this.editExpiresDays) || 0;
        date.setDate(date.getDate() + days);
        return this.formatDate(date);
    },

    getDisplayEditExpiry() {
        if (this.editExpiryMode === 'permanent') {
            return '{{ __("Permanent") }}';
        }
        if (this.editCustomExpiry) {
            return this.editCustomExpiry.replace('T', ' ');
        }
        return this.calculateEditDaysExpiry();
    },

    getSubmittedEditExpiry() {
        if (this.editExpiryMode === 'permanent') {
            return '';
        }
        if (this.editCustomExpiry) {
            return this.editCustomExpiry;
        }
        return this.calculateEditDaysExpiry();
    },

    formatDate(date) {
        return date.getFullYear() + '-' +
            String(date.getMonth() + 1).padStart(2, '0') + '-' +
            String(date.getDate()).padStart(2, '0') + ' ' +
            String(date.getHours()).padStart(2, '0') + ':' +
            String(date.getMinutes()).padStart(2, '0');
    },

    async fetchTabData(tab, customUrl = null) {
        if (this.tabLoading === tab) return;
        this.tabLoading = tab;
        const container = document.getElementById(`tab-${tab}-container`);
        let url = customUrl;

        if (!url) {
            let params = new URLSearchParams();
            params.set('tab', tab);
            params.set('_ajax', '1');

            if (container) {
                const form = container.querySelector('form');
                if (form) {
                    const formData = new FormData(form);
                    formData.forEach((value, key) => {
                        if (value.trim() !== '') params.append(key, value);
                    });
                }
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

    confirmDelete(formId) {
        this.deleteTargetForm = formId;
        this.showDeleteModal = true;
    },

    submitDelete() {
        if (this.deleteTargetForm) {
            const form = document.getElementById(this.deleteTargetForm);
            if (form) form.submit();
        }
        this.showDeleteModal = false;
    },

    copyQrCode() {
        if (!this.activeQrCode) return;
        navigator.clipboard.writeText(this.activeQrCode).then(() => {
            window.showToast('{{ __("Code Copied") }}', 'success');
        });
    },

    copyQrLink() {
        if (!this.activeTicketUrl) return;
        navigator.clipboard.writeText(this.activeTicketUrl).then(() => {
            window.showToast('{{ __("Link Copied") }}', 'success');
        });
    },

    async downloadQrCode() {
        const url = '{{ route('admin.basic-settings.qr-code') }}?size=500&data=' + encodeURIComponent(this.activeQrCode);
        fetch(url)
            .then(response => response.blob())
            .then(blob => {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `welcome-gift-${this.activeQrCode}.png`;
                link.click();
                URL.revokeObjectURL(link.href);
            });
    },

    syncSelect(id, value) {
        this.$nextTick(() => {
            const el = document.getElementById(id);
            if (el) {
                const valStr = (value !== undefined && value !== null && value.toString().trim() !== '') ? value.toString() : ' ';
                el.value = valStr;
                if (window.HSSelect) {
                    const inst = window.HSSelect.getInstance(el);
                    if (inst) inst.setValue(valStr);
                }
            }
        });
    },

    validateAndSubmit(e) {
        if (!this.selectedMachine) {
            window.showToast('{{ __("Please select a machine") }}', 'error');
            return;
        }
        if (!this.giftName.trim()) {
            window.showToast('{{ __("Please enter a name") }}', 'error');
            return;
        }
        if (!this.discountValInput) {
            window.showToast('{{ __("Please enter discount value") }}', 'error');
            return;
        }
        if (this.discountType === 'percentage') {
            const val = parseFloat(this.discountValInput);
            if (isNaN(val) || val <= 0 || val >= 10) {
                window.showToast('{{ __("Discount fold must be between 0.1 and 9.9") }}', 'error');
                return;
            }
        } else {
            const val = parseInt(this.discountValInput);
            if (isNaN(val) || val <= 0) {
                window.showToast('{{ __("Discount amount must be a positive integer") }}', 'error');
                return;
            }
        }
        e.target.submit();
    },

    getDiscountHelpText(input) {
        const val = parseFloat(input);
        if (isNaN(val) || val <= 0 || val >= 10) {
            return '';
        }
        const percentOff = Math.round((10 - val) * 10);
        const map = {
            '1': '一', '2': '二', '3': '三', '4': '四', '5': '五',
            '6': '六', '7': '七', '8': '八', '9': '九', '0': '零'
        };
        let foldStr = val.toString();
        let chineseFold = '';
        if (foldStr.includes('.')) {
            const parts = foldStr.split('.');
            const integer = parts[0];
            const decimal = parts[1];
            const intChar = map[integer] || integer;
            const decChar = map[decimal.charAt(0)] || decimal.charAt(0);
            chineseFold = intChar + decChar + '折';
        } else {
            const char = map[foldStr] || foldStr;
            chineseFold = char + '折';
        }
        return '{{ __("Discount") }}: ' + percentOff + '% off (' + chineseFold + ')';
    },

    adjustDiscountValue(target, delta) {
        const isEdit = target === 'edit';
        const type = isEdit ? this.editDiscountType : this.discountType;
        const key = isEdit ? 'editDiscountValInput' : 'discountValInput';
        const current = parseFloat(this[key]) || 0;
        const step = type === 'percentage' ? 0.1 : 1;
        let next = current + (delta * step);

        if (type === 'percentage') {
            next = Math.max(0.1, Math.min(9.9, next));
            this[key] = (Math.round(next * 10) / 10).toString();
        } else {
            next = Math.max(1, Math.round(next));
            this[key] = next.toString();
        }
    },

    init() {
        this.$watch('activeTab', (newTab) => {
            const url = new URL(window.location.origin + window.location.pathname);
            url.searchParams.set('tab', newTab);
            window.history.pushState({}, '', url);
            this.$nextTick(() => {
                if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
            });
        });

        this.$watch('showCreateModal', (val) => {
            if (val) {
                this.selectedMachine = '';
                this.giftName = '';
                this.discountType = 'percentage';
                this.discountValInput = '';
                this.usageType = 'once';
                this.usageLimit = 1;
                this.expiryMode = 'permanent';
                this.expiresDays = 7;
                this.customExpiry = '';
                this.generateRandomCode();
                this.syncSelect('modal-gift-machine', '');
                this.syncSelect('create-discount-type', this.discountType);
                this.syncSelect('create-usage-type', this.usageType);
            }
        });
    }
} " @ajax:navigate.window.prevent="fetchTabData(activeTab, $event.detail.url)">
    {{-- Page Header --}}
    <x-page-header :title="__('Welcome Gifts')"
        :subtitle="__('Manage welcoming promotion codes for new guests')">
        <button @click="showCreateModal = true; generateRandomCode()" class="btn-luxury-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>{{ __('Add Welcome Gift') }}</span>
        </button>
    </x-page-header>

    {{-- Tabs --}}
    <x-tab-nav model="activeTab">
        <x-tab-nav-item value="list" :label="__('Welcome Gifts')" model="activeTab" />
        <x-tab-nav-item value="logs" :label="__('Usage Logs')" model="activeTab" />
    </x-tab-nav>

    {{-- Main Content --}}
    <div class="mt-6">
        <div class="relative min-h-[400px]">
            {{-- List Tab --}}
            <div x-show="activeTab === 'list'" class="relative min-h-[400px]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0">
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden">
                    <x-luxury-spinner show="tabLoading === 'list'" />
                    <div id="tab-list-container" class="relative"
                        :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': tabLoading === 'list' }">
                        @include('admin.sales.welcome-gifts.partials.tab-list')
                    </div>
                </div>
            </div>

            {{-- Logs Tab --}}
            <div x-show="activeTab === 'logs'" class="relative min-h-[400px]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" x-cloak>
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden">
                    <x-luxury-spinner show="tabLoading === 'logs'" />
                    <div id="tab-logs-container" class="relative"
                        :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': tabLoading === 'logs' }">
                        @include('admin.sales.welcome-gifts.partials.tab-logs')
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div x-show="showCreateModal" class="fixed inset-0 z-[110] overflow-y-auto"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                @click="showCreateModal = false"></div>

            <div x-show="showCreateModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="inline-block px-8 py-10 text-left align-bottom transition-all transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full overflow-visible">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{
                        __('Add Welcome Gift') }}</h3>
                    <button @click="showCreateModal = false"
                        class="p-2.5 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700">
                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.sales.store-gifts.store') }}" method="POST" class="space-y-6"
                    @submit.prevent="validateAndSubmit">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2 relative focus-within:z-[60]">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Target Machine') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select name="machine_id" id="modal-gift-machine"
                                :placeholder="__('Please select a machine')" x-model="selectedMachine"
                                @change="selectedMachine = $event.target.value">
                                @foreach($machines as $machine)
                                <option value="{{ $machine->id }}"
                                    data-title="{{ $machine->name }} ({{ $machine->serial_no }})">{{ $machine->name }}
                                    ({{ $machine->serial_no }})</option>
                                @endforeach
                            </x-searchable-select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Welcome Gift Name') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" x-model="giftName" class="luxury-input w-full"
                                placeholder="{{ __('e.g. New Guest 15% Off Discount') }}" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Discount Type') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select name="discount_type" id="create-discount-type" :hasSearch="false"
                                x-model="discountType" @change="discountType = $event.target.value">
                                <option value="percentage" data-title="{{ __('Percentage Discount') }}">{{ __('Percentage Discount') }}</option>
                                <option value="amount" data-title="{{ __('Amount Discount') }}">{{ __('Amount Discount') }}</option>
                            </x-searchable-select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                <span x-show="discountType === 'percentage'">{{ __('Discount Fold') }} ({{ __('e.g. 8.5 for 85折, 8 for 8折') }}) <span class="text-rose-500">*</span></span>
                                <span x-show="discountType === 'amount'">{{ __('Discount Amount (NTD)') }} <span class="text-rose-500">*</span></span>
                            </label>
                            <div class="flex items-center h-12 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                <button type="button" @click="adjustDiscountValue('create', -1)"
                                    class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                </button>
                                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                <input type="text" inputmode="decimal" name="discount_val_input" x-model="discountValInput"
                                    class="flex-1 min-w-0 h-full bg-transparent border-none text-center text-lg font-black text-slate-800 dark:text-white focus:ring-0 px-3"
                                    :placeholder="discountType === 'percentage' ? '8.5' : '50'" required>
                                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                <button type="button" @click="adjustDiscountValue('create', 1)"
                                    class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                </button>
                            </div>
                            <div class="mt-1.5 text-xs text-cyan-600 dark:text-cyan-400 font-bold" x-show="discountType === 'percentage' && discountValInput"><span x-text="getDiscountHelpText(discountValInput)"></span></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Usage Type') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select name="usage_type" id="create-usage-type" :hasSearch="false"
                                x-model="usageType" @change="usageType = $event.target.value">
                                <option value="once" data-title="{{ __('Once') }}">{{ __('Once') }}</option>
                                <option value="unlimited" data-title="{{ __('Unlimited') }}">{{ __('Unlimited') }}</option>
                            </x-searchable-select>
                        </div>
                        <input type="hidden" name="usage_limit" value="1">
                    </div>

                    {{-- Welcome Gift Code Section (Flexible) --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Welcome Gift Code (8 Digits)') }} <span class="text-rose-500">*</span>
                            </label>
                            <button type="button" @click="generateRandomCode()"
                                class="text-[10px] font-black text-cyan-500 hover:text-cyan-600 uppercase tracking-widest flex items-center gap-1 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Regenerate') }}
                            </button>
                        </div>
                        <input type="text" name="custom_code" x-model="customCode"
                            class="luxury-input w-full py-4 font-mono text-2xl tracking-[0.3em] text-center text-cyan-600 dark:text-cyan-400 bg-cyan-50/30 dark:bg-cyan-500/5 uppercase"
                            maxlength="12" required>
                    </div>

                    {{-- Validity Period Section --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Validity Period') }}</label>
                            <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                                <button type="button" @click="expiryMode = 'permanent'" 
                                    class="px-3 py-1 text-[10px] font-black uppercase tracking-tighter rounded-lg transition-all"
                                    :class="expiryMode === 'permanent' ? 'bg-white dark:bg-slate-700 text-cyan-600 shadow-sm' : 'text-slate-400'">{{ __('Permanent') }}</button>
                                <button type="button" @click="expiryMode = 'date'" 
                                    class="px-3 py-1 text-[10px] font-black uppercase tracking-tighter rounded-lg transition-all"
                                    :class="expiryMode === 'date' ? 'bg-white dark:bg-slate-700 text-cyan-600 shadow-sm' : 'text-slate-400'">{{ __('Custom Expiry') }}</button>
                            </div>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800/40 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800/50 shadow-sm">
                            <input type="hidden" name="expires_at" :value="getSubmittedExpiry()">
                            
                            <div x-show="expiryMode === 'date'" x-transition class="space-y-6">
                                {{-- Days Stepper --}}
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-1">{{ __('Validity Period (Days)') }}</label>
                                    <div class="flex flex-col sm:flex-row items-center gap-4">
                                        {{-- Unified Luxury Counter --}}
                                        <div class="flex items-center h-12 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden flex-1 min-w-[150px] group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                            <button type="button" @click="expiresDays = Math.max(1, parseInt(expiresDays || 1) - 1)" 
                                                class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                            </button>
                                            <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                            <div class="flex-1 min-w-[50px]">
                                                <input type="text" x-model="expiresDays" readonly
                                                    class="w-full bg-transparent border-none text-center text-lg font-black text-slate-800 dark:text-white focus:ring-0 p-0">
                                            </div>
                                            <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                            <button type="button" @click="expiresDays = parseInt(expiresDays || 1) + 1" 
                                                class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                            </button>
                                        </div>

                                        {{-- Quick Select Buttons --}}
                                        <div class="flex items-center gap-1">
                                            <template x-for="val in [1, 3, 7, 30]">
                                                <button type="button" @click="expiresDays = val; customExpiry = ''"
                                                    class="w-10 h-8 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[10px] font-black transition-all shrink-0"
                                                    :class="expiresDays == val && !customExpiry ? 'bg-cyan-500 text-white border-cyan-500 shadow-lg shadow-cyan-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 hover:border-cyan-500/50'">
                                                    <span x-text="val + ' ' + '{{ __('d') }}'"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Custom Expiry Input --}}
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-1">{{ __('Or Choose Custom Date') }}</label>
                                    <div class="relative group">
                                        <input type="text" x-model="customExpiry"
                                            x-init="const fp = flatpickr($el, { 
                                                enableTime: true, 
                                                dateFormat: 'Y-m-d H:i', 
                                                time_24hr: true,
                                                minuteIncrement: 1,
                                                disableMobile: true,
                                                locale: window.flatpickrLocale
                                            }); $watch('customExpiry', v => fp.setDate(v, false))"
                                            class="luxury-input w-full py-3 text-center font-mono text-base bg-white dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 pl-4 pr-12 focus:border-cyan-500 transition-all"
                                            placeholder="{{ __('Choose Date') }}">
                                        <div class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 group-hover:text-cyan-500 transition-colors pointer-events-none">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Expected Expiry Card --}}
                            <div class="pt-6" :class="{ 'border-t border-slate-100 dark:border-slate-800/50 mt-6': expiryMode === 'date' }">
                                <div class="flex items-center justify-between mb-3 px-1">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Estimated Expiry') }}</span>
                                    <span class="text-[10px] font-bold text-slate-400/70 tracking-wide" x-text="expiryMode === 'permanent' ? '{{ __('Permanent') }}' : '{{ __('Expiry Date Set') }}'"></span>
                                </div>
                                <div class="bg-white dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-700/50 flex items-center justify-center gap-3">
                                    <svg class="w-5 h-5 text-cyan-500/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xl font-mono font-black text-slate-700 dark:text-slate-200 tracking-wider" x-text="getDisplayExpiry()"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-x-4 pt-8">
                        <button type="button" @click="showCreateModal = false" class="btn-luxury-ghost px-8">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn-luxury-primary px-12">
                            {{ __('Create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showEditModal" class="fixed inset-0 z-[110] overflow-y-auto"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                @click="showEditModal = false"></div>

            <div x-show="showEditModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="inline-block px-8 py-10 text-left align-bottom transition-all transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-xl sm:w-full overflow-visible">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{
                        __('Edit Welcome Gift') }}</h3>
                    <button @click="showEditModal = false"
                        class="p-2.5 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700">
                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="editFormAction" method="POST" class="space-y-6">
                    @csrf
                    @method('PATCH')
                    
                    {{-- Readonly Welcome Gift Code Section --}}
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                            {{ __('Welcome Gift Code') }}
                        </label>
                        <input type="text" x-model="editCustomCode" readonly
                            class="luxury-input w-full py-4 font-mono text-2xl tracking-[0.3em] text-center text-cyan-600 dark:text-cyan-400 bg-cyan-50/10 dark:bg-cyan-500/5 cursor-not-allowed opacity-80 uppercase"
                            maxlength="12">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                            {{ __('Welcome Gift Name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="editGiftName" class="luxury-input w-full"
                            placeholder="{{ __('e.g. New Guest 15% Off Discount') }}" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Discount Type') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select name="discount_type" id="edit-discount-type" :hasSearch="false"
                                x-model="editDiscountType" @change="editDiscountType = $event.target.value">
                                <option value="percentage" data-title="{{ __('Percentage Discount') }}">{{ __('Percentage Discount') }}</option>
                                <option value="amount" data-title="{{ __('Amount Discount') }}">{{ __('Amount Discount') }}</option>
                            </x-searchable-select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                <span x-show="editDiscountType === 'percentage'">{{ __('Discount Fold') }} <span class="text-rose-500">*</span></span>
                                <span x-show="editDiscountType === 'amount'">{{ __('Discount Amount (NTD)') }} <span class="text-rose-500">*</span></span>
                            </label>
                            <div class="flex items-center h-12 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                <button type="button" @click="adjustDiscountValue('edit', -1)"
                                    class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                </button>
                                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                <input type="text" inputmode="decimal" name="discount_val_input" x-model="editDiscountValInput"
                                    class="flex-1 min-w-0 h-full bg-transparent border-none text-center text-lg font-black text-slate-800 dark:text-white focus:ring-0 px-3"
                                    required>
                                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                <button type="button" @click="adjustDiscountValue('edit', 1)"
                                    class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                </button>
                            </div>
                            <div class="mt-1.5 text-xs text-cyan-600 dark:text-cyan-400 font-bold" x-show="editDiscountType === 'percentage' && editDiscountValInput"><span x-text="getDiscountHelpText(editDiscountValInput)"></span></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Usage Type') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select name="usage_type" id="edit-usage-type" :hasSearch="false"
                                x-model="editUsageType" @change="editUsageType = $event.target.value">
                                <option value="once" data-title="{{ __('Once') }}">{{ __('Once') }}</option>
                                <option value="unlimited" data-title="{{ __('Unlimited') }}">{{ __('Unlimited') }}</option>
                            </x-searchable-select>
                        </div>
                        <input type="hidden" name="usage_limit" value="1">
                    </div>

                    {{-- Validity Period Section --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Validity Period') }}</label>
                            <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                                <button type="button" @click="editExpiryMode = 'permanent'" 
                                    class="px-3 py-1 text-[10px] font-black uppercase tracking-tighter rounded-lg transition-all"
                                    :class="editExpiryMode === 'permanent' ? 'bg-white dark:bg-slate-700 text-cyan-600 shadow-sm' : 'text-slate-400'">{{ __('Permanent') }}</button>
                                <button type="button" @click="editExpiryMode = 'date'" 
                                    class="px-3 py-1 text-[10px] font-black uppercase tracking-tighter rounded-lg transition-all"
                                    :class="editExpiryMode === 'date' ? 'bg-white dark:bg-slate-700 text-cyan-600 shadow-sm' : 'text-slate-400'">{{ __('Custom Expiry') }}</button>
                            </div>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-800/40 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800/50 shadow-sm">
                            <input type="hidden" name="expires_at" :value="getSubmittedEditExpiry()">
                            
                            <div x-show="editExpiryMode === 'date'" x-transition class="space-y-6">
                                {{-- Days Stepper --}}
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-1">{{ __('Validity Period (Days)') }}</label>
                                    <div class="flex flex-col sm:flex-row items-center gap-4">
                                        {{-- Unified Luxury Counter --}}
                                        <div class="flex items-center h-12 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden flex-1 min-w-[150px] group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                            <button type="button" @click="editExpiresDays = Math.max(1, parseInt(editExpiresDays || 1) - 1)" 
                                                class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                            </button>
                                            <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                            <div class="flex-1 min-w-[50px]">
                                                <input type="text" x-model="editExpiresDays" readonly
                                                    class="w-full bg-transparent border-none text-center text-lg font-black text-slate-800 dark:text-white focus:ring-0 p-0">
                                            </div>
                                            <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                            <button type="button" @click="editExpiresDays = parseInt(editExpiresDays || 1) + 1" 
                                                class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                            </button>
                                        </div>

                                        {{-- Quick Select Buttons --}}
                                        <div class="flex items-center gap-1">
                                            <template x-for="val in [1, 3, 7, 30]">
                                                <button type="button" @click="editExpiresDays = val; editCustomExpiry = ''"
                                                    class="w-10 h-8 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[10px] font-black transition-all shrink-0"
                                                    :class="editExpiresDays == val && !editCustomExpiry ? 'bg-cyan-500 text-white border-cyan-500 shadow-lg shadow-cyan-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 hover:border-cyan-500/50'">
                                                    <span x-text="val + ' ' + '{{ __('d') }}'"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Custom Expiry Input --}}
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-1">{{ __('Or Choose Custom Date') }}</label>
                                    <div class="relative group">
                                        <input type="text" x-model="editCustomExpiry"
                                            x-init="const fp = flatpickr($el, { 
                                                enableTime: true, 
                                                dateFormat: 'Y-m-d H:i', 
                                                time_24hr: true,
                                                minuteIncrement: 1,
                                                disableMobile: true,
                                                locale: window.flatpickrLocale
                                            }); $watch('editCustomExpiry', v => fp.setDate(v, false))"
                                            class="luxury-input w-full py-3 text-center font-mono text-base bg-white dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 pl-4 pr-12 focus:border-cyan-500 transition-all"
                                            placeholder="{{ __('Choose Date') }}">
                                        <div class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 group-hover:text-cyan-500 transition-colors pointer-events-none">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Expected Expiry Card --}}
                            <div class="pt-6" :class="{ 'border-t border-slate-100 dark:border-slate-800/50 mt-6': editExpiryMode === 'date' }">
                                <div class="flex items-center justify-between mb-3 px-1">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Estimated Expiry') }}</span>
                                    <span class="text-[10px] font-bold text-slate-400/70 tracking-wide" x-text="editExpiryMode === 'permanent' ? '{{ __('Permanent') }}' : '{{ __('Expiry Date Set') }}'"></span>
                                </div>
                                <div class="bg-white dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-700/50 flex items-center justify-center gap-3">
                                    <svg class="w-5 h-5 text-cyan-500/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xl font-mono font-black text-slate-700 dark:text-slate-200 tracking-wider" x-text="getDisplayEditExpiry()"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="showEditModal = false"
                            class="flex-1 px-8 py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-[0.2em] hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            class="flex-[2] px-8 py-4 rounded-2xl bg-cyan-500 text-white font-black text-xs uppercase tracking-[0.2em] hover:bg-cyan-600 shadow-xl shadow-cyan-500/25 transition-all active:scale-95">
                            {{ __('Update Welcome Gift') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- QR Modal --}}
    <div x-show="showQrModal" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 transition-opacity" @click="showQrModal = false">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            </div>

            <div
                class="relative bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 w-full max-w-sm overflow-hidden animate-luxury-in">
                <div
                    class="px-8 py-6 border-b border-slate-50 dark:border-slate-800/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">{{ __('QR Code') }}
                        </h3>
                        <p class="text-xs font-bold text-cyan-600 dark:text-cyan-400 mt-1 uppercase tracking-widest">
                            {{ __('Welcome Gift Code') }}: <span class="font-mono" x-text="activeQrCode"></span>
                        </p>
                    </div>
                    <button @click="showQrModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-10 flex flex-col items-center gap-6">
                    <div class="p-4 bg-white rounded-3xl shadow-xl border border-slate-100">
                        <x-qr-code data="activeQrCode" size="200" class="w-48 h-48" />
                    </div>

                    <div class="text-center w-full space-y-4">
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 leading-relaxed px-4">
                            {{ __('Scan this code at the machine or share the link with the customer.') }}
                        </p>

                        <div class="flex items-center justify-center gap-3">
                            <button @click="copyQrLink()"
                                class="flex-1 py-3 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-slate-100 dark:border-slate-700 hover:border-cyan-500/20 transition-all flex items-center justify-center gap-2 group"
                                title="{{ __('Copy Link') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 006.364 6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                </svg>
                                <span class="text-xs font-black uppercase tracking-widest">{{ __('Link') }}</span>
                            </button>

                            <button @click="copyQrCode()"
                                class="flex-1 py-3 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-slate-100 dark:border-slate-700 hover:border-cyan-500/20 transition-all flex items-center justify-center gap-2 group"
                                title="{{ __('Copy Code') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span class="text-xs font-black uppercase tracking-widest">{{ __('Copy') }}</span>
                            </button>

                            <button @click="downloadQrCode()"
                                class="flex-1 py-3 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-emerald-500 hover:bg-emerald-500/5 dark:hover:bg-emerald-500/10 border border-slate-100 dark:border-slate-700 hover:border-emerald-500/20 transition-all flex items-center justify-center gap-2 group"
                                title="{{ __('Download Image') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 12l4.5 4.5m0 0l4.5-4.5M12 3v13.5" />
                                </svg>
                                <span class="text-xs font-black uppercase tracking-widest">{{ __('Save') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="px-8 py-6 bg-slate-50 dark:bg-slate-900/50 flex justify-center border-t border-slate-100 dark:border-slate-800">
                    <button @click="showQrModal = false" class="btn-luxury-primary w-full py-4 rounded-2xl">{{
                        __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <x-confirm-modal alpineVar="showDeleteModal" confirmAction="submitDelete()" :title="__('Confirm Deactivation')"
        :message="__('Are you sure you want to deactivate this welcome gift? It will no longer be usable by guests.')"
        :confirmText="__('Yes, Deactivate')" :cancelText="__('Cancel')" iconType="danger" confirmColor="rose" />
</div>
@endsection
