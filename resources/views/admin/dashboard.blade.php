@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Top Section: Connectivity & Transactions -->
    <div class="grid lg:grid-cols-2 gap-6 items-stretch">
        <!-- Connectivity Card -->
        <div class="luxury-card rounded-2xl p-8 animate-luxury-in flex flex-col">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{
                        __('Connectivity Status') }}</h3>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1">{{ __('Real-time status monitoring') }}</p>
                </div>
                <div
                    class="flex items-center gap-x-1.5 px-3 py-1 rounded-full bg-cyan-500/10 text-cyan-500 border border-cyan-500/20">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
                    </span>
                    <span class="text-[10px] font-black uppercase tracking-wider">{{ __('LIVE') }}</span>
                </div>
            </div>

            <div class="flex-1 flex flex-col sm:flex-row items-center gap-y-8 sm:gap-y-0">
                <!-- Left: Stats List -->
                <div class="w-full sm:flex-1 space-y-6">
                    <div class="flex items-center justify-between sm:pr-10">
                        <div class="flex items-center gap-x-4">
                            <div class="w-2 h-2 rounded-full bg-cyan-500 shadow-[0_0_10px_rgba(6,182,212,0.6)]"></div>
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ __('Online Machines')
                                }}</span>
                        </div>
                        <span class="text-2xl font-black text-slate-900 dark:text-white">{{ $activeMachines }}</span>
                    </div>
                    <div class="flex items-center justify-between sm:pr-10">
                        <div class="flex items-center gap-x-4">
                            <div class="w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.6)]"></div>
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ __('Offline Machines')
                                }}</span>
                        </div>
                        <span class="text-2xl font-black text-rose-500">{{ $offlineMachines }}</span>
                    </div>
                    <div class="flex items-center justify-between sm:pr-10">
                        <div class="flex items-center gap-x-4">
                            <div class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.6)]"></div>
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ __('Alerts Pending')
                                }}</span>
                        </div>
                        <span class="text-2xl font-black text-slate-900 dark:text-white">{{ $alertsPending }}</span>
                    </div>
                </div>

                <!-- Divider (Hidden on Mobile) -->
                <div class="hidden sm:block w-px h-32 bg-slate-100 dark:bg-slate-800 mx-2"></div>

                <!-- Right: Big Total -->
                <div
                    class="w-full sm:w-40 text-center pt-6 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800/50">
                    <p
                        class="text-6xl sm:text-7xl font-black text-cyan-500 drop-shadow-[0_0_20px_rgba(6,182,212,0.3)] leading-none">
                        {{ $activeMachines }}</p>
                    <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.2em] mt-4">
                        {{ __('Total Connected') }}</p>
                </div>
            </div>
        </div>

        <!-- Transaction Card -->
        <div class="luxury-card rounded-2xl p-8 animate-luxury-in flex flex-col" style="animation-delay: 100ms">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{
                        __('Monthly Transactions') }}</h3>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1">{{ __('Monthly Cumulative Revenue') }}: <span class="text-cyan-500 font-black">${{ number_format($monthlyRevenue, 0) }}</span></p>
                </div>
                <div
                    class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 text-slate-400 dark:text-slate-500 border border-transparent dark:border-slate-700/50">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="flex-1 flex flex-col space-y-4 justify-center">
                <!-- Today Stat Card -->
                <div
                    class="group flex items-center justify-between p-5 rounded-2xl bg-white dark:bg-slate-900 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] dark:shadow-none border border-slate-100 dark:border-slate-800 transition-all hover:border-cyan-500/30">
                    <div class="flex items-center gap-x-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-cyan-500/10 dark:bg-cyan-500/20 flex items-center justify-center text-cyan-600 dark:text-cyan-400 shadow-sm transition-transform group-hover:scale-110">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 18L9 11.25l4.5 4.5L21.75 7.5M21.75 7.5V12m0-4.5H17.25" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __("Today Cumulative Sales") }}</p>
                            <p
                                class="text-4xl font-black text-slate-900 dark:text-white mt-1 tracking-tight drop-shadow-sm">
                                ${{ number_format($todayRevenue, 0) }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-y-1">
                        <span
                            class="text-[10px] font-black {{ $yesterdayTrend >= 0 ? 'text-emerald-500 bg-emerald-500/10' : 'text-rose-500 bg-rose-500/10' }} px-2.5 py-0.5 rounded-full">{{ $yesterdayTrend >= 0 ? '+' : '' }}{{ number_format($yesterdayTrend, 1) }}%</span>
                        <p class="text-[9px] font-bold text-slate-300 dark:text-slate-500 uppercase tracking-tighter">{{
                            __('vs Yesterday') }}</p>
                    </div>
                </div>

                <!-- Previous Days Stats Row -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Yesterday Card -->
                    <div
                        class="group flex flex-col p-5 rounded-2xl bg-slate-50/50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 transition-all hover:border-cyan-500/20">
                        <div class="flex justify-between items-start mb-2">
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __("Yesterday") }}</p>
                            <div
                                class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-xl font-black text-slate-800 dark:text-slate-200">${{ number_format($yesterdayRevenue, 0) }}</p>
                    </div>

                    <!-- Before Yesterday Card -->
                    <div
                        class="group flex flex-col p-5 rounded-2xl bg-slate-50/50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 transition-all hover:border-cyan-500/20">
                        <div class="flex justify-between items-start mb-2">
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __("Day Before") }}</p>
                            <div
                                class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-xl font-black text-slate-800 dark:text-slate-200">${{ number_format($dayBeforeRevenue, 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Status List -->
    <div class="luxury-card rounded-3xl p-8 border border-slate-100 dark:border-white/5 animate-luxury-in" style="animation-delay: 200ms">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-6">
            <div>
                <div class="flex items-center gap-x-3">
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{
                        __('Machine Status List') }}</h2>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-black bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 uppercase tracking-tighter">
                        {{ __('Total items', ['count' => $machines->total()]) }}
                    </span>
                </div>
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1">{{ __('Real-time monitoring across all machines') }}</p>
            </div>

            <form action="{{ route('admin.dashboard') }}" method="GET"
                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full md:w-auto">
                <div class="relative group w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="py-3 pl-12 pr-6 block w-full border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-2xl text-sm font-bold text-slate-700 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all outline-none"
                        placeholder="{{ __('Quick search...') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                </div>
            </form>
        </div>

        <!-- Desktop Table View (xl and above) -->
        <div class="hidden xl:block overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-y-0">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-900/30">
                        <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">{{ __('Machine Info') }}</th>
                        <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Running Status') }}</th>
                        <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Sub-machine Status') }}</th>
                        <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Today Cumulative Sales') }}</th>
                        <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Current Stock') }}</th>
                        <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Last Signal') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                    @forelse($machines as $machine)
                    <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                        <td class="px-6 py-6">
                            <a href="{{ route('admin.machines.index', ['search' => $machine->serial_no]) }}" class="block group/info hover:scale-105 transition-transform">
                                <div class="flex items-center gap-x-5 text-left">
                                    <div class="w-11 h-11 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-300 group-hover/info:bg-cyan-500 group-hover/info:text-white transition-all shadow-sm">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover/info:text-cyan-600 dark:group-hover/info:text-cyan-400 transition-colors flex items-center gap-2">
                                            <span>{{ $machine->name }}</span>
                                            @if($machine->has_login_warning)
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 uppercase tracking-wider tooltip shrink-0" title="{{ __('Login warning alert!') }}">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                {{ __('Login Alert') }}
                                            </span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-[0.15em]">(SN: {{ $machine->serial_no }})</span>
                                    </div>
                                </div>
                            </a>
                        </td>
                        <td class="px-6 py-6 text-center">
                            <a href="{{ route('admin.machines.index', ['search' => $machine->serial_no]) }}" class="block hover:scale-105 transition-transform">
                                @php $cStatus = $machine->calculated_status; @endphp
                                <div class="flex flex-col items-center gap-0.5">
                                    @if($cStatus === 'online')
                                    <div class="flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 tooltip"
                                        title="{{ $machine->is_unstable ? __('Connection is unstable (disconnected :count times in the last hour)', ['count' => $machine->recent_disconnection_count]) : __('Machine connection is normal') }}">
                                        <div class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </div>
                                        <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 tracking-widest uppercase">{{ __('Online') }}</span>
                                        @if($machine->is_unstable)
                                        <div class="ml-1 text-amber-500 animate-pulse">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        @endif
                                    </div>
                                    @elseif($cStatus === 'warning')
                                    <div class="flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20">
                                        <div class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></div>
                                        <span class="text-[10px] font-black text-amber-600 dark:text-amber-400 tracking-widest uppercase">{{ __('Warning') }}</span>
                                    </div>
                                    @elseif($cStatus === 'error')
                                    <div class="flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full bg-rose-500/10 border border-rose-500/20">
                                        <div class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></div>
                                        <span class="text-[10px] font-black text-rose-600 dark:text-rose-400 tracking-widest uppercase">{{ __('Error') }}</span>
                                    </div>
                                    @else
                                    <div class="flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-500/10 border border-slate-500/20">
                                        <div class="h-2 w-2 rounded-full bg-slate-400"></div>
                                        <span class="text-[10px] font-black text-slate-600 dark:text-slate-400 tracking-widest uppercase">{{ __('Offline') }}</span>
                                    </div>
                                    @endif
                                    @if($machine->latest_status_log_time && in_array($cStatus, ['error', 'warning']))
                                    <span class="text-[9px] font-bold text-slate-400">{{ $machine->latest_status_log_time->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </a>
                        </td>
                        <td class="px-6 py-6 text-center">
                            <a href="{{ route('admin.machines.index', ['search' => $machine->serial_no]) }}" class="block hover:scale-105 transition-transform">
                                @php $hStatus = $machine->hardware_status; @endphp
                                <div class="flex flex-col items-center gap-0.5">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full {{ 
                                            $hStatus === 'error' ? 'bg-rose-500 animate-pulse' : 
                                            ($hStatus === 'warning' ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500/50') 
                                        }}"></span>
                                        <span class="text-[10px] font-black {{ 
                                            $hStatus === 'error' ? 'text-rose-500' : 
                                            ($hStatus === 'warning' ? 'text-amber-500' : 'text-slate-400') 
                                        }} uppercase tracking-widest">
                                            {{ $hStatus === 'error' ? __('Error') : ($hStatus === 'warning' ? __('Warning') : __('Normal')) }}
                                        </span>
                                    </div>
                                    @if($machine->latest_hardware_log_time && $hStatus !== 'normal')
                                    <span class="text-[9px] font-bold text-slate-400">{{ $machine->latest_hardware_log_time->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </a>
                        </td>
                        <td class="px-6 py-6 text-center">
                            <span class="text-base font-extrabold text-slate-900 dark:text-slate-100">$ {{ number_format($machine->today_sales_sum ?? 0, 0) }}</span>
                        </td>
                        <td class="px-6 py-6">
                            <div class="block group/stock">
                                @php
                                $stockSum = (float)($machine->slots_sum_stock ?? 0);
                                $maxStockSum = (float)($machine->slots_sum_max_stock ?? 0);
                                $stockPercentage = $maxStockSum > 0 ? round(($stockSum / $maxStockSum) * 100, 1) : 0;
                                $barColor = $stockPercentage < 20 ? 'bg-rose-500' : ($stockPercentage < 50 ? 'bg-amber-500' : 'bg-emerald-500');
                                $textColor = str_replace('bg-', 'text-', $barColor);
                                @endphp
                                <div class="flex flex-col items-center gap-y-2.5">
                                    <div class="w-32 h-2 bg-slate-100 dark:bg-white/10 border border-slate-200/60 dark:border-slate-700/30 rounded-full overflow-hidden shadow-inner transition-all">
                                        <div class="h-full {{ $barColor }} rounded-full" style="width: {{ $stockPercentage }}%"></div>
                                    </div>
                                    <span class="text-sm font-black {{ $textColor }} uppercase tracking-[0.2em] transition-colors">{{ $stockPercentage }}%</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6 text-center">
                            <div class="text-xs font-black text-slate-400 dark:text-slate-400/80 uppercase tracking-widest leading-none">
                                {{ $machine->last_heartbeat_at ? $machine->last_heartbeat_at->format('Y-m-d H:i:s') : '---' }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-32 text-center text-slate-400">{{ __('No data available') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile/Tablet Card View (xl:hidden) -->
        <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($machines as $machine)
            <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group relative overflow-hidden">
                <!-- Card Header -->
                <div class="flex items-start justify-between gap-4 mb-6">
                    <a href="{{ route('admin.machines.index', ['search' => $machine->serial_no]) }}" class="flex items-center gap-4 min-w-0 group/info hover:scale-105 transition-transform origin-left">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover/info:bg-cyan-500 group-hover/info:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                            @if(isset($machine->image_urls[0]))
                                <img src="{{ $machine->image_urls[0] }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                </svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 group-hover/info:text-cyan-600 dark:group-hover/info:text-cyan-400 transition-colors tracking-tight flex items-center gap-2 min-w-0">
                                <span class="truncate">{{ $machine->name }}</span>
                                @if($machine->has_login_warning)
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 uppercase tracking-wider tooltip shrink-0" title="{{ __('Login warning alert!') }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                    {{ __('Login Alert') }}
                                </span>
                                @endif
                            </h3>
                            <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                                SN: {{ $machine->serial_no ?: '--' }}
                            </p>
                        </div>
                    </a>

                    @php $cStatus = $machine->calculated_status; @endphp
                    <div class="flex items-center">
                        @if($cStatus === 'online')
                            @if($machine->is_unstable)
                                <div class="text-amber-500 animate-pulse mr-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                            @endif
                            <div class="flex h-2.5 w-2.5 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </div>
                        @elseif($cStatus === 'error')
                            <div class="h-2.5 w-2.5 rounded-full bg-rose-500 animate-pulse"></div>
                        @else
                            <div class="h-2.5 w-2.5 rounded-full bg-slate-400"></div>
                        @endif
                    </div>
                </div>

                <!-- Info Grid (Content from Dashboard) -->
                <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Today Cumulative Sales') }}</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">$ {{ number_format($machine->today_sales_sum ?? 0, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Status') }}</p>
                        <div class="flex flex-col">
                            <a href="{{ route('admin.machines.index', ['search' => $machine->serial_no]) }}" class="block hover:scale-105 transition-transform origin-left">
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 capitalize">
                                    @if($cStatus === 'online')
                                        <span class="text-emerald-500">{{ __('Online') }}</span>
                                    @elseif($cStatus === 'warning')
                                        <span class="text-amber-500">{{ __('Warning') }}</span>
                                    @elseif($cStatus === 'error')
                                        <span class="text-rose-500">{{ __('Error') }}</span>
                                    @else
                                        <span class="text-slate-400">{{ __('Offline') }}</span>
                                    @endif
                                </p>
                                @if($machine->latest_status_log_time && in_array($cStatus, ['error', 'warning']))
                                <span class="text-[9px] font-bold text-slate-400">{{ $machine->latest_status_log_time->diffForHumans() }}</span>
                                @endif
                            </a>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Sub-machine Status') }}</p>
                        @php $hStatus = $machine->hardware_status; @endphp
                        <div class="flex flex-col">
                            <a href="{{ route('admin.machines.index', ['search' => $machine->serial_no]) }}" class="block hover:scale-105 transition-transform origin-left">
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 capitalize">
                                    @if($hStatus === 'error')
                                        <span class="text-rose-500">{{ __('Error') }}</span>
                                    @elseif($hStatus === 'warning')
                                        <span class="text-amber-500">{{ __('Warning') }}</span>
                                    @else
                                        <span class="text-emerald-500">{{ __('Normal') }}</span>
                                    @endif
                                </p>
                                @if($machine->latest_hardware_log_time && $hStatus !== 'normal')
                                <span class="text-[9px] font-bold text-slate-400">{{ $machine->latest_hardware_log_time->diffForHumans() }}</span>
                                @endif
                            </a>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Last Signal') }}</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">
                            {{ $machine->last_heartbeat_at ? $machine->last_heartbeat_at->format('Y-m-d H:i:s') : '---' }}
                        </p>
                    </div>
                </div>

                <!-- Stock Bar Section (Essential for Dashboard) -->
                <div class="mb-2">
                    <div class="block">
                        @php
                            $stockSum = (float)($machine->slots_sum_stock ?? 0);
                            $maxStockSum = (float)($machine->slots_sum_max_stock ?? 0);
                            $stockPercentage = $maxStockSum > 0 ? round(($stockSum / $maxStockSum) * 100, 1) : 0;
                            $barColor = $stockPercentage < 20 ? 'bg-rose-500' : ($stockPercentage < 50 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <div class="flex justify-between items-end mb-2">
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest transition-colors">{{ __('Current Stock') }}</p>
                            <span class="text-xs font-black {{ str_replace('bg-', 'text-', $barColor) }} transition-transform">{{ $stockPercentage }}%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 dark:bg-white/10 rounded-full overflow-hidden shadow-inner transition-all">
                            <div class="h-full {{ $barColor }} transition-all duration-500" style="width: {{ $stockPercentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full py-20 text-center text-slate-400 font-bold border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-3xl">
                {{ __('No data available') }}
            </div>
            @endforelse
        </div>

<div class="mt-8 border-t border-slate-100 dark:border-slate-800 pt-6">
    {{ $machines->appends(request()->query())->links('vendor.pagination.luxury') }}
</div>
</div>
</div>
@endsection