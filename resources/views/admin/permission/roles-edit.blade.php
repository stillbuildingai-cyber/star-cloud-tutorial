@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{ 
    @php
        $isSystemLevel = $role->is_system || (!$role->exists && auth()->user()->isSystemAdmin() && !request()->routeIs('*.sub-account-roles.*'));
        $availablePermissions = $all_permissions->flatten();
        if (!$isSystemLevel) {
            $availablePermissions = $availablePermissions->filter(function($p) {
                return !str_starts_with($p->name, 'menu.basic') && 
                       !str_starts_with($p->name, 'menu.permissions');
            });
        }
        $availableNames = $availablePermissions->pluck('name')->toArray();
        $initialSelected = collect($role->permissions->pluck('name')->toArray())
            ->intersect($availableNames)
            ->values()
            ->toArray();
    @endphp
    selectedPermissions: {{ json_encode($initialSelected) }},
    availableCount: {{ count($availableNames) }},
    activeCategory: '',
    toggleCategory(category, permissions) {
        const allSelected = permissions.every(p => this.selectedPermissions.includes(p));
        if (allSelected) {
            this.selectedPermissions = this.selectedPermissions.filter(p => !permissions.includes(p));
        } else {
            const newPerms = permissions.filter(p => !this.selectedPermissions.includes(p));
            this.selectedPermissions = [...this.selectedPermissions, ...newPerms];
        }
    },
    isCategorySelected(permissions) {
        return permissions.length > 0 && permissions.every(p => this.selectedPermissions.includes(p));
    },
    isCategoryPartial(permissions) {
        const selectedCount = permissions.filter(p => this.selectedPermissions.includes(p)).length;
        return selectedCount > 0 && selectedCount < permissions.length;
    },
    toggleParent(parentName, childrenNames) {
        const isSelected = this.selectedPermissions.includes(parentName);
        if (isSelected) {
            // 取消父項目與所有子項目
            this.selectedPermissions = this.selectedPermissions.filter(p => p !== parentName && !childrenNames.includes(p));
        } else {
            // 勾選父項目
            if (!this.selectedPermissions.includes(parentName)) {
                this.selectedPermissions.push(parentName);
            }
            // 勾選所有尚未勾選的子項目
            childrenNames.forEach(p => {
                if (!this.selectedPermissions.includes(p)) this.selectedPermissions.push(p);
            });
        }
    },
    isParentSelected(parentName, childrenNames) {
        return this.selectedPermissions.includes(parentName) && 
               childrenNames.every(p => this.selectedPermissions.includes(p));
    },
    isParentPartial(parentName, childrenNames) {
        const hasParent = this.selectedPermissions.includes(parentName);
        const selectedChildrenCount = childrenNames.filter(p => this.selectedPermissions.includes(p)).length;
        return (hasParent && selectedChildrenCount < childrenNames.length) || 
               (!hasParent && selectedChildrenCount > 0);
    },
    scrollTo(id) {
        const el = document.getElementById(id);
        if (el) {
            window.scrollTo({
                top: el.offsetTop - 100,
                behavior: 'smooth'
            });
            this.activeCategory = id;
        }
    }
}">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <a href="{{ $back_url }}" class="inline-flex items-center justify-center p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-slate-900 dark:hover:text-white transition-all shadow-sm">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <div>
                <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight font-display">
                    {{ $title }}
                </h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2.5 py-0.5 rounded-lg bg-cyan-500/10 text-cyan-500 text-[10px] font-black tracking-widest uppercase">
                        {{ $role->name ?: __('New Role') }}
                    </span>
                    @if($isSystemLevel)
                        <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest">
                            • {{ __('System Role') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" form="role-permissions-form" class="btn-luxury-primary px-8 py-3">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                <span>{{ __('Save Changes') }}</span>
            </button>
        </div>
    </div>    @if(auth()->user()->roles->contains('id', $role->id))
        <div class="my-4 p-4 rounded-2xl bg-amber-500/5 border border-amber-500/20 flex items-center gap-4 animate-luxury-in ring-1 ring-amber-500/10">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0 text-amber-500">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <div>
                <p class="text-sm font-bold text-amber-800 dark:text-amber-200 leading-tight">{{ __('Modifying your own administrative permissions may result in losing access to certain system functions.') }}</p>
            </div>
        </div>
    @endif

    @php
        $isSubAccountRole = request()->routeIs('*.sub-account-roles.*');
        if ($role->exists) {
            $action = route($isSubAccountRole ? 'admin.data-config.sub-account-roles.update' : 'admin.permission.roles.update', $role->id);
            $method = 'PUT';
        } else {
            $action = route($isSubAccountRole ? 'admin.data-config.sub-account-roles.store' : 'admin.permission.roles.store');
            $method = 'POST';
        }
    @endphp

    <form id="role-permissions-form" action="{{ $action }}" method="POST" class="flex flex-col lg:flex-row gap-6 items-start">
        @csrf
        @if($method === 'PUT')
            @method('PUT')
        @endif

        <!-- Sidebar: Control Panel -->
        <aside class="w-full lg:w-80 lg:sticky top-24 z-10 space-y-6">
            <!-- Role Info Card -->
            <div class="luxury-card p-6 rounded-3xl border border-slate-200/50 dark:border-slate-800/50 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <h2 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{ __('Role Identification') }}</h2>
                </div>

                <div class="space-y-5">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest pl-1">{{ __('Role Name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $role->name) }}" required 
                            class="luxury-input w-full @error('name') border-rose-500 @enderror @if($role->name === 'super-admin') bg-slate-50 dark:bg-slate-800/50 cursor-not-allowed @endif" 
                            placeholder="{{ __('Enter role name') }}"
                            {{ $role->name === 'super-admin' ? 'readonly' : '' }}>
                        @error('name')
                            <p class="text-[11px] text-rose-500 font-bold mt-1.5 px-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest pl-1">{{ __('Affiliated Unit') }}</label>
                        <div class="px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 flex items-center gap-3 group">
                            <div class="w-8 h-8 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center">
                                @if($isSystemLevel)
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                @else
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                @endif
                            </div>
                            <span class="text-sm font-black text-slate-700 dark:text-slate-200">
                                @if($isSystemLevel)
                                    {{ __('System Official') }}
                                @else
                                    {{ $role->company->name ?? auth()->user()->company->name ?? '-' }}
                                @endif
                            </span>
                            @if(auth()->user()->isSystemAdmin())
                                <input type="hidden" name="is_system" value="{{ $role->exists ? ($role->is_system ? 1 : 0) : ($isSystemLevel ? 1 : 0) }}">
                            @endif
                        </div>
                    </div>
            <!-- Sidebar: Stats Card -->
            <div class="luxury-card p-4 rounded-3xl border border-slate-200/50 dark:border-slate-800/50 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Total Selected') }}</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-display font-black text-cyan-500" x-text="selectedPermissions.length">0</span>
                            <span class="text-xs font-bold text-slate-400">/ <span x-text="availableCount"></span></span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/5 flex items-center justify-center text-emerald-500">
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>
            
            {{-- Tip Box removed --}}
        </aside>

        <!-- Main Content (Permissions Matrix) -->
        <div class="flex-1 w-full space-y-12">
            @foreach($all_permissions as $group => $permissions)
                @php 
                    // 如果非系統角色，過濾掉敏感權限
                    if (!$isSystemLevel && $group === 'menu') {
                        $permissions = $permissions->filter(function($p) {
                            return !str_starts_with($p->name, 'menu.basic') && 
                                   !str_starts_with($p->name, 'menu.permissions');
                        });
                    }
                    $groupId = 'group-' . $group;
                    $groupPermissions = $permissions->pluck('name')->toArray();
                @endphp
                <section id="{{ $groupId }}" class="luxury-card p-6 md:p-8 rounded-3xl border border-slate-200/50 dark:border-slate-800/50 shadow-sm animate-luxury-in scroll-mt-32">
                    <div class="flex items-center justify-between mb-10 group/title">
                        <div class="flex items-center gap-3">
                            <span class="w-1.5 h-6 rounded-full bg-cyan-500"></span>
                            <h2 class="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight whitespace-nowrap">
                                {{ __($group == 'menu' ? 'Menu Permissions' : 'Other Permissions') }}
                            </h2>
                        </div>
                        <label class="flex items-center gap-3 px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 cursor-pointer hover:bg-cyan-50 dark:hover:bg-cyan-500/10 transition-all border border-transparent hover:border-cyan-100 dark:hover:border-cyan-500/20 group">
                            <span class="text-[11px] font-black text-slate-400 group-hover:text-cyan-600 uppercase tracking-widest transition-colors">{{ __('Select All') }}</span>
                            <div class="relative flex items-center">
                                <input type="checkbox" 
                                    @change="toggleCategory('{{ $group }}', {{ json_encode($groupPermissions) }})"
                                    :checked="isCategorySelected({{ json_encode($groupPermissions) }})"
                                    :indeterminate="isCategoryPartial({{ json_encode($groupPermissions) }})"
                                    class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 text-cyan-500 focus:ring-cyan-500/20 transition-all cursor-pointer accent-cyan-500">
                            </div>
                        </label>
                    </div>

                    @if($group === 'menu')
                        <div class="grid grid-cols-1 gap-8">
                            @php
                                $parents = $permissions->filter(fn($p) => count(explode('.', $p->name)) === 2);
                            @endphp

                            @foreach($parents as $parent)
                                @php
                                    $children = $permissions->filter(function($p) use ($parent) {
                                        if ($p->name === $parent->name) return false;
                                        // 取得基礎前綴，例如 menu.basic-settings -> basic, menu.permission -> permission
                                        $parentSlug = explode('.', $parent->name)[1] ?? '';
                                        $parentBase = rtrim(str_replace('-settings', '', $parentSlug), 's');
                                        
                                        $childSlug = explode('.', $p->name)[1] ?? '';
                                        return str_starts_with($childSlug, $parentBase);
                                    });
                                @endphp

                                <div class="rounded-3xl border border-slate-100/80 dark:border-slate-800/80 overflow-hidden bg-slate-50/30 dark:bg-slate-900/20 hover:border-cyan-500/30 transition-all duration-300"
                                     x-data="{ expanded: true }">
                                    <div class="p-4 flex items-center gap-4">
                                        <div class="flex-1 flex items-center gap-3">
                                            @if($children->count() > 0)
                                                <button type="button" @click="expanded = !expanded" class="w-9 h-9 flex-shrink-0 rounded-xl bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-slate-400 hover:text-cyan-500 dark:hover:text-cyan-400 transition-all border border-slate-100 dark:border-slate-800 hover:border-cyan-500/30">
                                                    <svg class="w-4 h-4 transition-transform duration-300" :class="expanded ? 'rotate-180 text-cyan-500' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                                </button>
                                            @endif
                                            <div class="flex flex-col">
                                                <h3 class="text-base font-black text-slate-800 dark:text-white leading-tight tracking-tight">
                                                    {{ __($parent->name) }}
                                                </h3>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center pr-1">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="permissions[]" value="{{ $parent->name }}" 
                                                    :checked="selectedPermissions.includes('{{ $parent->name }}')"
                                                    @change="toggleParent('{{ $parent->name }}', {{ json_encode($children->pluck('name')->toArray()) }})"
                                                    class="sr-only peer">
                                                <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 rounded-full peer peer-focus:outline-none peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500 transition-all duration-200 shadow-inner"
                                                     :class="isParentPartial('{{ $parent->name }}', {{ json_encode($children->pluck('name')->toArray()) }}) ? 'ring-2 ring-cyan-500/50' : ''"></div>
                                            </label>
                                        </div>
                                    </div>

                                    @if($children->count() > 0)
                                        <div x-show="expanded" x-collapse>
                                            <div class="px-8 pb-8 pt-2 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 bg-transparent">
                                                @foreach($children as $child)
                                                    <label class="group relative flex items-center justify-between p-4 rounded-2xl border border-transparent bg-slate-100/50 dark:bg-slate-900/40 cursor-pointer transition-all hover:bg-cyan-50/50 dark:hover:bg-cyan-900/10 hover:shadow-sm hover:border-cyan-500/20">
                                                        <div class="flex flex-col flex-1 min-w-0 mr-3">
                                                            <span class="text-sm font-bold text-slate-500 dark:text-slate-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors break-words">{{ __($child->name) }}</span>
                                                        </div>
                                                        <div class="relative flex items-center flex-shrink-0">
                                                            <input type="checkbox"
                                                                name="permissions[]"
                                                                value="{{ $child->name }}"
                                                                x-model="selectedPermissions"
                                                                class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-700 text-cyan-500 focus:ring-cyan-500/20 transition-all cursor-pointer accent-cyan-500">
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @foreach($permissions as $perm)
                                <label class="group relative flex items-center justify-between p-4 rounded-2xl border border-slate-100/80 dark:border-slate-800/80 bg-slate-50/30 dark:bg-slate-900/20 hover:border-cyan-500/40 cursor-pointer transition-all duration-300">
                                    <div class="flex flex-col flex-1">
                                        <span class="text-sm font-black text-slate-500 dark:text-slate-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                            {{ __($perm->name) }}
                                        </span>
                                        <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-widest font-mono">
                                            {{ $perm->name }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0 pr-1">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                            x-model="selectedPermissions"
                                            class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 text-cyan-500 focus:ring-cyan-500/20 transition-all cursor-pointer accent-cyan-500">
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach

            <!-- Action Bar -->
            <div class="pt-8 flex items-center justify-end gap-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ $back_url }}" class="btn-luxury-ghost px-8">{{ __('Cancel') }}</a>
                <button type="submit" class="btn-luxury-primary px-12 h-14 shadow-xl shadow-cyan-500/20">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <span>{{ __('Save Permissions') }}</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<style>
    [x-cloak] { display: none !important; }
    
    .animate-luxury-in {
        animation: luxuryIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    @keyframes luxuryIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection
