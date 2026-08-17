@extends('layouts.admin')

@section('content')
<div class="space-y-4 pb-20" x-data="{
    showDetailDrawer: false,
    currentRecord: null,
    isLoadingTable: false,
    translations: {
        'Repair': '{{ __('Repair') }}',
        'Installation': '{{ __('Installation') }}',
        'Removal': '{{ __('Removal') }}',
        'Maintenance': '{{ __('Maintenance') }}',
        'System': '{{ __('System') }}'
    },
    async fetchPage(url) {
        if (!url || this.isLoadingTable) return;
        this.isLoadingTable = true;
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector('#ajax-content-container');
            if (newContent) {
                document.querySelector('#ajax-content-container').innerHTML = newContent.innerHTML;
                window.history.pushState({}, '', url);

                const container = document.querySelector('#ajax-content-container');
                if (container && window.Alpine) {
                    Alpine.initTree(container);
                }
                if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
                    setTimeout(() => window.HSStaticMethods.autoInit(), 100);
                }
            }
        } catch (e) {
            console.error('AJAX Load Failed:', e);
        } finally {
            this.isLoadingTable = false;
        }
    },
    openDetail(record) {
        this.currentRecord = record;
        this.showDetailDrawer = true;
    },
    enlargedImage: null
}">
    <!-- 1. Header Area -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white tracking-tight font-display">{{ __('Maintenance Records') }}</h1>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">{{ __('Track device health and maintenance history') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.maintenance.create') }}" class="btn-luxury-primary flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="text-sm sm:text-base">{{ __('New Record') }}</span>
            </a>
        </div>
    </div>

    <!-- 2. Main Content Card -->
    <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden">
        <!-- Loading Spinner Overlay -->
        <div x-show="isLoadingTable" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] rounded-3xl" x-cloak>
            
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

        <div id="ajax-content-container" 
             :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': isLoadingTable }"
             @ajax:navigate.prevent.stop="fetchPage($event.detail.url)"
             @click="if ($event.target.closest('a') && $event.target.closest('a').href && ($event.target.closest('a').href.includes('page=') || $event.target.closest('a').href.includes('search=') || $event.target.closest('a').href.includes('category=') || $event.target.closest('a').href.includes('company_id='))) { $event.preventDefault(); fetchPage($event.target.closest('a').href); }">

        <!-- Toolbar & Filters -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-6">
                <!-- Search -->
                <form method="GET" action="{{ route('admin.maintenance.index') }}" class="flex flex-wrap items-center gap-4"
                      @submit.prevent="
                        const url = new URL($el.action, window.location.origin);
                        new FormData($el).forEach((value, key) => {
                            if (value.trim()) url.searchParams.set(key, value);
                            else url.searchParams.delete(key);
                        });
                        fetchPage(url.pathname + url.search);
                      ">

                <div class="relative group flex-1 sm:flex-none">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search serial or machine...') }}"
                        class="luxury-input luxury-input-sm pl-12 pr-6 block w-full sm:w-72 font-bold">
                </div>

                <!-- Category Filter (Custom Select UI) -->
                <div class="flex items-center gap-3 w-full sm:w-48">
                    <x-searchable-select name="category" :placeholder="__('All Categories')" :selected="request('category')" 
                        x-on:change="$el.closest('form').dispatchEvent(new Event('submit', { cancelable: true }))" :hasSearch="false" class="luxury-select-sm">

                        @foreach(['Repair', 'Installation', 'Removal', 'Maintenance'] as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                {{ __($cat) }}
                            </option>
                        @endforeach
                    </x-searchable-select>
                </div>

                @if(auth()->user()->isSystemAdmin())
                <div class="w-full sm:w-72">
                    <x-searchable-select name="company_id" :options="$companies" :selected="request('company_id')"
                        :placeholder="__('All Companies')"
                        x-on:change="$el.closest('form').dispatchEvent(new Event('submit', { cancelable: true }))" />
                </div>
                @endif

                <div class="flex items-center gap-2 flex-none ml-auto sm:ml-0">
                    <button type="submit" class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 group transition-all active:scale-95" title="{{ __('Search') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                    <button type="button" 
                        @click="
                            $el.closest('form').querySelectorAll('input[type=text]').forEach(i => i.value = '');
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
                </form>
            </div>
        </div>

        <!-- Table (Desktop) -->
        <div class="hidden xl:block overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-y-0">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Time') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Machine Information') }}</th>
                        @if(auth()->user()->isSystemAdmin())
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Company') }}</th>
                        @endif
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Category') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Engineer') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                    @forelse($records as $record)
                    <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                        <td class="px-6 py-6 font-mono text-xs font-bold text-slate-600 dark:text-slate-300">
                            {{ $record->maintenance_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-6 py-6 cursor-pointer group/cell" @click="openDetail({{ $record->load('machine', 'user', 'company')->toJson() }})">
                            <div class="flex flex-col">
                                <span class="text-sm font-black text-slate-800 dark:text-slate-100 group-hover/cell:text-cyan-600 transition-colors">{{ $record->machine->name ?? 'N/A' }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest group-hover/cell:text-cyan-500/60 transition-colors">{{ $record->machine->serial_no ?? 'N/A' }}</span>
                            </div>
                        </td>
                        @if(auth()->user()->isSystemAdmin())
                        <td class="px-6 py-6">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ $record->company->name ?? __('System') }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $record->company->company_code ?? '' }}</span>
                            </div>
                        </td>
                        @endif
                        <td class="px-6 py-6">
                            @php
                                $color = match($record->category) {
                                    'Repair' => 'rose',
                                    'Installation' => 'emerald',
                                    'Removal' => 'amber',
                                    'Maintenance' => 'cyan',
                                    default => 'slate'
                                };
                            @endphp
                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-{{ $color }}-500/10 border border-{{ $color }}-500/20 w-fit">
                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-{{ $color }}-500"></span>
                                <span class="text-[10px] font-black text-{{ $color }}-600 dark:text-{{ $color }}-400 tracking-widest uppercase">
                                    {{ __($record->category) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-500 border border-slate-200 dark:border-slate-700">
                                    {{ mb_substr($record->user->name, 0, 1) }}
                                </div>
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ $record->user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-6 text-right">
                            <button @click="openDetail({{ $record->load('machine', 'user', 'company')->toJson() }})"
                                class="text-slate-400 hover:text-cyan-500 transition-colors p-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-transparent hover:border-cyan-500/20">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSystemAdmin() ? 6 : 5 }}" class="px-6 py-24 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.1-3.1a6 6 0 0 1-7.9 7.9l-5.6 5.6a2.1 2.1 0 0 1-3-3l5.6-5.6a6 6 0 0 1 7.9-7.9l-3.1 3.1Z" />
                                </svg>
                                <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No maintenance records found') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile & Tablet Card View -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 xl:hidden">
            @forelse($records as $record)
            <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
                
                <!-- Card Header -->
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.65 2.65 0 0021 17.25l-5.83-5.83M11.42 15.17l2.42-2.42a2.65 2.65 0 000-3.75l-.6-.6a2.65 2.65 0 00-3.75 0l-2.42 2.42M11.42 15.17L4.41 8.16a2.12 2.12 0 010-3l.6-.6a2.12 2.12 0 013 0l7.01 7.01" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 truncate group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">
                                {{ $record->machine->name ?? 'N/A' }}
                            </h3>
                            <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate mt-0.5">
                                {{ $record->machine->serial_no ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    @php
                        $color = match($record->category) {
                            'Repair' => 'rose',
                            'Installation' => 'emerald',
                            'Removal' => 'amber',
                            'Maintenance' => 'cyan',
                            default => 'slate'
                        };
                    @endphp
                    <div class="shrink-0">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-{{ $color }}-500/10 border border-{{ $color }}-500/20 w-fit">
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-{{ $color }}-500"></span>
                            <span class="text-[10px] font-black text-{{ $color }}-600 dark:text-{{ $color }}-400 tracking-widest uppercase">
                                {{ __($record->category) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Execution Time') }}</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">{{ $record->maintenance_at->format('Y-m-d H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Engineer') }}</p>
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[8px] font-bold text-slate-500 border border-slate-200 dark:border-slate-700">
                                {{ mb_substr($record->user->name, 0, 1) }}
                            </div>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $record->user->name }}</span>
                        </div>
                    </div>
                    @if(auth()->user()->isSystemAdmin())
                    <div class="col-span-2">
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Company') }}</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                            {{ $record->company->name ?? __('System') }} <span class="text-[10px] text-slate-400 opacity-60">({{ $record->company->company_code ?? '' }})</span>
                        </p>
                    </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3">
                    <button type="button" @click="openDetail({{ $record->load('machine', 'user', 'company')->toJson() }})"
                            class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-cyan-600 dark:text-cyan-400 font-black text-xs uppercase tracking-widest hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        {{ __('View Details') }}
                    </button>
                </div>
            </div>
            @empty
            <div class="col-span-full py-20 text-center flex flex-col items-center gap-4 bg-white/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800/50 luxury-card">
                <div class="flex flex-col items-center gap-3">
                    <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.1-3.1a6 6 0 0 1-7.9 7.9l-5.6 5.6a2.1 2.1 0 0 1-3-3l5.6-5.6a6 6 0 0 1 7.9-7.9l-3.1 3.1Z" />
                    </svg>
                    <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No maintenance records found') }}</p>
                </div>
            </div>
            @endforelse
        </div>

        <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
            @if($records->total() > 0)
                {{ $records->appends(request()->query())->links('vendor.pagination.luxury') }}
            @else
                <div class="flex items-center justify-between gap-4 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                    <span>{{ __('No records found') }}</span>
                    <span>0 / 0</span>
                </div>
            @endif
        </div>
        </div>
    </div>



    <!-- 1. Maintenance Detail Drawer -->
    <template x-teleport="body">
        <div x-show="showDetailDrawer" class="fixed inset-0 z-[150]" x-cloak>
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" x-show="showDetailDrawer"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showDetailDrawer = false">
            </div>
            <div class="fixed inset-y-0 right-0 max-w-full flex">
                <div class="w-screen max-w-md" x-show="showDetailDrawer"
                    x-transition:enter="transform transition ease-in-out duration-500"
                    x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-500"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                    <div class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-100 dark:border-slate-800">
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-800 dark:text-white">{{ __('Maintenance Details') }}</h2>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1" x-text="currentRecord?.machine?.name"></p>
                            </div>
                            <button @click="showDetailDrawer = false" class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-6 custom-scrollbar">
                            <!-- Basic Info -->
                            <section class="space-y-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">{{ __('Category') }}</span>
                                        <div class="text-xs font-black text-slate-700 dark:text-slate-100" x-text="currentRecord ? (translations[currentRecord.category] || currentRecord.category) : ''"></div>
                                    </div>
                                    <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">{{ __('Engineer') }}</span>
                                        <div class="text-xs font-black text-slate-700 dark:text-slate-100" x-text="currentRecord?.user?.name"></div>
                                    </div>
                                </div>
                                <template x-if="@js(auth()->user()->isSystemAdmin())">
                                    <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">{{ __('Company') }}</span>
                                        <div class="text-xs font-black text-slate-700 dark:text-slate-100" x-text="currentRecord?.company?.name || translations['System']"></div>
                                    </div>
                                </template>
                                <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">{{ __('Execution Time') }}</span>
                                    <div class="text-xs font-black text-slate-700 dark:text-slate-100" x-text="currentRecord ? new Date(currentRecord.maintenance_at).toLocaleString() : ''"></div>
                                </div>
                            </section>

                            <!-- Content -->
                            <section class="space-y-3">
                                <h3 class="text-[11px] font-black text-indigo-500 uppercase tracking-[0.3em]">{{ __('Work Content') }}</h3>
                                <div class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap" x-text="currentRecord?.content || '{{ __('No content provided') }}'"></p>
                                </div>
                            </section>

                            <!-- Photos -->
                            <template x-if="currentRecord?.photos && currentRecord.photos.length > 0">
                                <section class="space-y-4">
                                    <h3 class="text-[11px] font-black text-cyan-500 uppercase tracking-[0.3em]">{{ __('Maintenance Photos') }}</h3>
                                    <div class="grid grid-cols-2 gap-3">
                                        <template x-for="(path, index) in currentRecord.photos" :key="index">
                                            <div @click="enlargedImage = '/storage/' + path" 
                                                class="relative aspect-square rounded-2xl overflow-hidden border border-slate-100 dark:border-slate-800 shadow-sm bg-slate-50 dark:bg-slate-800/50 cursor-zoom-in hover:ring-2 hover:ring-cyan-500/50 transition-all duration-300 group/img">
                                                <img :src="'/storage/' + path" class="absolute inset-0 w-full h-full object-cover group-hover/img:scale-105 transition-transform duration-500">
                                                <div class="absolute inset-0 bg-slate-900/0 group-hover/img:bg-slate-900/20 flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-all duration-300">
                                                    <svg class="w-6 h-6 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </section>
                            </template>
                        </div>
                        <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                            <button @click="showDetailDrawer = false" class="w-full btn-luxury-ghost">{{ __('Close Panel') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>


    <!-- Image Lightbox Overlay -->
    <template x-teleport="body">
        <div x-show="enlargedImage" 
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 md:p-12"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak>
            
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-slate-950/90 backdrop-blur-xl" @click="enlargedImage = null"></div>
            
            <!-- Close Button -->
            <button @click="enlargedImage = null" 
                class="absolute top-6 right-6 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white backdrop-blur-md transition-all duration-300 z-10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Image Container -->
            <div class="relative max-w-5xl w-full max-h-full flex items-center justify-center p-4 animate-luxury-in" 
                @click.away="enlargedImage = null">
                <img :src="enlargedImage" 
                    class="max-w-full max-h-[85vh] rounded-3xl shadow-2xl border border-white/10 ring-1 ring-white/5 object-contain"
                    x-show="enlargedImage"
                    x-transition:enter="transition ease-out duration-500 delay-100"
                    x-transition:enter-start="scale-95 opacity-0"
                    x-transition:enter-end="scale-100 opacity-100">
            </div>
            
            <!-- Helper text -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white/40 text-[10px] font-bold uppercase tracking-[0.3em] pointer-events-none">
                {{ __('Click anywhere to close') }}
            </div>
        </div>
    </template>
</div>
@endsection
