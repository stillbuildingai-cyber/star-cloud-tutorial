{{--
    共用帳號列表 Partial
    Props:
      $users        — 分頁結果
      $baseRoute    — 路由前綴
      $companies    — 公司選單
      $tabHidden    — 是否嵌入 hidden[name=tab]（舊版相容，預設 false）
      $searchParam  — search 欄位名稱（預設 accounts_search）
      $companyParam — company_id 欄位名稱（預設 accounts_company_id）
      $perPageParam — per_page 欄位名稱（預設 accounts_per_page）
      $containerId  — ajax container id（預設 ajax-accounts-container）
--}}
@php
    $tabHidden    ??= false;
    $searchParam  ??= 'accounts_search';
    $companyParam ??= 'accounts_company_id';
    $perPageParam ??= 'accounts_per_page';
    $containerId  ??= 'ajax-accounts-container';
@endphp

<form action="{{ route($baseRoute) }}" method="GET"
      class="flex flex-col md:flex-row md:items-center gap-3 mb-8"
      @submit.prevent="fetchTabContent($el.action + '?' + new URLSearchParams(new FormData($el)).toString(), '{{ $containerId }}')">
    @if($tabHidden)<input type="hidden" name="tab" value="accounts">@endif

    <div class="relative group flex-1 md:flex-none">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input type="text" name="{{ $searchParam }}" value="{{ request($searchParam) }}"
            class="py-2.5 pl-12 pr-6 block w-full md:w-80 luxury-input"
            placeholder="{{ __('Search users...') }}">
    </div>

    @if(auth()->user()->isSystemAdmin())
    <div class="relative w-full md:w-72">
        <x-searchable-select name="{{ $companyParam }}" :options="$companies" :selected="request($companyParam)"
            :placeholder="__('All Affiliations')"
            onchange="this.form.dispatchEvent(new Event('submit'))" />
    </div>
    @endif

    <input type="hidden" name="{{ $perPageParam }}" value="{{ request($perPageParam, 10) }}">
    <div class="flex items-center gap-2 ml-auto md:ml-0 shrink-0">
        <button type="submit" class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all" title="{{ __('Search') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </button>
        <button type="button"
            @click="$el.closest('form').querySelectorAll('input[type=text]').forEach(i=>i.value=''); $el.closest('form').querySelectorAll('select').forEach(s=>{s.value=' ';window.HSSelect?.getInstance(s)?.setValue(' ');}); $el.closest('form').dispatchEvent(new Event('submit'));"
            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all" title="{{ __('Reset') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </button>
    </div>
</form>

{{-- 桌面表格 --}}
<div class="hidden xl:block overflow-x-auto">
    <table class="w-full text-left border-separate border-spacing-y-0">
        <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('User Info') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Contact Info') }}</th>
                @if(auth()->user()->isSystemAdmin())
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Affiliation') }}</th>
                @endif
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Role') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Status') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            @forelse($users as $user)
            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                <td class="px-6 py-6">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                            @if($user->avatar)<img src="{{ Storage::url($user->avatar) }}" class="w-full h-full object-cover">
                            @else<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight truncate">{{ $user->name }}</p>
                            <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ $user->username }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-6">
                    <div class="flex flex-col gap-0.5">
                        @if($user->phone)<p class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-widest">{{ $user->phone }}</p>@endif
                        @if($user->email)<p class="text-xs font-bold text-slate-400 dark:text-slate-500 tracking-wide">{{ $user->email }}</p>@endif
                        @if(!$user->phone && !$user->email)<span class="text-xs text-slate-300 dark:text-slate-600">—</span>@endif
                    </div>
                </td>
                @if(auth()->user()->isSystemAdmin())
                <td class="px-6 py-6">
                    @if($user->company)
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-widest uppercase">{{ $user->company->name }}</span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 uppercase tracking-widest border border-cyan-500/20">{{ __('SYSTEM') }}</span>
                    @endif
                </td>
                @endif
                <td class="px-6 py-6 text-center">
                    <div class="flex flex-wrap justify-center gap-1">
                        @forelse($user->roles as $role)
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 uppercase tracking-widest">{{ $role->name }}</span>
                        @empty
                            <span class="text-xs font-bold text-slate-300 dark:text-slate-600">{{ __('No Role') }}</span>
                        @endforelse
                    </div>
                </td>
                <td class="px-6 py-6 text-center">
                    <x-status-badge :status="$user->status ? 'active' : 'disabled'" size="sm" />
                </td>
                <td class="px-6 py-6 text-right">
                    <div class="flex items-center justify-end gap-2">
                        @if(!$user->hasRole('super-admin') || auth()->user()->hasRole('super-admin'))
                            @if($user->status)
                            <button type="button"
                                @click="toggleFormAction = '{{ route($baseRoute . '.status.toggle', $user->id) }}'; statusToggleSource = 'list'; isStatusConfirmOpen = true"
                                class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-amber-500 hover:bg-amber-500/5 transition-all border border-transparent hover:border-amber-500/20 tooltip" title="{{ __('Disable') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5"/></svg>
                            </button>
                            @else
                            <button type="button"
                                @click="toggleFormAction = '{{ route($baseRoute . '.status.toggle', $user->id) }}'; $nextTick(() => $refs.statusToggleForm.submit())"
                                class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all border border-transparent hover:border-emerald-500/20 tooltip" title="{{ __('Enable') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z"/></svg>
                            </button>
                            @endif
                            <button @click="openEditModal(@js($user))"
                                class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20 tooltip" title="{{ __('Edit') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                            </button>
                            <button type="button" @click="confirmDelete('{{ route($baseRoute . '.destroy', $user->id) }}')"
                                class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all border border-transparent hover:border-rose-500/20 tooltip" title="{{ __('Delete') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        @else
                            <span class="text-[10px] font-black text-slate-300 dark:text-slate-600 uppercase tracking-[0.15em] px-2">{{ __('Protected') }}</span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <x-empty-state mode="table" :colspan="auth()->user()->isSystemAdmin() ? 6 : 5" :message="__('No users found.')" />
            @endforelse
        </tbody>
    </table>
</div>

{{-- Mobile/Tablet Card Grid --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse($users as $user)
        @include('admin.data-config.partials.account-card', ['user' => $user, 'baseRoute' => $baseRoute])
    @empty
        <x-empty-state :message="__('No users found.')" />
    @endforelse
</div>

{{-- 分頁 --}}
<div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
    {{ $users->appends(request()->query())->links('vendor.pagination.luxury', [
        'ajax_navigate_event' => 'ajax:navigate:accounts',
        'per_page_param'      => $perPageParam,
    ]) }}
</div>
