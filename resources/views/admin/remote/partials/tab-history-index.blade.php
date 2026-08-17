<!-- Loading Overlay -->
<div x-show="tabLoading === 'history'" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="absolute inset-0 z-20 bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] flex flex-col items-center justify-center"
    x-cloak>
    <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
        <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin"></div>
        <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin" style="animation-duration: 3s; direction: reverse;"></div>
        <div class="relative w-8 h-8 flex items-center justify-center">
            <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
        </div>
    </div>
    <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">
        {{ __('Loading Data') }}...</p>
</div>

        <!-- Filters Area -->
        <div class="flex flex-wrap items-center gap-3 sm:gap-4 mb-6">
            <form @submit.prevent="searchInTab('history')" class="contents">
                {{-- 搜尋輸入框 --}}
                <div class="relative group flex-1 sm:flex-none">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search machines...') }}"
                        class="luxury-input py-2.5 pl-12 pr-6 block w-full sm:w-72 text-sm font-bold">
                </div>

                {{-- 起始時間 --}}
                <div class="relative group w-full sm:w-48 sm:flex-none">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <input type="text" name="start_date" 
                        x-init="flatpickr($el, { 
                            dateFormat: 'Y-m-d H:i', 
                            enableTime: true, 
                            time_24hr: true, 
                            disableMobile: true,
                            locale: 'zh_tw',
                            defaultDate: $el.value
                        })"
                        value="{{ request('start_date', now()->startOfDay()->format('Y-m-d H:i')) }}"
                        placeholder="{{ __('Start Date') }}"
                        class="luxury-input py-2.5 pl-12 pr-4 block w-full text-sm font-bold cursor-pointer">
                    <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity">{{ __('Start Time') }}</div>
                </div>

                {{-- 結束時間 --}}
                <div class="relative group w-full sm:w-48 sm:flex-none">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <input type="text" name="end_date" 
                        x-init="flatpickr($el, { 
                            dateFormat: 'Y-m-d H:i', 
                            enableTime: true, 
                            time_24hr: true, 
                            disableMobile: true,
                            locale: 'zh_tw',
                            defaultDate: $el.value,
                            position: 'auto right'
                        })"
                        value="{{ request('end_date', now()->endOfDay()->format('Y-m-d H:i')) }}"
                        placeholder="{{ __('End Date') }}"
                        class="luxury-input py-2.5 pl-12 pr-4 block w-full text-sm font-bold cursor-pointer">
                    <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity">{{ __('End Time') }}</div>
                </div>

                {{-- 指令類型 --}}
                <div class="w-full sm:w-48">
                    <x-searchable-select name="command_type" :options="[
                        'reboot' => __('Machine Reboot'),
                        'reboot_force' => __('Machine Force Reboot'),
                        'reboot_card' => __('Card Reader Reboot'),
                        'checkout' => __('Remote Settlement'),
                        'lock' => __('Lock Page Lock'),
                        'unlock' => __('Lock Page Unlock'),
                        'fanon' => __('Fan On'),
                        'fanoff' => __('Fan Off'),
                        'fanauto' => __('Fan Auto'),
                        'ambient_temp_limit' => __('Ambient Temperature Upper Limit'),
                        'change' => __('Remote Change'),
                        'dispense' => __('Remote Dispense'),
                        'update_ads' => __('Sync Ads'),
                        'update_products' => __('Sync Products'),
                        'update_settings' => __('Sync Settings'),
                        'update_app' => __('Update App'),
                    ]" :selected="request('command_type')" :placeholder="__('All Command Types')"
                        :hasSearch="false"
                     @change="searchInTab('history')" />
                </div>

                {{-- 狀態 --}}
                <div class="w-full sm:w-48">
                    <x-searchable-select name="status" :options="[
                        'pending' => __('Pending'),
                        'sent' => __('Sent'),
                        'success' => __('Success'),
                        'failed' => __('Failed'),
                        'superseded' => __('Superseded'),
                        'timeout' => __('Timeout'),
                    ]" :selected="request('status')" :placeholder="__('All Status')" :hasSearch="false" @change="searchInTab('history')" />
                </div>

                {{-- 搜尋 + 重置按鈕 --}}
                <div class="flex items-center gap-2 ml-auto sm:ml-0">
                    <button type="submit" class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95" title="{{ __('Search') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    <button type="button" @click="searchInTab('history', true)" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95" title="{{ __('Reset') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
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
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 whitespace-nowrap">
                            {{ __('Command Type') }}</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 whitespace-nowrap">
                            {{ __('Operation Note') }}</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 whitespace-nowrap">
                            {{ __('Operator') }}</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                            {{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                    @foreach ($history as $item)
                    <tr
                        class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                        <td class="px-6 py-6 cursor-pointer" @click="selectMachine({{ Js::from(($item->machine?->only(['id', 'name', 'serial_no', 'image_urls']) ?? null)) }})">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm overflow-hidden shrink-0">
                                    <template x-if="{{ Js::from(!empty($item->machine->image_urls) && isset($item->machine->image_urls[0])) }}">
                                        <img src="{{ !empty($item->machine->image_urls) ? $item->machine->image_urls[0] : '' }}"
                                            class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="{{ Js::from(empty($item->machine->image_urls) || !isset($item->machine->image_urls[0])) }}">
                                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-5.25v9" />
                                        </svg>
                                    </template>
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
                                <span class="text-[15px] font-bold text-slate-500 dark:text-slate-400">{{ $item->created_at->format('H:i:s')
                                    }}</span>
                            </div>
                        </td>
                        <td
                            class="px-6 py-6 font-mono text-xs font-black text-slate-400 tracking-widest whitespace-nowrap text-center">
                            @if($item->executed_at)
                            <div class="flex flex-col text-cyan-600/80 dark:text-cyan-400/60">
                                <span>{{ $item->executed_at->format('Y/m/d') }}</span>
                                <span class="text-[15px] font-bold">{{ $item->executed_at->format('H:i:s')
                                    }}</span>
                            </div>
                            @else
                            <span class="text-slate-300 dark:text-slate-700">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex flex-col min-w-[140px]">
                                <span
                                    class="text-sm font-black text-slate-700 dark:text-slate-300 tracking-tight whitespace-nowrap"
                                    x-text="getCommandName({{ Js::from($item->command_type) }})"></span>
                                <div class="flex flex-col gap-0.5 mt-1">
                                    <span x-show="getPayloadDetails({{ Js::from($item) }})"
                                        class="text-[11px] font-bold text-cyan-600 dark:text-cyan-400/80 bg-cyan-500/5 px-2 py-0.5 rounded-md border border-cyan-500/10 w-fit"
                                        :class="{'line-through opacity-60': {{ Js::from($item->status) }} === 'failed'}"
                                        x-text="getPayloadDetails({{ Js::from($item) }})"></span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex flex-col min-w-[200px]">
                                @if($item->remark)
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-300 break-words leading-tight">{{ __($item->remark) }}</span>
                                @endif
                                
                                @if($item->note)
                                <div class="flex items-center gap-1 mt-1 opacity-60">
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-[10px] text-slate-400 italic font-medium"
                                        x-text="translateNote({{ Js::from($item->note) }})"></span>
                                </div>
                                @endif

                                @if(!$item->remark && !$item->note)
                                <span class="text-slate-300 dark:text-slate-700">-</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-6 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-6 h-6 rounded-full bg-cyan-500/10 flex items-center justify-center text-[10px] font-black text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                                    {{ mb_substr($item->user ? $item->user->name : __('System'), 0, 1) }}
                                </div>
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{
                                    $item->user ? $item->user->name : __('System') }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-6 text-center">
                            <div class="flex flex-col items-center gap-1.5">
                                <div class="inline-flex items-center px-4 py-1.5 rounded-full border text-[10px] font-black uppercase tracking-widest shadow-sm"
                                    :class="getCommandBadgeClass({{ Js::from($item->status) }})">
                                    <div class="w-1.5 h-1.5 rounded-full mr-2" :class="{
                                              'bg-amber-500 animate-pulse': {{ Js::from($item->status) }} === 'pending',
                                              'bg-cyan-500': {{ Js::from($item->status) }} === 'sent',
                                              'bg-emerald-500': {{ Js::from($item->status) }} === 'success',
                                              'bg-rose-500': {{ Js::from($item->status) }} === 'failed' || {{ Js::from($item->status) }} === 'timeout',
                                              'bg-slate-400': {{ Js::from($item->status) }} === 'superseded'
                                         }"></div>
                                    <span x-text="getCommandStatus({{ Js::from($item->status) }})"></span>
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
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <p class="text-slate-400 font-bold tracking-widest uppercase text-xs">{{
                                    __('No records found') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Mobile Card View --}}
        <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse ($history as $item)
            <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group"
                 @click="selectMachine({{ Js::from(($item->machine?->only(['id', 'name', 'serial_no', 'image_urls']) ?? null)) }})">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                            <template x-if="{{ Js::from(!empty($item->machine->image_urls) && isset($item->machine->image_urls[0])) }}">
                                <img src="{{ !empty($item->machine->image_urls) ? $item->machine->image_urls[0] : '' }}" class="w-full h-full object-cover">
                            </template>
                            <template x-if="{{ Js::from(empty($item->machine->image_urls) || !isset($item->machine->image_urls[0])) }}">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-5.25v9" />
                                </svg>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base sm:text-lg font-black text-slate-800 dark:text-slate-100 tracking-tight group-hover:text-cyan-600 transition-colors tracking-tight">{{ $item->machine->name }}</h3>
                            <p class="text-[11px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ $item->machine->serial_no }}</p>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <div class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black border tracking-widest uppercase shadow-sm" :class="getCommandBadgeClass({{ Js::from($item->status) }})">
                            <div class="w-1.5 h-1.5 rounded-full mr-1.5" :class="{
                                      'bg-amber-500 animate-pulse': {{ Js::from($item->status) }} === 'pending',
                                      'bg-cyan-500': {{ Js::from($item->status) }} === 'sent',
                                      'bg-emerald-500': {{ Js::from($item->status) }} === 'success',
                                      'bg-rose-500': {{ Js::from($item->status) }} === 'failed' || {{ Js::from($item->status) }} === 'timeout',
                                      'bg-slate-400': {{ Js::from($item->status) }} === 'superseded'
                                 }"></div>
                            <span x-text="getCommandStatus({{ Js::from($item->status) }})"></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Command Type') }}</p>
                        <div class="flex flex-col gap-1.5">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200" x-text="getCommandName({{ Js::from($item->command_type) }})"></span>
                            <div class="flex flex-wrap gap-1">
                                <span x-show="getPayloadDetails({{ Js::from($item) }})"
                                    class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400/80 bg-cyan-500/5 px-2 py-0.5 rounded-md border border-cyan-500/10 w-fit"
                                    :class="{'line-through opacity-60': {{ Js::from($item->status) }} === 'failed'}"
                                    x-text="getPayloadDetails({{ Js::from($item) }})"></span>
                            </div>
                        </div>
                    </div>
                    @if($item->remark || $item->note)
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Operation Note') }}</p>
                        <div class="flex flex-col gap-1.5 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/50">
                            @if($item->remark)
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200 leading-tight">{{ __($item->remark) }}</span>
                            @endif
                            @if($item->note)
                            <div class="flex items-center gap-1.5 opacity-60">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-[10px] text-slate-400 italic font-medium" x-text="translateNote({{ Js::from($item->note) }})"></span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Creation Time') }}</p>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $item->created_at->format('Y-m-d') }}</span>
                                <span class="text-[10px] font-mono text-slate-400">{{ $item->created_at->format('H:i:s') }}</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Operator') }}</p>
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full bg-cyan-500/10 flex items-center justify-center text-[9px] font-black text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                                    {{ mb_substr($item->user ? $item->user->name : __('System'), 0, 1) }}
                                </div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate">{{ $item->user ? $item->user->name : __('System') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click.stop="selectMachine({{ Js::from(($item->machine?->only(['id', 'name', 'serial_no', 'image_urls']) ?? null)) }})"
                       class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-[10px] sm:text-xs tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-300 border border-slate-200 dark:border-slate-700 shadow-sm">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        {{ __('New Command') }}
                    </button>
                </div>
            </div>
            @empty
            <x-empty-state
                icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'/>"
                :title="__('No records found')"
                :subtitle="''"
                class="md:col-span-2"
            />
            @endforelse
        </div>

        <!-- Pagination Area -->
        <div class="mt-8">
            {{ $history->appends(request()->query())->links('vendor.pagination.luxury') }}
        </div>
