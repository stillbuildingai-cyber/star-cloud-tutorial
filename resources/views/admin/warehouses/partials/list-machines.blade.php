<div class="space-y-6">
    {{-- Table View (Desktop) --}}
    <div class="hidden xl:block overflow-x-auto luxury-scrollbar">
        <table class="w-full text-left border-separate border-spacing-y-0">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('Machine') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('Serial No.') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Slots') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Total Stock') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Stock Rate') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                        {{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                @forelse($machines as $machine)
                @php
                $maxStock = $machine->slots->sum('max_stock') ?: 1;
                $currentStock = (int)($machine->total_stock ?? 0);
                $fillRate = round(($currentStock / $maxStock) * 100);
                $fillColor = $fillRate >= 50 ? 'emerald' : ($fillRate >= 20 ? 'amber' : 'rose');
                @endphp
                <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200 cursor-pointer"
                    @click="viewSlots({{ json_encode($machine->only(['id', 'name', 'serial_no'])) }})">
                    <td class="px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all overflow-hidden shadow-sm border border-slate-200 dark:border-slate-700">
                                @if($machine->image_urls && isset($machine->image_urls[0]))
                                <img src="{{ $machine->image_urls[0] }}" class="w-full h-full object-cover">
                                @else
                                <svg class="w-6 h-6 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                @endif
                            </div>
                            <span class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors leading-tight">{{ $machine->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap">
                        <span class="font-mono font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-sm group-hover:text-cyan-500/70 transition-colors">{{ $machine->serial_no }}</span>
                    </td>
                    <td class="px-6 py-5 text-center">
                        <span class="text-sm font-black text-slate-800 dark:text-white">{{ $machine->total_slots ?? 0 }}</span>
                    </td>
                    <td class="px-6 py-5 text-center">
                        <span class="text-sm font-black text-slate-800 dark:text-white">{{ $currentStock }}</span>
                    </td>
                    <td class="px-6 py-5 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-3">
                            <div class="w-24 h-2 bg-slate-100 dark:bg-slate-700/50 rounded-full overflow-hidden shadow-inner">
                                <div class="h-full bg-{{ $fillColor }}-500 rounded-full transition-all duration-500"
                                    style="width: {{ $fillRate }}%;"></div>
                            </div>
                            <span class="text-sm font-black text-{{ $fillColor }}-500 w-8">{{ $fillRate }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.warehouses.replenishments') }}?auto_machine_id={{ $machine->id }}"
                                @click.stop
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                title="{{ __('Auto Replenishment') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                            </a>
                            {{-- 庫存流水帳按鈕 --}}
                            <button @click.stop="openMovements({{ json_encode($machine->only(['id', 'name', 'serial_no'])) }})"
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-indigo-500 hover:bg-indigo-500/5 transition-all border border-transparent hover:border-indigo-500/20"
                                title="{{ __('Stock Movement Log') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                                </svg>
                            </button>
                            <button
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                title="{{ __('View Slots') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9.75 17 9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                            </svg>
                            <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No machines found') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Card View (Mobile & Tablet) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:hidden gap-6">
        @forelse($machines as $machine)
        @php
        $maxStock = $machine->slots->sum('max_stock') ?: 1;
        $currentStock = (int)($machine->total_stock ?? 0);
        $fillRate = round(($currentStock / $maxStock) * 100);
        $fillColor = $fillRate >= 50 ? 'emerald' : ($fillRate >= 20 ? 'amber' : 'rose');
        @endphp
        <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group"
             @click="viewSlots({{ json_encode($machine->only(['id', 'name', 'serial_no'])) }})">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                        @if($machine->image_urls && isset($machine->image_urls[0]))
                        <img src="{{ $machine->image_urls[0] }}" class="w-full h-full object-cover">
                        @else
                        <svg class="w-7 h-7 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 cursor-pointer">
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate group-hover:text-cyan-600 transition-colors tracking-tight">{{ $machine->name }}</h3>
                        <p class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-widest truncate mt-1">{{ $machine->serial_no }}</p>
                    </div>
                </div>
                <div class="shrink-0">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-{{ $fillColor }}-500/10 text-{{ $fillColor }}-500 border border-{{ $fillColor }}-500/20 tracking-widest uppercase shadow-sm">
                        {{ $fillRate }}%
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Total Slots') }}</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $machine->total_slots ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Total Stock') }}</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $currentStock }}</p>
                </div>
                <div class="col-span-2 pt-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ __('Stock Rate') }}</p>
                    <div class="w-full h-2 bg-slate-100 dark:bg-slate-700/50 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-{{ $fillColor }}-500 rounded-full transition-all duration-500"
                            style="width: {{ $fillRate }}%;"></div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" @click.stop="viewSlots({{ json_encode($machine->only(['id', 'name', 'serial_no'])) }})"
                   class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200 dark:border-slate-700 shadow-sm">
                    <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    {{ __('Details') }}
                </button>
                <a href="{{ route('admin.warehouses.replenishments') }}?auto_machine_id={{ $machine->id }}" @click.stop
                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-cyan-500/10 text-cyan-500 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-cyan-500/20 shadow-sm">
                    <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                    {{ __('Replenish') }}
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-full py-20 text-center flex flex-col items-center gap-4 bg-white/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800/50 luxury-card">
            <div class="flex flex-col items-center gap-3">
                <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9.75 17 9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                </svg>
                <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No machines found') }}</p>
            </div>
        </div>
        @endforelse
    </div>

    <div class="mt-6 pt-6 border-t border-slate-50 dark:border-slate-800/50">
        @if($machines->total() > 0)
            {{ $machines->links('vendor.pagination.luxury') }}
        @else
            <div class="flex items-center justify-between gap-4 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                <span>{{ __('No records found') }}</span>
                <span>0 / 0</span>
            </div>
        @endif
    </div>
</div>
