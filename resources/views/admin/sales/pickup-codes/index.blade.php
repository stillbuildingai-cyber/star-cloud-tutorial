@extends('layouts.admin')

@php
$slotSelectConfig = [
"placeholder" => __("Select Product"),
"hasSearch" => true,
"searchPlaceholder" => __("Search Product..."),
"isHidePlaceholder" => false,
"searchClasses" => "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg
focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200
placeholder:text-slate-400 dark:placeholder:text-slate-500",
"searchWrapperClasses" => "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
"toggleClasses" => "hs-select-toggle luxury-select-toggle",
"toggleTemplate" => '<button type="button" aria-expanded="false"><span class="me-2" data-icon></span><span
        class="text-slate-800 dark:text-slate-200" data-title></span>
    <div class="ms-auto"><svg class="size-4 text-slate-400 transition-transform duration-300"
            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6" />
        </svg></div>
</button>',
"dropdownClasses" => "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10
rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[150] animate-luxury-in",
"optionClasses" => "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer
hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between
transition-all duration-300",
"optionTemplate" => '<div class="flex items-center justify-between w-full"><span data-title></span><span
        class="hs-select-active-indicator hidden text-cyan-500"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg"
            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
            stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg></span></div>'
];
@endphp

@section('content')
<div class="space-y-2 pb-20" x-data="pickupCodeManager(@js($slotSelectConfig))"
    @ajax:navigate.window.prevent="fetchTabData(activeTab, $event.detail.url)">

    @include('admin.sales.partials.batch-result-banner')

    {{-- Page Header --}}
    <x-page-header :title="__('Pickup Codes')"
        :subtitle="__('Generate and manage one-time pickup codes for customers')">
        <button @click="showCreateModal = true"
            class="btn-luxury-primary whitespace-nowrap flex items-center gap-2 transition-all duration-300">
            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
            <span class="text-sm sm:text-base">{{ __('Add Pickup Code') }}</span>
        </button>
    </x-page-header>

    {{-- Tab Navigation --}}
    <x-tab-nav model="activeTab">
        <x-tab-nav-item value="list" :label="__('Pickup Codes')" model="activeTab" />
        <x-tab-nav-item value="logs" :label="__('Usage Logs')" model="activeTab" />
    </x-tab-nav>

    {{-- Tab Contents --}}
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
                        @include('admin.sales.pickup-codes.partials.tab-list')
                    </div>
                </div>
            </div>
            {{-- End List Tab --}}

            {{-- Logs Tab --}}
            <div x-show="activeTab === 'logs'" class="relative min-h-[400px]"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden">
                    <x-luxury-spinner show="tabLoading === 'logs'" />
                    <div id="tab-logs-container" class="relative"
                        :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': tabLoading === 'logs' }">
                        @include('admin.sales.pickup-codes.partials.tab-logs')
                    </div>
                </div>
            </div>
            {{-- End Logs Tab --}}
        </div> {{-- End relative min-h --}}
    </div> {{-- End mt-6 --}}

    {{-- Edit Pickup Code Modal --}}
    <div x-show="showEditModal" 
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak>
        
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showEditModal = false"></div>

        <div class="relative w-full max-w-2xl luxury-card bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-slate-900/20 overflow-hidden border border-slate-100 dark:border-slate-800"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-8"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            
            <form :action="`/admin/sales/pickup-codes/${editPickupCodeId}`" method="POST" class="p-8">
                @csrf
                @method('PUT')
                
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-600 border border-cyan-500/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">{{ __('Edit Pickup Code') }}</h3>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Update usage and expiry') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="showEditModal = false" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-8">
                    {{-- Usage Limit --}}
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-4">{{ __('Usage Limit') }} (1-20)</label>
                        <div class="flex flex-col sm:flex-row items-center gap-10">
                            {{-- Unified Luxury Counter --}}
                            <div class="flex items-center h-12 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden flex-1 min-w-[200px] group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                <button type="button" @click="editUsageLimit > 1 ? editUsageLimit-- : 1" 
                                    class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                </button>
                                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                <div class="flex-1 min-w-[60px]">
                                    <input type="text" name="usage_limit" x-model="editUsageLimit" readonly
                                        class="w-full bg-transparent border-none text-center text-lg font-black text-slate-800 dark:text-white focus:ring-0 p-0">
                                </div>
                                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                <button type="button" @click="editUsageLimit < 20 ? editUsageLimit++ : 20" 
                                    class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                </button>
                            </div>

                            {{-- Quick Select Buttons --}}
                            <div class="flex items-center gap-1.5">
                                <template x-for="val in [1, 5, 10, 20]">
                                    <button type="button" @click="editUsageLimit = val"
                                        class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[11px] font-black transition-all shrink-0"
                                        :class="editUsageLimit == val ? 'bg-cyan-500 text-white border-cyan-500 shadow-lg shadow-cyan-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 hover:border-cyan-500/50'">
                                        <span x-text="val"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Expiry Date --}}
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-4">{{ __('Expires At') }}</label>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                                <svg class="w-4 h-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            <input type="text" name="expires_at" x-model="editExpiresAt"
                                x-init="const fp = flatpickr($el, { 
                                    enableTime: true, 
                                    dateFormat: 'Y-m-d H:i', 
                                    time_24hr: true,
                                    minuteIncrement: 1,
                                    disableMobile: true,
                                    locale: window.flatpickrLocale
                                }); $watch('editExpiresAt', v => fp.setDate(v, false))"
                                class="py-3 pl-12 pr-4 block w-full luxury-input text-sm font-bold">
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex gap-3">
                    <button type="button" @click="showEditModal = false"
                        class="flex-1 py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-black text-xs uppercase tracking-[0.2em] hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="flex-[2] py-4 rounded-2xl bg-cyan-500 text-white font-black text-xs uppercase tracking-[0.2em] hover:bg-cyan-600 shadow-xl shadow-cyan-500/25 transition-all active:scale-[0.98]">
                        {{ __('Update') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Create Pickup Code Modal --}}
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
                        __('Add Pickup Code') }}</h3>
                    <button @click="showCreateModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.sales.pickup-codes.store') }}" method="POST" class="space-y-6"
                    @submit.prevent="validateAndSubmit" x-ref="createForm">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                __('Target Machine') }} <span class="text-rose-500">*</span></label>
                            <x-searchable-select name="machine_id" id="modal-pickup-machine"
                                placeholder="{{ __('Please select a machine') }}" x-model="selectedMachine"
                                @change="selectedMachine = $event.target.value; fetchCompanyProducts()">
                                @foreach($machines as $machine)
                                <option value="{{ $machine->id }}"
                                    data-title="{{ $machine->name }} ({{ $machine->serial_no }})">{{ $machine->name }}
                                    ({{ $machine->serial_no }})</option>
                                @endforeach
                            </x-searchable-select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                __('Select Product') }} <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div id="slot-select-wrapper" class="relative">
                                    {{-- Rebuilt by Alpine --}}
                                </div>
                                <div x-show="loadingSlots" class="absolute right-12 top-1/2 -translate-y-1/2 z-10">
                                    <x-luxury-spinner show="loadingSlots" />
                                </div>
                            </div>
                            <p x-show="productNotOnMachine" x-cloak class="text-[11px] font-bold text-amber-500 pl-1 flex items-center gap-1">
                                <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 0 0-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                                {{ __('This product is not stocked on the selected machine; the code may not be redeemable until restocked.') }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                __('Usage Limit') }} (Max 20)</label>
                            <div class="flex flex-col sm:flex-row items-center gap-10">
                                {{-- Unified Luxury Counter --}}
                                <div class="flex items-center h-12 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden flex-1 min-w-[200px] group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                    <button type="button" @click="usageLimit > 1 ? usageLimit-- : 1" 
                                        class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                    </button>
                                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                    <div class="flex-1 min-w-[60px]">
                                        <input type="text" name="usage_limit" x-model="usageLimit" readonly
                                            class="w-full bg-transparent border-none text-center text-lg font-black text-slate-800 dark:text-white focus:ring-0 p-0">
                                    </div>
                                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                    <button type="button" @click="usageLimit < 20 ? usageLimit++ : 20" 
                                        class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                    </button>
                                </div>

                                {{-- Quick Select Buttons - Removed scrollbar --}}
                                <div class="flex items-center gap-1.5">
                                    <template x-for="val in [1, 5, 10, 20]">
                                        <button type="button" @click="usageLimit = val"
                                            class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[11px] font-black transition-all shrink-0"
                                            :class="usageLimit == val ? 'bg-cyan-500 text-white border-cyan-500 shadow-lg shadow-cyan-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 hover:border-cyan-500/50'">
                                            <span x-text="val"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Quantity (Batch Generate) — 獨立整列，避免與使用次數上限擠在同一列重疊。上限/快捷選項依角色分級 --}}
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{
                            __('Generate Quantity') }} (1-{{ $maxBatchQuantity ?? 20 }})</label>
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

                    {{-- Pickup Code Preview Section --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                                {{ __('Pickup Code (8 Digits)') }} <span class="text-rose-500">*</span>
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

                    {{-- Validity Period Section (Flexible) --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Validity Period') }}</label>
                            <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                                <button type="button" @click="expiryMode = 'hours'" 
                                    class="px-3 py-1 text-[10px] font-black uppercase tracking-tighter rounded-lg transition-all"
                                    :class="expiryMode === 'hours' ? 'bg-white dark:bg-slate-700 text-cyan-600 shadow-sm' : 'text-slate-400'">{{ __('Hours') }}</button>
                                <button type="button" @click="expiryMode = 'date'" 
                                    class="px-3 py-1 text-[10px] font-black uppercase tracking-tighter rounded-lg transition-all"
                                    :class="expiryMode === 'date' ? 'bg-white dark:bg-slate-700 text-cyan-600 shadow-sm' : 'text-slate-400'">{{ __('Custom Date') }}</button>
                            </div>
                        </div>
        
                        <div class="bg-slate-50 dark:bg-slate-800/40 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800/50 shadow-sm">
                            <div x-show="expiryMode === 'hours'" x-transition>
                                <div class="flex items-center gap-6 mb-6">
                                    <div class="flex-1 relative">
                                        <input type="range" name="expires_hours" x-model="expiresHours" min="1" max="168"
                                            :disabled="expiryMode !== 'hours'"
                                            class="w-full h-1.5 bg-slate-200 dark:bg-slate-700/50 rounded-lg appearance-none cursor-pointer accent-cyan-500">
                                    </div>
                                    <div class="shrink-0 w-24 py-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm text-center">
                                        <span class="text-xl font-black text-slate-800 dark:text-white" x-text="expiresHours"></span>
                                        <span class="block text-[9px] font-black text-slate-400 uppercase mt-0.5 tracking-widest">{{ __('Hrs') }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <template x-for="h in [24, 48, 72, 168]">
                                        <button type="button" @click="expiresHours = h"
                                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-[10px] font-black transition-all"
                                            :class="expiresHours == h ? 'bg-cyan-500 text-white border-cyan-500' : 'bg-white dark:bg-slate-800 text-slate-500'">
                                            <span x-text="h == 168 ? '7 {{ __('Days') }}' : h + 'h'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
        
                            <div x-show="expiryMode === 'date'" x-transition>
                                <div class="relative group mb-6">
                                    <input type="text" name="expires_at" x-model="customExpiry"
                                        :disabled="expiryMode !== 'date'"
                                        x-init="const fp = flatpickr($el, {
                                            enableTime: true, 
                                            dateFormat: 'Y-m-d H:i', 
                                            time_24hr: true,
                                            minuteIncrement: 1,
                                            disableMobile: true,
                                            locale: window.flatpickrLocale
                                        }); $watch('customExpiry', v => fp.setDate(v, false))"
                                        class="luxury-input w-full py-4 text-center font-mono text-xl bg-white dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 pl-4 pr-12 focus:border-cyan-500 transition-all">
                                    <div class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 group-hover:text-cyan-500 transition-colors pointer-events-none">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
        
                            <div class="pt-6 border-t border-slate-100 dark:border-slate-800/50">
                                <div class="flex items-center justify-between mb-3 px-1">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Expected Expiry') }}</span>
                                    <span class="text-[10px] font-bold text-slate-400/70 tracking-wide" x-text="expiryMode === 'hours' ? '{{ __('Based on Hours') }}' : '{{ __('Custom Date Set') }}'"></span>
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
                        <button type="button" @click="showCreateModal = false" class="btn-luxury-ghost px-8">{{
                            __('Cancel') }}</button>
                        <button type="submit" class="btn-luxury-primary px-12">
                            {{ __('Generate') }}
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
                            {{ __('Pickup Code') }}: <span class="font-mono" x-text="activeQrCode"></span>
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
                            {{-- Copy Link Button --}}
                            <button @click="copyQrLink()"
                                class="flex-1 py-3 px-4 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-slate-100 dark:border-slate-700 hover:border-cyan-500/20 transition-all flex items-center justify-center gap-2 group"
                                title="{{ __('Copy Link') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
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

    {{-- Confirm Cancel Modal --}}
    <x-confirm-modal alpineVar="showCancelModal" :title="__('Cancel Pickup Code')"
        :message="__('Are you sure you want to cancel this pickup code? This action cannot be undone.')"
        :confirmText="__('Yes, Cancel')" confirmAction="submitCancel()" confirmColor="rose" iconType="danger" />
</div>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pickupCodeManager', (config) => ({
            activeTab: '{{ request("tab", "list") }}',
            tabLoading: null,
            showCreateModal: false,
            selectedMachine: '',
            selectedProduct: '',
            expiresHours: 24,
            maxHours: 168,
            expiryMode: 'hours',
            customExpiry: '',
            usageLimit: 1,
            quantity: 1,
            maxBatchQty: {{ (int) ($maxBatchQuantity ?? 20) }},
            batchQuickOptions: @js($batchQuickOptions ?? [10, 20]),
            showQrModal: false,
            activeQrCode: '',
            activeTicketUrl: '',
            companyProducts: [],
            machineProductIds: [],
            productNotOnMachine: false,
            tabLoading: null,
            loadingSlots: false,
            showCancelModal: false,
            cancelTargetForm: null,
            slotSelectConfig: config,
            customCode: '',

            // Edit Pickup Code State
            showEditModal: false,
            editPickupCodeId: '',
            editUsageLimit: 1,
            editExpiresAt: '',



            async fetchTabData(tab, url = null) {
                if (this.tabLoading === tab) return;
                this.tabLoading = tab;
                const container = document.getElementById('tab-' + tab + '-container');

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

            generateRandomCode() {
                this.customCode = Math.floor(Math.random() * 90000000 + 10000000).toString();
            },

            calculateExpiry() {
                const date = new Date();
                date.setHours(date.getHours() + parseInt(this.expiresHours));
                return this.formatDate(date);
            },

            formatDate(date) {
                return date.getFullYear() + '-' +
                    String(date.getMonth() + 1).padStart(2, '0') + '-' +
                    String(date.getDate()).padStart(2, '0') + ' ' +
                    String(date.getHours()).padStart(2, '0') + ':' +
                    String(date.getMinutes()).padStart(2, '0');
            },

            getDisplayExpiry() {
                if (this.expiryMode === 'date' && this.customExpiry) {
                    return this.customExpiry.replace('T', ' ');
                }
                return this.calculateExpiry();
            },

            // 取貨碼綁商品：下拉以「機台所屬公司的商品」為準（非僅機台貨道）；
            // machineProductIds 用來標示/提示「此機台目前未上架」的商品。
            async fetchCompanyProducts() {
                if (!this.selectedMachine) {
                    this.companyProducts = [];
                    this.machineProductIds = [];
                    return;
                }
                this.loadingSlots = true;
                try {
                    const response = await fetch(`/admin/sales/pickup-codes/company-products/${this.selectedMachine}`);
                    const data = await response.json();
                    this.companyProducts = data.products || [];
                    this.machineProductIds = (data.machine_product_ids || []).map(id => String(id));
                } catch (error) {
                    console.error('Error fetching company products:', error);
                } finally {
                    this.loadingSlots = false;
                }
            },

            validateAndSubmit() {
                if (!this.selectedMachine || this.selectedMachine.toString().trim() === '') {
                    window.showToast('{{ __("Please select a machine") }}', 'error');
                    return;
                }
                if (!this.selectedProduct) {
                    window.showToast('{{ __("Please select a product") }}', 'error');
                    return;
                }
                this.$refs.createForm.submit();
            },

            openEditModal(id, limit, expiry) {
                this.editPickupCodeId = id;
                this.editUsageLimit = parseInt(limit);
                this.editExpiresAt = expiry;
                this.showEditModal = true;
            },
            copyQrLink() {
                if (!this.activeTicketUrl) return;
                navigator.clipboard.writeText(this.activeTicketUrl).then(() => {
                    window.showToast("{{ __('Link Copied') }}", 'success');
                });
            },

            copyQrCode() {
                if (!this.activeQrCode) return;
                navigator.clipboard.writeText(this.activeQrCode).then(() => {
                    window.showToast("{{ __('Code Copied') }}", 'success');
                });
            },

            downloadQrCode() {
                const url = `{{ route('admin.basic-settings.qr-code') }}?size=500&data=` + encodeURIComponent(this.activeQrCode);
                fetch(url)
                    .then(response => response.blob())
                    .then(blob => {
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = `pickup-code-${this.activeQrCode}.png`;
                        link.click();
                        URL.revokeObjectURL(link.href);
                    });
            },

            confirmCancel(formId) {
                this.cancelTargetForm = formId;
                this.showCancelModal = true;
            },

            submitCancel() {
                if (this.cancelTargetForm) {
                    const form = document.getElementById(this.cancelTargetForm);
                    if (form) form.submit();
                }
                this.showCancelModal = false;
            },

            // 取貨碼改綁商品：下拉改列「此機台有上架的商品」（由貨道清單去重），
            // 提交 product_id；貨道改由 App 刷碼當下依即時庫存挑選。
            updateSlotSelect() {
                this.$nextTick(() => {
                    const wrapper = document.getElementById('slot-select-wrapper');
                    if (!wrapper) return;

                    wrapper.querySelectorAll('select').forEach(s => {
                        try { window.HSSelect?.getInstance(s)?.destroy(); } catch (e) { }
                    });
                    wrapper.innerHTML = '';

                    if (!this.selectedMachine) {
                        const dummy = document.createElement('div');
                        dummy.className = 'luxury-input opacity-50 cursor-not-allowed flex items-center justify-between';
                        dummy.innerHTML = `<span>{{ __("Select Product") }}</span><svg class="size-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>`;
                        wrapper.appendChild(dummy);
                        return;
                    }

                    const selectEl = document.createElement('select');
                    selectEl.name = 'product_id';
                    // Remove required to allow our custom validation toast to trigger
                    selectEl.id = 'modal-pickup-product-' + Date.now();
                    selectEl.className = 'hidden';

                    // 清理配置字串中的換行符
                    const cleanConfig = JSON.parse(JSON.stringify(this.slotSelectConfig), (key, value) => {
                        return typeof value === 'string' ? value.replace(/\r?\n|\r/g, ' ').trim() : value;
                    });
                    selectEl.setAttribute('data-hs-select', JSON.stringify(cleanConfig));

                    const placeholderOpt = document.createElement('option');
                    placeholderOpt.value = "";
                    placeholderOpt.textContent = "{{ __('Select Product') }}";
                    placeholderOpt.setAttribute('data-title', "{{ __('Select Product') }}");
                    selectEl.appendChild(placeholderOpt);

                    // 列出機台所屬公司的商品；機台目前未上架者於名稱後標示提示
                    this.companyProducts.forEach(product => {
                        const opt = document.createElement('option');
                        opt.value = product.id;
                        const onMachine = this.machineProductIds.includes(String(product.id));
                        const text = onMachine ? product.name : `${product.name}（{{ __('Not stocked on this machine') }}）`;
                        opt.textContent = text;
                        opt.setAttribute('data-title', text);
                        if (this.selectedProduct == product.id) opt.selected = true;
                        selectEl.appendChild(opt);
                    });

                    wrapper.appendChild(selectEl);
                    selectEl.addEventListener('change', e => {
                        this.selectedProduct = e.target.value;
                        // 選到機台未上架的商品 → 提示，但仍允許產碼
                        this.productNotOnMachine = !!this.selectedProduct
                            && !this.machineProductIds.includes(String(this.selectedProduct));
                        if (this.productNotOnMachine) {
                            window.showToast('{{ __("This product is not stocked on the selected machine; the code may not be redeemable until restocked.") }}', 'warning');
                        }
                    });

                    if (window.HSStaticMethods?.autoInit) {
                        window.HSStaticMethods.autoInit(['select']);
                    }
                });
            },

            syncSelect(id, value) {
                this.$nextTick(() => {
                    const el = document.getElementById(id);
                    if (el) {
                        const valStr = (value !== undefined && value !== null && value.toString().trim() !== '') ? value.toString() : ' ';
                        el.value = valStr;
                        window.HSSelect?.getInstance(el)?.setValue(valStr);
                    }
                });
            },

            init() {
                this.generateRandomCode();

                // Tab 切換時更新 URL（同庫存管理模式）
                this.$watch('activeTab', (newTab) => {
                    const url = new URL(window.location.origin + window.location.pathname);
                    url.searchParams.set('tab', newTab);
                    window.history.pushState({}, '', url);

                    // Tab data is pre-rendered; refresh only via filters/pagination or specific actions


                    this.$nextTick(() => {
                        if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
                    });
                });

                this.$watch('companyProducts', () => this.updateSlotSelect());
                this.$watch('selectedMachine', (val) => {
                    this.syncSelect('modal-pickup-machine', val);
                    this.selectedProduct = ''; // Reset product when machine changes
                    this.productNotOnMachine = false;
                    this.fetchCompanyProducts();
                });
                this.$watch('selectedProduct', (val) => {
                    // Selection toast removed per user request
                });
                this.$watch('expiryMode', (mode) => {
                    if (mode === 'date') {
                        this.customExpiry = this.calculateExpiry();
                    }
                });

                this.$watch('showCreateModal', (val) => {
                    if (val) {
                        this.selectedMachine = '';
                        this.selectedProduct = '';
                        this.productNotOnMachine = false;
                        this.usageLimit = 1;
                        this.quantity = 1;
                        this.expiryMode = 'hours';
                        this.expiresHours = 24;
                        const tomorrow = new Date();
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        tomorrow.setMinutes(0);
                        this.customExpiry = this.formatDate(tomorrow);
                        this.companyProducts = [];
                        this.machineProductIds = [];
                        this.generateRandomCode();
                        this.updateSlotSelect();
                    }
                });
            }
        }));
    });
</script>
@endsection