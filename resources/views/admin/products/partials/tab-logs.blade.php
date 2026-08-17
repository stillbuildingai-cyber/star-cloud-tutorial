{{-- Filter Form --}}
<form action="{{ route('admin.data-config.products.index') }}" method="GET"
    class="flex flex-wrap items-center gap-3 sm:gap-4 mb-8" @submit.prevent="handleFilterSubmit('logs')">
    <input type="hidden" name="tab" value="logs">

    {{-- Company Filter (If Admin) --}}
    @if(auth()->user()->isSystemAdmin())
    <div class="w-full sm:w-64 flex-none">
        <x-searchable-select
            name="log_company_id"
            :options="$companies"
            :selected="request('log_company_id')"
            :placeholder="__('Filter by Company')"
        />
    </div>
    @endif

    {{-- Search Keyword --}}
    <div class="relative group w-full sm:w-64 sm:flex-none">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
            <svg class="w-4 h-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </span>
        <input type="text" name="search_log" value="{{ request('search_log') }}"
            class="py-2.5 pl-11 pr-4 block w-full luxury-input text-sm font-bold"
            placeholder="{{ __('Search notes or values...') }}">
    </div>

    {{-- 開始時間 --}}
    <div class="relative group w-full sm:w-56 sm:flex-none" 
         x-data="{ fp: null }" 
         x-init="fp = flatpickr($refs.startDate, { 
            disableMobile: true,
            dateFormat: 'Y-m-d H:i',
            enableTime: true,
            time_24hr: true,
            defaultHour: 0,
            defaultMinute: 0,
            locale: 'zh_tw',
            position: 'auto right',
            defaultDate: '{{ request('start_date', $logDefaultStart ?? '') }}'
         })">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </span>
        <input type="text" name="start_date" x-ref="startDate" 
            value="{{ request('start_date', $logDefaultStart ?? '') }}"
            placeholder="{{ __('Start Time') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer">
        <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none">{{ __('Start Time') }}</div>
    </div>

    {{-- 結束時間 --}}
    <div class="relative group w-full sm:w-56 sm:flex-none" 
         x-data="{ fp: null }" 
         x-init="fp = flatpickr($refs.endDate, { 
            disableMobile: true,
            dateFormat: 'Y-m-d H:i',
            enableTime: true,
            time_24hr: true,
            defaultHour: 23,
            defaultMinute: 59,
            locale: 'zh_tw',
            position: 'auto right',
            defaultDate: '{{ request('end_date', $logDefaultEnd ?? '') }}'
         })">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </span>
        <input type="text" name="end_date" x-ref="endDate" 
            value="{{ request('end_date', $logDefaultEnd ?? '') }}"
            placeholder="{{ __('End Time') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer">
        <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none">{{ __('End Time') }}</div>
    </div>

    {{-- Buttons --}}
    <div class="flex items-center gap-2 ml-auto sm:ml-0">
        <button type="submit"
            class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95" title="{{ __('Search') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>
        <button type="button" @click="
                $el.closest('form').querySelectorAll('input').forEach(i => {
                    if (i.name === 'start_date' && i._flatpickr) i._flatpickr.setDate('{{ $logDefaultStart }}');
                    else if (i.name === 'end_date' && i._flatpickr) i._flatpickr.setDate('{{ $logDefaultEnd }}');
                    else if (i._flatpickr) i._flatpickr.clear();
                    else if (i.name !== 'tab') i.value = '';
                });
                $el.closest('form').querySelectorAll('select').forEach(s => {
                    s.value = ' ';
                    const instance = window.HSSelect.getInstance(s);
                    if (instance) instance.setValue(' ');
                });
                fetchTabData('logs', '{{ route('admin.data-config.products.index') }}?tab=logs');
            "
            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95" title="{{ __('Reset') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
    </div>
</form>

{{-- Desktop Table --}}
<div class="hidden xl:block overflow-x-auto">
    <table class="w-full text-left border-separate border-spacing-y-0">
        <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                    {{ __('Time') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                    {{ __('Operator') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">
                    {{ __('Action') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                    {{ __('Target Item') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                    {{ __('Note') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-right">
                    {{ __('Details') }}
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            @forelse($logs as $log)
            @php
                $oldValues = is_string($log->old_values) ? json_decode($log->old_values, true) : (array)($log->old_values ?? []);
                $newValues = is_string($log->new_values) ? json_decode($log->new_values, true) : (array)($log->new_values ?? []);
                $ignoredKeys = ['updated_at', 'created_at', 'deleted_at', 'localized_name'];
                $allKeys = array_diff(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))), $ignoredKeys);
                
                $changedKeys = [];
                foreach($allKeys as $k) {
                    if (($oldValues[$k] ?? null) !== ($newValues[$k] ?? null)) {
                        $changedKeys[] = $k;
                    }
                }
                
                $displayNote = $log->translated_note;
                if ($log->action === 'update' && !empty($changedKeys)) {
                    $translatedKeys = array_map(function($k) { return __($k); }, $changedKeys);
                    $displayNote = __('Modified fields') . ': ' . implode(', ', $translatedKeys);
                }
            @endphp
            <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                <td class="px-6 py-5 whitespace-nowrap">
                    <span class="text-xs font-mono font-black text-slate-700 dark:text-slate-200 tracking-tighter">
                        {{ $log->created_at?->format('Y-m-d H:i:s') }}
                    </span>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center border border-slate-200 dark:border-white/5 shrink-0">
                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $log->user->name ?? 'System' }}</span>
                    </div>
                </td>
                <td class="px-6 py-5 text-center">
                    <x-status-badge :status="$log->action" size="sm" />
                </td>
                <td class="px-6 py-5">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest">{{ __($log->module) }}</span>
                        <span class="text-sm font-black text-slate-800 dark:text-slate-100 truncate max-w-[150px]" title="{{ $log->target_name }}">{{ $log->target_name ?: '#' . $log->target_id }}</span>
                        @if($log->target_name)
                            <span class="text-[10px] font-bold text-slate-400">#{{ $log->target_id }}</span>
                        @endif
                    </div>
                </td>
                <td class="px-6 py-5">
                    <p class="text-sm font-bold text-slate-600 dark:text-slate-400">{{ $displayNote }}</p>
                </td>
                <td class="px-6 py-5 text-right whitespace-nowrap">
                    @if(!empty($changedKeys))
                    <button type="button" @click="toggleLog({{ $log->id }})"
                        class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-cyan-500 hover:bg-cyan-50 dark:hover:bg-cyan-500/10 transition-all active:scale-95"
                        :class="expandedLogs.includes({{ $log->id }}) ? 'bg-cyan-50 text-cyan-500 dark:bg-cyan-500/10' : ''">
                        <svg class="w-4 h-4 transition-transform duration-300" :class="expandedLogs.includes({{ $log->id }}) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    @endif
                </td>
            </tr>
            {{-- Desktop Expandable Row --}}
            @if(!empty($changedKeys))
            <tr x-show="expandedLogs.includes({{ $log->id }})" x-cloak>
                <td colspan="6" class="px-6 py-4 bg-slate-50/50 dark:bg-slate-900/30 border-t-0 border-b border-slate-100 dark:border-slate-800">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($changedKeys as $key)
                            <div class="p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-white/5">
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ __($key) }}</div>
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[10px] text-slate-400">{{ __('Old Value') }}</div>
                                        @php $oldVal = $oldValues[$key] ?? null; @endphp
                                        <div class="text-sm font-bold text-slate-600 dark:text-slate-300 truncate" title="{{ is_array($oldVal) ? json_encode($oldVal) : ($oldVal ?? '-') }}">{{ is_array($oldVal) ? json_encode($oldVal) : ($oldVal ?? '-') }}</div>
                                    </div>
                                    <div class="shrink-0 text-slate-300 dark:text-slate-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[10px] text-cyan-500">{{ __('New Value') }}</div>
                                        @php $newVal = $newValues[$key] ?? null; @endphp
                                        <div class="text-sm font-bold text-cyan-600 dark:text-cyan-400 truncate" title="{{ is_array($newVal) ? json_encode($newVal) : ($newVal ?? '-') }}">{{ is_array($newVal) ? json_encode($newVal) : ($newVal ?? '-') }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="6" class="px-6 py-20 text-center">
                    <x-empty-state :message="__('No operation logs found')" />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Card Mode (Mobile) --}}
<div class="xl:hidden space-y-4">
    @forelse($logs as $log)
    @php
        $oldValuesMob = is_string($log->old_values) ? json_decode($log->old_values, true) : (array)($log->old_values ?? []);
        $newValuesMob = is_string($log->new_values) ? json_decode($log->new_values, true) : (array)($log->new_values ?? []);
        $ignoredKeysMob = ['updated_at', 'created_at', 'deleted_at', 'localized_name'];
        $allKeysMob = array_diff(array_unique(array_merge(array_keys($oldValuesMob), array_keys($newValuesMob))), $ignoredKeysMob);
        
        $changedKeysMob = [];
        foreach($allKeysMob as $k) {
            if (($oldValuesMob[$k] ?? null) !== ($newValuesMob[$k] ?? null)) {
                $changedKeysMob[] = $k;
            }
        }
        
        $displayNoteMob = $log->translated_note;
        if ($log->action === 'update' && !empty($changedKeysMob)) {
            $translatedKeysMob = array_map(function($k) { return __($k); }, $changedKeysMob);
            $displayNoteMob = __('Modified fields') . ': ' . implode(', ', $translatedKeysMob);
        }
    @endphp
    <div class="luxury-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-500 text-[10px] font-black uppercase tracking-widest">
                    {{ __($log->module) }}
                </span>
                <x-status-badge :status="$log->action" size="xs" />
            </div>
            <span class="text-[10px] font-mono font-bold text-slate-400">{{ $log->created_at?->format('H:i:s') }}</span>
        </div>
        
        <p class="text-sm font-black text-slate-800 dark:text-slate-100 mb-2">{{ $displayNoteMob }}</p>
        
        <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-white/5">
            <div class="flex flex-col">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ $log->target_name ?: '#' . $log->target_id }}</span>
                @if($log->target_name)
                    <span class="text-[10px] font-bold text-slate-400">#{{ $log->target_id }}</span>
                @endif
                <span class="text-[10px] font-black text-slate-500 uppercase">{{ $log->user->name ?? 'System' }}</span>
            </div>
            @if(!empty($changedKeysMob))
            <button type="button" @click="toggleLog({{ $log->id }})"
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-black hover:bg-cyan-500 hover:text-white transition-all flex items-center gap-2">
                {{ __('Details') }}
                <svg class="w-3 h-3 transition-transform duration-300" :class="expandedLogs.includes({{ $log->id }}) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            @endif
        </div>
        
        {{-- Mobile Expandable Section --}}
        @if(!empty($changedKeysMob))
        <div x-show="expandedLogs.includes({{ $log->id }})" x-cloak class="mt-4 pt-4 border-t border-slate-100 dark:border-white/5 space-y-3">
            @foreach($changedKeysMob as $key)
                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ __($key) }}</div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="text-[10px] text-slate-400">{{ __('Old Value') }}</div>
                            @php $oldValMob = $oldValuesMob[$key] ?? null; @endphp
                            <div class="text-sm font-bold text-slate-600 dark:text-slate-300 truncate" title="{{ is_array($oldValMob) ? json_encode($oldValMob) : ($oldValMob ?? '-') }}">{{ is_array($oldValMob) ? json_encode($oldValMob) : ($oldValMob ?? '-') }}</div>
                        </div>
                        <div class="shrink-0 text-slate-300 dark:text-slate-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[10px] text-cyan-500">{{ __('New Value') }}</div>
                            @php $newValMob = $newValuesMob[$key] ?? null; @endphp
                            <div class="text-sm font-bold text-cyan-600 dark:text-cyan-400 truncate" title="{{ is_array($newValMob) ? json_encode($newValMob) : ($newValMob ?? '-') }}">{{ is_array($newValMob) ? json_encode($newValMob) : ($newValMob ?? '-') }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
    @empty
    <x-empty-state :message="__('No operation logs found')" />
    @endforelse
</div>

{{-- Pagination --}}
<div class="mt-8 py-6 border-t border-slate-50 dark:border-slate-800/50">
    {{ $logs->links('vendor.pagination.luxury') }}
</div>
