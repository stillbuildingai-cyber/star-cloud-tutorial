@php
$baseRoute = 'admin.data-config.products';
@endphp

<div class="relative">
    <!-- Toolbar & Filters -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-6 w-full">
            <form @submit.prevent="handleFilterSubmit('products')" id="products-filter-form"
                class="flex flex-wrap items-center gap-4 w-full">
                
                <!-- Search Group -->
                <div class="relative group w-full sm:w-72">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search products...') }}"
                        class="luxury-input py-2.5 pl-11 pr-4 block w-full text-sm font-bold">
                </div>

                <div class="relative w-full sm:w-72 flex-none">
                    <x-searchable-select name="category_id" :options="$categories" :selected="request('category_id')"
                        :placeholder="__('All Categories')" @change="handleFilterSubmit('products')" />
                </div>

                @if(auth()->user()->isSystemAdmin())
                <div class="relative w-full sm:w-72 flex-none">
                    <x-searchable-select name="product_company_id" :options="$companies"
                        :selected="request('product_company_id')" :placeholder="__('All Companies')"
                        @change="handleFilterSubmit('products')" />
                </div>
                @endif

                <div class="flex items-center gap-2 ml-auto sm:ml-0">
                    <button type="submit"
                        class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95"
                        title="{{ __('Search') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    <button type="button" @click="
                            $el.closest('form').querySelectorAll('input[type=text], input[type=hidden]:not([name=tab])').forEach(i => i.value = '');
                            $el.closest('form').querySelectorAll('select').forEach(s => {
                                s.value = '';
                                const instance = window.HSSelect.getInstance(s);
                                if(instance) instance.setValue('');
                            });
                            handleFilterSubmit('products');
                        "
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95 border border-slate-200 dark:border-slate-700"
                        title="{{ __('Reset') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    <button @click="isImportModalOpen = true" type="button"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition-all active:scale-95 flex items-center gap-2 text-xs font-bold whitespace-nowrap"
                        title="{{ __('Import Excel') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('Import Excel') }}</span>
                    </button>
                    <div class="relative" x-data="{ exportOpen: false }">
                        <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 shadow-lg shadow-emerald-500/25 transition-all active:scale-95 flex items-center gap-2 text-xs font-bold whitespace-nowrap"
                            title="{{ __('Export') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span class="hidden sm:inline">{{ __('Export') }}</span>
                            <svg class="w-3 h-3 transition-transform duration-200" :class="exportOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="exportOpen" x-transition
                             class="absolute right-0 top-full mt-2 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-xl rounded-2xl p-2 z-30 min-w-[160px]"
                             x-cloak>
                            <a href="{{ route($baseRoute . '.export', array_merge(request()->only(['search','category_id','product_company_id']), ['export' => 'csv'])) }}"
                               download @click="exportOpen = false"
                               class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                                📄 {{ __('Export to CSV') }}
                            </a>
                            <a href="{{ route($baseRoute . '.export', array_merge(request()->only(['search','category_id','product_company_id']), ['export' => 'excel'])) }}"
                               download @click="exportOpen = false"
                               class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                                📊 {{ __('Export to Excel') }}
                            </a>
                        </div>
                    </div>
                </div>

        <input type="hidden" name="tab" value="products">
            </form>
        </div>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden xl:block overflow-x-auto luxury-scrollbar">
        <table class="w-full text-left border-separate border-spacing-y-0">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                    <th
                        class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('ID') }}</th>
                    <th
                        class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('Product Info') }}</th>
                    <th
                        class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('Barcode') }}</th>
                    @if(auth()->user()->isSystemAdmin())
                    <th
                        class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Company') }}</th>
                    @endif
                    <th
                        class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Sale Price') }}</th>
                    <th
                        class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Track Limit (Track/Spring)') }}</th>
                    <th
                        class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Status') }}</th>
                    <th
                        class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                        {{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                @forelse($products as $product)
                <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                    <td class="px-6 py-5 whitespace-nowrap">
                        <span class="text-sm font-mono font-black text-slate-400 dark:text-slate-500 tracking-tight">#{{ $product->id }}</span>
                    </td>
                    <td class="px-6 py-5 cursor-pointer group/info" @click="viewProductDetail(@js($product))"
                        title="{{ __('View Details') }}">
                        <div class="flex items-center gap-x-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover/info:bg-cyan-500 group-hover/info:text-white group-hover/info:border-cyan-500 shadow-sm group-hover/info:shadow-cyan-500/50 transition-all duration-300 overflow-hidden relative">
                                @if($product->image_url)
                                <img src="{{ $product->image_url }}" class="w-full h-full object-cover">
                                @else
                                <svg class="w-6 h-6 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                @endif
                            </div>
                            <div class="flex flex-col">
                                <span
                                    class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover/info:text-cyan-600 dark:group-hover/info:text-cyan-400 transition-colors leading-tight">{{
                                    $product->localized_name }}</span>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                    @php
                                    $catName = $product->category->localized_name ?? __('Uncategorized');
                                    @endphp
                                    <span
                                        class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.14em] bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 px-2.5 py-1 rounded-lg backdrop-blur-sm transition-all duration-300 group-hover/info:bg-cyan-500/10 group-hover/info:border-cyan-500/20 group-hover/info:text-cyan-500 group-hover/info:shadow-sm group-hover/info:shadow-cyan-500/10">{{
                                        $catName }}</span>
                                    @if(($companySettings['enable_material_code'] ?? false) &&
                                    isset($product->metadata['material_code']))
                                    <span
                                        class="text-[10px] font-black text-emerald-500/90 uppercase tracking-widest bg-emerald-500/10 px-2 py-0.5 rounded-lg border border-emerald-500/20 shadow-sm shadow-emerald-500/5">#{{
                                        $product->metadata['material_code'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap">
                        <span class="text-sm font-mono font-bold text-slate-600 dark:text-slate-300 tracking-tight">{{
                            $product->barcode ?: '-' }}</span>
                    </td>
                    @if(auth()->user()->isSystemAdmin())
                    <td class="px-6 py-5 text-center">
                        <span
                            class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors">{{
                            $product->company->name ?? '-' }}</span>
                    </td>
                    @endif
                    <td class="px-6 py-5 text-center whitespace-nowrap">
                        <span class="text-sm font-black text-slate-800 dark:text-white leading-none">${{
                            number_format($product->price, 0) }}</span>
                    </td>
                    <td class="px-6 py-5">
                        <div class="flex items-center justify-center gap-2 font-black">
                            <span class="text-base text-indigo-500 dark:text-indigo-400">
                                {{ $product->track_limit ?: 0 }}
                            </span>
                            <span class="text-xs text-slate-300 dark:text-slate-700">/</span>
                            <span class="text-base text-amber-500 dark:text-amber-500">
                                {{ $product->spring_limit ?: 0 }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-5 text-center">
                        <x-status-badge
                            :status="$product->is_active ? 'active' : 'inactive'"
                            size="sm"
                        />
                    </td>
                    <td class="px-6 py-5 text-right">
                        <div class="flex justify-end items-center gap-2">
                            @if($product->is_active)
                            <button type="button"
                                @click="toggleStatus('{{ route($baseRoute . '.status.toggle', $product->id) }}')"
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-amber-500 hover:bg-amber-500/5 transition-all border border-transparent hover:border-amber-500/20"
                                title="{{ __('Disable') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                </svg>
                            </button>
                            @else
                            <button type="button"
                                @click="toggleFormAction = '{{ route($baseRoute . '.status.toggle', $product->id) }}'; $nextTick(() => $refs.statusToggleForm.submit())"
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all border border-transparent hover:border-emerald-500/20"
                                title="{{ __('Enable') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z" />
                                </svg>
                            </button>
                            @endif
                            <a href="{{ route($baseRoute . '.edit', $product->id) }}"
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                title="{{ __('Edit') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </a>
                            <button type="button"
                                @click="confirmDelete('{{ route($baseRoute . '.destroy', $product->id) }}')"
                                class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all duration-200 border border-transparent hover:border-rose-500/20"
                                title="{{ __('Delete') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <x-empty-state
                    mode="table"
                    colspan="7"
                    :message="__('No products found matching your criteria.')"
                />
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($products as $product)
        <div
            class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
            <!-- Card Header -->
            <div class="flex items-start justify-between gap-4 mb-6">
                <div class="flex items-center gap-4 min-w-0">
                    <div @click="viewProductDetail(@js($product))"
                        class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0 cursor-pointer">
                        @if($product->image_url)
                        <img src="{{ $product->image_url }}" class="w-full h-full object-cover">
                        @else
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 @click="viewProductDetail(@js($product))"
                            class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight cursor-pointer">
                            {{ $product->localized_name }}
                        </h3>
                        <p
                            class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                            {{ __('ID') }}: #{{ $product->id }} | {{ $product->barcode ?: '--' }}
                        </p>
                    </div>
                </div>

                {{-- Status Label --}}
                <x-status-badge
                    :status="$product->is_active ? 'active' : 'inactive'"
                    size="sm"
                />
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                        {{ __('Category') }}
                    </p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">
                        {{ $product->category->localized_name ?? __('Uncategorized') }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                        {{ __('Sale Price') }}
                    </p>
                    <p class="text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono">
                        ${{ number_format($product->price, 0) }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                        {{ __('Track Limit') }}
                    </p>
                    <p class="text-sm font-bold text-indigo-500 dark:text-indigo-400 font-mono">
                        {{ $product->track_limit ?: 0 }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                        {{ __('Spring Limit') }}
                    </p>
                    <p class="text-sm font-bold text-amber-500 dark:text-amber-500 font-mono">
                        {{ $product->spring_limit ?: 0 }}
                    </p>
                </div>
                @if(auth()->user()->isSystemAdmin())
                <div class="col-span-2">
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                        {{ __('Company') }}
                    </p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                        {{ $product->company->name ?? '-' }}
                    </p>
                </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <button type="button"
                        @if($product->is_active)
                        @click="toggleStatus('{{ route($baseRoute . '.status.toggle', $product->id) }}')"
                        @else
                        @click="toggleFormAction = '{{ route($baseRoute . '.status.toggle', $product->id) }}'; $nextTick(() => $refs.statusToggleForm.submit())"
                        @endif
                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-emerald-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                        title="{{ __('Status') }}">
                        @if($product->is_active)
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                        </svg>
                        @else
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z" />
                        </svg>
                        @endif
                    </button>
                    <a href="{{ route($baseRoute . '.edit', $product->id) }}"
                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                        title="{{ __('Edit') }}">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                    </a>
                </div>

                <button type="button" @click="viewProductDetail(@js($product))"
                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.67 8.5 7.652 6 12 6c4.348 0 8.332 2.5 9.964 5.678a1.012 1.012 0 0 1 0 .644C20.33 15.5 16.348 18 12 18c-4.348 0-8.332-2.5-9.964-5.678z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                    </svg>
                    {{ __('Details') }}
                </button>
            </div>
        </div>
        @empty
        <x-empty-state :message="__('No products found matching your criteria.')" />
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6 py-6 border-t border-slate-50 dark:border-slate-800/50">
        {{ $products->links('vendor.pagination.luxury', ['page_param' => 'product_page']) }}
    </div>
</div>
