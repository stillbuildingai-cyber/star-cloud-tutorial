@extends('layouts.admin')

@section('content')
<div class="space-y-2 pb-20" x-data="discordNotificationApp()">
    <!-- Header -->
    <x-page-header
        :title="__('Discord Notifications')"
        :subtitle="__('Configure Discord Webhook alerts for hardware exceptions and connectivity logs')"
    />

    <!-- Tab Selection -->
    <x-tab-nav model="activeTab">
        <x-tab-nav-item value="company" :label="__('Company Settings')" />
        <x-tab-nav-item value="machine" :label="__('Machine Settings')" />
    </x-tab-nav>

    <!-- Main Container -->
    <div id="ajax-discord-container" class="relative mt-6">
        <x-luxury-spinner show="isLoadingTable" />

        <div :class="{ 'opacity-30 pointer-events-none': isLoadingTable }"
             @ajax:navigate:discord.prevent="fetchTabContent($event.detail.url, 'ajax-discord-container')">
            
            <!-- ================= Tab 1: 公司全域設定 ================= -->
            <div x-show="activeTab === 'company'" class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden" x-cloak>
                <!-- 霓虹光斑裝飾 -->
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-cyan-500/5 rounded-full blur-[100px] pointer-events-none"></div>
                
                @if(auth()->user()->isSystemAdmin())
                <form action="{{ route('admin.basic-settings.discord-notifications.index') }}" method="GET"
                      class="flex flex-col md:flex-row md:items-center gap-3 mb-8 relative z-10"
                      @submit.prevent="fetchTabContent($el.action + '?' + new URLSearchParams(new FormData($el)).toString() + '&tab=company', 'ajax-discord-container')">
                    <input type="hidden" name="tab" value="company">
                    <div class="relative group flex-1 md:flex-none">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <input type="text" name="search_company" value="{{ request('search_company') }}"
                            class="py-2.5 pl-12 pr-6 block w-full md:w-80 luxury-input"
                            placeholder="{{ __('Search company name or code...') }}">
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="submit" class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all" title="{{ __('Search') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                        <button type="button"
                            @click="$el.closest('form').querySelector('input[name=search_company]').value=''; $el.closest('form').dispatchEvent(new Event('submit'));"
                            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all" title="{{ __('Reset') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </form>
                @endif

                {{-- 桌面版表格 --}}
                <div class="hidden xl:block overflow-x-auto {{ !auth()->user()->isSystemAdmin() ? 'mt-6' : '' }}">
                    <table class="w-full text-left border-separate border-spacing-y-0">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Company Info') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Webhook URL') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Push Status') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Configured Alert Types') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                            @forelse($companies as $company)
                            @php
                                $settings = $company->settings ?? [];
                                $isEnabled = (bool)($settings['discord_notify_enabled'] ?? false);
                                $webhook = $settings['discord_webhook_url'] ?? '';
                                $types = $settings['discord_notify_types'] ?? [];
                            @endphp
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight truncate">{{ $company->name }}</p>
                                            <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ $company->code }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6 max-w-xs">
                                    @if($webhook)
                                        <span class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400 tracking-tight block truncate" title="{{ $webhook }}">{{ Str::limit($webhook, 40) }}</span>
                                    @else
                                        <span class="text-xs font-bold text-slate-300 dark:text-slate-600">{{ __('Not Configured') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-6 text-center">
                                    <x-status-badge :status="$isEnabled ? 'active' : 'disabled'" size="sm" />
                                </td>
                                <td class="px-6 py-6">
                                    <div class="flex flex-wrap gap-1.5">
                                        @if(empty($types))
                                            <span class="text-xs font-bold text-slate-300 dark:text-slate-600">—</span>
                                        @endif
                                        
                                        @foreach($types as $type)
                                            @php
                                                $label = match($type) {
                                                    'submachine' => __('Device Exception'),
                                                    'status' => __('Connectivity Logs'),
                                                    'temperature' => __('Temperature Alert'),
                                                    default => $type
                                                };
                                            @endphp
                                            <span class="px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-widest bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                                                {{ $label }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-6 text-right">
                                    <button type="button" @click="openCompanyEdit(@js($company))"
                                        class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20 tooltip" title="{{ __('Edit Settings') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <x-empty-state mode="table" :colspan="5" :message="__('No records found')" />
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 行動版/平版 Card 列表 --}}
                <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6 {{ !auth()->user()->isSystemAdmin() ? 'mt-6' : '' }}">
                    @forelse($companies as $company)
                    @php
                        $settings = $company->settings ?? [];
                        $isEnabled = (bool)($settings['discord_notify_enabled'] ?? false);
                        $webhook = $settings['discord_webhook_url'] ?? '';
                        $types = $settings['discord_notify_types'] ?? [];
                    @endphp
                    <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">{{ $company->name }}</h3>
                                    <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">{{ $company->code }}</p>
                                </div>
                            </div>
                            <x-status-badge :status="$isEnabled ? 'active' : 'disabled'" size="sm" />
                        </div>

                        <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                            <div class="col-span-2">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Webhook URL') }}</p>
                                @if($webhook)
                                    <p class="text-xs font-mono font-bold text-slate-600 dark:text-slate-300 break-all leading-normal">{{ $webhook }}</p>
                                @else
                                    <p class="text-xs font-bold text-slate-300 dark:text-slate-600">{{ __('Not Configured') }}</p>
                                @endif
                            </div>
                            <div class="col-span-2">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Configured Alert Types') }}</p>
                                <div class="flex flex-wrap gap-1.5 mt-1">
                                    @if(empty($types))
                                        <span class="text-xs font-bold text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                    
                                    @foreach($types as $type)
                                        @php
                                            $label = match($type) {
                                                'submachine' => __('Device Exception'),
                                                'status' => __('Connectivity Logs'),
                                                'temperature' => __('Temperature Alert'),
                                                default => $type
                                            };
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-widest bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                                            {{ $label }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="button" @click="openCompanyEdit(@js($company))"
                                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                {{ __('Edit Settings') }}
                            </button>
                        </div>
                    </div>
                    @empty
                    <x-empty-state :message="__('No records found')" />
                    @endforelse
                </div>

                {{-- 分頁 --}}
                <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
                    {{ $companies->appends(array_merge(request()->query(), ['tab' => 'company']))->links('vendor.pagination.luxury', [
                        'ajax_navigate_event' => 'ajax:navigate:discord',
                        'per_page_param'      => 'company_per_page',
                    ]) }}
                </div>
            </div>

            <!-- ================= Tab 2: 機台個別設定 ================= -->
            <div x-show="activeTab === 'machine'" class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden" x-cloak>
                <!-- 霓虹光斑裝飾 -->
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-cyan-500/5 rounded-full blur-[100px] pointer-events-none"></div>

                <!-- 搜尋與篩選列 -->
                <form action="{{ route('admin.basic-settings.discord-notifications.index') }}" method="GET"
                      class="flex flex-col md:flex-row md:items-center gap-3 mb-8 relative z-10"
                      @submit.prevent="fetchTabContent($el.action + '?' + new URLSearchParams(new FormData($el)).toString() + '&tab=machine', 'ajax-discord-container')">
                    <input type="hidden" name="tab" value="machine">
                    
                    <!-- 關鍵字 -->
                    <div class="relative group flex-1 md:flex-none">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <input type="text" name="search_machine" value="{{ request('search_machine') }}"
                            class="py-2.5 pl-12 pr-6 block w-full md:w-80 luxury-input"
                            placeholder="{{ __('Search machine name or SN...') }}">
                    </div>

                    <!-- 公司篩選下拉選單 (僅 System Admin 可見) -->
                    @if(auth()->user()->isSystemAdmin())
                    <div class="w-full md:w-64 relative focus-within:z-[20]"
                         @change="$el.closest('form').dispatchEvent(new Event('submit'))">
                        <x-searchable-select name="filter_company_id" :placeholder="__('All Companies')" :selected="request('filter_company_id')">
                            @foreach($allCompanies as $c)
                                <option value="{{ $c->id }}" data-title="{{ $c->name }}" {{ (string)request('filter_company_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </x-searchable-select>
                    </div>
                    @endif

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="submit" class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all" title="{{ __('Search') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                        <button type="button"
                            @click="
                                const form = $el.closest('form');
                                form.querySelector('input[name=search_machine]').value = '';
                                @if(auth()->user()->isSystemAdmin())
                                const nativeSel = form.querySelector('select[name=filter_company_id]');
                                if (nativeSel) {
                                    nativeSel.value = ' ';
                                    const inst = window.HSSelect?.getInstance(nativeSel);
                                    if (inst) inst.setValue(' ');
                                }
                                @endif
                                form.dispatchEvent(new Event('submit'));
                            "
                            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all" title="{{ __('Reset') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </form>

                {{-- 桌面版表格 --}}
                <div class="hidden xl:block overflow-x-auto">
                    <table class="w-full text-left border-separate border-spacing-y-0">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Machine Info') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Model') }}</th>
                                @if(auth()->user()->isSystemAdmin())
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Company') }}</th>
                                @endif
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Webhook Status') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Configured Alert Types') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Temp Alert Range') }}</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                            @forelse($machines as $machine)
                            @php
                                $mSettings = $machine->settings ?? [];
                                $mWebhook = $mSettings['discord_webhook_url'] ?? null;
                                $cSettings = $machine->company->settings ?? [];
                                $cWebhook = $cSettings['discord_webhook_url'] ?? null;
                                $cNotifyTypes = $cSettings['discord_notify_types'] ?? [];
                                $mNotifyTypes = array_key_exists('discord_notify_types', $mSettings) && is_array($mSettings['discord_notify_types']) ? $mSettings['discord_notify_types'] : null;
                                $effectiveNotifyTypes = $mNotifyTypes ?? $cNotifyTypes;
                                $isTypeInherited = $mNotifyTypes === null;
                                $hasTemperatureNotify = in_array('temperature', $effectiveNotifyTypes, true);

                                $mTempAlert = $mSettings['temp_alert_enabled'] ?? 'inherit';

                                // 繼承鏈判定
                                $mUpper = $mSettings['temp_upper_limit'] ?? null;
                                $mLower = $mSettings['temp_lower_limit'] ?? null;

                                $modelSettings = $machine->machineModel->settings ?? [];
                                $modelUpper = $modelSettings['temp_upper_limit'] ?? null;
                                $modelLower = $modelSettings['temp_lower_limit'] ?? null;

                                $sysUpper = 40.0;
                                $sysLower = 0.0;

                                $finalUpper = $sysUpper;
                                $finalLower = $sysLower;
                                $tempSource = __('System Default');

                                if ($mTempAlert === 'enabled' && $hasTemperatureNotify && ($mUpper !== null || $mLower !== null)) {
                                    $finalUpper = $mUpper !== null ? $mUpper : $sysUpper;
                                    $finalLower = $mLower !== null ? $mLower : $sysLower;
                                    $tempSource = __('Custom');
                                } elseif ($hasTemperatureNotify && ($modelUpper !== null || $modelLower !== null)) {
                                    $finalUpper = $modelUpper !== null ? $modelUpper : $sysUpper;
                                    $finalLower = $modelLower !== null ? $modelLower : $sysLower;
                                    $tempSource = __('Model Default');
                                }
                            @endphp
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight truncate">{{ $machine->name }}</p>
                                            <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ $machine->serial_no }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                        {{ $machine->machineModel->name ?? __('No Model') }}
                                    </span>
                                </td>
                                @if(auth()->user()->isSystemAdmin())
                                <td class="px-6 py-6">
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-400">
                                        {{ $machine->company->name ?? __('No Company') }}
                                    </span>
                                </td>
                                @endif
                                <td class="px-6 py-6 max-w-xs">
                                    @if($mWebhook)
                                        <span class="text-xs font-mono font-bold text-slate-855 dark:text-slate-200 block truncate" title="{{ $mWebhook }}">{{ Str::limit($mWebhook, 30) }}</span>
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 mt-1 inline-block">{{ __('Machine Specific') }}</span>
                                    @elseif($cWebhook)
                                        <span class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 block truncate" title="{{ $cWebhook }}">{{ Str::limit($cWebhook, 30) }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700 mt-1 inline-block">{{ __('Inherit Company') }}</span>
                                    @else
                                        <span class="text-xs font-bold text-slate-300 dark:text-slate-600">{{ __('Not Configured') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-6">
                                    <div class="space-y-1.5">
                                        @if($isTypeInherited && !empty($effectiveNotifyTypes))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700 inline-block">{{ __('Inherit Company') }}</span>
                                        @endif
                                        <div class="flex flex-wrap gap-1.5">
                                                @foreach($effectiveNotifyTypes as $type)
                                                    @php
                                                        $label = match($type) {
                                                            'submachine' => __('Device Exception'),
                                                            'status' => __('Connectivity Logs'),
                                                            'temperature' => __('Temperature Alert'),
                                                            default => $type
                                                        };
                                                    @endphp
                                                <span class="px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-widest whitespace-nowrap bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">{{ $label }}</span>
                                            @endforeach
                                            @if(empty($effectiveNotifyTypes))
                                                <span class="text-xs font-bold text-slate-300 dark:text-slate-600">{{ __('Not Configured') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    @if($mTempAlert !== 'disabled' && $hasTemperatureNotify)
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-mono font-black bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 whitespace-nowrap">
                                                {{ number_format($finalLower, 0) }}°C ~ {{ number_format($finalUpper, 0) }}°C
                                            </span>
                                            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500" title="{{ __('Source of temperature limit settings') }}">
                                                ({{ $tempSource }})
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs font-bold text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-6 text-right">
                                    <button type="button" @click="openMachineEdit(@js($machine))"
                                        class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20 tooltip" title="{{ __('Edit Settings') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <x-empty-state mode="table" :colspan="7" :message="__('No records found')" />
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 行動版/平版 Card 列表 --}}
                <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($machines as $machine)
                    @php
                        $mSettings = $machine->settings ?? [];
                        $mWebhook = $mSettings['discord_webhook_url'] ?? null;
                        $cSettings = $machine->company->settings ?? [];
                        $cWebhook = $cSettings['discord_webhook_url'] ?? null;
                        $cNotifyTypes = $cSettings['discord_notify_types'] ?? [];
                        $mNotifyTypes = array_key_exists('discord_notify_types', $mSettings) && is_array($mSettings['discord_notify_types']) ? $mSettings['discord_notify_types'] : null;
                        $effectiveNotifyTypes = $mNotifyTypes ?? $cNotifyTypes;
                        $isTypeInherited = $mNotifyTypes === null;
                        $hasTemperatureNotify = in_array('temperature', $effectiveNotifyTypes, true);

                        $mTempAlert = $mSettings['temp_alert_enabled'] ?? 'inherit';

                        // 繼承鏈
                        $mUpper = $mSettings['temp_upper_limit'] ?? null;
                        $mLower = $mSettings['temp_lower_limit'] ?? null;

                        $modelSettings = $machine->machineModel->settings ?? [];
                        $modelUpper = $modelSettings['temp_upper_limit'] ?? null;
                        $modelLower = $modelSettings['temp_lower_limit'] ?? null;

                        $sysUpper = 40.0;
                        $sysLower = 0.0;

                        $finalUpper = $sysUpper;
                        $finalLower = $sysLower;
                        $tempSource = __('System Default');

                        if ($mTempAlert === 'enabled' && $hasTemperatureNotify && ($mUpper !== null || $mLower !== null)) {
                            $finalUpper = $mUpper !== null ? $mUpper : $sysUpper;
                            $finalLower = $mLower !== null ? $mLower : $sysLower;
                            $tempSource = __('Custom');
                        } elseif ($hasTemperatureNotify && ($modelUpper !== null || $modelLower !== null)) {
                            $finalUpper = $modelUpper !== null ? $modelUpper : $sysUpper;
                            $finalLower = $modelLower !== null ? $modelLower : $sysLower;
                            $tempSource = __('Model Default');
                        }
                    @endphp
                    <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">{{ $machine->name }}</h3>
                                    <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">{{ $machine->serial_no }}</p>
                                </div>
                            </div>
                            {{-- Temperature status is implied by configured alert types; show only range below. --}}
                        </div>

                        <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Model') }}</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $machine->machineModel->name ?? __('No Model') }}</p>
                            </div>
                            @if(auth()->user()->isSystemAdmin())
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Company') }}</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">{{ $machine->company->name ?? __('No Company') }}</p>
                            </div>
                            @endif
                            <div class="col-span-2">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Webhook URL') }}</p>
                                @if($mWebhook)
                                    <p class="text-xs font-mono font-bold text-slate-655 dark:text-slate-200 break-all leading-normal">{{ $mWebhook }}</p>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 mt-1 inline-block">{{ __('Machine Specific') }}</span>
                                @elseif($cWebhook)
                                    <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 break-all leading-normal">{{ $cWebhook }}</p>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700 mt-1 inline-block">{{ __('Inherit Company') }}</span>
                                @else
                                    <p class="text-xs font-bold text-slate-300 dark:text-slate-600">{{ __('Not Configured') }}</p>
                                @endif
                            </div>
                            <div class="col-span-2">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Configured Alert Types') }}</p>
                                @if($isTypeInherited && !empty($effectiveNotifyTypes))
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700 inline-block mb-1">{{ __('Inherit Company') }}</span>
                                @endif
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($effectiveNotifyTypes as $type)
                                        @php
                                            $label = match($type) {
                                                'submachine' => __('Device Exception'),
                                                'status' => __('Connectivity Logs'),
                                                'temperature' => __('Temperature Alert'),
                                                default => $type
                                            };
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-widest whitespace-nowrap bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">{{ $label }}</span>
                                    @endforeach
                                    @if(empty($effectiveNotifyTypes))
                                        <p class="text-xs font-bold text-slate-300 dark:text-slate-600">{{ __('Not Configured') }}</p>
                                    @endif
                                </div>
                            </div>
                            @if($mTempAlert !== 'disabled' && $hasTemperatureNotify)
                            <div class="col-span-2">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Temp Alert Range') }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-mono font-black bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                                        {{ number_format($finalLower, 0) }}°C ~ {{ number_format($finalUpper, 0) }}°C
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">
                                        ({{ $tempSource }})
                                    </span>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="button" @click="openMachineEdit(@js($machine))"
                                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                {{ __('Edit Settings') }}
                            </button>
                        </div>
                    </div>
                    @empty
                    <x-empty-state :message="__('No records found')" />
                    @endforelse
                </div>

                {{-- 分頁 --}}
                <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
                    {{ $machines->appends(array_merge(request()->query(), ['tab' => 'machine']))->links('vendor.pagination.luxury', [
                        'ajax_navigate_event' => 'ajax:navigate:discord',
                        'per_page_param'      => 'machine_per_page',
                    ]) }}
                </div>
            </div>

        </div>
    </div>

    <!-- ======================================================== -->
    <!--                  Tab 1: 公司全域設定 Modal               -->
    <!-- ======================================================== -->
    <div x-show="showCompanyModal" class="fixed inset-0 z-[110] overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showCompanyModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="showCompanyModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div x-show="showCompanyModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="relative inline-block align-bottom bg-white dark:bg-slate-900 rounded-[2.5rem] text-left shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-slate-100 dark:border-slate-800 z-10 overflow-hidden"
                @click.stop>
                
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-cyan-500/10 rounded-full blur-[100px] pointer-events-none"></div>

                <!-- Header -->
                <div class="px-10 py-8 pb-4 flex items-center justify-between relative z-10">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight">
                            {{ __('Edit Company Alert Settings') }}
                        </h3>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">
                            <span x-text="companyName" class="text-cyan-500 font-extrabold"></span>
                        </p>
                    </div>
                    <button @click="showCompanyModal = false"
                        class="p-2.5 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all border border-slate-100 dark:border-slate-700">
                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form :action="'{{ route('admin.basic-settings.discord-notifications.index') }}/company/' + companyId" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="px-10 py-6 space-y-6 max-h-[65vh] overflow-y-auto relative z-10">
                        <!-- Switch and Webhook URL -->
                        <div class="p-6 bg-slate-50 dark:bg-slate-800/25 rounded-2xl border border-slate-100 dark:border-slate-800/50 space-y-6">
                            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/50">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center border border-indigo-500/20 shrink-0">
                                        <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-black text-slate-700 dark:text-slate-300">{{ __('General Settings') }}</h4>
                                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ __('Enable and paste Webhook URL') }}</p>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="relative inline-flex items-center cursor-pointer select-none">
                                        <input type="hidden" name="discord_notify_enabled" value="0">
                                        <input type="checkbox" name="discord_notify_enabled" value="1" x-model="companyForm.discord_notify_enabled" class="sr-only peer">
                                        <div class="w-14 h-7 bg-slate-200 dark:bg-slate-800 rounded-full peer peer-focus:ring-2 peer-focus:ring-cyan-500/20 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-slate-600 peer-checked:bg-cyan-500 shadow-inner"></div>
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wider mb-2 block">{{ __('Discord Webhook URL') }}</label>
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <div class="relative flex-1">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 dark:text-slate-500">
                                            <svg class="h-5 w-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                                        </span>
                                        <input type="url" name="discord_webhook_url" x-model="companyForm.discord_webhook_url" class="luxury-input py-3 pl-12 pr-6 block w-full text-sm">
                                    </div>
                                    <button type="button" @click="testWebhook(companyForm.discord_webhook_url)" :disabled="isTesting" class="btn-luxury-ghost flex items-center justify-center gap-2 py-3 px-6 rounded-xl border border-slate-200 dark:border-slate-700/60 font-bold text-sm text-slate-600 dark:text-slate-300 hover:bg-cyan-500 hover:text-white active:scale-95 transition-all duration-300 shrink-0 disabled:opacity-50">
                                        <template x-if="isTesting">
                                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        </template>
                                        <template x-if="!isTesting">
                                            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                        </template>
                                        <span x-text="isTesting ? '{{ __('Sending...') }}' : '{{ __('Test Connection') }}'"></span>
                                    </button>
                                </div>

                                <div x-show="testStatus !== null" x-transition.duration.300ms class="mt-3 p-4 rounded-xl flex items-start gap-3 text-sm font-bold animate-luxury-in" :class="testStatus === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400'" style="display: none;">
                                    <template x-if="testStatus === 'success'">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </template>
                                    <template x-if="testStatus === 'error'">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </template>
                                    <span x-text="testMessage"></span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wider mb-2 block">{{ __('Notification Language') }}</label>
                                <x-searchable-select id="company-locale-select" name="discord_notify_locale" x-model="companyForm.discord_notify_locale" :hasSearch="false">
                                    <option value="zh_TW" data-title="繁體中文 (Traditional Chinese)">繁體中文 (Traditional Chinese)</option>
                                    <option value="en" data-title="English">English</option>
                                    <option value="ja" data-title="日本語 (Japanese)">日本語 (Japanese)</option>
                                </x-searchable-select>
                            </div>
                        </div>

                        <!-- Alert Types -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center border border-emerald-500/20 shrink-0">
                                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" /></svg>
                                </div>
                                <h4 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Alert Types') }}</h4>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="group relative flex items-center justify-between p-4 rounded-2xl border border-slate-100/80 dark:border-slate-800/80 bg-slate-50/30 dark:bg-slate-900/20 hover:border-cyan-500/40 cursor-pointer transition-all duration-300">
                                    <div class="flex flex-col flex-1 min-w-0 mr-3">
                                        <span class="text-sm font-black text-slate-500 dark:text-slate-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ __('Submachine Alert') }}</span>
                                    </div>
                                    <div class="flex-shrink-0 pr-1">
                                        <input type="checkbox" name="discord_notify_types[]" value="submachine" x-model="companyForm.discord_notify_types"
                                            class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 text-cyan-500 focus:ring-cyan-500/20 transition-all cursor-pointer accent-cyan-500">
                                    </div>
                                </label>

                                <label class="group relative flex items-center justify-between p-4 rounded-2xl border border-slate-100/80 dark:border-slate-800/80 bg-slate-50/30 dark:bg-slate-900/20 hover:border-cyan-500/40 cursor-pointer transition-all duration-300">
                                    <div class="flex flex-col flex-1 min-w-0 mr-3">
                                        <span class="text-sm font-black text-slate-500 dark:text-slate-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ __('Machine Connection Alert') }}</span>
                                    </div>
                                    <div class="flex-shrink-0 pr-1">
                                        <input type="checkbox" name="discord_notify_types[]" value="status" x-model="companyForm.discord_notify_types"
                                            class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 text-cyan-500 focus:ring-cyan-500/20 transition-all cursor-pointer accent-cyan-500">
                                    </div>
                                </label>

                                <label class="group relative flex items-center justify-between p-4 rounded-2xl border border-slate-100/80 dark:border-slate-800/80 bg-slate-50/30 dark:bg-slate-900/20 hover:border-cyan-500/40 cursor-pointer transition-all duration-300">
                                    <div class="flex flex-col flex-1 min-w-0 mr-3">
                                        <span class="text-sm font-black text-slate-500 dark:text-slate-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ __('Temperature Alert') }}</span>
                                    </div>
                                    <div class="flex-shrink-0 pr-1">
                                        <input type="checkbox" name="discord_notify_types[]" value="temperature" x-model="companyForm.discord_notify_types"
                                            class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 text-cyan-500 focus:ring-cyan-500/20 transition-all cursor-pointer accent-cyan-500">
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="px-10 py-6 border-t border-slate-100 dark:border-slate-800/50 flex justify-end gap-4 relative z-10">
                        <button type="button" @click="showCompanyModal = false" class="btn-luxury-ghost px-8 py-3 rounded-xl border border-slate-200 dark:border-slate-700/60 font-bold text-sm text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn-luxury-primary px-12 py-3 rounded-xl text-white font-bold text-sm bg-cyan-500 hover:bg-cyan-600 active:scale-95 shadow-lg shadow-cyan-500/20 transition-all duration-300">
                            {{ __('Save Settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- ======================================================== -->
    <!--                  Tab 2: 機台個別設定 Modal               -->
    <!-- ======================================================== -->
    <div x-show="showMachineModal" class="fixed inset-0 z-[110] overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showMachineModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="showMachineModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div x-show="showMachineModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="relative inline-block align-bottom bg-white dark:bg-slate-900 rounded-[2.5rem] text-left shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-slate-100 dark:border-slate-800 z-10 overflow-hidden"
                @click.stop>
                
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-cyan-500/10 rounded-full blur-[100px] pointer-events-none"></div>

                <!-- Header -->
                <div class="px-10 py-8 pb-4 flex items-center justify-between relative z-10">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight">
                            {{ __('Edit Machine Alert Settings') }}
                        </h3>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">
                            <span x-text="currentMachine.name" class="text-cyan-500 font-extrabold"></span>
                            <span class="mx-2 text-slate-300 dark:text-slate-700">|</span>
                            <span x-text="currentMachine.serial_no" class="font-mono"></span>
                        </p>
                    </div>
                    <button @click="showMachineModal = false"
                        class="p-2.5 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all border border-slate-100 dark:border-slate-700">
                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form :action="'{{ route('admin.basic-settings.discord-notifications.index') }}/machine/' + currentMachine.id" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="px-10 py-6 space-y-4 max-h-[65vh] overflow-y-auto overflow-x-hidden relative z-10">
                        
                        <!-- Pull-style Copy Settings (複製其他機台設定) -->
                        <div class="p-5 bg-gradient-to-br from-cyan-500/5 to-blue-500/5 rounded-2xl border border-cyan-500/15 relative z-[30]">
                            
                            <div class="relative focus-within:z-[60] space-y-2">
                                <label class="text-xs font-bold text-cyan-600 dark:text-cyan-400 tracking-wider mb-2 block flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376A8.965 8.965 0 0012 12.75c-.497 0-.982.04-1.455.12l-.178.03A8.965 8.965 0 006 17.25M17.25 12h-9.75m9.75 2.25h-9.75M17.25 16.5H7.5" /></svg>
                                    {{ __('Copy Settings From Another Machine') }}
                                </label>

                                {{-- 標準動態選單包裝容器，支援 x-searchable-select 奢華外觀 --}}
                                <div id="copy-select-wrapper" class="relative focus-within:z-[60] w-full"></div>

                                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 mt-1">
                                    {{ __('Only machines under the same company can be cloned for security.') }}
                                </p>
                            </div>

                            <!-- 複製成功提示 Micro-Toast -->
                            <div x-show="showCopyToast" 
                                 x-transition:enter="ease-out duration-300"
                                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="ease-in duration-200"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                 class="mt-3 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center gap-2 text-xs font-bold"
                                 style="display: none;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>{{ __('Settings loaded from') }} <span x-text="copiedMachineName" class="underline"></span>!</span>
                            </div>
                        </div>

                        <!-- Webhook Settings -->
                        <div class="p-6 bg-slate-50 dark:bg-slate-800/25 rounded-2xl border border-slate-100 dark:border-slate-800/50 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800/50">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center border border-indigo-500/20 shrink-0">
                                        <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-white">{{ __('Machine-Specific Webhook') }}</h4>
                                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 mt-1">{{ __('Leave empty to inherit company settings') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <div class="relative flex-1">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 dark:text-slate-500">
                                            <svg class="h-5 w-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                                        </span>
                                        <input type="url" name="discord_webhook_url" x-model="machineForm.discord_webhook_url" class="luxury-input py-3 pl-12 pr-6 block w-full text-sm">
                                    </div>
                                    <button type="button" @click="testWebhook(machineForm.discord_webhook_url)" :disabled="isTesting" class="btn-luxury-ghost flex items-center justify-center gap-2 py-3 px-6 rounded-xl border border-slate-200 dark:border-slate-700/60 font-bold text-sm text-slate-600 dark:text-slate-300 hover:bg-cyan-500 hover:text-white active:scale-95 transition-all duration-300 shrink-0 disabled:opacity-50">
                                        <template x-if="isTesting">
                                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        </template>
                                        <template x-if="!isTesting">
                                            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                        </template>
                                        <span x-text="isTesting ? '{{ __('Sending...') }}' : '{{ __('Test Connection') }}'"></span>
                                    </button>
                                </div>

                                <div x-show="testStatus !== null" x-transition.duration.300ms class="mt-3 p-4 rounded-xl flex items-start gap-3 text-sm font-bold animate-luxury-in" :class="testStatus === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400'" style="display: none;">
                                    <template x-if="testStatus === 'success'">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </template>
                                    <template x-if="testStatus === 'error'">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </template>
                                    <span x-text="testMessage"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Temperature Overrides -->
                        <input type="hidden" name="temp_alert_enabled" x-model="machineForm.temp_alert_enabled">
                        <div class="p-6 bg-slate-50 dark:bg-slate-800/25 rounded-2xl border border-slate-100 dark:border-slate-800/50 space-y-5">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800/50">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center border border-amber-500/20 shrink-0">
                                        <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0-18a9 9 0 110 18m0-18a9 9 0 000 18" /></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-white">{{ __('Machine Alert Settings') }}</h4>
                                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 mt-1">{{ __('Configure alert types and temperature thresholds') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wider mb-2 block">{{ __('Alert Types Source') }}</label>
                                <x-searchable-select id="machine-notify-mode-select" name="notify_types_mode" x-model="machineForm.notify_types_mode" :hasSearch="false" @change="syncTempAlertState()">
                                    <option value="inherit" data-title="{{ __('Inherit Company Alert Types') }}">{{ __('Inherit Company Alert Types') }}</option>
                                    <option value="custom" data-title="{{ __('Custom Alert Types') }}">{{ __('Custom Alert Types') }}</option>
                                </x-searchable-select>
                            </div>

                            <div x-show="machineForm.notify_types_mode === 'custom'" x-transition.duration.300ms class="space-y-3 animate-luxury-in">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wider mb-2 block">{{ __('Alert Types') }}</label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <label class="group relative flex items-center justify-between p-4 rounded-2xl border border-slate-100/80 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/40 hover:border-cyan-500/40 cursor-pointer transition-all duration-300">
                                        <div class="flex flex-col flex-1 min-w-0 mr-3">
                                            <span class="text-base font-black text-slate-500 dark:text-slate-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ __('Submachine Alert') }}</span>
                                        </div>
                                        <div class="flex-shrink-0 pr-1">
                                            <input type="checkbox" name="discord_notify_types[]" value="submachine" x-model="machineForm.discord_notify_types" @change="syncTempAlertState()"
                                                class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 text-cyan-500 focus:ring-cyan-500/20 transition-all cursor-pointer accent-cyan-500">
                                        </div>
                                    </label>
                                    <label class="group relative flex items-center justify-between p-4 rounded-2xl border border-slate-100/80 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/40 hover:border-cyan-500/40 cursor-pointer transition-all duration-300">
                                        <div class="flex flex-col flex-1 min-w-0 mr-3">
                                            <span class="text-base font-black text-slate-500 dark:text-slate-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ __('Machine Connection Alert') }}</span>
                                        </div>
                                        <div class="flex-shrink-0 pr-1">
                                            <input type="checkbox" name="discord_notify_types[]" value="status" x-model="machineForm.discord_notify_types" @change="syncTempAlertState()"
                                                class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 text-cyan-500 focus:ring-cyan-500/20 transition-all cursor-pointer accent-cyan-500">
                                        </div>
                                    </label>
                                    <label class="group relative flex items-center justify-between p-4 rounded-2xl border border-slate-100/80 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/40 hover:border-cyan-500/40 cursor-pointer transition-all duration-300">
                                        <div class="flex flex-col flex-1 min-w-0 mr-3">
                                            <span class="text-base font-black text-slate-500 dark:text-slate-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ __('Temperature Alert') }}</span>
                                        </div>
                                        <div class="flex-shrink-0 pr-1">
                                            <input type="checkbox" name="discord_notify_types[]" value="temperature" x-model="machineForm.discord_notify_types" @change="syncTempAlertState()"
                                                class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 text-cyan-500 focus:ring-cyan-500/20 transition-all cursor-pointer accent-cyan-500">
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div x-show="machineForm.notify_types_mode === 'inherit'" x-transition.duration.300ms class="space-y-2 animate-luxury-in">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wider mb-1 block">{{ __('Effective Alert Types') }}</label>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="type in effectiveNotifyTypes()" :key="type">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-widest bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20" x-text="notifyTypeLabel(type)"></span>
                                    </template>
                                </div>
                            </div>

                            <!-- Custom Threshold Limits -->
                            <div x-show="machineForm.notify_types_mode === 'custom' && isTemperatureChecked()" 
                                 x-transition.duration.300ms 
                                 class="grid grid-cols-1 sm:grid-cols-2 gap-4 animate-luxury-in">
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wider mb-2 block">{{ __('Custom Upper Limit (°C)') }}</label>
                                    <div class="flex items-center h-12 rounded-xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all w-full">
                                        <button type="button" @click="adjustTemp('temp_upper_limit', -1, currentMachine.machine_model && currentMachine.machine_model.settings && currentMachine.machine_model.settings.temp_upper_limit !== undefined ? currentMachine.machine_model.settings.temp_upper_limit : '40')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                        </button>
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <input type="text" inputmode="decimal" name="temp_upper_limit" x-model="machineForm.temp_upper_limit" 
                                            :placeholder="currentMachine.machine_model && currentMachine.machine_model.settings && currentMachine.machine_model.settings.temp_upper_limit !== undefined ? Math.round(parseFloat(currentMachine.machine_model.settings.temp_upper_limit)) : '40'"
                                            class="w-full bg-transparent border-none text-center text-sm font-bold text-slate-800 dark:text-white focus:ring-0 p-0">
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <button type="button" @click="adjustTemp('temp_upper_limit', 1, currentMachine.machine_model && currentMachine.machine_model.settings && currentMachine.machine_model.settings.temp_upper_limit !== undefined ? currentMachine.machine_model.settings.temp_upper_limit : '40')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wider mb-2 block">{{ __('Custom Lower Limit (°C)') }}</label>
                                    <div class="flex items-center h-12 rounded-xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all w-full">
                                        <button type="button" @click="adjustTemp('temp_lower_limit', -1, currentMachine.machine_model && currentMachine.machine_model.settings && currentMachine.machine_model.settings.temp_lower_limit !== undefined ? currentMachine.machine_model.settings.temp_lower_limit : '0')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                        </button>
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <input type="text" inputmode="decimal" name="temp_lower_limit" x-model="machineForm.temp_lower_limit" 
                                            :placeholder="currentMachine.machine_model && currentMachine.machine_model.settings && currentMachine.machine_model.settings.temp_lower_limit !== undefined ? Math.round(parseFloat(currentMachine.machine_model.settings.temp_lower_limit)) : '0'"
                                            class="w-full bg-transparent border-none text-center text-sm font-bold text-slate-800 dark:text-white focus:ring-0 p-0">
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <button type="button" @click="adjustTemp('temp_lower_limit', 1, currentMachine.machine_model && currentMachine.machine_model.settings && currentMachine.machine_model.settings.temp_lower_limit !== undefined ? currentMachine.machine_model.settings.temp_lower_limit : '0')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="px-10 py-6 border-t border-slate-100 dark:border-slate-800/50 flex justify-end gap-4 relative z-10">
                        <button type="button" @click="showMachineModal = false" class="btn-luxury-ghost px-8 py-3 rounded-xl border border-slate-200 dark:border-slate-700/60 font-bold text-sm text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn-luxury-primary px-12 py-3 rounded-xl text-white font-bold text-sm bg-cyan-500 hover:bg-cyan-600 active:scale-95 shadow-lg shadow-cyan-500/20 transition-all duration-300">
                            {{ __('Save Settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('discordNotificationApp', () => ({
            activeTab: '{{ $activeTab }}',
            tabLoading: null,
            showCompanyModal: false,
            showMachineModal: false,
            companyId: null,
            companyName: '',
            companyForm: {
                discord_webhook_url: '',
                discord_notify_enabled: false,
                discord_notify_types: [],
                discord_notify_locale: 'zh_TW'
            },
            currentMachine: {
                id: null,
                name: '',
                serial_no: '',
                company_id: null,
                settings: {},
                machine_model: null
            },
            machineForm: {
                discord_webhook_url: '',
                notify_types_mode: 'inherit',
                discord_notify_types: [],
                temp_alert_enabled: 'inherit',
                temp_upper_limit: '',
                temp_lower_limit: ''
            },
            copyableMachines: @js($copyableMachines),
            isTesting: false,
            testStatus: null,
            testMessage: '',
            isLoadingTable: false,
            showCopyToast: false,
            copiedMachineName: '',

            openCompanyEdit(company) {
                const settings = company.settings || {};
                this.companyId = company.id;
                this.companyName = company.name;
                this.companyForm.discord_webhook_url = settings.discord_webhook_url || '';
                this.companyForm.discord_notify_enabled = !!(settings.discord_notify_enabled);
                this.companyForm.discord_notify_types = settings.discord_notify_types || ['submachine', 'status'];
                this.companyForm.discord_notify_locale = settings.locale || 'zh_TW';
                
                this.testStatus = null;
                this.testMessage = '';
                this.showCompanyModal = true;
                
                this.$nextTick(() => {
                    const el = window.HSSelect?.getInstance('#company-locale-select');
                    if (el) el.setValue(this.companyForm.discord_notify_locale);
                });
            },

            openMachineEdit(machine) {
                const settings = machine.settings || {};
                const machineNotifyTypes = Array.isArray(settings.discord_notify_types) ? settings.discord_notify_types : null;
                this.currentMachine = machine;
                this.machineForm.discord_webhook_url = settings.discord_webhook_url || '';
                this.machineForm.notify_types_mode = machineNotifyTypes === null ? 'inherit' : 'custom';
                this.machineForm.discord_notify_types = machineNotifyTypes === null ? [] : [...machineNotifyTypes];
                this.machineForm.temp_alert_enabled = settings.temp_alert_enabled || (this.isTemperatureChecked() ? 'enabled' : 'disabled');
                this.machineForm.temp_upper_limit = settings.temp_upper_limit !== undefined && settings.temp_upper_limit !== null ? Math.round(parseFloat(settings.temp_upper_limit)).toString() : '';
                this.machineForm.temp_lower_limit = settings.temp_lower_limit !== undefined && settings.temp_lower_limit !== null ? Math.round(parseFloat(settings.temp_lower_limit)).toString() : '';
                this.syncTempAlertState();
                
                this.testStatus = null;
                this.testMessage = '';
                this.showMachineModal = true;
                
                this.$nextTick(() => {
                    const modeSel = window.HSSelect?.getInstance('#machine-notify-mode-select');
                    if (modeSel) modeSel.setValue(this.machineForm.notify_types_mode);
                    this.rebuildCopySelect();
                });
            },

            notifyTypeLabel(type) {
                if (type === 'submachine') return '{{ __('Submachine Alert') }}';
                if (type === 'status') return '{{ __('Machine Connection Alert') }}';
                if (type === 'temperature') return '{{ __('Temperature Alert') }}';
                return type;
            },

            effectiveNotifyTypes() {
                if (this.machineForm.notify_types_mode === 'custom') {
                    return this.machineForm.discord_notify_types;
                }
                return this.currentMachine?.company?.settings?.discord_notify_types || [];
            },

            isTemperatureChecked() {
                return this.effectiveNotifyTypes().includes('temperature');
            },

            syncTempAlertState() {
                if (this.machineForm.notify_types_mode === 'inherit') {
                    this.machineForm.temp_alert_enabled = 'inherit';
                    this.machineForm.temp_upper_limit = '';
                    this.machineForm.temp_lower_limit = '';
                    return;
                }

                if (this.machineForm.discord_notify_types.includes('temperature')) {
                    this.machineForm.temp_alert_enabled = 'enabled';
                    return;
                }

                this.machineForm.temp_alert_enabled = 'disabled';
                this.machineForm.temp_upper_limit = '';
                this.machineForm.temp_lower_limit = '';
            },

            rebuildCopySelect() {
                this.$nextTick(() => {
                    const wrapper = document.getElementById('copy-select-wrapper');
                    if (!wrapper) return;

                    // 1. Destroy existing HSSelect instance if present
                    const oldSelect = wrapper.querySelector('select');
                    if (oldSelect) {
                        const instance = window.HSSelect?.getInstance(oldSelect);
                        if (instance) {
                            instance.destroy();
                        }
                    }
                    
                    // 2. Clear wrapper DOM
                    wrapper.innerHTML = '';

                    // 3. Filter other machines under the same company
                    const list = this.copyableMachines.filter(m => 
                        m.company_id === this.currentMachine.company_id && 
                        m.id !== this.currentMachine.id
                    );

                    // 4. Build standard select element with searchable select attributes
                    const selectEl = document.createElement('select');
                    selectEl.id = 'copy-machine-select';
                    selectEl.name = 'copy_machine_id';
                    selectEl.className = 'hidden';

                    const config = {
                        "placeholder": "{{ __('Select a machine to copy settings from...') }}",
                        "hasSearch": true,
                        "searchPlaceholder": "{{ __('Search machines...') }}",
                        "isHidePlaceholder": false,
                        "searchClasses": "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
                        "searchWrapperClasses": "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
                        "toggleClasses": "hs-select-toggle luxury-select-toggle w-full",
                        "toggleTemplate": '<button type="button" aria-expanded="false"><span class="me-2" data-icon></span><span class="text-slate-800 dark:text-slate-200" data-title></span><div class="ms-auto"><svg class="size-4 text-slate-400 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg></div></button>',
                        "dropdownClasses": "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[150] animate-luxury-in",
                        "optionClasses": "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
                        "optionTemplate": '<div class="flex items-center justify-between w-full"><span data-title></span><span class="hs-select-active-indicator hidden text-cyan-500"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></div>'
                    };

                    selectEl.setAttribute('data-hs-select', JSON.stringify(config));

                    // Append placeholder
                    const placeholderOpt = document.createElement('option');
                    placeholderOpt.value = ' ';
                    placeholderOpt.textContent = "{{ __('Select a machine to copy settings from...') }}";
                    placeholderOpt.setAttribute('data-title', "{{ __('Select a machine to copy settings from...') }}");
                    selectEl.appendChild(placeholderOpt);

                    if (list.length === 0) {
                        const emptyOpt = document.createElement('option');
                        emptyOpt.value = ' ';
                        emptyOpt.textContent = "{{ __('No machines found') }}";
                        emptyOpt.setAttribute('data-title', "{{ __('No machines found') }}");
                        emptyOpt.disabled = true;
                        selectEl.appendChild(emptyOpt);
                    } else {
                        list.forEach(m => {
                            const opt = document.createElement('option');
                            opt.value = m.id;
                            opt.textContent = `${m.name} (${m.serial_no})`;
                            opt.setAttribute('data-title', `${m.name} (${m.serial_no})`);
                            selectEl.appendChild(opt);
                        });
                    }

                    wrapper.appendChild(selectEl);

                    // Bind change event to copy handler
                    selectEl.addEventListener('change', (e) => {
                        this.handleCopyMachine(e.target.value);
                    });

                    // 5. Initialize select
                    this.$nextTick(() => {
                        if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                            window.HSStaticMethods.autoInit(['select']);
                        }
                    });
                });
            },

            handleCopyMachine(value) {
                if (!value || !String(value).trim()) return;
                const source = this.copyableMachines.find(m => m.id == value);
                if (source) {
                    const s = source.settings || {};
                    const sourceNotifyTypes = Array.isArray(s.discord_notify_types) ? s.discord_notify_types : null;
                    this.machineForm.discord_webhook_url = s.discord_webhook_url || '';
                    this.machineForm.notify_types_mode = sourceNotifyTypes === null ? 'inherit' : 'custom';
                    this.machineForm.discord_notify_types = sourceNotifyTypes === null ? [] : [...sourceNotifyTypes];
                    this.machineForm.temp_alert_enabled = s.temp_alert_enabled || (this.isTemperatureChecked() ? 'enabled' : 'disabled');
                    this.machineForm.temp_upper_limit = s.temp_upper_limit !== undefined && s.temp_upper_limit !== null ? Math.round(parseFloat(s.temp_upper_limit)).toString() : '';
                    this.machineForm.temp_lower_limit = s.temp_lower_limit !== undefined && s.temp_lower_limit !== null ? Math.round(parseFloat(s.temp_lower_limit)).toString() : '';
                    this.syncTempAlertState();
                    
                    this.$nextTick(() => {
                        const modeSel = window.HSSelect?.getInstance('#machine-notify-mode-select');
                        if (modeSel) modeSel.setValue(this.machineForm.notify_types_mode);
                        const copyEl = document.getElementById('copy-machine-select');
                        if (copyEl) {
                            copyEl.value = ' ';
                            const copyInstance = window.HSSelect?.getInstance('#copy-machine-select');
                            if (copyInstance) copyInstance.setValue(' ');
                        }
                    });

                    this.copiedMachineName = source.name;
                    this.showCopyToast = true;
                    setTimeout(() => this.showCopyToast = false, 3000);
                }
            },

            adjustTemp(field, delta, fallbackVal) {
                let current = this.machineForm[field];
                if (current === undefined || current === null || current === '') {
                    current = parseInt(fallbackVal);
                } else {
                    current = parseInt(current);
                }
                if (isNaN(current)) current = 0;
                let newValue = current + delta;
                if (newValue < -50) newValue = -50;
                if (newValue > 100) newValue = 100;
                this.machineForm[field] = newValue.toString();
            },

            async testWebhook(webhookUrl) {
                if (!webhookUrl) {
                    this.testStatus = 'error';
                    this.testMessage = '{{ __('Please enter Webhook URL first.') }}';
                    return;
                }
                this.isTesting = true;
                this.testStatus = null;
                try {
                    const response = await fetch('{{ route('admin.basic-settings.discord-notifications.test') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ discord_webhook_url: webhookUrl })
                    });
                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.testStatus = 'success';
                        this.testMessage = data.message;
                    } else {
                        this.testStatus = 'error';
                        this.testMessage = data.message || '{{ __('Failed to send test notification.') }}';
                    }
                } catch (error) {
                    this.testStatus = 'error';
                    this.testMessage = '{{ __('An error occurred during testing.') }}';
                } finally {
                    this.isTesting = false;
                }
            },

            async fetchTabContent(url, containerId) {
                if (!url || this.isLoadingTable) return;
                this.isLoadingTable = true;
                try {
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) throw new Error('Network error');
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContent = doc.getElementById(containerId);
                    const container = document.getElementById(containerId);
                    if (newContent && container) {
                        container.innerHTML = newContent.innerHTML;
                        window.history.pushState({}, '', url);
                        // 先讓 Preline (HSSelect 等) 初始化完成，再由 Alpine 接管
                        if (window.HSStaticMethods?.autoInit) window.HSStaticMethods.autoInit();
                        // Re-sync company filter HSSelect display after AJAX DOM replacement.
                        const filterSelect = container.querySelector('select[name="filter_company_id"]');
                        if (filterSelect) {
                            const selectedValue = (filterSelect.value ?? '').trim();
                            const hsSelect = window.HSSelect?.getInstance(filterSelect);
                            if (hsSelect) hsSelect.setValue(selectedValue || ' ');
                        }
                        requestAnimationFrame(() => {
                            if (window.Alpine?.initTree) window.Alpine.initTree(container);
                        });
                    }
                } catch (e) { 
                    console.error('AJAX Load Failed:', e); 
                    window.location.href = url; 
                } finally { 
                    this.isLoadingTable = false; 
                }
            }
        }));
    });
</script>
@endsection
