{{-- Permissions Tab Content (Partial) --}}
{{-- 此檔案被 index.blade.php 的 @include 和 AJAX 模式共用 --}}

{{-- Toolbar 區：搜尋框 + 公司篩選 + 按鈕 (Scenario B: Stacked) --}}
<div class="flex flex-wrap items-center gap-3 sm:gap-4 mb-8">
    {{-- 搜尋輸入框 --}}
    <div class="relative group flex-1 sm:flex-none">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </span>
        <input type="text" x-model="permissionSearch"
            @keydown.enter.prevent="searchInTab('permissions')"
            placeholder="{{ __('Search accounts...') }}"
            class="luxury-input py-2.5 pl-11 pr-4 block w-full sm:w-64 text-sm font-bold">
    </div>

    @if(auth()->user()->isSystemAdmin())
    <div class="w-full sm:w-72">
        <x-searchable-select name="company_filter" :options="$companies" :selected="request('company_id')"
            :placeholder="__('All Companies')"
            x-on:change="permissionCompanyId = $event.target.value; searchInTab('permissions')" />
    </div>
    @endif

    <div class="flex items-center gap-2 ml-auto sm:ml-0">
        {{-- 搜尋按鈕 --}}
        <button type="button" @click="searchInTab('permissions')"
            class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95"
            title="{{ __('Search') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>

        {{-- 重置按鈕 --}}
        <button type="button"
            @click="permissionSearch = ''; permissionCompanyId = ''; searchInTab('permissions')"
            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95"
            title="{{ __('Reset') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
    </div>
</div>

{{-- ── 桌面表格 (xl+) ── --}}
<div class="hidden xl:block overflow-x-auto">
    <table class="w-full text-left border-separate border-spacing-y-0">
        <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Account Info') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-left">
                    {{ __('Company Name') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                    {{ __('Authorized Machines') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                    {{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            @forelse($users_list ?? [] as $user)
            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                <td class="px-6 py-6 font-display text-left">
                    <div class="flex items-center gap-4 text-left">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white group-hover:border-cyan-500 shadow-sm group-hover:shadow-cyan-500/50 transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">{{ $user->name }}</span>
                            <span class="text-xs font-mono font-bold text-slate-500 tracking-widest uppercase">{{ $user->username }}</span>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-6 text-left">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold border border-sky-100 dark:border-sky-900/30 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 tracking-widest uppercase">
                        {{ $user->company->name ?? __('System') }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex flex-wrap gap-2 justify-center max-w-[420px] mx-auto max-h-[140px] overflow-y-auto pr-2 custom-scrollbar py-1 text-left">
                        @forelse($user->machines as $m)
                        <div class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-white/5 hover:border-cyan-500/30 transition-all duration-300 text-left">
                            <span class="text-xs font-black text-slate-700 dark:text-slate-200 leading-tight">{{ $m->name }}</span>
                        </div>
                        @empty
                        <div class="w-full text-center">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest opacity-40">-- {{ __('None') }} --</span>
                        </div>
                        @endforelse
                    </div>
                </td>
                <td class="px-6 py-6 text-right">
                    <button
                        @click='openPermissionModal({{ json_encode(["id" => $user->id, "name" => $user->name]) }})'
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-500 hover:text-white transition-all duration-300 text-xs font-black uppercase tracking-widest shadow-sm shadow-cyan-500/5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>{{ __('Authorize') }}</span>
                    </button>
                </td>
            </tr>
            @empty
            <x-empty-state mode="table" :colspan="4" :message="__('No accounts found')" />
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Mobile / Tablet Card Grid (< xl) ── --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-4">
    @forelse($users_list ?? [] as $user)
    <div class="luxury-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">

        {{-- Card Header --}}
        <div class="flex items-start justify-between gap-4 mb-5">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate tracking-tight">{{ $user->name }}</h3>
                    <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">{{ $user->username }}</p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold border border-sky-100 dark:border-sky-900/30 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 tracking-widest uppercase shrink-0">
                {{ $user->company->name ?? __('System') }}
            </span>
        </div>

        {{-- Machines Summary --}}
        @if($user->machines->count() > 0)
        <div class="mb-5 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ __('Authorized Machines') }}</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($user->machines->take(4) as $m)
                <span class="px-2.5 py-1 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-white/5 text-xs font-black text-slate-700 dark:text-slate-200">{{ $m->name }}</span>
                @endforeach
                @if($user->machines->count() > 4)
                <span class="px-2.5 py-1 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 text-xs font-black">+{{ $user->machines->count() - 4 }}</span>
                @endif
            </div>
        </div>
        @else
        <div class="mb-5 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Authorized Machines') }}</p>
            <p class="text-xs font-bold text-slate-400 opacity-60">-- {{ __('None') }} --</p>
        </div>
        @endif

        {{-- Action Buttons --}}
        <button
            @click='openPermissionModal({{ json_encode(["id" => $user->id, "name" => $user->name]) }})'
            class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-cyan-500/20">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            {{ __('Authorize') }}
        </button>
    </div>
    @empty
    <div class="col-span-full">
        <x-empty-state :message="__('No accounts found')" />
    </div>
    @endforelse
</div>

<div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6 mb-6">
    @if($users_list)
        {{ $users_list->appends(['tab' => 'permissions'])->links('vendor.pagination.luxury') }}
    @endif
</div>
