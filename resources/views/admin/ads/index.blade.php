@extends('layouts.admin')

@php
$routeName = request()->route()->getName();
$baseRoute = 'admin.data-config.advertisements';
@endphp

@section('content')
<div class="space-y-2 pb-20 relative" x-data="adManager" @ajax:navigate.window.prevent="fetchPage($event.detail.url)"
    @submit.prevent="if($event.target.method.toLowerCase() === 'get') { 
        const url = new URL($event.target.action, window.location.origin);
        new FormData($event.target).forEach((value, key) => {
            if(value.trim()) url.searchParams.set(key, value);
            else url.searchParams.delete(key);
        });
        fetchPage(url.pathname + url.search);
     }" data-ads="{{ json_encode($advertisements->items()) }}" data-machines="{{ json_encode($machines) }}"
    data-all-ads="{{ json_encode($allAds) }}" data-active-tab="{{ $tab }}" data-urls='{
        "store": "{{ route($baseRoute . ".store") }}",
        "update": "{{ route($baseRoute . ".update", ":id") }}",
        "delete": "{{ route($baseRoute . ".destroy", ":id") }}",
        "getMachineAds": "{{ route($baseRoute . ".machine.get", ":id") }}",
        "assign": "{{ route($baseRoute . ".assign") }}",
        "reorder": "{{ route($baseRoute . ".assignments.reorder") }}",
        "removeAssignment": "{{ route($baseRoute . ".assignment.remove", ":id") }}",
        "syncToMachine": "{{ route($baseRoute . ".machine.sync", ":id") }}"
     }'>

    <x-luxury-spinner show="isLoading" />

    <x-page-header :title="__('Advertisement Management')"
        :subtitle="__('Manage ad materials and machine playback settings')" :breadcrumbs="[
            ['label' => __('Management'), 'url' => '#'],
            ['label' => __('Advertisement'), 'url' => '#']
        ]">
        <div x-show="activeTab === 'list'" x-cloak>
            <button @click="openAddModal()" type="button"
                class="btn-luxury-primary whitespace-nowrap flex items-center gap-2 transition-all duration-300">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="text-sm sm:text-base">{{ __('Add Advertisement') }}</span>
            </button>
        </div>
    </x-page-header>

    {{-- Tab Navigation --}}
    <x-tab-nav>
        <x-tab-nav-item value="list" :label="__('Advertisement List')" />
        <x-tab-nav-item value="machine" :label="__('Machine Advertisement Settings')" />
    </x-tab-nav>

    {{-- Tab Contents --}}

    {{-- List Tab --}}
    <div x-show="activeTab === 'list'" class="luxury-card rounded-3xl p-6 animate-luxury-in relative min-h-[300px]"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0" x-cloak>


        <!-- Filters Area -->
        <div class="mb-8">
            <form method="GET" action="{{ route($baseRoute . '.index') }}"
                class="flex flex-wrap items-center gap-4 w-full"
                @submit.prevent="fetchPage($el.action + '?' + new URLSearchParams(new FormData($el)).toString())">
                <input type="hidden" name="tab" value="list">

                <!-- Search Group -->
                <div class="relative group w-full sm:w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors stroke-[2.5]"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search by name...') }}"
                        class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold">
                </div>

                <div class="relative w-full sm:w-64 flex-none">
                    <x-searchable-select name="type" :selected="request('type')" :placeholder="__('All Types')"
                        :hasSearch="false"
                        @change="$el.closest('form').dispatchEvent(new Event('submit', {cancelable: true}))">
                        <option value="image" {{ request('type')==='image' ? 'selected' : '' }}>{{ __('Image') }}
                        </option>
                        <option value="video" {{ request('type')==='video' ? 'selected' : '' }}>{{ __('Video') }}
                        </option>
                    </x-searchable-select>
                </div>

                @if(auth()->user()->isSystemAdmin())
                <div class="relative w-full sm:w-80 flex-none">
                    <x-searchable-select name="company_id" :options="$companies" :selected="request('company_id')"
                        :placeholder="__('All Companies')"
                        @change="$el.closest('form').dispatchEvent(new Event('submit', {cancelable: true}))" />
                </div>
                @endif

                <div class="flex items-center gap-2 ml-auto sm:ml-0">
                    <button type="submit"
                        class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95 group"
                        title="{{ __('Search') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                    <button type="button" @click="
                                $el.closest('form').querySelectorAll('input[type=text], input[type=hidden]:not([name=tab])').forEach(i => i.value = '');
                                $el.closest('form').querySelectorAll('select').forEach(s => {
                                    s.value = ' ';
                                    const instance = window.HSSelect.getInstance(s);
                                    if (instance) instance.setValue(' ');
                                });
                                $el.closest('form').dispatchEvent(new Event('submit'));
                            "
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95 border border-slate-200 dark:border-slate-700 group"
                        title="{{ __('Reset') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <div id="ajax-content-container"
            :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': isLoading }">
            @if($advertisements->isNotEmpty())
            <!-- Mobile Cards -->
            <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                @foreach($advertisements as $ad)
                @php
                $now = now();
                $isExpired = $ad->end_at && $ad->end_at < $now; $isWaiting=$ad->start_at && $ad->start_at > $now;

                    if (!$ad->is_active) {
                    $status = 'disabled';
                    $statusLabel = __('Disabled');
                    } elseif ($isExpired) {
                    $status = 'inactive';
                    $statusLabel = __('Expired');
                    } elseif ($isWaiting) {
                    $status = 'pending';
                    $statusLabel = __('Waiting');
                    } else {
                    $status = 'active';
                    $statusLabel = __('Ongoing');
                    }
                    @endphp
                    <div
                        class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
                        {{-- Card Header --}}
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div class="flex items-center gap-4 min-w-0">
                                <div @click="openPreview(@js($ad))"
                                    class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0 cursor-pointer">
                                    @if($ad->type === 'image')
                                    <img src="{{ $ad->url }}" class="w-full h-full object-cover" alt="preview">
                                    @else
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 @click="openPreview(@js($ad))"
                                        class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight cursor-pointer">
                                        {{ $ad->name }}
                                    </h3>
                                    <p
                                        class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate mt-0.5">
                                        {{ $ad->company ? $ad->company->name : __('System') }}
                                    </p>
                                </div>
                            </div>
                            <x-status-badge :status="$status" :text="$statusLabel" size="sm" />
                        </div>

                        {{-- Info Grid --}}
                        <div
                            class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                            <div>
                                <p
                                    class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                                    {{ __('Type') }}</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $ad->type === 'video'
                                    ? __('Video') : __('Image') }}</p>
                            </div>
                            <div>
                                <p
                                    class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                                    {{ __('Duration') }}</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">{{
                                    $ad->duration }} {{ __('sec') }}</p>
                            </div>

                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <button type="button"
                                    @if($ad->is_active)
                                    @click="toggleStatus('{{ route($baseRoute . '.status.toggle', $ad->id) }}')"
                                    @else
                                    @click="toggleFormAction = '{{ route($baseRoute . '.status.toggle', $ad->id) }}'; $nextTick(() => $refs.statusToggleForm.submit())"
                                    @endif
                                    class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-emerald-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                                    title="{{ __('Status') }}">
                                    @if($ad->is_active)
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                    </svg>
                                    @else
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z" />
                                    </svg>
                                    @endif
                                </button>
                                <button type="button" @click="openEditModal(@js($ad))"
                                    class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                                    title="{{ __('Edit') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </button>
                                <button type="button" @click="confirmDelete(@js($ad->id))"
                                    class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-rose-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                                    title="{{ __('Delete') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>

                            <button type="button" @click="openPreview(@js($ad))"
                                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.67 8.5 7.652 6 12 6c4.348 0 8.332 2.5 9.964 5.678a1.012 1.012 0 0 1 0 .644C20.33 15.5 16.348 18 12 18c-4.348 0-8.332-2.5-9.964-5.678z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                                </svg>
                                {{ __('Preview') }}
                            </button>
                        </div>
                    </div>
                    @endforeach
            </div>
            @endif

            <div class="hidden xl:block overflow-x-auto">
                <table class="w-full text-left border-separate border-spacing-y-0">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                                {{ __('Preview') }}</th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                                {{ __('Name') }}</th>
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
                                {{ __('Duration') }}</th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                                {{ __('Status') }}</th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                                {{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                        @forelse($advertisements as $ad)
                        <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                            <td class="px-6 py-4">
                                <div @click="openPreview(@js($ad))"
                                    class="w-16 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 overflow-hidden shadow-sm border border-slate-200 dark:border-white/5 cursor-pointer hover:scale-105 hover:shadow-cyan-500/20 transition-all duration-300">
                                    @if($ad->type === 'image')
                                    <img src="{{ $ad->url }}" class="w-full h-full object-cover">
                                    @else
                                    <div
                                        class="w-full h-full flex items-center justify-center bg-slate-900 group-hover:bg-cyan-900 transition-colors">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5.14v14l11-7-11-7z" />
                                        </svg>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td @click="openPreview(@js($ad))"
                                class="px-6 py-4 whitespace-nowrap text-sm font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors cursor-pointer">
                                {{ $ad->name }}
                            </td>
                            @if(auth()->user()->isSystemAdmin())
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-600 dark:text-slate-300">
                                {{ $ad->company->name ?? __('System Default') }}
                            </td>
                            @endif
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full {{ $ad->type === 'video' ? 'bg-indigo-500/10 text-indigo-500 border border-indigo-500/20' : 'bg-cyan-500/10 text-cyan-500 border border-cyan-500/20' }}">
                                    {{ __($ad->type) }}
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 text-center whitespace-nowrap text-sm font-mono font-bold text-slate-700 dark:text-slate-200">
                                {{ $ad->duration }}s
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                $now = now();
                                $isStarted = !$ad->start_at || $ad->start_at <= $now; $isExpired=$ad->end_at &&
                                    $ad->end_at < $now; $isPlaying=$ad->is_active && $isStarted && !$isExpired;
                                        @endphp

                                        <div class="flex flex-col items-center gap-1">
                                            @if(!$ad->is_active)
                                            <x-status-badge status="disabled" :text="__('Disabled')" />
                                            @elseif($isExpired)
                                            <x-status-badge status="inactive" :text="__('Expired')" />
                                            @elseif(!$isStarted)
                                            <x-status-badge status="pending" :text="__('Waiting')" />
                                            @else
                                            <x-status-badge status="active" :text="__('Ongoing')" />
                                            @endif
                                        </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end items-center gap-2">
                                    @if($ad->is_active)
                                    <button type="button" @click="toggleStatus('{{ route($baseRoute . '.status.toggle', $ad->id) }}')"
                                        class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-amber-500 hover:bg-amber-500/5 transition-all border border-transparent hover:border-amber-500/20"
                                        title="{{ __('Disable') }}">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                        </svg>
                                    </button>
                                    @else
                                    <button type="button" @click="toggleFormAction = '{{ route($baseRoute . '.status.toggle', $ad->id) }}'; $nextTick(() => $refs.statusToggleForm.submit())"
                                        class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all border border-transparent hover:border-emerald-500/20"
                                        title="{{ __('Enable') }}">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z" />
                                        </svg>
                                    </button>
                                    @endif
                                    <button @click="openEditModal(@js($ad))"
                                        class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                        title="{{ __('Edit') }}">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    <button @click="confirmDelete(@js($ad->id))"
                                        class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all border border-transparent hover:border-rose-500/20"
                                        title="{{ __('Delete') }}">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <x-empty-state mode="table" :colspan="auth()->user()->isSystemAdmin() ? 8 : 7"
                            :message="__('No advertisements found.')" />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-8">
                {{ $advertisements->appends(request()->query())->links('vendor.pagination.luxury') }}
            </div>
        </div>
    </div>

    <!-- Machine View Tab -->
    <div x-show="activeTab === 'machine'" class="space-y-6" x-cloak>
        <div class="luxury-card rounded-3xl p-8 animate-luxury-in">
            <!-- Machine Filter -->
            <div class="max-w-3xl mx-auto mb-10">
                <label
                    class="block text-sm font-black text-slate-700 dark:text-slate-200 mb-3 text-center uppercase tracking-widest">{{
                    __('Please select a machine first') }}</label>
                <div class="flex flex-col sm:flex-row items-center gap-4 justify-center">
                    <div class="w-full sm:w-[400px]">
                        <x-searchable-select name="machine_selector"
                            :options="$machines->map(fn($m) => (object)['id' => $m->id, 'name' => $m->name . ' (' . $m->serial_no . ')'])"
                            placeholder="{{ __('Search Machine...') }}" @change="selectMachine($event.target.value)" />
                    </div>
                    <div x-show="selectedMachineId" x-transition class="shrink-0 w-full sm:w-auto" x-cloak>
                        <button @click="syncAdsToMachine()" type="button" 
                            class="w-full sm:w-auto btn-luxury-primary h-[42px] whitespace-nowrap flex items-center justify-center gap-2 transition-all duration-300">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span class="text-sm font-bold uppercase tracking-widest">{{ __('Sync to Machine') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="selectedMachineId" class="animate-luxury-in">
                <!-- Positions Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    @foreach(['vending', 'visit_gift', 'standby'] as $pos)
                    <div class="space-y-4">
                        <div class="flex items-center justify-between px-2">
                            <h3
                                class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                                {{ __($pos) }}
                            </h3>
                            <div class="flex items-center gap-2">
                                <button x-cloak x-show="machineAds['{{ $pos }}'] && machineAds['{{ $pos }}'].length > 0"
                                    @click="startSequencePreview('{{ $pos }}')"
                                    class="p-1.5 px-3 text-[10px] font-black bg-slate-800 dark:bg-slate-700 text-white rounded-lg hover:bg-slate-700 transition-colors uppercase flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ __('Preview') }}
                                </button>
                                <button @click="openAssignModal('{{ $pos }}')"
                                    class="p-1.5 px-3 text-[10px] font-black bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition-colors uppercase shadow-sm shadow-cyan-500/20 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    {{ __('Ad Settings') }}
                                </button>
                            </div>
                        </div>

                        <div
                            class="luxury-card p-4 bg-slate-50/50 dark:bg-slate-900/40 border border-slate-100 dark:border-white/5 space-y-3 min-h-[150px]">
                            <template x-if="!machineAds['{{ $pos }}'] || machineAds['{{ $pos }}'].length === 0">
                                <div class="flex flex-col items-center justify-center h-full py-10">
                                    <svg class="w-6 h-6 text-slate-300 dark:text-slate-600 mb-2" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v6m-3-3h6m-9-3a9 9 0 1118 0 9 9 0 01-18 0z" />
                                    </svg>
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">{{
                                        __('No assignments') }}</span>
                                </div>
                            </template>

                            <template x-for="(assign, index) in machineAds['{{ $pos }}']" :key="assign.id">
                                <div
                                    class="flex items-center gap-4 bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-100 dark:border-white/5 shadow-sm group hover:border-cyan-500/30 transition-all">
                                    <!-- Sort Controls -->
                                    <div
                                        class="flex flex-col gap-1 shrink-0 bg-slate-50 dark:bg-slate-900 rounded-lg p-1 border border-slate-100 dark:border-white/5">
                                        <button @click.prevent="moveUp('{{ $pos }}', index)" :disabled="index === 0"
                                            class="p-1 text-slate-400 hover:text-cyan-500 disabled:opacity-30 disabled:hover:text-slate-400 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5 15l7-7 7 7" />
                                            </svg>
                                        </button>
                                        <button @click.prevent="moveDown('{{ $pos }}', index)"
                                            :disabled="index === machineAds['{{ $pos }}'].length - 1"
                                            class="p-1 text-slate-400 hover:text-cyan-500 disabled:opacity-30 disabled:hover:text-slate-400 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>

                                    <button @click="openPreview(assign.advertisement)"
                                        class="w-12 h-12 rounded-lg bg-slate-100 dark:bg-slate-900 border border-white/5 overflow-hidden shrink-0 hover:border-cyan-500/50 hover:shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all relative group/thumb">
                                        <div
                                            class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover/thumb:opacity-100 transition-opacity z-10">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </div>
                                        <template x-if="assign.advertisement.type === 'image'">
                                            <img :src="assign.advertisement.url" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="assign.advertisement.type === 'video'">
                                            <div
                                                class="w-full h-full flex items-center justify-center bg-slate-900 group-hover/thumb:bg-cyan-900 transition-colors">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5.14v14l11-7-11-7z" />
                                                </svg>
                                            </div>
                                        </template>
                                    </button>
                                    <div class="flex-1 min-w-0 flex flex-col justify-center cursor-pointer group-hover:text-cyan-500 transition-colors"
                                        @click="openPreview(assign.advertisement)">
                                        <p class="text-xs font-black text-slate-700 dark:text-white truncate transition-colors"
                                            x-text="assign.advertisement.name"></p>
                                        <p class="text-[11px] font-mono font-bold text-slate-400 uppercase tracking-tight mt-0.5"
                                            x-text="assign.advertisement.duration + 's'"></p>
                                    </div>
                                    <button @click="removeAssignment(assign.id)"
                                        class="p-1.5 text-slate-300 hover:text-rose-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div x-show="!selectedMachineId" class="py-20 text-center text-slate-400 italic">
                {{ __('Please select a machine to view and manage its advertisements.') }}
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('admin.ads.partials.ad-modal')
    @include('admin.ads.partials.assign-modal')

    <!-- Sync Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="isSyncConfirmOpen" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <!-- Background Backdrop -->
                <div x-show="isSyncConfirmOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="isSyncConfirmOpen = false"></div>

                <!-- Modal Content -->
                <div x-show="isSyncConfirmOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-[2.5rem] bg-white dark:bg-slate-900 p-8 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200 dark:border-slate-800">

                    <div class="flex items-center gap-4 mb-6">
                        <div
                            class="w-14 h-14 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 border border-amber-500/20">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight uppercase">{{
                                __('Command Confirmation') }}</h3>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ __('Please confirm the details below') }}</p>
                        </div>
                    </div>

                    <div class="space-y-4 bg-slate-50 dark:bg-slate-950/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-800/50 mb-8">
                        <div class="flex justify-between items-center px-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Command Type') }}</span>
                            <span class="text-sm font-black text-slate-800 dark:text-slate-200">{{ __('Sync to Machine') }}</span>
                        </div>
                        <div class="flex justify-between items-center px-1 pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                            <span class="text-[10px] font-black text-cyan-500 uppercase tracking-widest">{{ __('Target Machine') }}</span>
                            <span class="text-sm font-black text-slate-800 dark:text-slate-200" x-text="machines.find(m => m.id == selectedMachineId)?.name || ''"></span>
                        </div>
                        <div class="space-y-2 px-1 pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                            <label
                                class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em] ml-1">{{
                                __('Operation Note') }}</label>
                            <textarea x-model="syncNote"
                                class="luxury-input w-full min-h-[100px] text-sm py-3 px-4 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 focus:border-cyan-500/50 rounded-2xl"
                                placeholder="{{ __('Reason for this command...') }}"></textarea>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="isSyncConfirmOpen = false"
                            class="flex-1 px-6 py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button @click="executeSyncAds()"
                            class="flex-1 px-6 py-4 rounded-2xl bg-cyan-600 text-white text-xs font-black uppercase tracking-widest hover:bg-cyan-500 shadow-lg shadow-cyan-500/20 active:scale-[0.98] transition-all">
                            {{ __('Execute') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Preview Modal -->
    <div x-show="isPreviewOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4 sm:p-6" x-cloak
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @keydown.escape.window="isPreviewOpen = false">

        <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-xl" @click="isPreviewOpen = false"></div>

        <div class="relative max-w-5xl w-full max-h-[90vh] flex flex-col items-center justify-center animate-luxury-in">
            <!-- Close Button -->
            <button @click="isPreviewOpen = false"
                class="absolute -top-12 right-0 p-2 text-white/50 hover:text-white transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Content Area -->
            <div
                class="w-full bg-slate-900/40 rounded-[2rem] border border-white/5 overflow-hidden shadow-2xl flex items-center justify-center">
                <template x-if="isPreviewOpen && previewAd.type === 'image'">
                    <img :src="previewAd.url" class="max-w-full max-h-[80vh] object-contain shadow-2xl">
                </template>
                <template x-if="isPreviewOpen && previewAd.type === 'video'">
                    <video :src="previewAd.url" controls autoplay class="max-w-full max-h-[80vh] shadow-2xl"></video>
                </template>
            </div>

            <!-- Footer Info -->
            <div class="mt-4 text-center">
                <h4 class="text-white font-black text-lg uppercase tracking-widest" x-text="previewAd.name"></h4>
                <p class="text-white/40 text-[10px] font-bold uppercase tracking-[0.2em] mt-1"
                    x-text="previewAd.type + ' | ' + previewAd.duration + 's'"></p>
            </div>
        </div>
    </div>

    <!-- Sequence Preview Modal -->
    <div x-show="isSequencePreviewOpen" class="fixed inset-0 z-[120] flex items-center justify-center p-4 sm:p-6"
        x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @keydown.escape.window="stopSequencePreview()">

        <div class="fixed inset-0 bg-slate-950/95 backdrop-blur-md" @click="stopSequencePreview()"></div>

        <div class="relative w-full max-w-5xl h-[85vh] flex flex-col items-center justify-center animate-luxury-in"
            x-show="currentSequenceAd">

            <!-- Close Button -->
            <button @click="stopSequencePreview()"
                class="absolute -top-12 right-0 p-2 text-white/50 hover:text-white transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Media Container -->
            <div
                class="relative w-full h-full bg-slate-900 rounded-[2rem] border border-white/5 overflow-hidden shadow-2xl flex items-center justify-center">

                <template x-if="currentSequenceAd && currentSequenceAd.advertisement.type === 'image'">
                    <img :src="currentSequenceAd.advertisement.url"
                        class="max-w-full max-h-full object-contain animate-luxury-in"
                        :key="'img-'+currentSequenceAd.id">
                </template>

                <template x-if="currentSequenceAd && currentSequenceAd.advertisement.type === 'video'">
                    <video :src="currentSequenceAd.advertisement.url" autoplay muted playsinline
                        class="max-w-full max-h-full object-contain animate-luxury-in"
                        :key="'vid-'+currentSequenceAd.id"></video>
                </template>

                <!-- Progress Bar -->
                <div class="absolute bottom-0 left-0 h-1.5 bg-cyan-500 transition-all duration-100 ease-linear"
                    :style="'width: ' + sequenceProgress + '%'"></div>
            </div>

            <!-- Header Info -->
            <div class="absolute top-6 left-6 right-6 flex items-center justify-between z-10 w-[calc(100%-3rem)]">
                <div
                    class="bg-black/60 backdrop-blur-md rounded-xl px-4 py-2 border border-white/10 flex items-center gap-3">
                    <span class="text-white font-black tracking-widest text-sm"
                        x-text="currentSequenceAd?.advertisement.name"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-white/20"></span>
                    <span class="text-cyan-400 font-black tracking-widest text-xs uppercase"
                        x-text="(currentSequenceIndex + 1) + ' / ' + sequenceAds.length"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-white/20"></span>
                    <span class="text-white/80 font-mono font-bold tracking-tight text-xs tabular-nums"
                        x-text="Math.ceil(sequenceRemainingTime) + 's'"></span>
                </div>

                <div class="flex items-center gap-2">
                    <button @click.stop="prevSequenceAd()"
                        class="p-2.5 bg-black/60 backdrop-blur-md rounded-xl border border-white/10 text-white hover:bg-white/20 transition-all group">
                        <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <!-- Play/Pause -->
                    <button @click.stop="toggleSequencePlay()"
                        class="p-3 bg-cyan-500 backdrop-blur-md rounded-xl border border-cyan-400/30 text-white hover:bg-cyan-400 transition-all shadow-[0_0_15px_rgba(6,182,212,0.4)]">
                        <svg x-show="!isSequencePaused" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                        </svg>
                        <svg x-show="isSequencePaused" class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                    </button>
                    <button @click.stop="nextSequenceAd()"
                        class="p-2.5 bg-black/60 backdrop-blur-md rounded-xl border border-white/10 text-white hover:bg-white/20 transition-all group">
                        <svg class="w-5 h-5 group-hover:translate-x-0.5 transition-transform" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <x-delete-confirm-modal :title="__('Delete Advertisement Confirmation')"
        :message="__('Are you sure you want to delete this advertisement? This will also remove all assignments to machines.')" />
    
    <x-status-confirm-modal :title="__('Disable Advertisement Confirmation')" :message="__('Are you sure you want to disable this advertisement?')" />

    <x-confirm-modal
        alpineVar="isAssignmentDeleteOpen"
        confirmAction="submitRemoveAssignment()"
        iconType="danger"
        confirmColor="rose"
        :title="__('Remove Assignment Confirmation')"
        :message="__('Are you sure you want to remove this assignment?')"
        :confirmText="__('Delete Permanently')"
        isSubmittingVar="isSubmitting"
    />

    <form x-ref="statusToggleForm" :action="toggleFormAction" method="POST" class="hidden">
        @csrf
        @method('PATCH')
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adManager', () => ({
            isLoading: false,
            isSubmitting: false,
            uploadProgress: 0,
            activeTab: 'list',
            selectedMachineId: null,
            machines: [],
            allAds: [],
            machineAds: {
                vending: [],
                visit_gift: [],
                standby: []
            },
            urls: {},

            // Ad CRUD Modal
            isAdModalOpen: false,
            isDeleteConfirmOpen: false,
            deleteFormAction: '',

            // Assignment Delete Modal
            isAssignmentDeleteOpen: false,
            assignmentIdToDelete: null,

            // Status Toggle
            isStatusConfirmOpen: false,
            toggleFormAction: '',

            toggleStatus(actionUrl) {
                this.toggleFormAction = actionUrl;
                this.isStatusConfirmOpen = true;
            },

            submitConfirmedForm() {
                if (this.isStatusConfirmOpen) {
                    this.isStatusConfirmOpen = false;
                    this.$refs.statusToggleForm.submit();
                }
            },

            // Preview
            isPreviewOpen: false,
            previewAd: { url: '', type: '', name: '', duration: 15 },

            adFormMode: 'add',
            fileName: '',
            mediaPreview: null,
            adForm: {
                id: null,
                name: '',
                type: 'image',
                duration: 15,
                is_active: true,
                url: '',
                start_at: '',
                end_at: ''
            },

            // Assign Modal
            isAssignModalOpen: false,
            assignForm: {
                machine_id: null,
                advertisement_id: '',
                position: ''
            },

            // Sync Confirmation
            isSyncConfirmOpen: false,
            syncNote: '',

            // Sequence Preview
            isSequencePreviewOpen: false,
            sequenceAds: [],
            currentSequenceIndex: 0,
            sequenceInterval: null,
            sequenceRemainingTime: 0,
            sequenceProgress: 0,
            isSequencePaused: false,

            get currentSequenceAd() {
                return this.sequenceAds[this.currentSequenceIndex] || null;
            },

            handleFilterSubmit(e, tab) {
                const formData = new FormData(e.target);
                formData.set('tab', tab);
                const url = new URL(window.location.href.split('?')[0]);
                const params = new URLSearchParams(formData);
                url.search = params.toString();
                this.fetchPage(url.href);
            },

            async fetchPage(url) {
                if (!url || url === window.location.href) return;
                const urlObj = new URL(url, window.location.origin);
                if (urlObj.host === window.location.host) {
                    urlObj.protocol = window.location.protocol;
                }
                url = urlObj.toString();
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

                    // Update main content container
                    const newContent = doc.querySelector('#ajax-content-container');
                    const currentContent = document.querySelector('#ajax-content-container');
                    if (newContent && currentContent) {
                        currentContent.innerHTML = newContent.innerHTML;
                    }

                    // Update URL without reload
                    window.history.pushState({}, '', url);

                    // Re-initialize Preline UI
                    this.$nextTick(() => {
                        if (window.HSStaticMethods) {
                            window.HSStaticMethods.autoInit();
                        }
                    });

                } catch (error) {
                    console.error('Error fetching page:', error);
                    window.showToast?.('載入失敗，請重試', 'error');
                } finally {
                    this.isLoading = false;
                }
            },

            syncAdsToMachine() {
                if (!this.selectedMachineId) {
                    window.showToast?.(@js(__('Please select a machine first')), 'error');
                    return;
                }
                this.isSyncConfirmOpen = true;
            },

            async executeSyncAds() {
                this.isSyncConfirmOpen = false;
                try {
                    const url = this.urls.syncToMachine.replace(':id', this.selectedMachineId);
                    
                    const formData = new FormData();
                    formData.append('note', this.syncNote);

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        window.showToast?.(result.message || '指令已發送', 'success');
                    } else {
                        window.showToast?.(result.message || '發送失敗', 'error');
                    }
                    this.syncNote = ''; // reset note on finish
                } catch (error) {
                    console.error('Error syncing ads:', error);
                    window.showToast?.('發送失敗，請重試', 'error');
                }
            },

            startSequencePreview(pos) {
                if (!this.machineAds[pos] || this.machineAds[pos].length === 0) return;
                this.sequenceAds = this.machineAds[pos];
                this.currentSequenceIndex = 0;
                this.isSequencePreviewOpen = true;
                this.isSequencePaused = false;

                this.playSequenceAd();
            },

            stopSequencePreview() {
                this.isSequencePreviewOpen = false;
                this.clearSequenceTimers();
            },

            clearSequenceTimers() {
                if (this.sequenceInterval) clearInterval(this.sequenceInterval);
            },

            playSequenceAd() {
                this.clearSequenceTimers();

                if (this.isSequencePaused) return;

                const currentAd = this.currentSequenceAd?.advertisement;
                if (!currentAd) return;

                this.sequenceRemainingTime = currentAd.duration;
                this.sequenceProgress = 0;

                this.sequenceInterval = setInterval(() => {
                    if (this.isSequencePaused) return;

                    this.sequenceRemainingTime -= 0.1;
                    this.sequenceProgress = ((currentAd.duration - this.sequenceRemainingTime) / currentAd.duration) * 100;

                    if (this.sequenceRemainingTime <= 0) {
                        this.nextSequenceAd();
                    }
                }, 100);
            },

            toggleSequencePlay() {
                this.isSequencePaused = !this.isSequencePaused;
            },

            nextSequenceAd() {
                this.currentSequenceIndex++;
                if (this.currentSequenceIndex >= this.sequenceAds.length) {
                    this.currentSequenceIndex = 0; // Loop back
                }
                this.playSequenceAd();
            },

            prevSequenceAd() {
                this.currentSequenceIndex--;
                if (this.currentSequenceIndex < 0) {
                    this.currentSequenceIndex = this.sequenceAds.length - 1;
                }
                this.playSequenceAd();
            },

            openPreview(ad) {
                this.previewAd = { ...ad };
                this.isPreviewOpen = true;
            },

            // Sort Reordering logic
            moveUp(position, index) {
                if (index > 0) {
                    const list = this.machineAds[position];
                    const temp = list[index];
                    list[index] = list[index - 1];
                    list[index - 1] = temp;
                    this.syncSortOrder(position);
                }
            },

            moveDown(position, index) {
                const list = this.machineAds[position];
                if (index < list.length - 1) {
                    const temp = list[index];
                    list[index] = list[index + 1];
                    list[index + 1] = temp;
                    this.syncSortOrder(position);
                }
            },

            async syncSortOrder(position) {
                const list = this.machineAds[position];
                const assignmentIds = list.map(item => item.id);
                try {
                    const response = await fetch(this.urls.reorder, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ assignment_ids: assignmentIds })
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.showToast?.(result.message, 'success');
                    } else {
                        window.showToast?.(result.message || 'Error', 'error');
                        this.fetchMachineAds(); // 如果更新失敗，重取恢復畫面原本樣子
                    }
                } catch (e) {
                    console.error('Failed to update sort order', e);
                    window.showToast?.('System Error', 'error');
                }
            },

            handleFileChange(e) {
                const file = e.target.files[0];
                if (!file) return;

                // Validation
                const isVideo = file.type.startsWith('video/');
                const isImage = file.type.startsWith('image/');
                const maxSize = isVideo ? 50 * 1024 * 1024 : 10 * 1024 * 1024; // 50MB for video, 10MB for image

                if (file.size > maxSize) {
                    window.showToast?.(`{{ __("File is too large") }} (${isVideo ? '50MB' : '10MB'} MAX)`, 'error');
                    e.target.value = '';
                    return;
                }

                this.fileName = file.name;

                // Set form type based on file
                if (isVideo) this.adForm.type = 'video';
                else if (isImage) this.adForm.type = 'image';

                // Local Preview
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.mediaPreview = event.target.result;
                };
                reader.readAsDataURL(file);

                // Update Select UI
                this.$nextTick(() => {
                    window.HSSelect.getInstance('#ad_type_select')?.setValue(this.adForm.type);
                });
            },

            removeMedia() {
                this.fileName = '';
                this.mediaPreview = null;
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                // If editing, we still keep the original url in adForm.url but the UI shows "empty"
            },

            async submitAdForm() {
                const form = this.$refs.adFormEl;
                const formData = new FormData(form);

                // 基本驗證
                if (!this.adForm.name) {
                    window.showToast?.('{{ __("Please enter a name") }}', 'error');
                    return;
                }
                if (this.adFormMode === 'add' && !this.fileName) {
                    window.showToast?.('{{ __("Please select a file") }}', 'error');
                    return;
                }

                // 防止重複提交
                if (this.isSubmitting) return;
                this.isSubmitting = true;
                this.uploadProgress = 0;

                const url = this.adFormMode === 'add' ? this.urls.store : this.urls.update.replace(':id', this.adForm.id);
                if (this.adFormMode === 'edit') formData.append('_method', 'PUT');

                // 取得頂部進度條並切換為真實進度模式
                const bar = document.getElementById('top-loading-bar');
                if (bar) {
                    bar.style.animation = 'none';
                    bar.style.width = '0%';
                    bar.style.opacity = '1';
                    bar.classList.add('loading');
                }

                const xhr = new XMLHttpRequest();

                // 監聽上傳進度
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        this.uploadProgress = pct;
                        if (bar) {
                            // 最多到 95%，完成時才跳到 100%
                            bar.style.width = Math.min(pct, 95) + '%';
                        }
                    }
                });

                xhr.addEventListener('load', () => {
                    let result;
                    try {
                        result = JSON.parse(xhr.responseText);
                    } catch (e) {
                        console.error('Failed to parse response', e, xhr.responseText);
                        let errorMsg = 'System Error';
                        if (xhr.status === 413) {
                            errorMsg = '{{ __("File is too large for the server configuration") }} (Nginx 413)';
                        } else if (xhr.status >= 500) {
                            errorMsg = '{{ __("Server Internal Error") }} (500)';
                        }
                        window.showToast?.(errorMsg, 'error');
                        this.isSubmitting = false;
                        this.uploadProgress = 0;
                        if (bar) {
                            bar.style.width = '0%';
                            bar.style.opacity = '0';
                            bar.classList.remove('loading');
                        }
                        return;
                    }

                    if (xhr.status >= 200 && xhr.status < 300 && result.success) {
                        // 進度跳到 100%
                        if (bar) bar.style.width = '100%';
                        this.uploadProgress = 100;
                        this.isAdModalOpen = false;
                        // 延遲 300ms 讓進度條動畫完成後再重整
                        setTimeout(() => {
                            location.reload();
                        }, 300);
                    } else {
                        let errorMessage = result.message || 'Error';
                        if (result.errors) {
                            // 如果有驗證錯誤，彙整所有錯誤訊息
                            errorMessage = Object.values(result.errors).flat().join(' ');
                        }
                        window.showToast?.(errorMessage, 'error');
                        this.isSubmitting = false;
                        this.uploadProgress = 0;
                        if (bar) {
                            bar.style.width = '0%';
                            bar.style.opacity = '0';
                            bar.classList.remove('loading');
                        }
                    }
                });

                xhr.addEventListener('error', () => {
                    console.error('Upload failed');
                    window.showToast?.('{{ __("Upload failed, please try again") }}', 'error');
                    this.isSubmitting = false;
                    this.uploadProgress = 0;
                    if (bar) {
                        bar.style.width = '0%';
                        bar.style.opacity = '0';
                        bar.classList.remove('loading');
                    }
                });

                xhr.open('POST', url);
                xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.send(formData);
            },

            formatDateForInput(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${year}/${month}/${day} ${hours}:${minutes}`;
            },

            async submitAssignment() {
                if (this.isSubmitting) return;
                this.isSubmitting = true;

                const bar = document.getElementById('top-loading-bar');
                if (bar) bar.classList.add('loading');
                try {
                    const response = await fetch(this.urls.assign, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.assignForm)
                    });

                    const result = await response.json();
                    if (response.ok && result.success) {
                        this.isAssignModalOpen = false;
                        await this.fetchMachineAds();
                        window.showToast?.(result.message, 'success');
                    } else {
                        let msg = result.message || 'Error';
                        if (result.errors) {
                            msg = Object.values(result.errors).flat().join(' ');
                        }
                        window.showToast?.(msg, 'error');
                    }
                } catch (e) {
                    console.error('Failed to assign ad', e);
                    window.showToast?.('{{ __("System Error") }}', 'error');
                } finally {
                    this.isSubmitting = false;
                    if (bar) bar.classList.remove('loading');
                }
            },

            init() {
                this.urls = JSON.parse(this.$el.dataset.urls);
                this.machines = JSON.parse(this.$el.dataset.machines || '[]');
                this.allAds = JSON.parse(this.$el.dataset.allAds || '[]');
                this.activeTab = this.$el.dataset.activeTab || 'list';

                // Sync custom selects when modals open
                this.$watch('isAdModalOpen', value => {
                    if (value) {
                        this.$nextTick(() => {
                            window.HSSelect.getInstance('#ad_type_select')?.setValue(this.adForm.type);
                            window.HSSelect.getInstance('#ad_duration_select')?.setValue(this.adForm.duration.toString());
                            if (document.querySelector('#ad_company_select')) {
                                window.HSSelect.getInstance('#ad_company_select')?.setValue(this.adForm.company_id || '');
                            }
                        });
                    }
                });


            },

            selectMachine(id) {
                if (!id || id === ' ') {
                    this.selectedMachineId = null;
                    return;
                }
                this.selectedMachineId = id;
                this.fetchMachineAds();
            },

            async fetchMachineAds() {
                const url = this.urls.getMachineAds.replace(':id', this.selectedMachineId);
                this.isLoading = true;
                try {
                    const response = await fetch(url);
                    const result = await response.json();
                    if (result.success) {
                        this.machineAds = {
                            vending: result.data.vending || [],
                            visit_gift: result.data.visit_gift || [],
                            standby: result.data.standby || []
                        };
                    }
                } catch (e) {
                    console.error('Failed to fetch machine ads', e);
                } finally {
                    this.isLoading = false;
                }
            },

            openAddModal() {
                this.adFormMode = 'add';
                this.adForm = { id: null, company_id: '', name: '', type: 'image', duration: 15, is_active: true, url: '', start_at: '', end_at: '' };
                this.fileName = '';
                this.mediaPreview = null;
                this.isAdModalOpen = true;
            },

            openEditModal(ad) {
                this.adFormMode = 'edit';
                this.adForm = {
                    ...ad,
                    start_at: this.formatDateForInput(ad.start_at),
                    end_at: this.formatDateForInput(ad.end_at)
                };
                this.fileName = '';
                this.mediaPreview = ad.url; // Use existing URL as preview
                this.isAdModalOpen = true;
            },

            openAssignModal(pos) {
                this.assignForm = {
                    machine_id: this.selectedMachineId,
                    advertisement_id: '',
                    position: pos,
                    sort_order: this.machineAds[pos]?.length || 0
                };

                this.updateAssignSelect();
                this.isAssignModalOpen = true;
            },

            updateAssignSelect() {
                const machine = this.machines.find(m => m.id == this.selectedMachineId);
                const companyId = machine ? machine.company_id : null;

                // 篩選出同公司的素材（或是系統層級的共通素材如果 company_id 為 null）
                // 若沒有特別設定，通常 null 為系統共用
                const filteredAds = this.allAds.filter(ad => ad.company_id == companyId || ad.company_id == null);

                const wrapper = document.getElementById('assign_ad_select_wrapper');
                if (!wrapper) return;
                wrapper.innerHTML = '';

                const selectEl = document.createElement('select');
                selectEl.name = 'advertisement_id';
                selectEl.id = 'assign_ad_select_' + Date.now();
                selectEl.className = 'hidden';

                const configStr = JSON.stringify({
                    "placeholder": "{{ __('Please select a material') }}",
                    "hasSearch": true,
                    "searchPlaceholder": "{{ __('Search...') }}",
                    "isHidePlaceholder": false,
                    "searchClasses": "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
                    "searchWrapperClasses": "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
                    "toggleClasses": "hs-select-toggle luxury-select-toggle",
                    "dropdownClasses": "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[100] animate-luxury-in",
                    "optionClasses": "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
                    "optionTemplate": '<div class="flex items-center justify-between w-full"><span data-title></span><span class="hs-select-active-indicator hidden text-cyan-500"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></div>'
                });

                selectEl.setAttribute('data-hs-select', configStr);

                if (filteredAds.length === 0) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = "{{ __('No materials available') }}";
                    opt.disabled = true;
                    selectEl.appendChild(opt);
                } else {
                    const emptyOpt = document.createElement('option');
                    emptyOpt.value = '';
                    emptyOpt.textContent = "{{ __('Please select a material') }}";
                    selectEl.appendChild(emptyOpt);

                    filteredAds.forEach(ad => {
                        const opt = document.createElement('option');
                        opt.value = ad.id;
                        opt.textContent = `${ad.name} (${ad.type === 'video' ? "{{ __('video') }}" : "{{ __('image') }}"}, ${ad.duration}s)`;
                        opt.setAttribute('data-title', opt.textContent);
                        if (ad.id === this.assignForm.advertisement_id) opt.selected = true;
                        selectEl.appendChild(opt);
                    });
                }

                wrapper.appendChild(selectEl);

                // Set the initial value after appending but before autoInit
                if (this.assignForm.advertisement_id) {
                    selectEl.value = this.assignForm.advertisement_id;
                }

                selectEl.addEventListener('change', (e) => {
                    this.assignForm.advertisement_id = e.target.value;
                });

                this.$nextTick(() => {
                    if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                        window.HSStaticMethods.autoInit(['select']);

                        // If we have a value, ensure the Preline instance reflects it
                        if (this.assignForm.advertisement_id) {
                            setTimeout(() => {
                                window.HSSelect.getInstance(selectEl)?.setValue(this.assignForm.advertisement_id);
                            }, 50);
                        }
                    }
                });
            },

            removeAssignment(id) {
                this.assignmentIdToDelete = id;
                this.isAssignmentDeleteOpen = true;
            },

            async submitRemoveAssignment() {
                const id = this.assignmentIdToDelete;
                if (!id) return;
                
                if (this.isSubmitting) return;
                this.isSubmitting = true;

                const bar = document.getElementById('top-loading-bar');
                if (bar) bar.classList.add('loading');

                const url = this.urls.removeAssignment.replace(':id', id);
                try {
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.isAssignmentDeleteOpen = false;
                        await this.fetchMachineAds();
                        window.showToast?.(result.message, 'success');
                    } else {
                        window.showToast?.(result.message || 'Error', 'error');
                    }
                } catch (e) {
                    console.error('Failed to remove assignment', e);
                } finally {
                    this.assignmentIdToDelete = null;
                    this.isAssignmentDeleteOpen = false;
                    this.isSubmitting = false;
                    if (bar) bar.classList.remove('loading');
                }
            },

            confirmDelete(id) {
                this.deleteFormAction = this.urls.delete.replace(':id', id);
                this.isDeleteConfirmOpen = true;
            }
        }));
    });
</script>
@endsection
