<div class="luxury-card rounded-3xl p-8 animate-luxury-in">
    <form action="{{ route('admin.warehouses.inventory') }}" method="GET" class="flex flex-wrap items-center gap-4 mb-8"
        @submit.prevent="handleFilterSubmit('stock')">
        <input type="hidden" name="tab" value="stock">

        {{-- 搜尋輸入框 --}}
        <div class="relative group flex-1 sm:flex-none">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10"><svg
                    class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg></span>
            <input type="text" name="search" value="{{ request('search') }}"
                class="py-2.5 pl-12 pr-6 block w-full sm:w-72 luxury-input text-sm font-bold" placeholder="{{ __('Search products...') }}"
                @keydown.enter.prevent="handleFilterSubmit('stock')">
        </div>

        {{-- 多選倉庫篩選器 --}}
        <div class="w-full sm:w-72">
            <x-multi-select name="warehouse_ids" :options="$warehouses" :selected="request('warehouse_ids', [])"
                :placeholder="__('Filter by Warehouse Presence')" />
        </div>

        @if(auth()->user()->isSystemAdmin())
        <div class="w-full sm:w-72">
            <x-searchable-select
                name="company_id"
                :options="$companies"
                :selected="request('company_id')"
                :placeholder="__('All Companies')"
                x-on:change="
                    $el.closest('form').querySelectorAll('input[type=hidden]:not([name=tab])').forEach(i => i.value = '');
                    $el.closest('form').dispatchEvent(new CustomEvent('multi-select-reset', { bubbles: true }));
                    handleFilterSubmit('stock');
                "
            />
        </div>
        @endif

        <div class="flex items-center gap-2 ml-auto sm:ml-0">
            <button type="submit"
                class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 group transition-all active:scale-95"
                title="{{ __('Search') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>

            <button type="button" @click="
                    $el.closest('form').querySelectorAll('input[type=text], input[type=hidden]:not([name=tab])').forEach(i => i.value = '');
                    $el.closest('form').querySelectorAll('select').forEach(s => {
                        s.value = ' ';
                        const instance = window.HSSelect?.getInstance(s);
                        if (instance) instance.setValue(' ');
                    });
                    $el.closest('form').dispatchEvent(new CustomEvent('multi-select-reset', { bubbles: true }));
                    $nextTick(() => handleFilterSubmit('stock'));
                "
                class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 group transition-all active:scale-95"
                title="{{ __('Reset') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </form>

    <div class="hidden xl:block overflow-x-auto -mx-8 px-8 custom-scrollbar">
        <table class="w-full text-left border-separate border-spacing-y-0 min-w-max">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-white/[0.02]">
                    <th
                        class="px-6 py-5 text-sm font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 sticky left-0 bg-white dark:bg-[#1e293b] z-20 w-[180px] xl:w-[260px] min-w-[180px] xl:min-w-[260px]">
                        {{ __('Product') }}</th>
                    <th
                        class="px-6 py-5 text-sm font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.1em] border-b border-slate-100 dark:border-slate-800 text-center bg-white dark:bg-[#1e293b] sticky xl:left-[260px] z-20 border-r border-slate-200/50 dark:border-slate-700/50 shadow-[1px_0_0_0_rgba(0,0,0,0.05)] dark:shadow-[1px_0_0_0_rgba(255,255,255,0.05)]">
                        {{ __('Total') }}
                    </th>
                    @foreach($inventory_warehouses as $w)
                    <th
                        class="px-6 py-5 text-sm font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        <div class="flex flex-col items-center">
                            <span class="text-base font-black text-slate-800 dark:text-slate-200 tracking-tight">{{
                                $w->name }}</span>
                            <span class="text-[10px] font-bold opacity-60 tracking-widest mt-0.5 uppercase">{{ $w->type
                                === 'main' ? __('Main') : __('Branch') }}</span>
                        </div>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-white/[0.05]">
                @forelse($products as $product)
                @php
                $viewableWarehouseIds = $inventory_warehouses->pluck('id')->toArray();
                $totalQuantity = $product->stocks->whereIn('warehouse_id', $viewableWarehouseIds)->sum('quantity');
                @endphp
                <tr class="group hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-all duration-300">
                    <td
                        class="px-6 py-4 sticky left-0 bg-white dark:bg-[#1e293b] group-hover:bg-slate-50 dark:group-hover:bg-white/[0.03] z-10 transition-colors w-[180px] xl:w-[260px] min-w-[180px] xl:min-w-[260px]">
                        <div class="flex items-center gap-3 lg:gap-4">
                            <div
                                class="w-8 h-8 lg:w-11 lg:h-11 rounded-xl lg:rounded-2xl bg-slate-100 dark:bg-slate-800/50 flex items-center justify-center overflow-hidden border border-slate-200/50 dark:border-white/5 group-hover:border-cyan-500/30 transition-colors shrink-0">
                                @if($product->image_url)
                                <img src="{{ $product->image_url }}" class="w-full h-full object-cover">
                                @else
                                <svg class="w-4 h-4 lg:w-5 lg:h-5 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                @endif
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span
                                    class="text-xs lg:text-sm font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors truncate">{{
                                    $product->localized_name }}</span>
                                @if($product->barcode)
                                <span
                                    class="text-[9px] lg:text-[10px] font-bold text-slate-400 mt-0.5 tracking-tight truncate">{{
                                    $product->barcode }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td
                        class="px-4 lg:px-6 py-4 text-center bg-white dark:bg-[#1e293b] group-hover:bg-slate-50 dark:group-hover:bg-white/[0.03] transition-colors sticky xl:left-[260px] z-10 border-r border-slate-200/50 dark:border-slate-700/50 shadow-[4px_0_12px_-4px_rgba(0,0,0,0.1)] dark:shadow-[4px_0_12px_-4px_rgba(0,0,0,0.3)]">
                        <span
                            class="text-lg lg:text-xl font-mono font-black text-cyan-600 dark:text-cyan-400 drop-shadow-[0_0_10px_rgba(6,182,212,0.1)]">
                            {{ $totalQuantity }}
                        </span>
                    </td>
                    @foreach($inventory_warehouses as $w)
                    @php
                    $stock = $product->stocks->firstWhere('warehouse_id', $w->id);
                    $isLow = $stock && $stock->safety_stock > 0 && $stock->quantity <= $stock->safety_stock;
                        @endphp
                        <td class="px-6 py-4 text-center">
                            @if($stock && $stock->quantity > 0)
                            <div class="flex flex-col items-center">
                                <span
                                    class="text-base font-black {{ $isLow ? 'text-rose-500 drop-shadow-[0_0_8px_rgba(244,63,94,0.2)]' : 'text-slate-700 dark:text-slate-200' }}">
                                    {{ $stock->quantity }}
                                </span>
                                @if($isLow)
                                <span
                                    class="text-[9px] font-black text-rose-500 uppercase tracking-tighter opacity-80">{{
                                    __('Low') }}</span>
                                @endif
                            </div>
                            @else
                            <span class="text-sm font-bold text-slate-300 dark:text-slate-700/50">-</span>
                            @endif
                        </td>
                        @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($inventory_warehouses) + 2 }}" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No products found') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Card View --}}
    <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 items-start">
        @forelse($products as $product)
        @php
        $viewableWarehouseIds = $inventory_warehouses->pluck('id')->toArray();
        $totalQuantity = $product->stocks->whereIn('warehouse_id', $viewableWarehouseIds)->sum('quantity');
        @endphp
        <div x-data="{ localCardOpen: false }"
            class="luxury-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">

            {{-- Card Header --}}
            <div class="flex items-center justify-between gap-4 cursor-pointer" @click.stop="localCardOpen = !localCardOpen">
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 transition-all duration-300 shadow-sm shrink-0">
                        @if($product->image_url)
                        <img src="{{ $product->image_url }}"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        @else
                        <svg class="w-6 h-6 text-slate-400 group-hover:text-white transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3
                            class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">
                            {{ $product->localized_name }}
                        </h3>
                        @if($product->barcode)
                        <p
                            class="text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate mt-0.5">
                            {{ $product->barcode }}
                        </p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                    <span class="text-xl font-mono font-black text-cyan-600 dark:text-cyan-400">
                        {{ $totalQuantity }}
                    </span>
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-300"
                        :class="{ 'rotate-180': localCardOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </div>

            {{-- Collapsible Content --}}
            <div x-show="localCardOpen" x-collapse x-cloak
                class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800/50 space-y-2">
                @foreach($inventory_warehouses->sortBy('name', SORT_NATURAL) as $w)
                @php
                $stock = $product->stocks->firstWhere('warehouse_id', $w->id);
                $isLow = $stock && $stock->safety_stock > 0 && $stock->quantity <= $stock->safety_stock;
                    @endphp
                    <div
                        class="flex items-center justify-between p-4 rounded-2xl bg-slate-50/50 dark:bg-white/[0.02] border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all">
                        <div class="flex items-center gap-3">
                            <span class="text-base font-black text-slate-800 dark:text-slate-100">{{ $w->name }}</span>
                            <span
                                class="text-[10px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-800/50 px-2 py-0.5 rounded-lg uppercase tracking-tight">
                                {{ $w->type === 'main' ? __('Main') : __('Branch') }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($stock && $stock->quantity > 0)
                            <span
                                class="text-base font-mono font-black {{ $isLow ? 'text-rose-500' : 'text-slate-800 dark:text-slate-100' }}">
                                {{ $stock->quantity }}
                            </span>
                            @if($isLow)
                            <div class="px-2 py-0.5 rounded-full bg-rose-500/10 border border-rose-500/20">
                                <span
                                    class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-tighter">{{
                                    __('Low') }}</span>
                            </div>
                            @endif
                            @else
                            <span class="text-sm font-bold text-slate-300 dark:text-slate-700/50">-</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
            </div>
        </div>
        @empty
        <div class="col-span-full py-20 text-center flex flex-col items-center gap-4 bg-white/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800/50 luxury-card">
            <div class="flex flex-col items-center gap-3">
                <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"
                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No products found') }}</p>
            </div>
        </div>
        @endforelse
    </div>

    <div class="mt-6 py-6 border-t border-slate-50 dark:border-slate-800/50">
        @if($products->total() > 0)
            {{ $products->links('vendor.pagination.luxury') }}
        @else
            <div class="flex items-center justify-between gap-4 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                <span>{{ __('No records found') }}</span>
                <span>0 / 0</span>
            </div>
        @endif
    </div>
</div>
