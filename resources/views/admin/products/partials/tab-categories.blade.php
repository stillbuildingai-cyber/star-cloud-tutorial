<div class="relative">
    <!-- Toolbar & Filters -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-6 w-full">
            <form @submit.prevent="handleFilterSubmit('categories')" id="categories-filter-form" class="flex flex-wrap items-center gap-4 w-full">
                <!-- Search Group -->
                <div class="relative group w-full sm:w-72">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" 
                        placeholder="{{ __('Search categories...') }}"
                        class="luxury-input py-2.5 pl-11 pr-4 block w-full text-sm font-bold">
                </div>

                @if(auth()->user()->isSystemAdmin())
                <div class="relative w-full sm:w-72 flex-none">
                    <x-searchable-select name="category_company_id" 
                        :options="$companies"
                        :selected="request('category_company_id')"
                        :placeholder="__('All Companies')"
                        @change="handleFilterSubmit('categories')"
                    />
                </div>
                @endif

                <div class="flex items-center gap-2 ml-auto sm:ml-0">
                    <button type="submit" 
                        class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95"
                        title="{{ __('Search') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </button>
                    <button type="button" 
                        @click="
                            $el.closest('form').querySelectorAll('input[type=text], input[type=hidden]:not([name=tab])').forEach(i => i.value = '');
                            $el.closest('form').querySelectorAll('select').forEach(s => {
                                s.value = '';
                                const instance = window.HSSelect.getInstance(s);
                                if(instance) instance.setValue('');
                            });
                            handleFilterSubmit('categories');
                        "
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95 border border-slate-200 dark:border-slate-700"
                        title="{{ __('Reset') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </button>
                </div>

                <input type="hidden" name="tab" value="categories">
            </form>
        </div>
    </div>
    <!-- Desktop Table View -->
    <div class="hidden xl:block overflow-x-auto luxury-scrollbar">
        <table class="w-full text-left border-separate border-spacing-y-0">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Category Name') }}</th>
                    @if(auth()->user()->isSystemAdmin())
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Company') }}</th>
                    @endif
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                @forelse($categories as $category)
                <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                    <td class="px-6 py-5">
                        <div class="flex items-center gap-x-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white group-hover:border-cyan-500 shadow-sm group-hover:shadow-cyan-500/50 transition-all duration-300">
                                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v13.5A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V9.432a2.25 2.25 0 00-.659-1.591l-4.182-4.182A2.25 2.25 0 0014.568 3h-5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12h-6m6 4h-6m6-8h-6" />
                                </svg>
                            </div>
                            <span class="text-base font-extrabold text-slate-800 dark:text-slate-100 transition-colors group-hover:text-cyan-600 dark:group-hover:text-cyan-400">
                                {{ $category->localized_name }}
                            </span>
                        </div>
                    </td>
                    @if(auth()->user()->isSystemAdmin())
                    <td class="px-6 py-5 text-center">
                        <span class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ $category->company->name ?? __('System Default') }}</span>
                    </td>
                    @endif
                    <td class="px-6 py-5 text-right">
                        <div class="flex justify-end items-center gap-2">
                            <button type="button" @click="openCategoryModal(@js($category))" class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20" title="{{ __('Edit') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                            </button>
                            <button type="button" @click="confirmDelete('{{ route('admin.data-config.product-categories.destroy', $category->id) }}')" class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all border border-transparent hover:border-rose-500/20" title="{{ __('Delete') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ auth()->user()->isSystemAdmin() ? 3 : 2 }}" class="px-6 py-20 text-center text-slate-400 italic font-bold tracking-widest small-caps">
                        {{ __('No categories found.') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($categories as $category)
        <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
            <!-- Card Header -->
            <div class="flex items-start justify-between gap-4 mb-6">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v13.5A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V9.432a2.25 2.25 0 00-.659-1.591l-4.182-4.182A2.25 2.25 0 0014.568 3h-5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12h-6m6 4h-6m6-8h-6" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 truncate tracking-tight transition-colors group-hover:text-cyan-600 dark:group-hover:text-cyan-400">
                            {{ $category->localized_name }}
                        </h3>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate mt-1">
                            {{ __('Category ID') }}: #{{ $category->id }}
                        </p>
                    </div>
                </div>

            </div>

            <!-- Info Grid -->
            @if(auth()->user()->isSystemAdmin())
            <div class="mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                    {{ __('Company') }}
                </p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                    {{ $category->company->name ?? __('System Default') }}
                </p>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                <button type="button" 
                        @click="confirmDelete('{{ route('admin.data-config.product-categories.destroy', $category->id) }}')"
                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-rose-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                        title="{{ __('Delete') }}">
                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </button>

                <button type="button" 
                        @click="openCategoryModal(@js($category))"
                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    {{ __('Edit') }}
                </button>
            </div>
        </div>
        @empty
        <div class="col-span-full py-20 text-center">
            <p class="text-slate-400 italic font-bold tracking-widest small-caps">{{ __('No categories found.') }}</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6 py-6 border-t border-slate-50 dark:border-slate-800/50">
        {{ $categories->links('vendor.pagination.luxury') }}
    </div>
</div>
