{{-- 1. 儀表板 --}}
<li>
    <a class="luxury-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.dashboard') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
        <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Dashboard') }}</span>
    </a>
</li>

{{-- 2. 應用程式 (個人) --}}
<li x-data="{ open: localStorage.getItem('menu_profile') === 'true' || {{ request()->routeIs('profile.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_profile', open)"
            class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('profile.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Profile Settings') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            <li>
                <a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('profile.edit') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('profile.edit') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Profile') }}</span>
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- 
@can('menu.members')
3. 會員管理
<li x-data="{ open: localStorage.getItem('menu_members') === 'true' || {{ request()->routeIs('admin.members.*') || request()->routeIs('admin.membership-tiers.*') || request()->routeIs('admin.deposit-bonus-rules.*') || request()->routeIs('admin.point-rules.*') || request()->routeIs('admin.gift-definitions.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_members', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.members.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Member Management') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.members.index') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.members.index') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Member List') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.membership-tiers.*') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.membership-tiers.index') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Membership Tiers') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.deposit-bonus-rules.*') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.deposit-bonus-rules.index') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Deposit Bonus') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.point-rules.*') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.point-rules.index') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Point Rules') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.gift-definitions.*') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.gift-definitions.index') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Gift Definitions') }}</span></a></li>
        </ul>
    </div>
</li>
@endcan
--}}


@can('menu.machines')
{{-- 4. 機台管理 --}}
<li x-data="{ open: localStorage.getItem('menu_machines') === 'true' || {{ request()->routeIs('admin.machines.*') || request()->routeIs('admin.maintenance.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_machines', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.machines.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 01-2 2v4a2 2 0 012 2h14a2 2 0 012-2v-4a2 2 0 01-2-2m-2-4h.01M17 16h.01" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Machine Management') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            @can('menu.machines.list')
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.machines.index') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.machines.index') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Machine List') }}</span>
            </a></li>
            @endcan

            @can('menu.machines.permissions')
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.machines.permissions') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.machines.permissions') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Machine Permissions') }}</span>
            </a></li>
            @endcan

            @can('menu.machines.utilization')
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.machines.utilization') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.machines.utilization') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Utilization Rate') }}</span>
            </a></li>
            @endcan
            @can('menu.machines.maintenance')
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.maintenance.*') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.maintenance.index') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Maintenance Records') }}</span>
            </a></li>
            @endcan
        </ul>
    </div>
</li>
@endcan


{{-- 
@can('menu.app')
5. APP管理
<li x-data="{ open: localStorage.getItem('menu_app') === 'true' || {{ request()->routeIs('admin.app.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_app', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.app.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('APP Management') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.app.ui-elements') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.app.ui-elements') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('UI Elements') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.app.helper') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.app.helper') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Helper') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.app.questionnaire') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.app.questionnaire') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Questionnaire') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.app.games') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.app.games') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Games') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.app.timer') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.app.timer') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Timer') }}</span></a></li>
        </ul>
    </div>
</li>
@endcan
--}}


@can('menu.analysis')
{{-- 8. 分析管理 --}}
<li x-data="{ open: localStorage.getItem('menu_analysis') === 'true' || {{ request()->routeIs('admin.analysis.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_analysis', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.analysis.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Analysis Management') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            @can('menu.analysis.machine-reports')
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.analysis.machine-reports') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.analysis.machine-reports') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2" /><line x1="8" y1="21" x2="16" y2="21" /><line x1="12" y1="17" x2="12" y2="21" /><path d="M6 12l3-3 3 3 6-6" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Machine Reports') }}</span>
            </a></li>
            @endcan
        </ul>
    </div>
</li>
@endcan


{{--
@can('menu.audit')
9. 稽核管理
<li x-data="{ open: localStorage.getItem('menu_audit') === 'true' || {{ request()->routeIs('admin.audit.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_audit', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.audit.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Audit Management') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.audit.purchases') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.audit.purchases') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Purchase Audit') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.audit.transfers') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.audit.transfers') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Transfer Audit') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.audit.replenishments') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.audit.replenishments') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Replenishment Audit') }}</span></a></li>
        </ul>
    </div>
</li>
@endcan
--}}


@can('menu.data-config')
{{-- 10. 資料設定 --}}
<li x-data="{ open: localStorage.getItem('menu_data_config') === 'true' || {{ request()->routeIs('admin.data-config.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_data_config', open)" class="luxury-nav-item w-full text-start group">
         <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.data-config.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Data Configuration') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            @can('menu.data-config.sub-accounts')
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.data-config.sub-accounts') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.data-config.sub-accounts') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Sub Accounts') }}</span>
            </a></li>
            @endcan

            {{-- 
            @can('menu.data-config.points')
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.data-config.points') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.data-config.points') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Point Settings') }}</span>
            </a></li>
            @endcan
            --}}

        </ul>
    </div>
