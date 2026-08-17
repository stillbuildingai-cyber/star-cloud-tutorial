@extends('layouts.admin')

@section('content')
<div class="space-y-2 pb-20" x-data="{
    activeTab: '{{ $tab ?? 'list' }}',
    tabLoading: null,
    showCreateModal: false,
    showEditModal: false,
    showDeleteModal: false,
    deleteTargetForm: '',
    selectedMachine: '',
    passCodeName: '',
    expiresDays: 7,
    customCode: '',
    quantity: 1,
    maxBatchQty: {{ (int) ($maxBatchQuantity ?? 20) }},
    batchQuickOptions: @js($batchQuickOptions ?? [10, 20]),
    status: '{{ request('status', '') }}',
    showQrModal: false,
    activeQrCode: '',
    activeTicketUrl: '',
    editFormAction: '',
    editPassCodeName: '',
    editExpiresAt: '',

    openEditModal(item) {
        this.editFormAction = '{{ route('admin.sales.pass-codes.update', ':id') }}'.replace(':id', item.id);
        this.editPassCodeName = item.name;
        this.editExpiresAt = item.expires_at;
        this.showEditModal = true;
        this.$nextTick(() => {
            window.dispatchEvent(new CustomEvent('set-date', {
                detail: { name: 'expires_at', value: this.editExpiresAt }
            }));
        });
    },

    generateRandomCode() {
        this.customCode = Math.floor(Math.random() * 90000000 + 10000000).toString();
    },

    calculateExpiry() {
        if (!this.expiresDays || parseInt(this.expiresDays) <= 0) {
            return '{{ __('Permanent') }}';
        }
        const date = new Date();
        const days = parseInt(this.expiresDays) || 0;
        date.setDate(date.getDate() + days);
        return date.toLocaleString('zh-TW', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }).replace(/\//g, '-');
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
            window.showToast('{{ __('Please select a machine') }}', 'error');
            return;
        }
        if (!this.passCodeName.trim()) {
            window.showToast('{{ __('Please enter a description') }}', 'error');
            return;
        }
        if (parseInt(this.quantity) <= 1 && !this.customCode.trim()) {
            window.showToast('{{ __('Please enter or generate a pass code') }}', 'error');
            return;
        }
        e.target.submit();
    },

    copyQrCode() {
        if (!this.activeQrCode) return;
        navigator.clipboard.writeText(this.activeQrCode).then(() => {
            window.showToast('{{ __('Code Copied') }}', 'success');
        });
    },

    copyQrLink() {
        if (!this.activeTicketUrl) return;
        navigator.clipboard.writeText(this.activeTicketUrl).then(() => {
            window.showToast('{{ __('Link Copied') }}', 'success');
        });
    },

    async downloadQrCode() {
        const url = '{{ route('admin.basic-settings.qr-code') }}?size=500&data=' + encodeURIComponent(this.activeQrCode);
        fetch(url)
            .then(response => response.blob())
            .then(blob => {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `pass-code-${this.activeQrCode}.png`;
                link.click();
                URL.revokeObjectURL(link.href);
            });
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
                this.passCodeName = '';
                this.expiresDays = 7;
                this.quantity = 1;
                this.generateRandomCode();
                this.syncSelect('modal-pass-machine', '');
            }
        });
    }
} " @ajax:navigate.window.prevent="fetchTabData(activeTab, $event.detail.url)">
    @include('admin.sales.partials.batch-result-banner')

    {{-- Page Header --}}
    <x-page-header :title="__('Pass Codes')"
        :subtitle="__('Manage multi-use authorization codes for testing and maintenance')">
        <button @click="showCreateModal = true; generateRandomCode()" class="btn-luxury-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>{{ __('Add Pass Code') }}</span>
        </button>
    </x-page-header>

    {{-- Tabs --}}
    <x-tab-nav model="activeTab">
        <x-tab-nav-item value="list" :label="__('Pass Codes')" model="activeTab" />
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
                        @include('admin.sales.pass-codes.partials.tab-list')
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
                        @include('admin.sales.pass-codes.partials.tab-logs')
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div x-show="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto"
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
                        __('Add Pass Code') }}</h3>
                    <button @click="showCreateModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.sales.pass-codes.store') }}" method="POST" class="space-y-6"
                    @submit.prevent="validateAndSubmit">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Target Machine') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select name="machine_id" id="modal-pass-machine"
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
                                {{ __('Description / Name') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" x-model="passCodeName" class="luxury-input w-full"
                                placeholder="{{ __('e.g. Test Code for Maintenance') }}">
                        </div>
                    </div>

                    {{-- Quantity (Batch Generate) --}}
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                            {{ __('Generate Quantity') }} (1-{{ $maxBatchQuantity ?? 20 }})
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center h-12 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden flex-1 group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                <button type="button" @click="quantity = Math.max(1, parseInt(quantity || 1) - 1)"
                                    class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                </button>
                                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                <input type="number" name="quantity" x-model="quantity" min="1" :max="maxBatchQty"
                                    @input="if (parseInt(quantity) > maxBatchQty) quantity = maxBatchQty"
                                    class="flex-1 min-w-0 bg-transparent border-none text-center text-lg font-black text-slate-800 dark:text-white focus:ring-0 p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                <button type="button" @click="quantity = Math.min(maxBatchQty, parseInt(quantity || 1) + 1)"
                                    class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <template x-for="val in batchQuickOptions" :key="val">
                                    <button type="button" @click="quantity = val"
                                        class="px-2 h-10 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[11px] font-black transition-all shrink-0"
                                        :class="quantity == val ? 'bg-cyan-500 text-white border-cyan-500 shadow-lg shadow-cyan-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 hover:border-cyan-500/50'">
                                        <span x-text="val"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p class="text-[10px] font-bold text-amber-500 pl-1" x-show="parseInt(quantity) > 1" x-cloak>
                            {{ __('Batch mode: codes are auto-generated and the custom code is ignored.') }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Pass Code (8 Digits)') }} <span class="text-rose-500">*</span>
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
                            :disabled="parseInt(quantity) > 1" :required="parseInt(quantity) <= 1"
                            :class="parseInt(quantity) > 1 ? 'opacity-40 cursor-not-allowed' : ''"
                            class="luxury-input w-full py-4 font-mono text-2xl tracking-[0.3em] text-center text-cyan-600 dark:text-cyan-400 bg-cyan-50/30 dark:bg-cyan-500/5"
                            maxlength="12">
                    </div>

                    <div class="space-y-4">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                            {{ __('Validity Period (Days)') }}
                        </label>
                        <div
                            class="bg-slate-50 dark:bg-slate-800/40 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800/50 shadow-sm">
                            <div class="flex items-center gap-6 mb-6">
                                <button type="button" @click="expiresDays = Math.max(0, parseInt(expiresDays || 0) - 1)"
                                    class="shrink-0 w-12 h-12 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:shadow-lg active:scale-90 transition-all border border-slate-100 dark:border-slate-800">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                                    </svg>
                                </button>
                                <div class="flex-1 relative">
                                    <input type="number" name="expires_days" x-model="expiresDays"
                                        class="w-full bg-transparent border-none text-center font-black text-3xl text-slate-800 dark:text-white focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        placeholder="{{ __('Permanent') }}">
                                    <div
                                        class="text-center mt-1 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        {{ __('Days') }}</div>
                                </div>
                                <button type="button" @click="expiresDays = parseInt(expiresDays || 0) + 1"
                                    class="shrink-0 w-12 h-12 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:shadow-lg active:scale-90 transition-all border border-slate-100 dark:border-slate-800">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>

                            <div class="pt-6 border-t border-slate-100 dark:border-slate-800/50">
                                <div class="flex items-center justify-between mb-3 px-1">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{
                                        __('Estimated Expiry') }}</span>
                                    <template x-if="!expiresDays || expiresDays <= 0">
                                        <span
                                            class="text-[10px] font-black text-emerald-500 uppercase tracking-widest px-2 py-0.5 bg-emerald-500/10 rounded-lg border border-emerald-500/20">{{
                                            __('Permanent Code') }}</span>
                                    </template>
                                </div>
                                <div
                                    class="bg-white dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-700/50 flex items-center justify-center gap-3">
                                    <svg class="w-5 h-5 text-cyan-500/50 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span
                                        class="text-xl font-mono font-black text-slate-700 dark:text-slate-200 tracking-wider"
                                        x-text="calculateExpiry()"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 只能使用一次：勾選 → usage_limit=1；不勾 → 無限次（維持原行為） --}}
                    <label class="flex items-center gap-3 px-1 cursor-pointer select-none">
                        <input type="checkbox" name="single_use" value="1"
                            class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-cyan-500 focus:ring-cyan-500 cursor-pointer">
                        <span class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ __('Single use only') }}</span>
                    </label>

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
    <div x-show="showEditModal" class="fixed inset-0 z-50 overflow-y-auto"
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
                        __('Edit Pass Code') }}</h3>
                    <button @click="showEditModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="editFormAction" method="POST" class="space-y-6">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                            {{ __('Description / Name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="editPassCodeName" class="luxury-input w-full"
                            placeholder="{{ __('e.g. Test Code for Maintenance') }}" required>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                            {{ __('Expires At') }}
                        </label>
                        <x-luxury-datetime-input name="expires_at" :value="''"
                            :placeholder="__('Permanent if empty')" />
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="showEditModal = false"
                            class="flex-1 px-8 py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-[0.2em] hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            class="flex-[2] px-8 py-4 rounded-2xl bg-cyan-500 text-white font-black text-xs uppercase tracking-[0.2em] hover:bg-cyan-600 shadow-xl shadow-cyan-500/25 transition-all active:scale-95">
                            {{ __('Update Pass Code') }}
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
                            {{ __('Pass Code') }}: <span class="font-mono" x-text="activeQrCode"></span>
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
                            {{ __('Scan this code at the machine or share the link with the maintenance staff.') }}
                        </p>

                        <div class="flex items-center justify-center gap-3">
                            {{-- Copy Link Button --}}
                            <button @click="copyQrLink()"
                                class="flex-1 py-3 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-slate-100 dark:border-slate-700 hover:border-cyan-500/20 transition-all flex items-center justify-center gap-2 group"
                                title="{{ __('Copy Link') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 006.364 6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                </svg>
                                <span class="text-xs font-black uppercase tracking-widest">{{ __('Link') }}</span>
                            </button>

                            {{-- Copy Code Button --}}
                            <button @click="copyQrCode()"
                                class="flex-1 py-3 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-slate-100 dark:border-slate-700 hover:border-cyan-500/20 transition-all flex items-center justify-center gap-2 group"
                                title="{{ __('Copy Code') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span class="text-xs font-black uppercase tracking-widest">{{ __('Copy') }}</span>
                            </button>

                            {{-- Download Image Button --}}
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
        :message="__('Are you sure you want to deactivate this pass code? It will no longer be usable for authentication.')"
        :confirmText="__('Yes, Deactivate')" :cancelText="__('Cancel')" iconType="danger" confirmColor="rose" />
</div>
@endsection