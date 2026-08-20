{{--
    共用角色列表 Partial
    Props:
      $roles        — 分頁結果
      $baseRoute    — 路由前綴（用於 CRUD 操作連結）
      $listRoute    — 搜尋表單 action 路由（可選，預設同 $baseRoute）
      $companies    — 公司選單
      $tabHidden    — 是否嵌入 hidden[name=tab]（舊版相容，預設 false）
      $searchParam  — search 欄位名稱（預設 roles_search）
      $companyParam — company_id 欄位名稱（預設 roles_company_id）
      $perPageParam — per_page 欄位名稱（預設 roles_per_page）
      $containerId  — ajax container id（預設 ajax-roles-container）
--}}
@php
    $tabHidden    ??= false;
    $searchParam  ??= 'roles_search';
    $companyParam ??= 'roles_company_id';
    $perPageParam ??= 'roles_per_page';
    $containerId  ??= 'ajax-roles-container';
    $formAction   = isset($listRoute) ? route($listRoute) : route($baseRoute);
@endphp

<form action="{{ $formAction }}" method="GET"
      class="flex flex-col md:flex-row md:items-center gap-3 mb-8"
      @submit.prevent="fetchTabContent($el.action + '?' + new URLSearchParams(new FormData($el)).toString(), '{{ $containerId }}')">
    @if($tabHidden)<input type="hidden" name="tab" value="roles">@endif

    <div class="relative group flex-1 md:flex-none">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input type="text" name="{{ $searchParam }}" value="{{ request($searchParam) }}"
            class="py-2.5 pl-12 pr-6 block w-full md:w-80 luxury-input"
            placeholder="{{ __('Search roles...') }}">
    </div>

    @if(auth()->user()->isSystemAdmin())
    <div class="relative w-full md:w-72">
        <x-searchable-select name="{{ $companyParam }}" :options="$companies" :selected="request($companyParam)"
            :placeholder="__('All Affiliations')"
            onchange="this.form.dispatchEvent(new Event('submit'))">
            <option value="system" {{ request($companyParam) === 'system' ? 'selected' : '' }} data-title="{{ __('System Level') }}">{{ __('System Level') }}</option>
        </x-searchable-select>
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
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Role Name') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Affiliation') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Permissions') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Users') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            @forelse($roles as $role)
            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                <td class="px-6 py-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors truncate">{{ $role->name }}</p>
                            @if($role->is_system)
                            <span class="inline-flex items-center gap-1 text-[10px] font-black text-cyan-500 dark:text-cyan-400 uppercase tracking-widest">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                {{ __('System Role') }}
                            </span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-6">
                    @if($role->is_system)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 uppercase tracking-widest border border-cyan-500/20">{{ __('System Level') }}</span>
                    @else
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-widest uppercase">{{ $role->company->name ?? __('Company Level') }}</span>
                    @endif
                </td>
                <td class="px-6 py-6" width="30%">
                    <div class="flex flex-wrap gap-1 max-w-xs">
                        @php
                            $activeModules = ['menu.machines', 'menu.analysis', 'menu.data-config', 'menu.remote', 'menu.basic', 'menu.permissions'];
                            $displayPermissions = $role->permissions->filter(function($p) use ($activeModules) {
                                if ($p->name === 'menu.data-config.sub-account-roles') return false;
                                if (str_starts_with($p->name, 'menu.')) {
                                    foreach ($activeModules as $module) {
                                        if ($p->name === $module || str_starts_with($p->name, $module . '.')) return true;
                                    }
                                    return false;
                                }
                                return true;
                            });
                        @endphp
                        @forelse($displayPermissions->take(5) as $permission)
                            <span class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-full border border-slate-200 dark:border-slate-700 uppercase font-black tracking-widest">{{ __($permission->name) }}</span>
                        @empty
                            <span class="text-xs font-bold text-slate-400">{{ __('No permissions') }}</span>
                        @endforelse
                        @if($displayPermissions->count() > 5)
                            <div x-data="{ open: false }">
                                <button type="button" @click="open = true"
                                    class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-full border border-slate-200 dark:border-slate-700 uppercase font-black tracking-widest hover:bg-cyan-500/10 hover:text-cyan-600 hover:border-cyan-400/30 dark:hover:text-cyan-400 transition-all cursor-pointer">
                                    +{{ $displayPermissions->count() - 5 }}
                                </button>

                                {{-- 全螢幕置中彈窗 --}}
                                <template x-teleport="body">
                                    <div x-show="open" 
                                        class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-y-auto"
                                        x-cloak>
                                        {{-- 背景遮罩 --}}
                                        <div x-show="open" 
                                            x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                            x-transition:leave="transition ease-in duration-200"
                                            x-transition:leave-start="opacity-100"
                                            x-transition:leave-end="opacity-0"
                                            @click="open = false"
                                            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

                                        {{-- 內容視窗 --}}
                                        <div x-show="open"
                                            x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-200"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl p-6 md:p-8">
                                            
                                            <div class="flex items-center justify-between mb-6">
                                                <div>
                                                    <h3 class="text-xl font-bold text-slate-800 dark:text-white">{{ $role->display_name ?: $role->name }}</h3>
                                                    <p class="text-xs text-slate-400 mt-1 uppercase tracking-widest font-black">{{ __('All Permissions') }} ({{ $displayPermissions->count() }})</p>
                                                </div>
                                                <button @click="open = false" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                @foreach($displayPermissions as $permission)
                                                    <span class="px-3 py-1 text-xs bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-300 rounded-full border border-slate-200 dark:border-slate-700 uppercase font-bold tracking-tight">{{ __($permission->name) }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        @endif
                    </div>
                </td>
                <td class="px-6 py-6 text-center">
                    <span class="text-sm font-extrabold text-slate-700 dark:text-slate-300">{{ $role->users()->count() }}</span>
                </td>
                <td class="px-6 py-6 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route($baseRoute . '.edit', $role->id) }}"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20 tooltip" title="{{ __('Edit') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                        </a>
                        @if($role->name !== 'super-admin' && (auth()->user()->isSystemAdmin() || !$role->is_system))
                        <button type="button"
                            @click="if({{ $role->users()->count() }} > 0) { triggerDeleteWarning('{{ __('Cannot delete role with active users.') }}'); return; } confirmDelete('{{ route($baseRoute . '.destroy', $role->id) }}')"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all border border-transparent hover:border-rose-500/20 tooltip" title="{{ __('Delete') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <x-empty-state mode="table" :colspan="5" :message="__('No roles found.')" />
            @endforelse
        </tbody>
    </table>
</div>

{{-- Mobile/Tablet Card Grid --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse($roles as $role)
        @include('admin.data-config.partials.role-card', ['role' => $role, 'baseRoute' => $baseRoute])
    @empty
        <x-empty-state :message="__('No roles found.')" />
    @endforelse
</div>

{{-- 分頁 --}}
<div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
    {{ $roles->appends(request()->query())->links('vendor.pagination.luxury', [
        'ajax_navigate_event' => 'ajax:navigate:roles',
        'per_page_param'      => $perPageParam,
    ]) }}
</div>
