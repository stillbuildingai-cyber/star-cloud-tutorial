{{-- Filter Form --}}
<form action="{{ route('admin.sales.pass-codes') }}" method="GET"
    class="flex flex-wrap items-center gap-3 sm:gap-4 mb-8" @submit.prevent="fetchTabData('logs')">
    <input type="hidden" name="tab" value="logs">

    {{-- 搜尋關鍵字 --}}
    <div class="relative group w-full sm:w-64 sm:flex-none">
        <span
            class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </span>
        <input type="text" name="search_log" value="{{ request('search_log') }}"
            class="py-2.5 pl-12 pr-6 block w-full luxury-input text-sm font-bold"
            placeholder="{{ __('Search logs...') }}">
    </div>

    {{-- 類型篩選 --}}
    <div class="w-full sm:w-64">
        <x-searchable-select 
            name="action" 
            :selected="request('action')"
            :placeholder="__('All Types')"
            :hasSearch="false"
            @change="$el.closest('form').dispatchEvent(new Event('submit'))"
        >
            @foreach($actions as $key => $label)
                <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }} data-title="{{ $label }}">
                    {{ $label }}
                </option>
            @endforeach
        </x-searchable-select>
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
            defaultDate: '{{ request('start_date') }}'
         })">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </span>
        <input type="text" name="start_date" x-ref="startDate" 
            value="{{ request('start_date') }}"
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
            defaultDate: '{{ request('end_date') }}'
         })">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </span>
        <input type="text" name="end_date" x-ref="endDate" 
            value="{{ request('end_date') }}"
            placeholder="{{ __('End Time') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer">
        <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none">{{ __('End Time') }}</div>
    </div>

    {{-- 6 個月限制提示 (僅在未搜尋時間時顯示) --}}
    @if(!request('start_date') && !request('end_date'))
    <div class="hidden md:flex items-center gap-2 px-4 py-2 bg-amber-50/50 dark:bg-amber-900/10 border border-amber-100/50 dark:border-amber-800/30 rounded-xl">
        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-[10px] font-bold text-amber-600/80 dark:text-amber-500/80 uppercase tracking-wider">
            {{ __('Showing last 6 months by default when no date range is selected') }}
        </span>
    </div>
    @endif

    {{-- 按鈕組 --}}
    <div class="flex items-center gap-2 ml-auto sm:ml-0">
        <button type="submit"
            class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95"
            title="{{ __('Search') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="2.5">
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
                fetchTabData('logs', '{{ route('admin.sales.pass-codes') }}?tab=logs');
            "
            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95"
            title="{{ __('Reset') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
    </div>
</form>

{{-- Desktop Table Mode --}}
<div class="hidden xl:block overflow-x-auto">
    <table class="w-full text-left border-separate border-spacing-y-0">
        <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Machine / Pass Code') }}</th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                    {{ __('Action') }}</th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                    {{ __('Pass Code') }}</th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                    {{ __('Sales Record') }}</th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                    {{ __('Time') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            @forelse($logs as $log)
            <tr
                class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                <td class="px-6 py-5">
                    <div class="flex items-center gap-x-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                {{ $log->machine->name ?? $log->machine->serial_no ?? '-' }}
                            </span>
                            <span
                                class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wide mt-0.5">
                                {{ __('Code') }}: {{ $log->passCode->name ?? '-' }}
                            </span>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-5 text-center whitespace-nowrap">
                    <x-status-badge :status="$log->action" size="sm" />
                </td>
                <td
                    class="px-6 py-5 text-center font-mono font-extrabold text-slate-700 dark:text-slate-200">
                    <span
                        class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 tracking-tighter">
                        {{ $log->passCode->code ?? '-' }}
                    </span>
                </td>
                <td class="px-6 py-5 text-center whitespace-nowrap">
                    @if($log->order_id)
                        <a href="{{ route('admin.sales.index', ['tab' => 'orders', 'search' => $log->order?->order_no ?? $log->order_id]) }}" 
                           class="text-xs font-black text-cyan-600 dark:text-cyan-400 hover:underline flex items-center justify-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            {{ $log->order?->order_no ?? '#' . $log->order_id }}
                        </a>
                    @else
                        <span class="text-slate-300 dark:text-slate-700">-</span>
                    @endif
                </td>
                <td class="px-6 py-5 text-right whitespace-nowrap">
                    <span
                        class="text-xs font-mono font-black text-slate-700 dark:text-slate-200 tracking-widest">
                        {{ $log->created_at?->format('Y-m-d H:i:s') }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-20 text-center">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <div class="p-4 rounded-3xl bg-slate-50 dark:bg-slate-800/50">
                            <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">{{
                            __('No records found') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Card Mode (Mobile & Tablet) --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-4">
    @forelse($logs as $log)
    <div
        class="luxury-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div class="flex items-center gap-3 min-w-0">
                <div
                    class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3
                        class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 transition-colors tracking-tight">
                        {{ $log->machine->name ?? $log->machine->serial_no ?? '-' }}</h3>
                    <p
                        class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-widest truncate mt-0.5">
                        {{ __('Code') }}: {{ $log->passCode->name ?? '-' }}</p>
                </div>
            </div>
            <div class="shrink-0">
                <x-status-badge :status="$log->action" size="sm" />
            </div>
        </div>

        <div
            class="grid grid-cols-2 gap-y-4 mb-4 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{
                    __('Pass Code') }}</p>
                <p class="text-sm font-mono font-black text-slate-800 dark:text-slate-100">
                    {{ $log->passCode->code ?? '-' }}
                </p>
            </div>
            <div class="col-span-2">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{
                    __('Sales Record') }}</p>
                @if($log->order_id)
                    <a href="{{ route('admin.sales.index', ['tab' => 'orders', 'search' => $log->order?->order_no ?? $log->order_id]) }}" 
                       class="text-sm font-black text-cyan-600 dark:text-cyan-400 hover:underline">
                        {{ $log->order?->order_no ?? '#' . $log->order_id }}
                    </a>
                @else
                    <p class="text-sm font-bold text-slate-300 dark:text-slate-700">-</p>
                @endif
            </div>
            <div class="col-span-2">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{
                    __('Time') }}</p>
                <p
                    class="text-sm font-mono font-bold text-slate-800 dark:text-slate-100 tracking-tighter">
                    {{ $log->created_at?->format('Y-m-d H:i:s') }}</p>
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full">
        <x-empty-state :message="__('No records found')" />
    </div>
    @endforelse
</div>

{{-- Pagination --}}
<div class="mt-8 py-6 border-t border-slate-50 dark:border-slate-800/50">
    {{ $logs->links('vendor.pagination.luxury') }}
</div>
