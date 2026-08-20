{{-- Mobile Card: 角色 - 嚴格遵守 SKILL Mobile Card 三區結構 --}}
<div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">

    {{-- 區域一：Card Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div class="flex items-center gap-4 min-w-0">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight cursor-default">{{ $role->name }}</h3>
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                    {{ $role->is_system ? __('System Level') : ($role->company->name ?? __('Company Level')) }}
                </p>
            </div>
        </div>
        @if($role->is_system)
            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 uppercase tracking-widest border border-cyan-500/20 shrink-0">{{ __('System') }}</span>
        @else
            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 uppercase tracking-widest border border-slate-200 dark:border-slate-700 shrink-0">{{ __('Tenant') }}</span>
        @endif
    </div>

    {{-- 區域二：Info Grid --}}
    <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
        <div>
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Users') }}</p>
            <p class="text-sm font-extrabold text-slate-700 dark:text-slate-300">{{ $role->users()->count() }}</p>
        </div>
        <div>
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Permissions') }}</p>
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
            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $displayPermissions->count() }}</p>
        </div>
        @if($displayPermissions->count() > 0)
        <div class="col-span-2">
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ __('Permission Tags') }}</p>
            <div class="flex flex-wrap gap-1">
                @foreach($displayPermissions->take(4) as $permission)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 uppercase tracking-widest">{{ __($permission->name) }}</span>
                @endforeach
                @if($displayPermissions->count() > 4)
                    <div x-data="{ open: false }">
                        <button type="button" @click="open = true"
                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700 uppercase tracking-widest hover:bg-cyan-500/10 hover:text-cyan-600 hover:border-cyan-400/30 dark:hover:text-cyan-400 transition-all cursor-pointer">
                            +{{ $displayPermissions->count() - 4 }}
                        </button>

                        <template x-teleport="body">
                            <div x-show="open" 
                                class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-y-auto"
                                x-cloak>
                                <div x-show="open" 
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    @click="open = false"
                                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

                                <div x-show="open"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    class="relative w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl p-6">
                                    
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-bold text-slate-800 dark:text-white">{{ $role->display_name ?: $role->name }}</h3>
                                        <button @click="open = false" class="p-1.5 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>

                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($displayPermissions as $permission)
                                            <span class="px-2.5 py-1 text-[10px] bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-300 rounded-full border border-slate-200 dark:border-slate-700 uppercase font-bold tracking-tight">{{ __($permission->name) }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- 區域三：Action Buttons --}}
    <div class="flex items-center gap-3">
        <a href="{{ route($baseRoute . '.edit', $role->id) }}"
            class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
            {{ __('Edit') }}
        </a>
        @if($role->name !== 'super-admin' && (auth()->user()->isSystemAdmin() || !$role->is_system))
        <button type="button"
            @click="if({{ $role->users()->count() }} > 0) { triggerDeleteWarning('{{ __('Cannot delete role with active users.') }}'); return; } confirmDelete('{{ route($baseRoute . '.destroy', $role->id) }}')"
            class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-rose-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
            {{ __('Delete') }}
        </button>
        @endif
    </div>
</div>
