<div class="overflow-x-auto pb-4">
    <table class="hidden xl:table w-full text-left border-separate border-spacing-y-0 text-sm whitespace-nowrap">
        <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Machine Information') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                    {{ __('Status') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                    {{ __('Inventory Alerts') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                    {{ __('Last Sync') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                    {{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            @forelse($machines as $machine)
                <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300 cursor-pointer"
                    @click="selectMachine({{ Js::from($machine->only(['id', 'name', 'serial_no', 'image_urls'])) }})">
                    <td class="px-6 py-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm">
                                @if($machine->image_urls && isset($machine->image_urls[0]))
                                    <img src="{{ $machine->image_urls[0] }}" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-5.25v9" />
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <div class="text-[17px] font-black text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">
                                    {{ $machine->name }}</div>
                                <div class="text-[11px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">
                                    {{ $machine->serial_no }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-6 text-center">
                        <div class="flex justify-center">
                            @if($machine->status === 'online' || !$machine->status)
                                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20">
                                    <div class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </div>
                                    <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 tracking-[0.1em] uppercase">{{ __('Online') }}</span>
                                </div>
                            @elseif($machine->status === 'offline')
                                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-500/10 border border-slate-500/20">
                                    <div class="h-2 w-2 rounded-full bg-slate-400"></div>
                                    <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 tracking-[0.1em] uppercase">{{ __('Offline') }}</span>
                                </div>
                            @else
                                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-rose-500/10 border border-rose-500/20">
                                    <div class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                                    </div>
                                    <span class="text-[10px] font-black text-rose-600 dark:text-rose-400 tracking-[0.1em] uppercase">{{ __('Abnormal') }}</span>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-6 text-center">
                        <div class="flex flex-col items-center gap-1.5">
                            @if($machine->low_stock_count > 0)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-500 text-[10px] font-black border border-rose-500/20 uppercase tracking-widest leading-none shadow-sm shadow-rose-500/5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                    {{ $machine->low_stock_count }}&nbsp;{{ __('Low') }}
                                </span>
                            @endif
                            @if($machine->expiring_soon_count > 0)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-500 text-[10px] font-black border border-amber-500/20 uppercase tracking-widest leading-none shadow-sm shadow-amber-500/5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    {{ $machine->expiring_soon_count }}&nbsp;{{ __('Expiring') }}
                                </span>
                            @endif
                            @if(!$machine->low_stock_count && !$machine->expiring_soon_count)
                                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-[0.1em]">{{ __('Inventory Stable') }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-6 text-center">
                        <div class="flex flex-col items-center">
                            <span class="text-sm font-black text-slate-700 dark:text-slate-200"
                                x-text="formatTime({{ Js::from($machine->last_heartbeat_at) }})"></span>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">
                                {{ $machine->last_heartbeat_at ? \Illuminate\Support\Carbon::parse($machine->last_heartbeat_at)->format('Y-m-d') : '--' }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-6 text-right">
                        <div class="flex justify-end">
                            <button @click.stop="selectMachine({{ Js::from($machine->only(['id', 'name', 'serial_no', 'image_urls'])) }})"
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                title="{{ __('Manage') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-900/50 flex items-center justify-center text-slate-200 dark:text-slate-800">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                            <p class="text-slate-400 font-bold tracking-widest uppercase text-xs">{{ __('No machines found') }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Mobile Card View --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse($machines as $machine)
    <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group" 
         @click="selectMachine({{ Js::from($machine->only(['id', 'name', 'serial_no', 'image_urls'])) }})">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 min-w-0">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                    @if($machine->image_urls && isset($machine->image_urls[0]))
                        <img src="{{ $machine->image_urls[0] }}" class="w-full h-full object-cover">
                    @else
                        <svg class="w-7 h-7 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-5.25v9" />
                        </svg>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base sm:text-lg font-black text-slate-800 dark:text-slate-100 tracking-tight group-hover:text-cyan-600 transition-colors tracking-tight">{{ $machine->name }}</h3>
                    <p class="text-[11px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ $machine->serial_no }}</p>
                </div>
            </div>
            <div class="shrink-0">
                @if($machine->status === 'online' || !$machine->status)
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                        <div class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                        </div>
                        <span class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 tracking-[0.1em] uppercase">{{ __('Online') }}</span>
                    </div>
                @elseif($machine->status === 'offline')
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-500/10 border border-slate-500/20">
                        <div class="h-1.5 w-1.5 rounded-full bg-slate-400"></div>
                        <span class="text-[9px] font-black text-slate-500 dark:text-slate-400 tracking-[0.1em] uppercase">{{ __('Offline') }}</span>
                    </div>
                @else
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-500/10 border border-rose-500/20">
                        <div class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-rose-500"></span>
                        </div>
                        <span class="text-[9px] font-black text-rose-600 dark:text-rose-400 tracking-[0.1em] uppercase">{{ __('Abnormal') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">{{ __('Inventory Status') }}</p>
                <div class="flex flex-wrap gap-2">
                    @if($machine->low_stock_count > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-500 text-[10px] font-black border border-rose-500/20 uppercase tracking-widest leading-none shadow-sm shadow-rose-500/5">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $machine->low_stock_count }}&nbsp;{{ __('Low') }}
                        </span>
                    @endif
                    @if($machine->expiring_soon_count > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-500 text-[10px] font-black border border-amber-500/20 uppercase tracking-widest leading-none shadow-sm shadow-amber-500/5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            {{ $machine->expiring_soon_count }}&nbsp;{{ __('Expiring') }}
                        </span>
                    @endif
                    @if(!$machine->low_stock_count && !$machine->expiring_soon_count)
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-[0.1em] border border-slate-200/50 dark:border-slate-800 rounded-lg px-2.5 py-1">{{ __('Inventory Stable') }}</span>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Last Sync') }}</p>
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200 mt-0.5" x-text="formatTime({{ Js::from($machine->last_heartbeat_at) }})"></span>
                    <span class="text-[10px] font-mono text-slate-400">
                        {{ $machine->last_heartbeat_at ? \Illuminate\Support\Carbon::parse($machine->last_heartbeat_at)->format('Y-m-d') : '--' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" @click.stop="selectMachine({{ Js::from($machine->only(['id', 'name', 'serial_no', 'image_urls'])) }})"
               class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-black text-[10px] sm:text-xs tracking-widest hover:bg-cyan-500/20 transition-all duration-300 border border-cyan-500/20 shadow-sm">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                {{ __('Manage') }}
            </button>
        </div>
    </div>
    @empty
    <x-empty-state
        icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'/>"
        :title="__('No machines found')"
        :subtitle="''"
        class="md:col-span-2"
    />
    @endforelse
</div>

{{-- 標準化分頁底欄 --}}
<div class="mt-8">
    {{ $machines->appends(request()->except('machine_page'))->links('vendor.pagination.luxury') }}
</div>
