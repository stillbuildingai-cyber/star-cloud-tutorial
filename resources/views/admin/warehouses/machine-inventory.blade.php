@extends('layouts.admin')

@section('content')
<div class="space-y-4 pb-20" x-data="machineInventory">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <template x-if="viewMode === 'detail'">
            <button @click="backToList()"
                class="p-2.5 rounded-xl bg-white dark:bg-slate-900 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-all border border-slate-200/50 dark:border-slate-700/50 shadow-sm hover:shadow-md active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </button>
        </template>
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{
                __('Machine Inventory Overview') }}</h1>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">{{
                __('Real-time slot-level inventory across all machines') }}</p>
        </div>
    </div>

    {{-- List Mode --}}
    <div x-show="viewMode === 'list'" class="space-y-6 animate-luxury-in" x-cloak
         x-ref="machineList"
         @ajax:navigate.window="if($event.detail.url.includes('machine_id=') === false && viewMode === 'list') { $event.preventDefault(); fetchPage($event.detail.url); }">
        <div class="luxury-card rounded-3xl p-8 hover:translate-y-0 transition-transform duration-300 relative overflow-hidden">
            {{-- AJAX Loading Overlay --}}
            <x-luxury-spinner show="loading" />

            <div class="flex flex-wrap items-center justify-between gap-4 mb-8" :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': loading }">
                <form action="{{ route('admin.warehouses.machine-inventory') }}" method="GET"
                    @submit.prevent="fetchPage($el.action + '?' + new URLSearchParams(new FormData($el)).toString())"
                    class="flex flex-wrap items-center gap-4 flex-1">
                    <div class="relative group w-full sm:w-72">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="py-2.5 pl-12 pr-6 block w-full luxury-input"
                            placeholder="{{ __('Search machines...') }}">
                    </div>

                    @if(auth()->user()->isSystemAdmin())
                    <div class="w-full sm:w-72">
                        <x-searchable-select
                            name="company_id"
                            :options="$companies"
                            :selected="request('company_id')"
                            :placeholder="__('All Companies')"
                            x-on:change="$el.closest('form').dispatchEvent(new Event('submit'))"
                        />
                    </div>
                    @endif

                    <div class="flex items-center gap-2 flex-none ml-auto sm:ml-0">
                        <button type="submit"
                            class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 group transition-all active:scale-95"
                            title="{{ __('Search') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>

                        <button type="button" @click="
                                $el.closest('form').querySelectorAll('input[type=text]').forEach(i => i.value = '');
                                $el.closest('form').querySelectorAll('select').forEach(s => {
                                    s.value = ' ';
                                    const instance = window.HSSelect?.getInstance(s);
                                    if (instance) instance.setValue(' ');
                                });
                                $el.closest('form').dispatchEvent(new Event('submit'));
                            "
                            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 group transition-all active:scale-95"
                            title="{{ __('Reset') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                        <button type="button" @click="openExportModal()"
                            class="p-2.5 rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 shadow-lg shadow-emerald-500/25 group transition-all active:scale-95"
                            title="{{ __('Export Machine Inventory') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <div x-ref="listContent" :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': loading }">
                @include('admin.warehouses.partials.list-machines', ['machines' => $machines])
            </div>
        </div>
    </div>

    {{-- Detail Mode --}}
    {{-- 機台庫存批次匯出 Modal --}}
    <div x-show="exportModalOpen" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="exportModalOpen = false"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg border border-slate-100 dark:border-slate-800 max-h-[85vh] flex flex-col">
                <div class="px-8 pt-8 pb-4 border-b border-slate-50 dark:border-slate-800/50 flex justify-between items-center">
                    <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">{{ __('Export Machine Inventory') }}</h3>
                    <button @click="exportModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-8 py-4 border-b border-slate-50 dark:border-slate-800/50">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" :checked="exportAllChecked" @change="toggleExportAll($event)"
                            class="w-4 h-4 text-emerald-500 rounded border-slate-300 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Select All') }}
                            <span class="text-slate-400 font-bold">(<span x-text="exportSelectedIds.length"></span>/{{ $exportMachines->count() }})</span>
                        </span>
                    </label>
                </div>
                <div class="px-6 py-3 overflow-y-auto flex-1 space-y-1">
                    @forelse($exportMachines as $m)
                    <label class="flex items-center gap-3 py-2 px-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                        <input type="checkbox" value="{{ $m->id }}" x-model.number="exportSelectedIds"
                            class="w-4 h-4 text-emerald-500 rounded border-slate-300 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-700">
                        <span class="flex flex-col">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $m->name }}</span>
                            <span class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-widest">{{ $m->serial_no }}</span>
                        </span>
                    </label>
                    @empty
                    <p class="text-sm text-slate-400 text-center py-8">{{ __('No machines') }}</p>
                    @endforelse
                </div>
                <div class="px-8 py-6 bg-slate-50 dark:bg-slate-900/50 flex flex-wrap justify-end gap-3 rounded-b-3xl border-t border-slate-100 dark:border-slate-800">
                    <button @click="exportModalOpen = false" class="btn-luxury-ghost">{{ __('Cancel') }}</button>
                    {{-- 暫時隱藏 CSV 匯出（保留備用；後端仍支援 csv/zip） --}}
                    {{--
                    <button @click="doExport('csv')"
                        class="px-5 py-2.5 rounded-xl bg-emerald-500 text-white font-black text-sm uppercase tracking-widest hover:bg-emerald-600 transition-all active:scale-95 flex items-center gap-2">
                        <span>📄</span> {{ __('Export to CSV') }}
                    </button>
                    --}}
                    <button @click="doExport('excel')"
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-black text-sm uppercase tracking-widest hover:bg-emerald-700 transition-all active:scale-95 flex items-center gap-2">
                        <span>📊</span> {{ __('Export to Excel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="viewMode === 'detail'" class="space-y-8 animate-luxury-in" x-cloak>
        <!-- Statistics Dashboard -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
            <!-- Left: Overall Progress -->
            <div
                class="lg:col-span-5 luxury-card rounded-[2.5rem] p-10 flex items-center justify-center relative overflow-hidden group">
                <div
                    class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-700">
                </div>
                <div class="relative flex flex-col items-center">
                    <div class="relative w-48 h-48">
                        <!-- SVG Circle Progress -->
                        <svg class="w-full h-full -rotate-90 transform" viewBox="0 0 100 100">
                            <circle class="text-slate-100 dark:text-slate-700/50 stroke-current" stroke-width="10"
                                fill="transparent" r="40" cx="50" cy="50"></circle>
                            <circle class="text-emerald-500 stroke-current transition-all duration-1000 ease-out"
                                stroke-width="10" stroke-dasharray="251.2"
                                :stroke-dashoffset="251.2 - (251.2 * Math.min(overallFillRate, 100)) / 100"
                                stroke-linecap="round" fill="transparent" r="40" cx="50" cy="50"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter"
                                x-text="overallFillRate + '%'"></span>
                            <span class="text-sm font-black text-slate-400 uppercase tracking-[0.2em] mt-1"
                                x-text="totalStock + ' / ' + totalCapacity"></span>
                        </div>
                    </div>
                    <div class="mt-8 flex flex-col items-center gap-5">
                        <span class="text-base font-black text-slate-500 uppercase tracking-[0.3em]">{{ __('Total Stock Volume') }}</span>
                        <a @click.prevent="
                                if (hasReplenishPermission) {
                                    window.location.href = '{{ route('admin.warehouses.replenishments') }}?auto_machine_id=' + selectedMachine?.id;
                                } else {
                                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('You do not have permission to access replenishment orders') }}', type: 'error' } }));
                                }
                            "
                            href="#"
                            class="px-6 py-3 rounded-2xl text-sm font-black bg-cyan-500 text-white shadow-lg shadow-cyan-500/25 hover:shadow-cyan-500/40 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center gap-2 uppercase tracking-widest group cursor-pointer">
                            <svg class="w-5 h-5 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                            {{ __('Generate Replenishment Order') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right: Stats Cards -->
            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Machine Info Card -->
                <div
                    class="sm:col-span-2 luxury-card rounded-[2rem] p-6 border-slate-200/50 dark:border-slate-800/50 flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        <div
                            class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 overflow-hidden">
                            <template x-if="selectedMachine?.image_urls && selectedMachine?.image_urls[0]">
                                <img :src="selectedMachine.image_urls[0]" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!selectedMachine?.image_urls || !selectedMachine?.image_urls[0]">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </template>
                        </div>
                        <div>
                            <h2 x-text="selectedMachine?.name"
                                class="text-2xl font-black text-slate-800 dark:text-white tracking-tight leading-tight">
                            </h2>
                            <span x-text="selectedMachine?.serial_no"
                                class="text-sm font-mono font-bold text-slate-400 uppercase tracking-widest mt-1 block"></span>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-4">
                        <div class="text-right">
                            <span class="text-sm font-black text-slate-400 uppercase tracking-widest block mb-1">{{
                                __('Location') }}</span>
                            <span x-text="selectedMachine?.location || '{{ __('No Location') }}'"
                                class="text-base font-bold text-slate-600 dark:text-slate-300"></span>
                        </div>

                    </div>
                </div>

                <!-- Active Slots -->
                <div
                    class="luxury-card rounded-[2rem] p-6 border-emerald-500/10 bg-emerald-500/[0.02] hover:translate-y-0 transition-transform duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400"
                            x-text="activeSlotsCount"></span>
                    </div>
                    <span class="text-sm font-black text-slate-500 uppercase tracking-widest">{{ __('Active Slots')
                        }}</span>
                </div>

                <!-- Empty Slots -->
                <div
                    class="luxury-card rounded-[2rem] p-6 border-slate-500/10 bg-slate-500/[0.02] hover:translate-y-0 transition-transform duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-slate-500/10 flex items-center justify-center text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <span class="text-2xl font-black text-slate-600 dark:text-slate-400"
                            x-text="emptySlotsCount"></span>
                    </div>
                    <span class="text-sm font-black text-slate-500 uppercase tracking-widest">{{ __('Empty Slots')
                        }}</span>
                </div>

                <!-- High Stock -->
                <div
                    class="luxury-card rounded-[2rem] p-6 border-cyan-500/10 bg-cyan-500/[0.02] sm:col-span-2 hover:translate-y-0 transition-transform duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <span class="text-sm font-black text-slate-500 uppercase tracking-widest">{{ __('Stock Level > 50%') }}</span>
                        </div>
                        <span class="text-2xl font-black text-cyan-600 dark:text-cyan-400"
                            x-text="highStockSlotsCount"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cabinet Control & Display Toggle -->
        <div class="flex items-center justify-between px-4">
            <div class="flex items-center gap-6">
                <button @click="displayMode = 'table'"
                    :class="displayMode === 'table' ? 'text-cyan-500 bg-cyan-500/10' : 'text-slate-400 hover:text-slate-600'"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl transition-all font-black text-sm uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    {{ __('Table View') }}
                </button>
                <button @click="displayMode = 'grid'"
                    :class="displayMode === 'grid' ? 'text-cyan-500 bg-cyan-500/10' : 'text-slate-400 hover:text-slate-600'"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl transition-all font-black text-sm uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    {{ __('Grid View') }}
                </button>
                <div class="relative" x-data="{ exportOpen: false }" x-show="slots.length > 0" x-cloak>
                    <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl transition-all font-black text-sm uppercase tracking-widest text-emerald-500 hover:bg-emerald-500/10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        {{ __('Export') }}
                        <svg class="w-3 h-3 transition-transform duration-200" :class="exportOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div x-show="exportOpen" x-transition
                         class="absolute left-0 top-full mt-2 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-xl rounded-2xl p-2 z-30 min-w-[160px]"
                         x-cloak>
                        <a :href="`/admin/warehouses/machine-inventory/${selectedMachine.id}/export?export=csv`"
                           download @click="exportOpen = false"
                           class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                            📄 {{ __('Export to CSV') }}
                        </a>
                        <a :href="`/admin/warehouses/machine-inventory/${selectedMachine.id}/export?export=excel`"
                           download @click="exportOpen = false"
                           class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                            📊 {{ __('Export to Excel') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <span class="text-sm font-black text-slate-400 uppercase tracking-widest">{{ __('Expired') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <span class="text-sm font-black text-slate-400 uppercase tracking-widest">{{ __('Low Stock')
                        }}</span>
                </div>
            </div>
        </div>

        <!-- Grid View -->
        <div x-show="displayMode === 'grid'" class="space-y-6">
            <div
                class="luxury-card rounded-[2.5rem] p-8 border-slate-200/50 dark:border-slate-800/50 bg-white/30 dark:bg-slate-900/40 backdrop-blur-xl relative overflow-hidden min-h-[400px] hover:translate-y-0 transition-transform duration-300">
                <div x-show="loading"
                    class="absolute inset-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-md z-20 flex items-center justify-center transition-all duration-500">
                    <div class="w-12 h-12 border-4 border-cyan-500/20 border-t-cyan-500 rounded-full animate-spin">
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6 relative z-10"
                    x-show="!loading">
                    <template x-for="slot in slots" :key="slot.id">
                        <div :class="getSlotColorClass(slot)"
                            class="min-h-[260px] rounded-[2.5rem] p-5 flex flex-col items-center justify-center border-2 transition-all duration-500 group relative hover:scale-105 hover:-translate-y-1 hover:shadow-xl">

                            <div
                                class="absolute top-4 left-5 px-3 py-1 rounded-xl bg-slate-900/5 dark:bg-white/10 backdrop-blur-md">
                                <span class="text-sm font-black tracking-tighter text-slate-800 dark:text-white"
                                    x-text="slot.slot_no"></span>
                            </div>
                            <template x-if="slot.is_locked">
                                <div class="absolute top-4 right-5 flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-rose-500 text-white text-[9px] font-black uppercase tracking-widest shadow-lg shadow-rose-500/30 whitespace-nowrap select-none">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                    {{ __('Sale Paused') }}
                                </div>
                            </template>

                            <div class="relative w-16 h-16 mb-4 mt-2">
                                <div
                                    class="absolute inset-0 rounded-2xl bg-white/20 dark:bg-slate-900/40 backdrop-blur-xl border border-white/30 overflow-hidden shadow-inner">
                                    <template x-if="slot.product && slot.product.image_url">
                                        <img :src="slot.product.image_url" class="w-full h-full object-cover">
                                    </template>
                                </div>
                            </div>

                            <div class="text-center w-full">
                                <div class="text-base font-black truncate w-full opacity-90 tracking-tight"
                                    x-text="slot.product?.name || '{{ __('Empty') }}'"></div>
                                <template x-if="slot.product?.id">
                                    <div class="text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">{{ __('ID') }}: <span x-text="slot.product.id"></span></div>
                                </template>
                                <div class="flex items-baseline justify-center gap-1 mt-2">
                                    <span class="text-2xl font-black tracking-tighter" x-text="slot.stock"></span>
                                    <span class="text-sm font-black opacity-30">/</span>
                                    <span class="text-sm font-bold opacity-50" x-text="slot.max_stock"></span>
                                </div>
                                <div class="text-sm font-black tracking-widest opacity-40 uppercase mt-2"
                                    x-text="slot.expiry_date || '----/--/--'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div x-show="displayMode === 'table'" class="animate-luxury-in" x-cloak>
            <div
                class="hidden xl:block luxury-card rounded-[2.5rem] overflow-hidden border-slate-200/50 dark:border-slate-800/50 shadow-xl hover:translate-y-0 transition-transform duration-300">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/50">
                            <th class="px-8 py-5 text-sm font-black text-slate-400 uppercase tracking-[0.2em]">{{
                                __('Slot') }}</th>
                            <th class="px-8 py-5 text-sm font-black text-slate-400 uppercase tracking-[0.2em]">{{
                                __('Product') }}</th>
                            <th class="px-8 py-5 text-sm font-black text-slate-400 uppercase tracking-[0.2em]">{{
                                __('Barcode') }}</th>
                            <th
                                class="px-8 py-5 text-sm font-black text-slate-400 uppercase tracking-[0.2em] text-center">
                                {{ __('Stock Level') }} / {{ __('Max Stock') }}</th>
                            <th
                                class="px-8 py-5 text-sm font-black text-slate-400 uppercase tracking-[0.2em] text-right">
                                {{ __('Sale Price') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/40">
                        <template x-for="slot in slots" :key="slot.id">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-8 py-6">
                                    <span
                                        class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-sm font-black text-slate-600 dark:text-slate-300"
                                        x-text="slot.slot_no"></span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 flex items-center justify-center overflow-hidden">
                                            <template x-if="slot.product?.image_url">
                                                <img :src="slot.product.image_url" class="w-full h-full object-cover">
                                            </template>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-base font-black text-slate-800 dark:text-slate-200"
                                                    x-text="slot.product?.name || '{{ __('Empty Slot') }}'"></span>
                                                <template x-if="slot.is_locked">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-500 text-[10px] font-black uppercase tracking-wider whitespace-nowrap">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                        {{ __('Locked (Sale Paused)') }}
                                                    </span>
                                                </template>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 mt-1">
                                                <template x-if="slot.product?.id">
                                                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 font-mono tracking-widest uppercase">{{ __('ID') }}: <span x-text="slot.product.id"></span></span>
                                                </template>
                                                <template x-if="slot.product?.id && slot.expiry_date">
                                                    <span class="text-xs text-slate-300 dark:text-slate-700">|</span>
                                                </template>
                                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest"
                                                    x-text="slot.expiry_date ? '{{ __('Expiry') }}: ' + slot.expiry_date : ''">
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-sm font-mono font-bold text-slate-400"
                                        x-text="slot.product?.barcode || '--'"></span>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="text-lg font-black"
                                            :class="(slot.max_stock > 0 && slot.stock <= (slot.max_stock * 0.2)) ? 'text-rose-500' : 'text-slate-700 dark:text-slate-200'"
                                            x-text="slot.stock"></span>
                                        <span class="text-xs font-bold text-slate-300">/</span>
                                        <span class="text-sm font-bold text-slate-400" x-text="slot.max_stock"></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <span class="text-base font-black text-emerald-500"
                                        x-text="'$' + Math.floor(slot.product?.price || 0)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View for Slots --}}
            <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
                <template x-for="slot in slots" :key="slot.id">
                    <div :class="getSlotCardClass(slot)">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                                    <template x-if="slot.product?.image_url">
                                        <img :src="slot.product.image_url" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!slot.product?.image_url">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-500 dark:text-slate-400 tracking-tighter" x-text="slot.slot_no"></span>
                                        <h3 class="text-base font-black text-slate-800 dark:text-slate-100 truncate group-hover:text-cyan-600 transition-colors tracking-tight" x-text="slot.product?.name || '{{ __('Empty Slot') }}'"></h3>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-2 mt-1">
                                        <template x-if="slot.product?.id">
                                            <span class="text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('ID') }}: <span x-text="slot.product.id"></span></span>
                                        </template>
                                        <span class="text-[10px] text-slate-300 dark:text-slate-700">|</span>
                                        <span class="text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate" x-text="slot.product?.barcode || '--'"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-base font-black text-emerald-500" x-text="'$' + Math.floor(slot.product?.price || 0)"></p>
                                <template x-if="slot.is_locked">
                                    <span class="inline-flex items-center gap-1 mt-1 text-[10px] font-black px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-500 uppercase tracking-wider whitespace-nowrap">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                        {{ __('Sale Paused') }}
                                    </span>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-2 border-t border-slate-100 dark:border-slate-800/50 pt-4">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Stock Level') }}</p>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-lg font-black" :class="(slot.max_stock > 0 && slot.stock <= (slot.max_stock * 0.2)) ? 'text-rose-500' : 'text-slate-700 dark:text-slate-200'" x-text="slot.stock"></span>
                                    <span class="text-[10px] font-bold text-slate-300">/</span>
                                    <span class="text-xs font-bold text-slate-400" x-text="slot.max_stock"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Expiry') }}</p>
                                <p class="text-xs font-bold text-slate-500" x-text="slot.expiry_date || '--'"></p>
                            </div>
                        </div>

                        {{-- Stock Progress Bar in card --}}
                        <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200/50 dark:border-slate-700/50 shadow-inner">
                            <div :class="getSlotProgressClass(slot)"
                                class="h-full transition-all duration-1000 ease-out rounded-full"
                                :style="'width: ' + Math.min(100, Math.round((slot.stock / (slot.max_stock || 10)) * 100)) + '%'">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Movements Slide-out Panel --}}
    <div x-show="movementsOpen" style="display: none;" class="relative z-[80]" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div x-show="movementsOpen" x-transition.opacity.duration.300ms
             class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                    {{-- Panel --}}
                    <div x-show="movementsOpen" 
                         @click.outside="if (!$event.target.closest('.flatpickr-calendar')) movementsOpen = false"
                         x-transition:enter="transform transition ease-in-out duration-400 sm:duration-500"
                         x-transition:enter-start="translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in-out duration-400 sm:duration-500"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="translate-x-full"
                         class="pointer-events-auto w-screen max-w-4xl">
                        
                        <div class="flex h-full flex-col bg-white dark:bg-slate-900 shadow-2xl overflow-hidden border-l border-slate-200 dark:border-white/10">
                            {{-- Header --}}
                            <div class="px-5 py-6 sm:px-8 border-b border-slate-200 dark:border-white/10 bg-slate-50/50 dark:bg-slate-900/20">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white font-display flex items-center gap-2 sm:gap-3">
                                            <div class="p-2 rounded-xl bg-cyan-500/10 dark:bg-cyan-500/20">
                                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-cyan-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242" />
                                                    <path d="M12 12v9" />
                                                    <path d="m8 17 4 4 4-4" />
                                                </svg>
                                            </div>
                                            <span class="truncate">{{ __('Machine Stock Movements') }}</span>
                                        </h2>
                                        <div class="mt-2 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 text-[12px] sm:text-sm text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest overflow-hidden">
                                            <span x-text="movementsMachine?.serial_no" class="font-mono text-cyan-600 dark:text-cyan-400 truncate"></span>
                                            <span class="hidden sm:inline opacity-50">—</span>
                                            <span x-text="movementsMachine?.name" class="truncate"></span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 h-7 flex items-center">
                                        <button type="button" @click="movementsOpen = false"
                                            class="bg-white dark:bg-slate-800 rounded-full p-2 text-slate-400 hover:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none transition duration-300 shadow-sm border border-slate-200 dark:border-slate-700 active:scale-90">
                                            <span class="sr-only">{{ __('Close Panel') }}</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Compact Filters --}}
                                <div class="mt-6 flex flex-col sm:flex-row sm:items-end gap-4 sm:gap-6">
                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        {{-- Type --}}
                                        <div class="space-y-1.5 group">
                                            <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Type') }}</label>
                                            <x-searchable-select 
                                                id="movements-filter-type"
                                                name="type" 
                                                x-model="movementsFilter.type" 
                                                @change="loadMovements(1)"
                                                :placeholder="__('All Types')"
                                                :hasSearch="false"
                                                class="luxury-select-sm"
                                            >
                                                <option value="replenishment">{{ __('movement.type.replenishment') }}</option>
                                                <option value="pickup">{{ __('movement.type.pickup') }}</option>
                                                <option value="remote_dispense">{{ __('movement.type.remote_dispense') }}</option>
                                                <option value="adjustment">{{ __('movement.type.adjustment') }}</option>
                                                <option value="rollback">{{ __('movement.type.rollback') }}</option>
                                            </x-searchable-select>
                                        </div>
                                        
                                        {{-- Slot --}}
                                        <div class="space-y-1.5 group">
                                            <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Slot') }}</label>
                                            <div id="movements-slot-select-wrapper" class="luxury-select-sm">
                                                {{-- Dynamic Slot Select --}}
                                            </div>
                                        </div>

                                        {{-- Start Time --}}
                                        <div class="space-y-1.5 group">
                                            <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('Start Time') }}</label>
                                            <div class="relative">
                                                <input type="text" x-ref="movementsFromPicker" :value="movementsFilter.date_from" x-init="flatpickr($refs.movementsFromPicker, { 
                                                        enableTime: true, 
                                                        time_24hr: true,
                                                        dateFormat: 'Y/m/d H:i', 
                                                        disableMobile: true,
                                                        allowInput: true,
                                                        defaultDate: movementsFilter.date_from,
                                                        onReady: (selectedDates, dateStr, instance) => {
                                                            if (movementsFilter.date_from) instance.setDate(movementsFilter.date_from, false);
                                                        },
                                                        onOpen: (selectedDates, dateStr, instance) => {
                                                            if (movementsFilter.date_from) instance.setDate(movementsFilter.date_from, false);
                                                        },
                                                        onClose: (selectedDates, dateStr) => { movementsFilter.date_from = dateStr; loadMovements(1); }
                                                    })"
                                                    class="luxury-input py-2 pl-9 w-full text-sm font-bold cursor-pointer bg-white dark:bg-slate-900/50">
                                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                </span>
                                            </div>
                                        </div>

                                        {{-- End Time --}}
                                        <div class="space-y-1.5 group">
                                            <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">{{ __('End Time') }}</label>
                                            <div class="relative">
                                                <input type="text" x-ref="movementsToPicker" :value="movementsFilter.date_to" x-init="flatpickr($refs.movementsToPicker, { 
                                                        enableTime: true, 
                                                        time_24hr: true,
                                                        dateFormat: 'Y/m/d H:i', 
                                                        disableMobile: true,
                                                        allowInput: true,
                                                        defaultDate: movementsFilter.date_to,
                                                        onReady: (selectedDates, dateStr, instance) => {
                                                            if (movementsFilter.date_to) instance.setDate(movementsFilter.date_to, false);
                                                        },
                                                        onOpen: (selectedDates, dateStr, instance) => {
                                                            if (movementsFilter.date_to) instance.setDate(movementsFilter.date_to, false);
                                                        },
                                                        onClose: (selectedDates, dateStr) => { movementsFilter.date_to = dateStr; loadMovements(1); }
                                                    })"
                                                    class="luxury-input py-2 pl-9 w-full text-sm font-bold cursor-pointer bg-white dark:bg-slate-900/50">
                                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex-shrink-0 flex items-center">
                                        <button @click="movementsFilter.date_from = ''; movementsFilter.date_to = ''; movementsFilter.type = ''; movementsFilter.slot_no = ''; rebuildSlotSelect(); loadMovements(1)"
                                            class="text-[12px] font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-widest hover:text-cyan-500 transition-colors flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                            {{ __('Clear Filter') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="relative flex-1 overflow-y-auto px-6 py-6 bg-white dark:bg-slate-900/50">
                                {{-- Loading State --}}
                                <div x-show="movementsLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm">
                                    <div class="animate-spin inline-block w-8 h-8 border-[3px] border-current border-t-transparent text-cyan-600 rounded-full" role="status" aria-label="loading">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>

                                {{-- Movements List --}}
                                <template x-if="!movementsLoading && movements.length === 0">
                                    <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
                                        <div class="w-16 h-16 mb-4 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('No movements found') }}</h3>
                                    </div>
                                </template>

                                <template x-if="movements.length > 0">
                                    <div class="luxury-card border border-slate-200/50 dark:border-slate-800/50 rounded-2xl overflow-hidden shadow-xl bg-transparent">
                                        {{-- Desktop Table --}}
                                        <div class="hidden lg:block overflow-x-auto">
                                            <table class="w-full text-left border-separate border-spacing-y-0 text-sm whitespace-nowrap">
                                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                                    <tr>
                                                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-200 dark:border-slate-700 w-40">{{ __('Timestamp') }}</th>
                                                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-200 dark:border-slate-700 w-24 text-center">{{ __('Type') }}</th>
                                                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-200 dark:border-slate-700">{{ __('Message') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                                                    <template x-for="movement in movements" :key="movement.id">
                                                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all border-b border-slate-100 dark:border-slate-800/50 last:border-0">
                                                            <td class="px-6 py-4">
                                                                <div class="flex flex-col">
                                                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest"
                                                                        x-text="new Date(movement.created_at).toLocaleString('zh-TW', {year:'numeric', month:'2-digit', day:'2-digit'}).replace(/\//g, '-')"></span>
                                                                    <span class="text-[14px] font-bold text-slate-600 dark:text-slate-200 mt-0.5"
                                                                        x-text="new Date(movement.created_at).toLocaleString('zh-TW', {hour:'2-digit', minute:'2-digit', second:'2-digit', hour12: false})"></span>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-4 text-center">
                                                                <span :class="movementTypeBadge(movement.type)"
                                                                      class="inline-flex items-center px-2 py-0.5 rounded-md border border-current/10 text-[11px] font-black uppercase tracking-wider">
                                                                    <span x-text="movementTypeName(movement.type)"></span>
                                                                </span>
                                                            </td>
                                                            <td class="px-6 py-4">
                                                                <div class="flex flex-col gap-0.5">
                                                                    <div class="flex items-center gap-2 text-[13px] font-medium text-slate-700 dark:text-slate-200">
                                                                        <span class="font-black text-slate-400 dark:text-slate-500 tracking-tighter" x-text="'[' + '{{ __('Slot') }}' + ' ' + movement.slot_no + ']'"></span>
                                                                        <span x-text="movement.product?.name || '{{ __('Unknown Product') }}'"></span>
                                                                        <span class="mx-0.5 opacity-50">:</span>
                                                                        <span class="font-bold" x-text="movement.before_qty"></span>
                                                                        <svg class="w-3 h-3 text-slate-300 dark:text-slate-600 mx-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                                                        <span class="font-bold" x-text="movement.after_qty"></span>
                                                                        <span class="ml-2 font-black" 
                                                                              :class="movement.quantity > 0 ? 'text-emerald-500' : 'text-rose-500'" 
                                                                              x-text="(movement.quantity > 0 ? '+' : '') + movement.quantity"></span>
                                                                        
                                                                        <template x-if="movement.creator">
                                                                            <span class="ml-3 pl-3 border-l border-slate-100 dark:border-slate-800 text-[11px] font-bold text-slate-400 flex items-center gap-1.5">
                                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                                                <span x-text="movement.creator.name"></span>
                                                                            </span>
                                                                        </template>
                                                                    </div>
                                                                    <template x-if="movement.note">
                                                                        <p class="text-[11px] text-slate-400 flex items-center gap-1 font-medium italic">
                                                                            <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                                            <span x-text="movement.translated_note || movement.note"></span>
                                                                        </p>
                                                                    </template>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>

                                        {{-- Mobile List view --}}
                                        <div class="lg:hidden divide-y divide-slate-100 dark:divide-slate-800">
                                            <template x-for="movement in movements" :key="movement.id">
                                                <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <div class="flex flex-col">
                                                            <span class="text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest"
                                                                  x-text="new Date(movement.created_at).toLocaleString('zh-TW', {year:'numeric', month:'2-digit', day:'2-digit'}).replace(/\//g, '-')"></span>
                                                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mt-0.5"
                                                                  x-text="new Date(movement.created_at).toLocaleString('zh-TW', {hour:'2-digit', minute:'2-digit', hour12: false})"></span>
                                                        </div>
                                                        <span :class="movementTypeBadge(movement.type)"
                                                              class="inline-flex items-center px-1.5 py-0.5 rounded-md border border-current/10 text-[9px] font-black uppercase tracking-tight">
                                                            <span x-text="movementTypeName(movement.type)"></span>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <div class="flex items-center gap-2 text-[13px] font-medium text-slate-700 dark:text-slate-200">
                                                            <span class="font-black text-slate-400 dark:text-slate-500 tracking-tighter" x-text="'[' + '{{ __('Slot') }}' + ' ' + movement.slot_no + ']'"></span>
                                                            <span x-text="movement.product?.name || '{{ __('Unknown Product') }}'" class="truncate"></span>
                                                        </div>
                                                        <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                                                            <span class="font-bold" x-text="movement.before_qty"></span>
                                                            <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                                            <span class="font-bold" x-text="movement.after_qty"></span>
                                                            <span class="ml-1 font-black" 
                                                                  :class="movement.quantity > 0 ? 'text-emerald-500' : 'text-rose-500'" 
                                                                  x-text="(movement.quantity > 0 ? '+' : '') + movement.quantity"></span>
                                                        </div>
                                                    </div>
                                                    <template x-if="movement.creator || movement.note">
                                                        <div class="mt-3 flex items-center justify-between text-[11px] text-slate-400 font-medium">
                                                            <span x-text="movement.translated_note || movement.note" class="truncate w-full pr-2 text-slate-500"></span>
                                                            <span x-text="movement.creator?.name" class="flex items-center gap-1 whitespace-nowrap">
                                                                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                            </span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Pagination Footer --}}
                            <div x-show="movementsMeta.total > 0"
                                 class="mt-auto border-t border-slate-100 dark:border-slate-800/80 px-6 py-4 flex items-center justify-between bg-slate-50/30 dark:bg-slate-900/30 backdrop-blur-sm rounded-b-3xl">
                                <div class="flex items-center gap-4">
                                    <button @click="loadMovements(movementsMeta.current_page - 1)"
                                            :disabled="movementsMeta.current_page === 1"
                                            class="h-9 px-4 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all shadow-sm border border-slate-200 dark:border-slate-700 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-white dark:hover:bg-slate-800 flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                                        {{ __('Previous') }}
                                    </button>
                                    
                                    <div class="relative group">
                                        <select @change="loadMovements($event.target.value)"
                                                class="h-9 pl-4 pr-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-[11px] font-black text-slate-600 dark:text-slate-300 appearance-none focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 outline-none transition-all cursor-pointer shadow-sm hover:border-slate-300 dark:hover:border-slate-600 !bg-none">
                                            <template x-for="p in Array.from({length: movementsMeta.last_page}, (_, i) => i + 1)">
                                                <option :value="p" :selected="p === movementsMeta.current_page" x-text="p + ' / ' + movementsMeta.last_page"></option>
                                            </template>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-cyan-500/50 group-hover:text-cyan-500 transition-colors">
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>

                                    <button @click="loadMovements(movementsMeta.current_page + 1)"
                                            :disabled="movementsMeta.current_page === movementsMeta.last_page"
                                            class="h-9 px-4 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all shadow-sm border border-slate-200 dark:border-slate-700 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-white dark:hover:bg-slate-800 flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                        {{ __('Next') }}
                                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                                
                                <div class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.1em]">
                                    <span class="text-slate-600 dark:text-slate-300" x-text="movementsMeta.total"></span> {{ __('items') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('machineInventory', () => ({
        viewMode: "{{ request('machine_id') ? 'detail' : 'list' }}",
        displayMode: 'table',
        selectedMachine: null,
        slots: [],
        loading: false,
        hasReplenishPermission: {{ auth()->user()->can('menu.warehouses.replenishments') ? 'true' : 'false' }},

        // 機台庫存批次匯出
        exportModalOpen: false,
        exportSelectedIds: [],
        get exportAllChecked() {
            const total = {{ $exportMachines->count() }};
            return total > 0 && this.exportSelectedIds.length === total;
        },
        openExportModal() {
            this.exportSelectedIds = @js($exportMachines->pluck('id'));
            this.exportModalOpen = true;
        },
        toggleExportAll(e) {
            this.exportSelectedIds = e.target.checked ? @js($exportMachines->pluck('id')) : [];
        },
        doExport(format) {
            if (this.exportSelectedIds.length === 0) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Please select at least one machine') }}', type: 'error' } }));
                return;
            }
            const url = `/admin/warehouses/machine-inventory/export?export=${format}&ids=${this.exportSelectedIds.join(',')}`;
            const a = document.createElement('a');
            a.href = url;
            a.setAttribute('download', '');
            document.body.appendChild(a);
            a.click();
            a.remove();
            this.exportModalOpen = false;
        },

        async init() {
            const urlParams = new URLSearchParams(window.location.search);
            const machineId = urlParams.get('machine_id');
            if (machineId) {
                await this.viewSlots({id: machineId});
            }
        },

        async viewSlots(machine) {
            this.selectedMachine = machine;
            this.viewMode = 'detail';
            this.loading = true;
            this.slots = [];
            
            const url = new URL(window.location);
            url.searchParams.set('machine_id', machine.id);
            window.history.pushState({}, '', url);

            try {
                const res = await fetch(`/admin/warehouses/machine-inventory/${machine.id}/slots`);
                const data = await res.json();
                if (data.success) {
                    this.slots = data.slots || [];
                    this.selectedMachine = data.machine;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            } catch(e) { 
                console.error('Fetch error:', e);
                this.slots = []; 
            } finally {
                this.loading = false;
            }
        },

        backToList() {
            this.viewMode = 'list';
            this.selectedMachine = null;
            this.slots = [];
            const url = new URL(window.location);
            url.searchParams.delete('machine_id');
            window.history.pushState({}, '', url);
        },

        get activeSlotsCount() { return this.slots.filter(s => s.product_id != null).length; },
        get emptySlotsCount() { return this.slots.filter(s => s.product_id == null).length; },
        get highStockSlotsCount() { 
            return this.slots.filter(s => s.product_id != null && (s.stock / (s.max_stock || 1)) >= 0.5).length; 
        },
        get totalStock() { return this.slots.reduce((acc, s) => acc + (parseInt(s.stock) || 0), 0); },
        get totalCapacity() { return this.slots.reduce((acc, s) => acc + (parseInt(s.max_stock) || 10), 0); },
        get overallFillRate() { 
            if (this.totalCapacity === 0) return 0;
            return Math.round((this.totalStock / this.totalCapacity) * 100);
        },

        getSlotColorClass(slot) {
            const today = new Date().toISOString().split('T')[0];
            if (slot.expiry_date && slot.expiry_date < today) return 'bg-rose-50/60 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/30';
            if (!slot.product_id || parseInt(slot.stock) === 0) return 'bg-slate-50/50 dark:bg-slate-800/50 text-slate-400 border-slate-200/60 dark:border-slate-700/50';
            if (slot.max_stock > 0 && parseInt(slot.stock) <= (parseInt(slot.max_stock) * 0.2)) return 'bg-amber-50/60 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-500/30';
            const maxStock = parseInt(slot.max_stock) || 10;
            if (parseInt(slot.stock) > maxStock / 2) return 'bg-cyan-50/60 dark:bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-200 dark:border-cyan-500/30';
            return 'bg-emerald-50/60 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30';
        },

        getSlotCardClass(slot) {
            const base = 'luxury-card p-6 rounded-[2rem] backdrop-blur-xl group hover:shadow-2xl transition-all duration-500 relative ';
            const today = new Date().toISOString().split('T')[0];
            if (slot.expiry_date && slot.expiry_date < today) return base + 'bg-rose-500/[0.06] border-rose-500/20 dark:bg-rose-500/10 dark:border-rose-500/30 hover:shadow-rose-500/10';
            if (!slot.product_id || parseInt(slot.stock) === 0) return base + 'bg-slate-500/[0.06] border-slate-500/20 dark:bg-slate-500/10 dark:border-slate-500/30 hover:shadow-slate-500/10';
            if (slot.max_stock > 0 && parseInt(slot.stock) <= (parseInt(slot.max_stock) * 0.2)) return base + 'bg-amber-500/[0.06] border-amber-500/20 dark:bg-amber-500/10 dark:border-amber-500/30 hover:shadow-amber-500/10';
            const maxStock = parseInt(slot.max_stock) || 10;
            if (parseInt(slot.stock) > maxStock / 2) return base + 'bg-cyan-500/[0.06] border-cyan-500/20 dark:bg-cyan-500/10 dark:border-cyan-500/30 hover:shadow-cyan-500/10';
            return base + 'bg-emerald-500/[0.04] border-emerald-500/10 dark:bg-emerald-500/[0.08] dark:border-emerald-500/25 hover:shadow-emerald-500/10';
        },

        getSlotProgressClass(slot) {
            const today = new Date().toISOString().split('T')[0];
            if (slot.expiry_date && slot.expiry_date < today) return 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.4)]';
            if (!slot.product_id || parseInt(slot.stock) === 0) return 'bg-slate-400';
            if (slot.max_stock > 0 && parseInt(slot.stock) <= (parseInt(slot.max_stock) * 0.2)) return 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.4)]';
            const maxStock = parseInt(slot.max_stock) || 10;
            if (parseInt(slot.stock) > maxStock / 2) return 'bg-cyan-500 shadow-[0_0_8px_rgba(6,182,212,0.4)]';
            return 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]';
        },

        // ── Movements Panel ──
        movementsOpen: false,
        movementsMachine: null,
        movements: [],
        movementsLoading: false,
        movementsMeta: { current_page: 1, last_page: 1, total: 0 },
        movementsFilter: { type: '', slot_no: '', date_from: '', date_to: '' },
        movementsPage: 1,
        movementsPerPage: 20,
        movementsSlots: [],
        movementTypeTranslations: {
            replenishment: '{{ __("movement.type.replenishment") }}',
            pickup: '{{ __("movement.type.pickup") }}',
            remote_dispense: '{{ __("movement.type.remote_dispense") }}',
            adjustment: '{{ __("movement.type.adjustment") }}',
            rollback: '{{ __("movement.type.rollback") }}'
        },
        movementTypeName(type) {
            return this.movementTypeTranslations[type] || type.replace('_', ' ').toUpperCase();
        },

        openMovements(machine) {
            this.movementsMachine = machine;
            this.movementsOpen = true;
            this.movements = [];
            this.movementsPage = 1;
            
            const now = new Date();
            const y = now.getFullYear();
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const d = String(now.getDate()).padStart(2, '0');
            const todayStr = `${y}/${m}/${d}`;
            
            this.movementsFilter = { 
                type: '', 
                slot_no: '', 
                date_from: todayStr + ' 00:00', 
                date_to: todayStr + ' 23:59' 
            };

            this.updateSlotSelect();
            this.loadMovements();
        },

        updateSlotSelect() {
            if (!this.movementsMachine) return;
            const machineId = this.movementsMachine.id;
            
            // 每次開啟都重新抓取，以確保取得包含已移除貨道的完整清單
            fetch(`/admin/warehouses/machine-inventory/${machineId}/slots`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // 優先使用歷史貨道清單 (由 Controller 新增回傳)
                        let slotNos = data.historical_slot_nos || (data.slots || []).map(s => s.slot_no);
                        
                        // 數字排序確保 UI 體驗一致
                        this.movementsSlots = [...new Set(slotNos)].sort((a, b) => {
                            return parseInt(a) - parseInt(b);
                        });
                        
                        this.rebuildSlotSelect();
                    }
                });
        },

        rebuildSlotSelect() {
            const wrapper = document.getElementById('movements-slot-select-wrapper');
            if (!wrapper) return;
            wrapper.innerHTML = '';
            
            const select = document.createElement('select');
            const uniqueId = 'movements-slot-select-' + Date.now();
            select.id = uniqueId;
            select.className = 'hidden';
            
            const config = {
                hasSearch: true,
                searchPlaceholder: '{{ __("Search...") }}',
                isHidePlaceholder: false,
                searchClasses: "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
                searchWrapperClasses: "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
                toggleClasses: "hs-select-toggle luxury-select-toggle",
                toggleTemplate: '<button type="button" aria-expanded="false"><span class="me-2" data-icon></span><span class="text-slate-800 dark:text-slate-200" data-title></span><div class="ms-auto"><svg class="size-4 text-slate-400 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg></div></button>',
                dropdownClasses: "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[150] animate-luxury-in",
                optionClasses: "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
                optionTemplate: '<div class="flex items-center justify-between w-full"><span data-title></span><span class="hs-select-active-indicator hidden text-cyan-500"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></div>'
            };
            
            select.setAttribute('data-hs-select', JSON.stringify(config));
            
            const allOpt = document.createElement('option');
            allOpt.value = 'all';
            allOpt.textContent = '{{ __("All Slots") }}';
            allOpt.setAttribute('data-title', '{{ __("All Slots") }}');
            if (!this.movementsFilter.slot_no) allOpt.selected = true;
            select.appendChild(allOpt);
            
            this.movementsSlots.forEach(slotNo => {
                const opt = document.createElement('option');
                opt.value = slotNo;
                opt.textContent = slotNo;
                opt.setAttribute('data-title', slotNo);
                if (this.movementsFilter.slot_no == slotNo) opt.selected = true;
                select.appendChild(opt);
            });
            
            wrapper.appendChild(select);
            select.addEventListener('change', (e) => {
                const val = e.target.value;
                this.movementsFilter.slot_no = (val === 'all') ? '' : val;
                this.loadMovements(1);
            });
            
            this.$nextTick(() => {
                if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                    window.HSStaticMethods.autoInit(['select']);
                }
            });
        },

        async loadMovements(page = 1) {
            if (!this.movementsMachine) return;
            this.movementsLoading = true;
            this.movementsPage = page;
            const params = new URLSearchParams({ page, per_page: this.movementsPerPage, ...this.movementsFilter });
            Object.keys(this.movementsFilter).forEach(k => {
                if (!this.movementsFilter[k]) params.delete(k);
            });
            try {
                const res = await fetch(`/admin/warehouses/machine-inventory/${this.movementsMachine.id}/movements?${params}`);
                const data = await res.json();
                if (data.success) {
                    this.movements = data.movements || [];
                    this.movementsMeta = data.meta || { current_page: 1, last_page: 1, total: 0 };
                }
            } catch (e) {
                console.error('Movements fetch error:', e);
            } finally {
                this.movementsLoading = false;
            }
        },

        async fetchPage(url) {
            this.loading = true;
            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    this.$refs.listContent.innerHTML = data.html;
                    window.history.pushState({}, '', url);
                    
                    this.$nextTick(() => {
                        if (window.Alpine) Alpine.initTree(this.$refs.listContent);
                        if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                            window.HSStaticMethods.autoInit();
                        }
                    });
                }
            } catch (e) {
                console.error('Fetch page error:', e);
            } finally {
                this.loading = false;
            }
        },

        movementTypeBadge(type) {
            const colors = {
                replenishment:   'emerald',
                pickup:          'indigo',
                remote_dispense: 'cyan',
                adjustment:      'amber',
                rollback:        'rose',
                decommission:    'slate'
            };
            const color = colors[type] || 'slate';
            return `bg-${color}-500/10 text-${color}-600 dark:text-${color}-400 border-${color}-500/20`;
        }
    }));
});
</script>
@endsection
