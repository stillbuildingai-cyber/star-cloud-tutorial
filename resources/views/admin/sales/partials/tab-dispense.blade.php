{{-- Search Bar --}}
<form action="{{ route('admin.sales.index') }}" method="GET"
    class="flex flex-col lg:flex-row lg:items-center flex-wrap gap-3 mb-8" @submit.prevent="fetchTabData('dispense')">

    <input type="hidden" name="tab" value="dispense">

    <div class="relative group flex-1 w-full min-w-0 lg:min-w-[280px]">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
            <svg class="w-4 h-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors stroke-[2.5]"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </span>
        <input type="text" name="search" value="{{ $tab === 'dispense' ? $filters['search'] : '' }}"
            class="py-2.5 pl-12 pr-6 block w-full luxury-input" placeholder="{{ __('Search Product / Slot...') }}">
    </div>

    <div class="w-full lg:w-48">
        <x-searchable-select name="machine_id" :placeholder="__('All Machines')" :selected="$filters['machine_id']"
            @change="$el.closest('form').dispatchEvent(new Event('submit'))">
            @foreach($machines as $m)
            <option value="{{ $m->id }}" {{ $filters['machine_id']==$m->id ? 'selected' : '' }} data-title="{{ $m->name
                }}">
                {{ $m->name }}
            </option>
            @endforeach
        </x-searchable-select>
    </div>

    {{-- 開始時間 --}}
    <div class="relative group w-full lg:w-56 lg:flex-none" 
         x-data="{ fp: null }" 
         x-init="fp = flatpickr($refs.startDate, { 
            disableMobile: true,
            dateFormat: 'Y-m-d H:i',
            enableTime: true,
            time_24hr: true,
            defaultHour: 0,
            defaultMinute: 0,
            locale: '{{ app()->getLocale() == 'zh_TW' ? 'zh_tw' : 'en' }}',
            position: 'auto right',
            defaultDate: '{{ $filters['start_date'] }}'
         })">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </span>
        <input type="text" name="start_date" x-ref="startDate" 
            value="{{ $filters['start_date'] }}"
            placeholder="{{ __('Start Time') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer">
        <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none">{{ __('Start Time') }}</div>
    </div>

    {{-- 結束時間 --}}
    <div class="relative group w-full lg:w-56 lg:flex-none" 
         x-data="{ fp: null }" 
         x-init="fp = flatpickr($refs.endDate, { 
            disableMobile: true,
            dateFormat: 'Y-m-d H:i',
            enableTime: true,
            time_24hr: true,
            defaultHour: 23,
            defaultMinute: 59,
            locale: '{{ app()->getLocale() == 'zh_TW' ? 'zh_tw' : 'en' }}',
            position: 'auto right',
            defaultDate: '{{ $filters['end_date'] }}'
         })">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </span>
        <input type="text" name="end_date" x-ref="endDate" 
            value="{{ $filters['end_date'] }}"
            placeholder="{{ __('End Time') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer">
        <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none">{{ __('End Time') }}</div>
    </div>

    <div class="flex items-center gap-2 ml-auto lg:ml-0 shrink-0">
        <button type="submit"
            class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95"
            title="{{ __('Search') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>
        <button type="button" 
            @click="
                $el.closest('form').querySelectorAll('input').forEach(i => {
                    if (i._flatpickr) i._flatpickr.clear();
                    else if (i.name !== 'tab') i.value = '';
                });
                $el.closest('form').querySelectorAll('select').forEach(s => {
                    s.value = ' ';
                    const instance = window.HSSelect.getInstance(s);
                    if (instance) instance.setValue(' ');
                });
                switchTab('dispense', '{{ route('admin.sales.index', ['tab' => 'dispense']) }}');
            "
            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95 border border-slate-200 dark:border-slate-700"
            title="{{ __('Reset') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
        <!-- Exporters Dropdown -->
        <div class="relative flex items-center gap-2" x-data="{ exportOpen: false }">
            <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false"
                class="p-2.5 rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 shadow-lg shadow-emerald-500/25 transition-all active:scale-95 flex items-center gap-2 text-xs font-bold"
                title="{{ __('Export Report') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <svg class="w-3 h-3 text-white/80 transition-transform duration-200" :class="exportOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
            
            <!-- Exporter Menu -->
            <div x-show="exportOpen" x-transition
                 class="absolute right-0 top-full mt-2 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-xl rounded-2xl p-2 z-30 min-w-[160px]"
                 x-cloak>
                <button type="button" @click="copyTableData(); exportOpen = false" class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                    📋 {{ __('Copy to Clipboard') }}
                </button>
                <button type="button" @click="exportData('csv'); exportOpen = false" class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                    📄 {{ __('Export to CSV') }}
                </button>
                <button type="button" @click="exportData('excel'); exportOpen = false" class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                    📊 {{ __('Export to Excel') }}
                </button>
                <button type="button" @click="printTable(); exportOpen = false" class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                    🖨️ {{ __('Print & PDF') }}
                </button>
            </div>
        </div>
    </div>
</form>

@if($isImplicitFilter)
<div class="flex items-center gap-2 px-1 mb-6 -mt-4">
    <div class="w-1.5 h-1.5 rounded-full bg-cyan-500/50"></div>
    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
        {{ __('Showing last 6 months by default when no date range is selected') }}
    </span>
