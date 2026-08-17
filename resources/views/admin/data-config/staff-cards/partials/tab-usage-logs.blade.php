<div class="relative">
    <!-- Toolbar & Filters -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-6 w-full">
            <form @submit.prevent="handleFilterSubmit('usage_logs')" id="usage-logs-filter-form"
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
                    <input type="text" name="log_search" value="{{ request('log_search') }}"
                        placeholder="{{ __('Search by ID, Name or UID...') }}"
                        class="luxury-input py-2.5 pl-11 pr-4 block w-full text-sm font-bold">
                </div>

                @if(auth()->user()->isSystemAdmin())
                <div class="relative w-full sm:w-64 flex-none">
                    <x-searchable-select 
                        name="log_company_id" 
                        :options="$companies" 
                        :selected="request('log_company_id')"
                        :placeholder="__('All Companies')" 
                        @change="handleFilterSubmit('usage_logs')" 
                    />
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
                    <button type="button" @click="fetchTabData('usage_logs', '{{ route('admin.data-config.staff-cards.index', ['tab' => 'usage_logs']) }}')"
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95 border border-slate-200 dark:border-slate-700"
                        title="{{ __('Reset') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>

                <input type="hidden" name="tab" value="usage_logs">
            </form>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="hidden xl:block overflow-x-auto luxury-scrollbar">
        <table class="w-full text-left border-separate border-spacing-y-0">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('Machine / Employee') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Action') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Identification') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Sales Record') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                        {{ __('Time') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                @forelse($logs as $log)
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-x-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 border border-slate-200 dark:border-slate-700 shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">
                                        {{ $log->machine?->name ?? $log->machine?->serial_no ?? __('Unknown Machine') }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wide mt-0.5">
                                        {{ __('Staff') }}: {{ $log->staffCard?->name ?? __('Unknown') }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center whitespace-nowrap">
                            <x-status-badge :status="$log->action" size="sm" />
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="flex flex-col">
                                <span class="text-sm font-black text-slate-800 dark:text-slate-200 tracking-tight">{{ $log->staffCard?->employee_id ?? '-' }}</span>
                                <code class="text-[10px] font-bold text-slate-400 font-mono tracking-widest mt-0.5 uppercase">{{ $log->staffCard?->card_uid ?? '-' }}</code>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center whitespace-nowrap">
                            @if($log->order_id)
                                <a href="{{ route('admin.sales.index', ['tab' => 'orders', 'search' => $log->order?->order_no ?? $log->order_id]) }}" 
                                   class="text-xs font-black text-cyan-600 dark:text-cyan-400 hover:underline flex items-center justify-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    {{ $log->order?->order_no ?? '#' . $log->order_id }}
                                </a>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-slate-100 dark:bg-white/5 text-slate-400 dark:text-slate-500">
                                    {{ __('Verified Only') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-right whitespace-nowrap">
                            <span class="text-xs font-black text-slate-600 dark:text-slate-300 font-mono tracking-widest">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                        </td>
                    </tr>
                @empty
                    <x-empty-state mode="table" :colspan="5" :message="__('No usage logs found')" />
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($logs as $log)
        <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
            <!-- Card Header -->
            <div class="flex items-start justify-between gap-4 mb-6">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate tracking-tight">
                            {{ $log->machine?->name ?? $log->machine?->serial_no ?? __('Unknown Machine') }}
                        </h3>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest truncate mt-1">
                            {{ __('Staff') }}: {{ $log->staffCard?->name ?? __('Unknown') }}
                        </p>
                    </div>
                </div>

                <x-status-badge :status="$log->action" size="sm" />
            </div>

            <!-- Card Body -->
            <div class="grid grid-cols-2 gap-y-5 border-y border-slate-100 dark:border-white/5 py-6 mb-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">
                        {{ __('Employee ID') }}
                    </p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                        {{ $log->staffCard?->employee_id ?? '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">
                        {{ __('Card UID') }}
                    </p>
                    <p class="text-xs font-mono font-bold text-slate-600 dark:text-slate-400 truncate">
                        {{ $log->staffCard?->card_uid ?? '-' }}
                    </p>
                </div>
                <div class="col-span-2">
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">
                        {{ __('Sales Record') }}
                    </p>
                    @if($log->order_id)
                        <a href="{{ route('admin.sales.index', ['tab' => 'orders', 'search' => $log->order?->order_no ?? $log->order_id]) }}" 
                           class="text-sm font-black text-cyan-600 dark:text-cyan-400 hover:underline flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            {{ $log->order?->order_no ?? '#' . $log->order_id }}
                        </a>
                    @else
                        <span class="text-sm font-bold text-slate-300 dark:text-slate-700">
                            {{ __('Verified Only') }}
                        </span>
                    @endif
                </div>
                <div class="col-span-2">
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">
                        {{ __('Time') }}
                    </p>
                    <p class="text-sm font-mono font-black text-slate-700 dark:text-slate-200 tracking-tight">
                        {{ $log->created_at->format('Y-m-d H:i:s') }}
                    </p>
                </div>
            </div>
        </div>
        @empty
        <x-empty-state :message="__('No usage logs found')" />
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
        {{ $logs->appends(request()->query())->links('vendor.pagination.luxury', [
            'per_page_param' => 'usage_per_page',
            'page_param' => 'usage_page',
        ]) }}
    </div>
</div>
