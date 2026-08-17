<div class="relative">
    <!-- Toolbar & Filters -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-6 w-full">
            <form @submit.prevent="handleFilterSubmit('staff_list')" id="staff-list-filter-form"
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
                        placeholder="{{ __('Search by ID, Name or UID...') }}"
                        class="luxury-input py-2.5 pl-11 pr-4 block w-full text-sm font-bold">
                </div>

                @if(auth()->user()->isSystemAdmin())
                <div class="relative w-full sm:w-64 flex-none">
                    <x-searchable-select 
                        name="company_id" 
                        :options="$companies" 
                        :selected="request('company_id')"
                        :placeholder="__('All Companies')" 
                        @change="handleFilterSubmit('staff_list')" 
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
                    <button type="button" @click="fetchTabData('staff_list', '{{ route('admin.data-config.staff-cards.index', ['tab' => 'staff_list']) }}')"
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95 border border-slate-200 dark:border-slate-700"
                        title="{{ __('Reset') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>

                <input type="hidden" name="tab" value="staff_list">
            </form>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="hidden xl:block overflow-x-auto luxury-scrollbar">
        <table class="w-full text-left border-separate border-spacing-y-0">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('Employee ID') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('Staff Name') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('IC Card UID') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                        {{ __('Status') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                        {{ __('Last Used') }}
                    </th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                @forelse($cards as $card)
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                        <td class="px-6 py-6">
                            <span class="font-black text-slate-900 dark:text-white font-mono tracking-wider">{{ $card->employee_id }}</span>
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex flex-col">
                                <span class="font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">{{ $card->name }}</span>
                                @if($card->notes)
                                    <span class="text-xs font-bold text-slate-400 truncate max-w-[200px] mt-0.5">{{ $card->notes }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-6">
                            <code class="px-2.5 py-1 bg-slate-100 dark:bg-white/5 rounded-lg text-slate-600 dark:text-slate-400 text-xs font-black font-mono tracking-widest">{{ $card->card_uid }}</code>
                        </td>
                        <td class="px-6 py-6 text-center">
                            <x-status-badge :status="$card->status" size="sm" />
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex flex-col gap-1">
                                @if($card->last_used_at)
                                    <span class="text-xs font-black text-slate-600 dark:text-slate-300 font-mono tracking-widest">{{ $card->last_used_at->format('Y-m-d H:i') }}</span>
                                    @if($card->lastMachine)
                                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-400">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                            </svg>
                                            <span class="truncate max-w-[120px]">{{ $card->lastMachine->name }}</span>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-xs font-bold text-slate-300 italic">{{ __('Never Used') }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-6 text-right">
                            <div class="flex justify-end items-center gap-2">
                                @if($card->status === 'active')
                                <button type="button"
                                    @click="toggleStatus('{{ route('admin.data-config.staff-cards.status.toggle', $card) }}')"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-amber-500 hover:bg-amber-500/5 transition-all border border-transparent hover:border-amber-500/20"
                                    title="{{ __('Disable') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                    </svg>
                                </button>
                                @else
                                <button type="button"
                                    @click="toggleStatus('{{ route('admin.data-config.staff-cards.status.toggle', $card) }}')"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all border border-transparent hover:border-emerald-500/20"
                                    title="{{ __('Enable') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z" />
                                    </svg>
                                </button>
                                @endif
                                <button type="button" @click="openStaffModal({{ json_encode($card) }})"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                    title="{{ __('Edit') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </button>
                                <button type="button"
                                    @click="confirmDelete('{{ route('admin.data-config.staff-cards.destroy', $card) }}')"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all duration-200 border border-transparent hover:border-rose-500/20"
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
                    <x-empty-state mode="table" :colspan="6" :message="__('No staff cards found')" />
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($cards as $card)
        <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
            <!-- Card Header -->
            <div class="flex items-start justify-between gap-4 mb-6">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate tracking-tight">
                            {{ $card->name }}
                        </h3>
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                            {{ $card->employee_id }}
                        </p>
                    </div>
                </div>

                <x-status-badge :status="$card->status" size="sm" />
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-1 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                        {{ __('IC Card UID') }}
                    </p>
                    <p class="text-sm font-mono font-bold text-slate-700 dark:text-slate-300 truncate">
                        {{ $card->card_uid }}
                    </p>
                </div>
                @if($card->notes)
                <div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                        {{ __('Notes') }}
                    </p>
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400">
                        {{ $card->notes }}
                    </p>
                </div>
                @endif
                <div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                        {{ __('Last Used') }}
                    </p>
                    <p class="text-sm font-bold text-slate-600 dark:text-slate-300">
                        @if($card->last_used_at)
                            {{ $card->last_used_at->format('Y-m-d H:i') }}
                            @if($card->lastMachine)
                                <span class="text-xs text-slate-400 ml-1">({{ $card->lastMachine->name }})</span>
                            @endif
                        @else
                            <span class="text-slate-300 italic">{{ __('Never Used') }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    @if($card->status === 'active')
                    <button type="button"
                        @click="toggleStatus('{{ route('admin.data-config.staff-cards.status.toggle', $card) }}')"
                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-amber-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                        title="{{ __('Disable') }}">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                        </svg>
                    </button>
                    @else
                    <button type="button"
                        @click="toggleStatus('{{ route('admin.data-config.staff-cards.status.toggle', $card) }}')"
                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-emerald-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                        title="{{ __('Enable') }}">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z" />
                        </svg>
                    </button>
                    @endif
                    <button type="button" @click="openStaffModal({{ json_encode($card) }})"
                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                        title="{{ __('Edit') }}">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                    </button>
                </div>

                <button type="button" @click="confirmDelete('{{ route('admin.data-config.staff-cards.destroy', $card) }}')"
                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-rose-500 hover:text-white font-bold text-xs transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    {{ __('Delete') }}
                </button>
            </div>
        </div>
        @empty
        <x-empty-state :message="__('No staff cards found')" />
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
        {{ $cards->appends(request()->query())->links('vendor.pagination.luxury', [
            'per_page_param' => 'staff_per_page',
            'page_param' => 'staff_page',
        ]) }}
    </div>
</div>
