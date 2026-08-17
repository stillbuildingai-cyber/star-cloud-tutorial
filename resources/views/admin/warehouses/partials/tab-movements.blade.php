<div class="luxury-card rounded-3xl p-8 animate-luxury-in">
    <form action="{{ route('admin.warehouses.inventory') }}" method="GET" class="flex flex-wrap items-center gap-3 sm:gap-4 mb-8"
          @submit.prevent="handleFilterSubmit('movements')">
        <input type="hidden" name="tab" value="movements">

        @if(auth()->user()->isSystemAdmin())
        <div class="w-full sm:w-72">
            <x-searchable-select
                name="company_id"
                :options="$companies"
                :selected="request('company_id')"
                :placeholder="__('All Companies')"
                x-on:change="
                    const warehouseSelect = $el.closest('form').querySelector('select[name=warehouse_id]');
                    if (warehouseSelect) {
                        warehouseSelect.value = ' ';
                        const warehouseInstance = window.HSSelect?.getInstance(warehouseSelect);
                        if (warehouseInstance) warehouseInstance.setValue(' ');
                    }
                    handleFilterSubmit('movements');
                "
            />
        </div>
        @endif

        {{-- 倉庫篩選 --}}
        <div class="w-full sm:w-72">
            <x-searchable-select 
                name="warehouse_id" 
                :options="$warehouses" 
                :selected="request('warehouse_id')"
                :placeholder="__('All Warehouses')"
            />
        </div>

        {{-- 類型篩選 --}}
        <div class="w-full sm:w-72">
            <x-searchable-select 
                name="type" 
                :selected="request('type')"
                :placeholder="__('All Types')"
            >
                @foreach(\App\Models\Warehouse\StockMovement::TYPE_LABELS as $key => $label)
                    <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }} data-title="{{ __($label) }}">
                        {{ __($label) }}
                    </option>
                @endforeach
            </x-searchable-select>
        </div>

        {{-- 開始時間 --}}
        <div class="relative group w-full sm:w-56 sm:flex-none" 
             x-data="{ fp: null }" 
             x-init="fp = flatpickr($refs.startDate, { 
                dateFormat: 'Y-m-d H:i',
                enableTime: true,
                time_24hr: true,
                disableMobile: true,
                locale: 'zh_tw',
                position: 'auto right',
                defaultDate: '{{ request('start_date', $movementDefaultStart ?? '') }}'
             })">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </span>
            <input type="text" name="start_date" x-ref="startDate" 
                value="{{ request('start_date', $movementDefaultStart ?? '') }}"
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
                locale: 'zh_tw',
                position: 'auto right',
                defaultDate: '{{ request('end_date', $movementDefaultEnd ?? '') }}'
             })">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </span>
            <input type="text" name="end_date" x-ref="endDate" 
                value="{{ request('end_date', $movementDefaultEnd ?? '') }}"
                placeholder="{{ __('End Time') }}" class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer">
            <div class="absolute -top-2 left-4 px-1 bg-white dark:bg-slate-900 text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-0 group-focus-within:opacity-100 transition-opacity pointer-events-none">{{ __('End Time') }}</div>
        </div>

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
                        if (i.name === 'start_date' && i._flatpickr) i._flatpickr.setDate('{{ $movementDefaultStart }}');
                        else if (i.name === 'end_date' && i._flatpickr) i._flatpickr.setDate('{{ $movementDefaultEnd }}');
                        else if (i._flatpickr) i._flatpickr.clear();
                        else if (i.name !== 'tab' && i.name !== 'per_page') i.value = '';
                    });
                    $el.closest('form').querySelectorAll('select').forEach(s => {
                        s.value = ' ';
                        const instance = window.HSSelect?.getInstance(s);
                        if (instance) instance.setValue(' ');
                    });
                    $el.closest('form').dispatchEvent(new CustomEvent('multi-select-reset', { bubbles: true }));
                    handleFilterSubmit('movements');
                "
                class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95" title="{{ __('Reset') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </form>

    {{-- Table Mode (Desktop) --}}
    <div class="hidden xl:block overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-y-0">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Product / Stock') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Warehouse') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Type') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Qty Change') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Time') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Operator') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                @forelse($movements as $mv)
                <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                    <td class="px-6 py-5">
                        <div class="flex items-center gap-x-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                    {{ $mv->product?->localized_name }}
                                </span>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wide">
                                        ID: {{ $mv->product_id }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-5">
                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                            {{ $mv->warehouse?->name }}
                        </span>
                    </td>
                    <td class="px-6 py-5 text-center whitespace-nowrap">
                        @php $typeColor = in_array($mv->type, ['in', 'transfer_in']) ? 'cyan' : (in_array($mv->type, ['out', 'transfer_out']) ? 'rose' : 'amber'); @endphp
                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black bg-{{ $typeColor }}-500/10 text-{{ $typeColor }}-500 border border-{{ $typeColor }}-500/20 tracking-widest uppercase shadow-sm shadow-{{ $typeColor }}-500/5">{{ __(\App\Models\Warehouse\StockMovement::TYPE_LABELS[$mv->type] ?? $mv->type) }}</span>
                    </td>
                    <td class="px-6 py-5 text-center font-mono font-extrabold {{ in_array($mv->type, ['in', 'transfer_in']) ? 'text-cyan-500' : 'text-rose-500' }}">
                        <div class="flex flex-col items-center">
                            <span class="text-lg tracking-tighter">{{ in_array($mv->type, ['in', 'transfer_in']) ? '+' : '-' }}{{ $mv->quantity }}</span>
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 tracking-tight">({{ $mv->before_qty }} → {{ $mv->after_qty }})</div>
                        </div>
                    </td>
                    <td class="px-6 py-5 text-center whitespace-nowrap">
                        <span class="text-xs font-mono font-black text-slate-700 dark:text-slate-200 tracking-widest">
                            {{ $mv->created_at?->format('Y-m-d H:i:s') }}
                        </span>
                    </td>
                    <td class="px-6 py-5 text-right whitespace-nowrap">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                            {{ $mv->creator?->name ?? '-' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No movement records found') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Card Mode (Mobile & Tablet) --}}
    <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        @forelse($movements as $mv)
        <div class="luxury-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 break-all group-hover:text-cyan-600 transition-colors tracking-tight">{{ $mv->product?->localized_name }}</h3>
                        <p class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-widest truncate mt-0.5">ID: {{ $mv->product_id }}</p>
                    </div>
                </div>
                <div class="shrink-0">
                    @php $typeColor = in_array($mv->type, ['in', 'transfer_in']) ? 'cyan' : (in_array($mv->type, ['out', 'transfer_out']) ? 'rose' : 'amber'); @endphp
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-{{ $typeColor }}-500/10 text-{{ $typeColor }}-500 border border-{{ $typeColor }}-500/20 uppercase tracking-widest shadow-sm">
                        {{ __(\App\Models\Warehouse\StockMovement::TYPE_LABELS[$mv->type] ?? $mv->type) }}
                    </span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-y-4 mb-4 border-y border-slate-100 dark:border-slate-800/50 py-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Warehouse') }}</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">{{ $mv->warehouse?->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Qty Change') }}</p>
                    <p class="text-sm font-extrabold {{ in_array($mv->type, ['in', 'transfer_in']) ? 'text-cyan-500' : 'text-rose-500' }}">
                        {{ in_array($mv->type, ['in', 'transfer_in']) ? '+' : '-' }}{{ $mv->quantity }}
                        <span class="text-[10px] text-slate-400 font-bold ml-1">({{ $mv->before_qty }}→{{ $mv->after_qty }})</span>
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Operator') }}</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $mv->creator?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Time') }}</p>
                    <p class="text-sm font-mono font-bold text-slate-800 dark:text-slate-100 tracking-tighter">{{ $mv->created_at?->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>

        </div>
        @empty
        <div class="col-span-full py-20 text-center flex flex-col items-center gap-4 bg-white/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800/50 luxury-card">
            <div class="flex flex-col items-center gap-3">
                <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No movement records found') }}</p>
            </div>
        </div>
        @endforelse
    </div>
    <div class="mt-8 py-6 border-t border-slate-50 dark:border-slate-800/50">
        @if($movements->total() > 0)
            {{ $movements->links('vendor.pagination.luxury') }}
        @else
            <div class="flex items-center justify-between gap-4 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                <span>{{ __('No records found') }}</span>
                <span>0 / 0</span>
            </div>
        @endif
    </div>
</div>
