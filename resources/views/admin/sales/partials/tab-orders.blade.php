{{-- Search Bar - 對齊取貨碼風格 --}}
<form action="{{ route('admin.sales.index') }}" method="GET"
    class="flex flex-col lg:flex-row lg:items-center flex-wrap gap-3 mb-8" @submit.prevent="fetchTabData('orders')">

    <input type="hidden" name="tab" value="orders">

    <div class="relative group flex-1 w-full min-w-0 lg:min-w-[280px]">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
            <svg class="w-4 h-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors stroke-[2.5]"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </span>
        <input type="text" name="search" value="{{ $tab === 'orders' ? $filters['search'] : '' }}"
            class="py-2.5 pl-12 pr-6 block w-full luxury-input"
            placeholder="{{ __('Search Order No / Cards & Codes...') }}">
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

    {{-- 付款狀態 --}}
    <div class="w-full lg:w-44">
        {{-- 不傳 placeholder：避免產生 value=" " 的空值選項；「所有付款狀態」改為明確的 value=all 選項，預設為「已完成」 --}}
        <x-searchable-select name="status" :selected="$filters['status']"
            :has-search="false" @change="$el.closest('form').dispatchEvent(new Event('submit'))">
            <option value="all" {{ $filters['status']==='all' ? 'selected' : '' }} data-title="{{ __('All Payment Statuses') }}">{{ __('All Payment Statuses') }}</option>
            <option value="pending" {{ $filters['status']==='pending' ? 'selected' : '' }} data-title="{{ __('Pending') }}">{{ __('Pending') }}</option>
            <option value="paid" {{ $filters['status']==='paid' ? 'selected' : '' }} data-title="{{ __('Paid') }}">{{ __('Paid') }}</option>
            <option value="completed" {{ $filters['status']==='completed' ? 'selected' : '' }} data-title="{{ __('Completed') }}">{{ __('Completed') }}</option>
            <option value="awaiting_pickup" {{ $filters['status']==='awaiting_pickup' ? 'selected' : '' }} data-title="{{ __('Awaiting Pickup') }}">{{ __('Awaiting Pickup') }}</option>
            <option value="failed" {{ $filters['status']==='failed' ? 'selected' : '' }} data-title="{{ __('Failed') }}">{{ __('Failed') }}</option>
            <option value="abandoned" {{ $filters['status']==='abandoned' ? 'selected' : '' }} data-title="{{ __('Unpaid') }}">{{ __('Unpaid') }}</option>
            <option value="cancelled" {{ $filters['status']==='cancelled' ? 'selected' : '' }} data-title="{{ __('Cancelled') }}">{{ __('Cancelled') }}</option>
            <option value="refunded" {{ $filters['status']==='refunded' ? 'selected' : '' }} data-title="{{ __('Refunded') }}">{{ __('Refunded') }}</option>
        </x-searchable-select>
    </div>

    {{-- 出貨狀態 --}}
    <div class="w-full lg:w-44">
        <x-searchable-select name="delivery_status" :placeholder="__('All Dispense Statuses')"
            :selected="$filters['delivery_status']" :has-search="false"
            @change="$el.closest('form').dispatchEvent(new Event('submit'))">
            <option value="1" {{ (string)$filters['delivery_status']==='1' ? 'selected' : '' }} data-title="{{ __('Dispense Success') }}">{{ __('Dispense Success') }}</option>
            <option value="2" {{ (string)$filters['delivery_status']==='2' ? 'selected' : '' }} data-title="{{ __('Partial Dispense Success') }}">{{ __('Partial Dispense Success') }}</option>
            <option value="0" {{ (string)$filters['delivery_status']==='0' ? 'selected' : '' }} data-title="{{ __('Dispense Failed') }}">{{ __('Dispense Failed') }}</option>
        </x-searchable-select>
    </div>

    {{-- 開始時間 --}}
    <div class="relative group w-full lg:w-56 lg:flex-none" 
         x-data="{ fp: null }" 
         x-init="fp = flatpickr($refs.startDate, { 
            dateFormat: 'Y-m-d H:i',
            enableTime: true,
            time_24hr: true,
            disableMobile: true,
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
            dateFormat: 'Y-m-d H:i',
            enableTime: true,
            time_24hr: true,
            disableMobile: true,
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
        @if(auth()->user()->isSystemAdmin())
        {{-- 手動補單：機台斷線/漏報時人工補登銷售紀錄（僅系統管理員） --}}
        <button type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-manual-order'))"
            class="px-3.5 py-2.5 rounded-xl bg-slate-800 dark:bg-slate-700 text-white hover:bg-slate-900 dark:hover:bg-slate-600 shadow-lg transition-all active:scale-95 flex items-center gap-2 text-xs font-bold whitespace-nowrap"
            title="{{ __('Manual Order Entry') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">{{ __('Manual Entry') }}</span>
        </button>
        @endif
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
                switchTab('orders', '{{ route('admin.sales.index', ['tab' => 'orders']) }}');
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
                    {{ __('Order Number / Time') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Machine') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Product') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Payment Amount') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Payment Status') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Dispense Status') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Pickup Code') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Invoice Number') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Remark') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                    {{ __('Action') }}
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse($orders as $order)
            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                <td class="px-6 py-6">
                    <div class="flex flex-col">
                        <span @click.stop="openDetail({{ $order->id }})"
                            class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors cursor-pointer">
                            {{ $order->order_no }}
                        </span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                {{ $order->created_at->format('Y-m-d H:i:s') }}
                            </span>
                            @if($order->is_manual)
                            <span class="px-1.5 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-[9px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest">{{ __('Manual') }}</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-6">
                    <div class="text-sm font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">
                        {{ $order->machine->name ?? 'Unknown' }}
                    </div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">
                        {{ $order->machine->serial_no ?? '---' }}
                    </div>
                </td>
                <td class="px-6 py-6">
                    <div class="flex flex-col max-w-[200px]">
                        @if($order->items->count() > 0)
                        <span class="text-sm font-black text-cyan-600 dark:text-cyan-400 mb-0.5 truncate">
                            {{ $order->items->first()->product_name }}
                        </span>
                        @if($order->items->count() > 1)
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ __('and :count other items', ['count' => $order->items->count()]) }}
                        </span>
                        @endif
                        @else
                        <span class="text-sm text-slate-400 italic">{{ __('No product record found') }}</span>
                        @endif
                    </div>
                </td>
                <td class="px-6 py-6">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5">
                            <span class="text-base font-black text-slate-800 dark:text-white tracking-tight">
                                ${{ number_format($order->total_amount, 0) }}
                            </span>
                            <span
                                class="text-[10px] px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest border border-slate-200 dark:border-slate-700">
                                {{ $order->payment_type_label }}
                            </span>
                        </div>
                        @if($order->discount_amount > 0)
                        <span class="text-[10px] text-rose-500 font-bold uppercase tracking-widest mt-1">
                            {{ __('Discounted') }} ${{ number_format($order->discount_amount, 0) }}
                        </span>
                        @endif
                        @if($order->cash_received_summary)
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mt-1 tracking-tight leading-relaxed">
                            {{ $order->cash_received_summary }}
                        </span>
                        @endif
                        @if((int) $order->payment_type === 6 && !empty($order->member_barcode))
                        {{-- 取貨碼交易：於支付金額欄換行加註取貨碼序號 --}}
                        <span class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 mt-1.5 flex items-center gap-1">
                            {{ __('Pickup Code') }}:
                            <span class="font-mono tracking-tighter normal-case text-slate-700 dark:text-slate-200">{{ $order->member_barcode }}</span>
                        </span>
                        @endif
                        @if($order->payment_type == 41 && $order->staffCardLog && $order->staffCardLog->staffCard)
                        <span class="text-[10px] text-cyan-600 dark:text-cyan-400 font-extrabold mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                            </svg>
                            {{ $order->staffCardLog->staffCard->name }} ({{ $order->staffCardLog->staffCard->employee_id }})
                        </span>
                        @endif
                        @if($order->payment_type == 42 && !empty($order->member_barcode))
                        <span class="text-[10px] text-cyan-600 dark:text-cyan-400 font-extrabold mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                            </svg>
                            {{ $order->masked_pickup_recipient }}
                        </span>
                        @endif
                    </div>
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    @php
                    $statusMap = [
                    'pending' => ['label' => __('Pending'), 'color' => 'amber'],
                    'paid' => ['label' => __('Paid'), 'color' => 'emerald'],
                    'completed' => ['label' => __('Completed'), 'color' => 'emerald'],
        'awaiting_pickup' => ['label' => __('Awaiting Pickup'), 'color' => 'amber'],
                    'awaiting_pickup' => ['label' => __('Awaiting Pickup'), 'color' => 'amber'],
                    'failed' => ['label' => __('Failed'), 'color' => 'rose'],
                    'abandoned' => ['label' => __('Unpaid'), 'color' => 'slate'],
                    'cancelled' => ['label' => __('Cancelled'), 'color' => 'slate'],
                    'refunded' => ['label' => __('Refunded'), 'color' => 'rose'],
                    ];
                    $s = $statusMap[$order->status] ?? ['label' => $order->status, 'color' => 'slate'];
                    @endphp
                    <x-status-badge :color="$s['color']" :label="$s['label']" size="xs" />
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    {{-- 非付款成功(未完成/支付失敗/未支付)的單從未出貨，出貨狀態無意義 → 顯示 '--' --}}
                    @if(! $order->hasDeliveryOutcome())
                    <span class="text-xs font-bold text-slate-300 dark:text-slate-700 tracking-widest">--</span>
                    @else
                    @php
                    $deliveryStatusMap = [
                        0 => ['label' => __('Dispense Failed'), 'color' => 'rose'],
                        1 => ['label' => __('Dispense Success'), 'color' => 'emerald'],
                        2 => ['label' => __('Partial Dispense Success'), 'color' => 'amber'],
                    ];
                    $ds = $deliveryStatusMap[$order->delivery_status] ?? ['label' => __('Unknown'), 'color' => 'slate'];
                    @endphp
                    <x-status-badge :color="$ds['color']" :label="$ds['label']" size="xs" />
                    @endif
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    @php
                        $qualifiesForPickup = in_array($order->status, ['completed','paid'], true) && in_array((int) $order->delivery_status, [0, 2], true);
                        $orderPickupCodes = $order->relationLoaded('compensationCodes') ? $order->compensationCodes : collect();
                    @endphp
                    @if($orderPickupCodes->count())
                    <div class="flex flex-col gap-1">
                        @foreach($orderPickupCodes as $cc)
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-black">
                            <span class="font-mono tracking-tighter px-1.5 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 text-slate-700 dark:text-slate-200">{{ $cc->code }}</span>
                            @if($cc->status === 'active' && $cc->usage_count < $cc->usage_limit)
                            <span class="px-1.5 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-[9px] text-amber-600 dark:text-amber-400 uppercase tracking-widest">{{ __('Pending Use') }}</span>
                            @else
                            <span class="px-1.5 py-0.5 rounded bg-slate-500/10 border border-slate-500/20 text-[9px] text-slate-500 uppercase tracking-widest">{{ __('Used') }}</span>
                            @endif
                        </span>
                        @endforeach
                    </div>
                    @elseif($qualifiesForPickup)
                    <button type="button" @click="generatePickupCode({{ $order->id }})"
                        title="{{ __('Generate Pickup Code') }}"
                        class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 flex items-center justify-center transition-all active:scale-95 border border-emerald-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                    @else
                    <span class="text-xs font-bold text-slate-300 dark:text-slate-700 tracking-widest">--</span>
                    @endif
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    @php
                    $invoiceSearchValue = $order->invoice ? $order->invoice->invoice_no : $order->order_no;
                    $targetUrl = route('admin.sales.index', ['tab' => 'invoices', 'search' => $invoiceSearchValue]);
                    @endphp
                    @if($order->invoice)
                    @php $invVoided = $order->invoice->status === 'void'; @endphp
                    <div @click.stop="switchTab('invoices', '{{ $targetUrl }}')"
                        class="flex items-center gap-1.5 text-sm font-extrabold cursor-pointer hover:text-cyan-500 transition-colors group/invoice {{ $invVoided ? 'text-slate-400 dark:text-slate-600' : 'text-slate-600 dark:text-slate-400' }}"
                        title="{{ __('Go to E-Invoice') }}">
                        <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover/invoice:text-cyan-500"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="group-hover/invoice:underline decoration-2 underline-offset-4 {{ $invVoided ? 'line-through' : '' }}">{{
                            $order->invoice->invoice_no }}</span>
                        @if($invVoided)
                        <span class="px-1.5 py-0.5 rounded bg-slate-500/10 border border-slate-500/20 text-[9px] font-black text-slate-500 uppercase tracking-widest no-underline">{{ __('Voided') }}</span>
                        @endif
                    </div>
                    @else
                    <span class="text-xs font-bold text-slate-300 dark:text-slate-700 tracking-widest">---</span>
                    @endif
                </td>
                <td class="px-6 py-6">
                    <x-remark-cell :url="route('admin.sales.orders.remark', $order)" :value="$order->remark" />
                </td>
                <td class="px-6 py-6 text-right">
                    <button @click.stop="openDetail({{ $order->id }})"
                        class="p-2.5 rounded-xl text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-transparent hover:border-cyan-500/20 transition-all group/btn"
                        title="{{ __('View Details') }}">
                        <svg class="w-4 h-4 stroke-[2.5] transition-transform group-hover/btn:scale-110" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="py-20 text-center">
                    <x-empty-state mode="table" colspan="10" :message="__('No transaction orders found')" />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Mobile Cards (xl:hidden) --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse($orders as $order)
    <div
        class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
        @php
        $statusMap = [
        'pending' => ['label' => __('Pending'), 'color' => 'amber'],
        'paid' => ['label' => __('Paid'), 'color' => 'emerald'],
        'completed' => ['label' => __('Completed'), 'color' => 'emerald'],
        'failed' => ['label' => __('Failed'), 'color' => 'rose'],
        'abandoned' => ['label' => __('Unpaid'), 'color' => 'slate'],
        'cancelled' => ['label' => __('Cancelled'), 'color' => 'slate'],
        'refunded' => ['label' => __('Refunded'), 'color' => 'rose'],
        ];
        $s = $statusMap[$order->status] ?? ['label' => $order->status, 'color' => 'slate'];
        $invoiceSearchValue = $order->invoice ? $order->invoice->invoice_no : $order->order_no;
        $targetUrl = route('admin.sales.index', ['tab' => 'invoices', 'search' => $invoiceSearchValue]);
        @endphp

        {{-- Card Header --}}
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 min-w-0">
                <div
                    class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 @click="openDetail({{ $order->id }})"
                        class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight cursor-pointer">
                        {{ $order->order_no }}
                        @if($order->is_manual)
                        <span class="px-1.5 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-[9px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest align-middle">{{ __('Manual') }}</span>
                        @endif
                    </h3>
                    <p
                        class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                        {{ $order->machine->name ?? 'Unknown' }}
                    </p>
                </div>
            </div>
            <div @click.stop="switchTab('invoices', '{{ $targetUrl }}')" class="cursor-pointer">
                <x-status-badge :color="$s['color']" :label="$s['label']" size="sm" />
            </div>
        </div>

        {{-- Info Grid --}}
        <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Order Items') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">
                    @if($order->items->count() > 0)
                    {{ $order->items->first()->product_name }}
                    @if($order->items->count() > 1)
                    <span class="text-[10px] text-slate-400 font-bold ml-1">{{ __('and :count other items', ['count' =>
                        $order->items->count()]) }}</span>
                    @endif
                    @else
                    <span class="text-slate-400 italic">{{ __('No product record found') }}</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Amount / Payment') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span class="text-cyan-600 dark:text-cyan-400">${{ number_format($order->total_amount, 0) }}</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800/50 text-slate-500">{{
                        $paymentTypes[$order->payment_type] ?? '??' }}</span>
                </p>
                @if($order->cash_received_summary)
                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mt-1 leading-relaxed">
                    {{ $order->cash_received_summary }}
                </div>
                @endif
                @if($order->payment_type == 41 && $order->staffCardLog && $order->staffCardLog->staffCard)
                <div class="text-[10px] text-cyan-600 dark:text-cyan-400 font-extrabold mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                    </svg>
                    {{ $order->staffCardLog->staffCard->name }} ({{ $order->staffCardLog->staffCard->employee_id }})
                </div>
                @endif
                @if($order->payment_type == 42 && !empty($order->member_barcode))
                <div class="text-[10px] text-cyan-600 dark:text-cyan-400 font-extrabold mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                    </svg>
                    {{ $order->masked_pickup_recipient }}
                </div>
                @endif
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Order Time') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                    {{ $order->created_at->format('m-d H:i') }}
                    <span class="text-[10px] text-slate-400 ml-1">{{ $order->created_at->diffForHumans() }}</span>
                </p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Invoice Number') }}</p>
                @php $invVoided = $order->invoice && $order->invoice->status === 'void'; @endphp
                <p @click.stop="switchTab('invoices', '{{ $targetUrl }}')"
                    class="text-sm font-bold cursor-pointer hover:text-cyan-500 transition-colors flex items-center gap-1.5 {{ $invVoided ? 'text-slate-400 dark:text-slate-600' : 'text-slate-700 dark:text-slate-300' }}">
                    <span class="{{ $invVoided ? 'line-through' : '' }}">{{ $order->invoice->invoice_no ?? '---' }}</span>
                    @if($invVoided)
                    <span class="px-1.5 py-0.5 rounded bg-slate-500/10 border border-slate-500/20 text-[9px] font-black text-slate-500 uppercase tracking-widest">{{ __('Voided') }}</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Dispense Status') }}</p>
                {{-- 非付款成功(未完成/支付失敗/未支付)的單從未出貨，出貨狀態無意義 → 顯示 '--' --}}
                @if(! $order->hasDeliveryOutcome())
                <span class="text-xs font-bold text-slate-300 dark:text-slate-700 tracking-widest">--</span>
                @else
                @php
                $deliveryStatusMap = [
                    0 => ['label' => __('Dispense Failed'), 'color' => 'rose'],
                    1 => ['label' => __('Dispense Success'), 'color' => 'emerald'],
                    2 => ['label' => __('Partial Dispense Success'), 'color' => 'amber'],
                ];
                $ds = $deliveryStatusMap[$order->delivery_status] ?? ['label' => __('Unknown'), 'color' => 'slate'];
                @endphp
                <x-status-badge :color="$ds['color']" :label="$ds['label']" size="xs" />
                @endif
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Pickup Code') }}</p>
                @php
                    $qualifiesForPickup = in_array($order->status, ['completed','paid'], true) && in_array((int) $order->delivery_status, [0, 2], true);
                    $orderPickupCodes = $order->relationLoaded('compensationCodes') ? $order->compensationCodes : collect();
                @endphp
                @if($orderPickupCodes->count())
                <div class="flex flex-col gap-1">
                    @foreach($orderPickupCodes as $cc)
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-black">
                        <span class="font-mono tracking-tighter px-1.5 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 text-slate-700 dark:text-slate-200">{{ $cc->code }}</span>
                        @if($cc->status === 'active' && $cc->usage_count < $cc->usage_limit)
                        <span class="px-1.5 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-[9px] text-amber-600 dark:text-amber-400 uppercase tracking-widest">{{ __('Pending Use') }}</span>
                        @else
                        <span class="px-1.5 py-0.5 rounded bg-slate-500/10 border border-slate-500/20 text-[9px] text-slate-500 uppercase tracking-widest">{{ __('Used') }}</span>
                        @endif
                    </span>
                    @endforeach
                </div>
                @elseif($qualifiesForPickup)
                <button type="button" @click="generatePickupCode({{ $order->id }})"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 text-xs font-black uppercase tracking-widest transition-all active:scale-95 border border-emerald-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('Generate Pickup Code') }}
                </button>
                @else
                <span class="text-xs font-bold text-slate-300 dark:text-slate-700 tracking-widest">--</span>
                @endif
            </div>
        </div>

        {{-- Remark --}}
        <div class="mb-6">
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Remark') }}</p>
            <x-remark-cell :url="route('admin.sales.orders.remark', $order)" :value="$order->remark" />
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-3">
            <button @click="openDetail({{ $order->id }})"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest border border-slate-100 dark:border-slate-800 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all duration-300">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                {{ __('View Details') }}
            </button>
        </div>
    </div>
    @empty
    <div class="col-span-full py-10">
        <x-empty-state :message="__('No transaction orders found')" />
    </div>
    @endforelse
</div>

{{-- Pagination --}}
<div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
    {{ $orders->links('vendor.pagination.luxury', ['page_param' => 'orders_page']) }}
</div>
