<div class="luxury-card rounded-3xl p-8 animate-luxury-in">
    {{-- Filters --}}
    <form action="{{ route('admin.warehouses.inventory') }}" method="GET" class="flex flex-wrap items-center gap-3 sm:gap-4 mb-8"
        @submit.prevent="handleFilterSubmit('stock-in')">
        <input type="hidden" name="tab" value="stock-in">

        {{-- 搜尋輸入框 --}}
        <div class="relative group flex-1 sm:flex-none">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                    <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
            <input type="text" name="search_order" value="{{ request('search_order') }}"
                @keydown.enter.prevent="handleFilterSubmit('stock-in')"
                class="luxury-input py-2.5 pl-12 pr-6 block w-full sm:w-72 text-sm font-bold" placeholder="{{ __('Search order number...') }}">
        </div>

        {{-- 開始時間 --}}
        <div class="relative group w-full sm:w-56 sm:flex-none" 
             x-data="{ fp: null }" 
             x-init="fp = flatpickr($refs.startDate, { 
                dateFormat: 'Y-m-d H:i',
                enableTime: true,
                time_24hr: true,
                disableMobile: true,
                defaultHour: 0,
                defaultMinute: 0,
                locale: 'zh_tw',
                position: 'auto right',
                defaultDate: '{{ request('start_date', $stockInDefaultStart ?? '') }}'
             })">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </span>
            <input type="text" name="start_date" x-ref="startDate" 
                value="{{ request('start_date', $stockInDefaultStart ?? '') }}"
                placeholder="{{ __('Start Time') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer">
            <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none">{{ __('Start Time') }}</div>
        </div>

        {{-- 結束時間 --}}
        <div class="relative group w-full sm:w-56 sm:flex-none" 
             x-data="{ fp: null }" 
             x-init="fp = flatpickr($refs.endDate, { 
                dateFormat: 'Y-m-d H:i',
                enableTime: true,
                time_24hr: true,
                disableMobile: true,
                defaultHour: 23,
                defaultMinute: 59,
                locale: 'zh_tw',
                position: 'auto right',
                defaultDate: '{{ request('end_date', $stockInDefaultEnd ?? '') }}'
             })">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </span>
            <input type="text" name="end_date" x-ref="endDate" 
                value="{{ request('end_date', $stockInDefaultEnd ?? '') }}"
                placeholder="{{ __('End Time') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer">
            <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none">{{ __('End Time') }}</div>
        </div>

        {{-- 狀態篩選 --}}
        @if(isset($stock_in_status_options))
        <div class="w-full sm:w-48">
            <x-searchable-select 
                name="status" 
                :selected="request('status')" 
                :placeholder="__('All Status')"
                x-on:change="handleFilterSubmit('stock-in')"
            >
                @foreach($stock_in_status_options as $key => $label)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </x-searchable-select>
        </div>
        @endif

        @if(auth()->user()->isSystemAdmin())
        <div class="w-full sm:w-72">
            <x-searchable-select
                name="company_id"
                :options="$companies"
                :selected="request('company_id')"
                :placeholder="__('All Companies')"
                x-on:change="handleFilterSubmit('stock-in')"
            />
        </div>
        @endif

        {{-- 搜尋 + 重置按鈕 --}}
        <div class="flex items-center gap-2 ml-auto sm:ml-0">
            <button type="submit" class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95" title="{{ __('Search') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>

            <button type="button" 
                @click="
                    $el.closest('form').querySelectorAll('input').forEach(i => {
                        if (i.name === 'start_date' && i._flatpickr) i._flatpickr.setDate('{{ $stockInDefaultStart }}');
                        else if (i.name === 'end_date' && i._flatpickr) i._flatpickr.setDate('{{ $stockInDefaultEnd }}');
                        else if (i._flatpickr) i._flatpickr.clear();
                        else if (i.name !== 'tab' && i.name !== 'per_page') i.value = '';
                    });
                    $el.closest('form').querySelectorAll('select').forEach(s => {
                        s.value = ' ';
                        const instance = window.HSSelect?.getInstance(s);
                        if (instance) instance.setValue(' ');
                    });
                    handleFilterSubmit('stock-in');
                "
                class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95" title="{{ __('Reset') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </form>
    <div class="flex items-center gap-2 px-1 mb-6 -mt-4">
        <div class="w-1.5 h-1.5 rounded-full bg-cyan-500/50"></div>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            {{ __('Showing last 6 months by default when no date range is selected') }}
        </span>
    </div>

    {{-- Table Mode (Desktop) --}}
    <div class="hidden xl:block overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-y-0">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('Order Info') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('Warehouse') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Status') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Items') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Date') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                @forelse($orders as $order)
                <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                    <td class="px-6 py-5 cursor-pointer" @click="openOrderDetails('{{ $order->id }}')">
                        <div class="flex items-center gap-x-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                    {{ $order->order_no }}
                                </span>
                                @if($order->note)
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-0.5 tracking-wide">
                                    {{ Str::limit($order->note, 30) }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                                {{ $order->warehouse?->name }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-5 text-center whitespace-nowrap">
                        @if($order->status === 'draft')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black bg-amber-500/10 text-amber-500 border border-amber-500/20 uppercase tracking-widest shadow-sm shadow-amber-500/5">
                            {{ __('Draft') }}
                        </span>
                        @elseif($order->status === 'cancelled')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black bg-slate-500/10 text-slate-500 border border-slate-500/20 uppercase tracking-widest shadow-sm">
                            {{ __('Cancelled') }}
                        </span>
                        @else
                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 uppercase tracking-widest shadow-sm shadow-emerald-500/5">
                            {{ __('Completed') }}
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-5 text-center whitespace-nowrap">
                        <span class="font-black text-slate-800 dark:text-white">{{ $order->items_count }}</span>
                    </td>
                    <td class="px-6 py-5 text-center whitespace-nowrap">
                        <span class="text-xs font-mono font-black text-slate-500 dark:text-slate-400 tracking-widest">
                            {{ $order->created_at->format('Y-m-d H:i:s') }}
                        </span>
                    </td>
                    <td class="px-6 py-5 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" @click="openOrderDetails('{{ $order->id }}')"
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                title="{{ __('View Details') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </button>
                            
                            @if($order->status === 'draft')
                            {{-- Confirm Button --}}
                            <form action="{{ route('admin.warehouses.inventory.stock-in.confirm', $order) }}" method="POST" class="inline" @submit.prevent="confirmStockIn($el)">
                                @csrf @method('PATCH')
                                <button type="submit" 
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all border border-transparent hover:border-emerald-500/20"
                                    title="{{ __('Confirm Stock-In') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </form>

                            {{-- Delete/Cancel Button --}}
                            <button type="button" @click="confirmDelete('{{ route('admin.warehouses.inventory.stock-in.destroy', $order) }}')"
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all border border-transparent hover:border-rose-500/20"
                                title="{{ __('Delete Draft') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No stock-in orders found') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Card Mode (Mobile & Tablet) --}}
    <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        @forelse($orders as $order)
        <div class="luxury-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group cursor-pointer"
             @click="openOrderDetails('{{ $order->id }}')">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 break-all group-hover:text-cyan-600 transition-colors tracking-tight">{{ $order->order_no }}</h3>
                        <p class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-widest truncate mt-0.5">{{ $order->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                </div>
                <div class="shrink-0">
                    @if($order->status === 'draft')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-amber-500/10 text-amber-500 border border-amber-500/20 uppercase tracking-widest shadow-sm">
                            {{ __('Draft') }}
                        </span>
                    @elseif($order->status === 'cancelled')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-slate-500/10 text-slate-500 border border-slate-500/20 uppercase tracking-widest shadow-sm">
                            {{ __('Cancelled') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 uppercase tracking-widest shadow-sm">
                            {{ __('Completed') }}
                        </span>
                    @endif
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-y-4 mb-5 border-y border-slate-100 dark:border-slate-800/50 py-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Warehouse') }}</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">{{ $order->warehouse?->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Items') }}</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $order->items_count }}</p>
                </div>
                @if($order->note)
                <div class="col-span-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Note') }}</p>
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400 italic line-clamp-1">"{{ $order->note }}"</p>
                </div>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <button type="button" @click="openOrderDetails('{{ $order->id }}')"
                   class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-[10px] sm:text-xs tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-300 border border-slate-200 dark:border-slate-700 shadow-sm">
                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    {{ __('Details') }}
                </button>
                @if($order->status === 'draft')
                    <form action="{{ route('admin.warehouses.inventory.stock-in.confirm', $order) }}" method="POST" class="flex-1" @submit.prevent="confirmStockIn($el)">
                        @csrf @method('PATCH')
                        <button type="submit" 
                            class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-emerald-500/10 text-emerald-500 font-black text-xs uppercase tracking-widest hover:bg-emerald-500 hover:text-white transition-all duration-300 border border-emerald-500/20 shadow-sm">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            {{ __('Confirm') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full py-20 text-center flex flex-col items-center gap-4 bg-white/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800/50 luxury-card">
            <div class="flex flex-col items-center gap-3">
                <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No stock-in orders found') }}</p>
            </div>
        </div>
        @endforelse
    </div>

    <div class="mt-8 py-6 border-t border-slate-50 dark:border-slate-800/50">
        @if($orders->total() > 0)
            {{ $orders->links('vendor.pagination.luxury', ['ajax_navigate_event' => 'ajax:navigate:stock-in']) }}
        @else
            <div class="flex items-center justify-between gap-4 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                <span>{{ __('No records found') }}</span>
                <span>0 / 0</span>
            </div>
        @endif
    </div>
</div>