</div>
@endif

{{-- Table (Desktop) --}}
<div class="hidden xl:block overflow-x-auto">
    <table class="w-full text-left border-separate border-spacing-0">
        <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Machine') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Product / Slot') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Amount') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Dispense Status') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Dispense Time') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                    {{ __('Associated Order') }}
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse($dispenseLogs as $log)
            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200 {{ $log->order_id ? 'cursor-pointer' : '' }}"
                @click="if({{ $log->order_id ?? 'null' }}) openDetail({{ $log->order_id }})">
                <td class="px-6 py-6">
                    <div class="text-sm font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">
                        {{ $log->machine->name ?? 'Unknown' }}
                    </div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">
                        {{ $log->machine->serial_no ?? '---' }}
                    </div>
                </td>
                <td class="px-6 py-6">
                    <div class="text-sm font-black text-cyan-600 dark:text-cyan-400 mb-0.5">
                        {{ $log->product->localized_name ?? __('Unknown Product') }}
                    </div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                        {{ __('Slot') }}: {{ $log->slot_no }}
                    </div>
                </td>
                <td class="px-6 py-6">
                    <span class="text-sm font-black text-slate-700 dark:text-slate-200">
                        {{ $log->amount }}
                    </span>
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    @php
                    $statusMap = [
                    'success' => ['label' => __('Dispense Success'), 'color' => 'emerald'],
                    'failed' => ['label' => __('Dispense Failed'), 'color' => 'rose'],
                    'pending' => ['label' => __('Dispense Pending'), 'color' => 'amber'],
                    '1' => ['label' => __('Dispense Success'), 'color' => 'emerald'],
                    '0' => ['label' => __('Dispense Failed'), 'color' => 'rose'],
                    ];
                    $statusKey = (string)($log->dispense_status ?? 'pending');
                    $s = $statusMap[$statusKey] ?? ['label' => $statusKey, 'color' => 'slate'];
                    @endphp
                    <x-status-badge :color="$s['color']" :label="$s['label']" size="xs" />
                </td>
                <td class="px-6 py-6">
                    <span class="text-sm font-black text-slate-700 dark:text-slate-200">
                        {{ $log->machine_time }}
                    </span>
                </td>
                <td class="px-6 py-6 text-right">
                    @if($log->order)
                    <button @click.stop="openDetail({{ $log->order_id }})"
                        class="text-sm font-black text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-100 dark:border-slate-700 hover:text-cyan-500 transition-all">
                        {{ $log->order->order_no }}
                    </button>
                    @else
                    <span class="text-xs text-slate-300 italic">{{ __('No associated order found') }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="py-20 text-center">
                    <x-empty-state mode="table" colspan="6" :message="__('No dispense records found')" />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Mobile Cards (xl:hidden) --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse($dispenseLogs as $log)
    <div
        class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
        @php
        $statusMap = [
        'success' => ['label' => __('Dispense Success'), 'color' => 'emerald'],
        'failed' => ['label' => __('Dispense Failed'), 'color' => 'rose'],
        'pending' => ['label' => __('Dispense Pending'), 'color' => 'amber'],
        '1' => ['label' => __('Dispense Success'), 'color' => 'emerald'],
        '0' => ['label' => __('Dispense Failed'), 'color' => 'rose'],
        ];
        $statusKey = (string)($log->dispense_status ?? 'pending');
        $s = $statusMap[$statusKey] ?? ['label' => $statusKey, 'color' => 'slate'];
        @endphp

        {{-- Card Header --}}
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 min-w-0">
                <div
                    class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate tracking-tight">
                        {{ $log->machine->name ?? 'Unknown' }}
                    </h3>
                    <p
                        class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                        {{ $log->machine->serial_no ?? '---' }}
                    </p>
                </div>
            </div>
            <x-status-badge :color="$s['color']" :label="$s['label']" size="sm" />
        </div>

        {{-- Info Grid --}}
        <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Product / Slot') }}</p>
                <div class="flex flex-col gap-0.5 min-w-0">
                    <p class="text-sm font-black text-cyan-600 dark:text-cyan-400 truncate">
                        {{ $log->product->localized_name ?? __('Unknown Product') }}
                    </p>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        {{ __('Slot') }}: {{ $log->slot_no }}
                    </p>
                </div>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Amount') }}</p>
                <p class="text-sm font-black text-slate-700 dark:text-slate-300">{{ $log->amount }}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Dispense Time') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $log->machine_time }}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Associated Order') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">
                    {{ $log->order->order_no ?? '---' }}
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-3">
            @if($log->order_id)
            <button @click="openDetail({{ $log->order_id }})"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest border border-slate-100 dark:border-slate-800 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all duration-300">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                {{ __('View Order') }}
            </button>
            @else
            <div
                class="flex-1 py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-slate-50/50 dark:bg-slate-800/30 rounded-xl border border-dashed border-slate-200 dark:border-slate-700">
                {{ __('No related detail found') }}
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="col-span-full py-10">
        <x-empty-state :message="__('No dispense records found')" />
    </div>
    @endforelse
</div>

{{-- Pagination --}}
<div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
    {{ $dispenseLogs->links('vendor.pagination.luxury', ['page_param' => 'dispense_page']) }}
</div>