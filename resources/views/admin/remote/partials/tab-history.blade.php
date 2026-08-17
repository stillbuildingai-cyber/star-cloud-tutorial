{{-- 庫存操作紀錄 Partial (AJAX 可替換) --}}
<!-- Filters Area -->
<div class="mb-6">
    <form method="GET" action="{{ route('admin.remote.stock') }}" class="flex flex-wrap items-center gap-4"
        @submit.prevent="searchInTab()">
        <!-- Search Box -->
        <div class="relative group flex-[1.5] min-w-[200px]">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </span>
            <input type="text" name="search" value="{{ request('search') }}" x-model="historySearch"
                placeholder="{{ __('Search machines...') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full">
        </div>

        <!-- Start Date -->
        <div class="relative group flex-1 min-w-[180px]">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </span>
            <input type="text" name="start_date" 
                x-model="historyStartDate"
                x-init="flatpickr($el, { 
                    dateFormat: 'Y-m-d H:i', 
                    enableTime: true, 
                    time_24hr: true, 
                    disableMobile: true,
                    defaultHour: 0,
                    defaultMinute: 0,
                    locale: 'zh_tw',
                    defaultDate: historyStartDate || '{{ now()->startOfDay()->format('Y-m-d H:i') }}',
                    onChange: (selectedDates, dateStr) => { historyStartDate = dateStr }
                })"
                placeholder="{{ __('Start Date') }}"
                class="luxury-input py-2.5 pl-12 pr-4 block w-full text-sm font-bold cursor-pointer">
            <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity">{{ __('Start Time') }}</div>
        </div>

        <!-- End Date -->
        <div class="relative group flex-1 min-w-[180px]">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </span>
            <input type="text" name="end_date" 
                x-model="historyEndDate"
                x-init="flatpickr($el, { 
                    dateFormat: 'Y-m-d H:i', 
                    enableTime: true, 
                    time_24hr: true, 
                    disableMobile: true,
                    defaultHour: 23,
                    defaultMinute: 59,
                    locale: 'zh_tw',
                    defaultDate: historyEndDate || '{{ now()->endOfDay()->format('Y-m-d H:i') }}',
                    position: 'auto right',
                    onChange: (selectedDates, dateStr) => { historyEndDate = dateStr }
                })"
                placeholder="{{ __('End Date') }}"
                class="luxury-input py-2.5 pl-12 pr-4 block w-full text-sm font-bold cursor-pointer">
            <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity">{{ __('End Time') }}</div>
        </div>

        <!-- Status -->
        <div class="flex-1 min-w-[160px]" @change="
            historyStatus = $event.target.value === ' ' ? '' : $event.target.value;
            searchInTab();
        ">
            <x-searchable-select name="status" :options="[
                    'pending' => __('Pending'),
                    'sent' => __('Sent'),
                    'success' => __('Success'),
                    'failed' => __('Failed'),
                    'superseded' => __('Superseded'),
                ]" :selected="request('status')" :placeholder="__('All Status')" :hasSearch="false" />
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2">
            <button type="submit"
                class="p-2.5 rounded-xl bg-cyan-600 text-white hover:bg-cyan-500 shadow-lg shadow-cyan-500/20 transition-all active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
            <button type="button"
                @click="historySearch = ''; historyStartDate = ''; historyEndDate = ''; historyStatus = ''; searchInTab()"
                class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </form>