</li>
@endcan



@can('menu.remote')
{{-- 11. 遠端管理 --}}
<li x-data="{ open: localStorage.getItem('menu_remote') === 'true' || {{ request()->routeIs('admin.remote.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_remote', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.remote.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Remote Management') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            @can('menu.remote.commands')
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.remote.index') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.remote.index') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Command Center') }}</span>
            </a></li>
            @endcan
        </ul>
    </div>
</li>
@endcan


{{-- 
@can('menu.line')
12. Line管理
<li x-data="{ open: localStorage.getItem('menu_line') === 'true' || {{ request()->routeIs('admin.line.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_line', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.line.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Line Management') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.line.official-account') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.line.official-account') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Official Account') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.line.members') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.line.members') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Line Members') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.line.machines') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.line.machines') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Line Machines') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.line.products') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.line.products') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Line Products') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.line.orders') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.line.orders') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Line Orders') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.line.coupons') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.line.coupons') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Line Coupons') }}</span></a></li>
        </ul>
    </div>
</li>
@endcan
--}}

{{-- 
@can('menu.reservation')
13. 預約系統
<li x-data="{ open: localStorage.getItem('menu_reservation') === 'true' || {{ request()->routeIs('admin.reservation.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_reservation', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.reservation.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Reservation System') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.reservation.reservations') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.reservation.reservations') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Reservation List') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.reservation.members') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.reservation.members') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Reservation Members') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.reservation.stores') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.reservation.stores') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Stores Management') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.reservation.time-slots') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.reservation.time-slots') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Time Slots') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.reservation.venues') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.reservation.venues') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Venues Management') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.reservation.coupons') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.reservation.coupons') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Reservation Coupons') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.reservation.orders') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.reservation.orders') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Reservation Orders') }}</span></a></li>
        </ul>
    </div>
</li>
@endcan
--}}

{{-- 
@can('menu.special-permission')
<li x-data="{ open: localStorage.getItem('menu_special_permission') === 'true' || {{ request()->routeIs('admin.special-permission.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_special_permission', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.special-permission.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Special Permission') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.special-permission.clear-stock') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.special-permission.clear-stock') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Clear Stock') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.special-permission.apk-versions') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.special-permission.apk-versions') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('APK Versions') }}</span></a></li>
            <li><a class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.special-permission.discord-notifications') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.special-permission.discord-notifications') }}"><span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Discord Notifications') }}</span></a></li>
        </ul>
    </div>
</li>
@endcan
--}}

@if(auth()->user()->isSystemAdmin() || (auth()->user()->isTenant() && auth()->user()->can('menu.basic.machines')))
{{-- 14.5. 基本設定 --}}
<li x-data="{ open: localStorage.getItem('menu_basic_settings') === 'true' || {{ request()->routeIs('admin.basic-settings.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_basic_settings', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.basic-settings.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Basic Settings') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            @can('menu.basic.machines')
            <li><a class="flex items-center gap-x-3 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.basic-settings.machines.*') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.basic-settings.machines.index') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Machine Settings') }}</span>
            </a></li>
            @endcan
        </ul>
    </div>
</li>
@endif

@if(auth()->user()->isSystemAdmin())
@can('menu.permissions')
{{-- 15. 權限設定 --}}
<li x-data="{ open: localStorage.getItem('menu_permissions') === 'true' || {{ request()->routeIs('admin.permission.*') ? 'true' : 'false' }} }">
    <button type="button" @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; } localStorage.setItem('menu_permissions', open)" class="luxury-nav-item w-full text-start group">
        <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.permission.*') ? 'text-cyan-500' : 'text-slate-600 group-hover:text-cyan-500 dark:text-slate-300 dark:group-hover:text-cyan-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
        <div class="flex flex-1 items-center justify-between whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
            <span>{{ __('Permission Settings') }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-300 text-slate-600 dark:text-slate-300" :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>
    <div x-show="open && !sidebarCollapsed" x-collapse>
        <ul class="luxury-submenu" data-sidebar-sub>
            @can('menu.permissions.companies')
            <li><a class="flex items-center gap-x-3 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.permission.companies.*') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.permission.companies.index') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Customer Management') }}</span>
            </a></li>
            @endcan
            @can('menu.permissions.accounts')
            <li><a class="flex items-center gap-x-3 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.permission.accounts') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.permission.accounts') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Account Management') }}</span>
            </a></li>
            @endcan
            @can('menu.permissions.roles')
            <li><a class="flex items-center gap-x-3 py-2 px-2.5 text-sm transition-colors rounded-lg {{ request()->routeIs('admin.permission.roles') ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route('admin.permission.roles') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300" :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">{{ __('Role Permissions') }}</span>
            </a></li>
            @endcan
        </ul>
    </div>
</li>
@endcan
@endif

