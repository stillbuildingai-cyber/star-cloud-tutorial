{{-- Search Bar --}}
<form action="{{ route('admin.sales.index') }}" method="GET"
    class="flex flex-col lg:flex-row lg:items-center flex-wrap gap-3 mb-8" @submit.prevent="fetchTabData('invoices')">

    <input type="hidden" name="tab" value="invoices">

    <div class="relative group flex-1 w-full min-w-0 lg:min-w-[280px]">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
            <svg class="w-4 h-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors stroke-[2.5]"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </span>
        <input type="text" name="search" value="{{ $tab === 'invoices' ? $filters['search'] : '' }}"
            class="py-2.5 pl-12 pr-6 block w-full luxury-input"
            placeholder="{{ __('Search Invoice No / Flow ID...') }}">
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
                switchTab('invoices', '{{ route('admin.sales.index', ['tab' => 'invoices']) }}');
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
                    {{ __('Invoice Number') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Invoice Date') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Machine') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Amount') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Status') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Associated Order') }}
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
            @forelse($invoices as $invoice)
            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200 cursor-pointer"
                @click="openDetail({{ $invoice->order_id }})">
                <td class="px-6 py-6">
                    <div class="flex flex-col">
                        <span
                            class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                            {{ $invoice->invoice_no }}
                        </span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                            {{ ($invoice->machine_time ?? $invoice->created_at)->format('Y-m-d H:i:s') }}
                        </span>
                    </div>
                </td>
                <td class="px-6 py-6">
                    <span
                        class="text-sm font-bold text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/50 px-2 py-1 rounded-md">
                        {{ $invoice->invoice_date }}
                    </span>
                </td>
                <td class="px-6 py-6">
                    <div class="text-sm font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">
                        {{ $invoice->machine->name ?? ($invoice->order->machine->name ?? 'Unknown') }}
                    </div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">
                        {{ $invoice->machine->serial_no ?? ($invoice->order->machine->serial_no ?? '---') }}
                    </div>
                </td>
                <td class="px-6 py-6">
                    <span class="text-base font-black text-slate-800 dark:text-white tracking-tight">
                        ${{ number_format($invoice->amount, 0) }}
                    </span>
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    @php
                    // 以發票狀態機顯示（pending/issued/failed/void）；舊資料無 status 時以發票號回推
                    $st = $invoice->status ?: (empty($invoice->invoice_no) ? 'failed' : 'issued');
                    $statusMap = [
                    'pending' => ['label' => __('Pending'), 'color' => 'amber'],
                    'issued' => ['label' => __('Issued'), 'color' => 'emerald'],
                    'failed' => ['label' => __('Failed'), 'color' => 'rose'],
                    'void' => ['label' => __('Void'), 'color' => 'slate'],
                    ];
                    $s = $statusMap[$st] ?? ['label' => $st, 'color' => 'slate'];
                    @endphp
                    <x-status-badge :color="$s['color']" :label="$s['label']" size="xs" />
                </td>
                <td class="px-6 py-6">
                    <span
                        class="text-sm font-black text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800 px-3 py-1 rounded-lg border border-slate-100 dark:border-slate-700">
                        {{ $invoice->order->order_no ?? '---' }}
                    </span>
                </td>
                <td class="px-6 py-6">
                    <x-remark-cell :url="route('admin.sales.invoices.remark', $invoice)" :value="$invoice->remark" />
                </td>
                <td class="px-6 py-6 text-right" @click.stop>
                    <div class="flex items-center justify-end gap-1.5">
                        @if(in_array($st, ['pending', 'failed']))
                        {{-- 查詢（對帳） --}}
                        <form method="POST" action="{{ route('admin.sales.invoices.reconcile', $invoice) }}">
                            @csrf
                            <button type="submit" title="{{ __('Query ECPay') }}"
                                class="p-2 rounded-xl text-slate-400 hover:text-amber-500 hover:bg-amber-500/5 dark:hover:bg-amber-500/10 border border-transparent hover:border-amber-500/20 transition-all">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                            </button>
                        </form>
                        {{-- 補開：使用自製確認框 UI --}}
                        <form id="invoice-reissue-form-{{ $invoice->id }}" method="POST" action="{{ route('admin.sales.invoices.reissue', $invoice) }}" class="hidden">
                            @csrf
                        </form>
                        <button type="button" title="{{ __('Re-issue') }}"
                            @click="confirmReissueInvoice('invoice-reissue-form-{{ $invoice->id }}')"
                            class="p-2 rounded-xl text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-transparent hover:border-cyan-500/20 transition-all">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                        @endif
                        {{-- 作廢按鈕僅在「訂單詳情」提供，列表操作欄不再顯示 --}}
                        <button @click="openDetail({{ $invoice->order_id }})"
                            class="p-2 rounded-xl text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-transparent hover:border-cyan-500/20 transition-all">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="py-20 text-center">
                    <x-empty-state mode="table" colspan="8" :message="__('No invoices found')" />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Mobile Cards (xl:hidden) --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse($invoices as $invoice)
    <div
        class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
        @php
        $st = $invoice->status ?: (empty($invoice->invoice_no) ? 'failed' : 'issued');
        $statusMap = [
        'pending' => ['label' => __('Pending'), 'color' => 'amber'],
        'issued' => ['label' => __('Issued'), 'color' => 'emerald'],
        'failed' => ['label' => __('Failed'), 'color' => 'rose'],
        'void' => ['label' => __('Void'), 'color' => 'slate'],
        ];
        $s = $statusMap[$st] ?? ['label' => $st, 'color' => 'slate'];
        @endphp

        {{-- Card Header --}}
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 min-w-0">
                <div
                    class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3
                        class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight">
                        {{ $invoice->invoice_no }}
                    </h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span
                            class="px-1.5 py-0.5 rounded bg-cyan-50 dark:bg-cyan-500/10 text-[9px] font-black text-cyan-600 dark:text-cyan-400 border border-cyan-100 dark:border-cyan-500/20">
                            {{ $invoice->invoice_date }}
                        </span>
                        <p
                            class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                            {{ ($invoice->machine_time ?? $invoice->created_at)->format('Y-m-d H:i:s') }}
                        </p>
                    </div>
                </div>
            </div>
            <x-status-badge :color="$s['color']" :label="$s['label']" size="sm" />
        </div>

        {{-- Info Grid --}}
        <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Machine') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">
                    {{ $invoice->machine->name ?? ($invoice->order->machine->name ?? 'Unknown') }}
                </p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                    {{ $invoice->machine->serial_no ?? ($invoice->order->machine->serial_no ?? '---') }}
                </p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Amount') }}</p>
                <p class="text-base font-black text-cyan-600 dark:text-cyan-400">${{ number_format($invoice->amount, 0)
                    }}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Associated Order') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">
                    {{ $invoice->order->order_no ?? '---' }}
                </p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{
                    __('Machine Time') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                    {{ ($invoice->machine_time ?? $invoice->created_at)->format('Y-m-d H:i:s') }}
                </p>
            </div>
        </div>

        {{-- Remark --}}
        <div class="mb-6">
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Remark') }}</p>
            <x-remark-cell :url="route('admin.sales.invoices.remark', $invoice)" :value="$invoice->remark" />
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col gap-3">
            <button @click="openDetail({{ $invoice->order_id }})"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest border border-slate-100 dark:border-slate-800 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all duration-300">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                {{ __('View Details') }}
            </button>
            @if(in_array($st, ['pending', 'failed']))
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('admin.sales.invoices.reconcile', $invoice) }}" class="flex-1">
                    @csrf
                    <button type="submit"
                        class="w-full py-2.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 font-black text-xs uppercase tracking-widest border border-amber-500/20">
                        {{ __('Query') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.sales.invoices.reissue', $invoice) }}" class="flex-1"
                    onsubmit="return confirm('{{ __('Re-issue this invoice?') }}')">
                    @csrf
                    <button type="submit"
                        class="w-full py-2.5 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-black text-xs uppercase tracking-widest border border-cyan-500/20">
                        {{ __('Re-issue') }}
                    </button>
                </form>
            </div>
            @endif
            @if($st === 'issued' && auth()->user()->isSystemAdmin())
            <form method="POST" action="{{ route('admin.sales.invoices.void', $invoice) }}"
                onsubmit="return confirm('{{ __('Void this invoice?') }}')">
                @csrf
                <button type="submit"
                    class="w-full py-2.5 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 font-black text-xs uppercase tracking-widest border border-rose-500/20">
                    {{ __('Void') }}
                </button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="col-span-full py-10">
        <x-empty-state :message="__('No invoices found')" />
    </div>
    @endforelse
</div>

{{-- Pagination --}}
<div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
    {{ $invoices->links('vendor.pagination.luxury', ['page_param' => 'invoices_page']) }}
</div>