</div>
<div class="overflow-x-auto">
    <table class="hidden xl:table w-full text-left border-separate border-spacing-y-0 text-sm">
        <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Machine Information') }}</th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center whitespace-nowrap">
                    {{ __('Creation Time') }}</th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center whitespace-nowrap">
                    {{ __('Picked up Time') }}</th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Command Type') }}</th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Operator') }}</th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                    {{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            @foreach ($history as $item)
            <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                <td class="px-6 py-6 cursor-pointer" @click="selectMachine(@js(($item->machine?->only(['id', 'name', 'serial_no', 'image_urls']) ?? null)))">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm overflow-hidden">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-5.25v9" />
                            </svg>
                        </div>
                        <div>
                            <div
                                class="text-[17px] font-black text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">
                                {{ $item->machine->name }}</div>
                            <div
                                class="text-[11px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">
                                {{ $item->machine->serial_no }}</div>
                        </div>
                    </div>
                </td>
                <td
                    class="px-6 py-6 font-mono text-xs font-black text-slate-400 tracking-widest whitespace-nowrap text-center">
                    <div class="flex flex-col">
                        <span>{{ $item->created_at->format('Y/m/d') }}</span>
                        <span class="text-[15px] font-bold text-slate-500 dark:text-slate-400">{{
                            $item->created_at->format('H:i:s') }}</span>
                    </div>
                </td>
                <td
                    class="px-6 py-6 font-mono text-xs font-black text-slate-400 tracking-widest whitespace-nowrap text-center">
                    @if($item->executed_at)
                    <div class="flex flex-col text-cyan-600/80 dark:text-cyan-400/60">
                        <span>{{ $item->executed_at->format('Y/m/d') }}</span>
                        <span class="text-[15px] font-bold">{{ $item->executed_at->format('H:i:s') }}</span>
                    </div>
                    @else
                    <span class="text-slate-300 dark:text-slate-700">-</span>
                    @endif
                </td>
                <td class="px-6 py-6">
                    <div class="flex flex-col min-w-[200px]">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300 tracking-tight"
                            x-text="getCommandName(@js($item->command_type))"></span>
                        <div class="flex flex-col gap-0.5 mt-1">
                            <span x-show="getPayloadDetails(@js($item->only(['payload', 'command_type', 'status'])))"
                                class="text-[11px] font-bold text-cyan-600 dark:text-cyan-400/80 bg-cyan-500/5 px-2 py-0.5 rounded-md border border-cyan-500/10 w-fit"
                                :class="{'line-through opacity-60': @js($item->status) === 'failed'}"
                                x-text="getPayloadDetails(@js($item->only(['payload', 'command_type', 'status'])))"></span>
                            @if($item->note)
                            <span class="text-[10px] text-slate-400 italic pl-1"
                                x-text="translateNote(@js($item->note))"></span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-6 h-6 rounded-full bg-cyan-500/10 flex items-center justify-center text-[10px] font-black text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                            {{ mb_substr($item->user ? $item->user->name : __('System'), 0, 1) }}
                        </div>
                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ $item->user ?
                            $item->user->name : __('System') }}</span>
                    </div>
                </td>
                <td class="px-6 py-6 text-center">
                    <div class="flex flex-col items-center gap-1.5">
                        <div class="inline-flex items-center px-4 py-1.5 rounded-full border text-[10px] font-black uppercase tracking-widest shadow-sm"
                            :class="getCommandBadgeClass(@js($item->status))">
                            <div class="w-1.5 h-1.5 rounded-full mr-2" :class="{
                                          'bg-amber-500 animate-pulse': @js($item->status) === 'pending',
                                          'bg-cyan-500': @js($item->status) === 'sent',
                                          'bg-emerald-500': @js($item->status) === 'success',
                                          'bg-rose-500': @js($item->status) === 'failed',
                                          'bg-slate-400': @js($item->status) === 'superseded'
                                     }"></div>
                            <span x-text="getCommandStatus(@js($item->status))"></span>
                        </div>
                    </div>
                </td>
            </tr>
            @endforeach
            @if($history->isEmpty())
            <tr>
                <td colspan="6" class="px-6 py-20 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-900/50 flex items-center justify-center text-slate-200 dark:text-slate-800">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <p class="text-slate-400 font-bold tracking-widest uppercase text-xs">{{ __('No records found')
                            }}</p>
                    </div>
                </td>
            </tr>
            @endif
        </tbody>
    </table>
    </table>
</div>

{{-- Mobile Card View --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse ($history as $item)
    <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group"
        @click="selectMachine(@js(($item->machine?->only(['id', 'name', 'serial_no', 'image_urls']) ?? null)))">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 min-w-0">
                <div
                    class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-5.25v9" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3
                        class="text-base sm:text-lg font-black text-slate-800 dark:text-slate-100 tracking-tight group-hover:text-cyan-600 transition-colors tracking-tight">
                        {{ $item->machine->name }}</h3>
                    <p
                        class="text-[11px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">
                        {{ $item->machine->serial_no }}</p>
                </div>
            </div>
            <div class="shrink-0">
                <div class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black border tracking-widest uppercase shadow-sm"
                    :class="getCommandBadgeClass(@js($item->status))">
                    <div class="w-1.5 h-1.5 rounded-full mr-1.5" :class="{
                              'bg-amber-500 animate-pulse': @js($item->status) === 'pending',
                              'bg-cyan-500': @js($item->status) === 'sent',
                              'bg-emerald-500': @js($item->status) === 'success',
                              'bg-rose-500': @js($item->status) === 'failed',
                              'bg-slate-400': @js($item->status) === 'superseded'
                         }"></div>
                    <span x-text="getCommandStatus(@js($item->status))"></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Command Type') }}
                </p>
                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300 tracking-tight"
                        x-text="getCommandName(@js($item->command_type))"></span>
                    <div class="flex flex-wrap gap-1">
                        <span x-show="getPayloadDetails(@js($item->only(['payload', 'command_type', 'status'])))"
                            class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400/80 bg-cyan-500/5 px-2 py-0.5 rounded-md border border-cyan-500/10 w-fit"
                            :class="{'line-through opacity-60': @js($item->status) === 'failed'}"
                            x-text="getPayloadDetails(@js($item->only(['payload', 'command_type', 'status'])))"></span>
                        @if($item->note)
                        <span class="text-[10px] text-slate-400 italic" x-text="translateNote(@js($item->note))"></span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Creation Time') }}</p>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{
                            $item->created_at->format('Y-m-d') }}</span>
                        <span class="text-[10px] font-mono text-slate-400">{{ $item->created_at->format('H:i:s')
                            }}</span>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Operator') }}
                    </p>
                    <div class="flex items-center gap-2">
                        <div
                            class="w-5 h-5 rounded-full bg-cyan-500/10 flex items-center justify-center text-[9px] font-black text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                            {{ mb_substr($item->user ? $item->user->name : __('System'), 0, 1) }}
                        </div>
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate">{{ $item->user ?
                            $item->user->name : __('System') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" @click.stop="selectMachine(@js(($item->machine?->only(['id', 'name', 'serial_no', 'image_urls']) ?? null)))"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-[10px] sm:text-xs tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-300 border border-slate-200 dark:border-slate-700 shadow-sm">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                {{ __('Manage') }}
            </button>
        </div>
    </div>
    @empty
    <x-empty-state
        icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'/>"
        :title="__('No records found')" :subtitle="''" class="md:col-span-2" />
    @endforelse
</div>

<!-- Pagination Area -->
<div class="mt-8">
    {{ $history->appends(request()->query())->links('vendor.pagination.luxury') }}
</div>