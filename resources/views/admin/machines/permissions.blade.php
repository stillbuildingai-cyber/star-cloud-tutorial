@extends('layouts.admin')

@section('content')
<div class="space-y-4 pb-20" x-data="{ 
    permissionSearchQuery: '',
    showPermissionModal: false,
    isPermissionsLoading: false,
    isLoadingTable: false,
    async fetchPage(url) {
        if (!url || this.isLoadingTable) return;
        this.isLoadingTable = true;
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector('#ajax-content-container');
            if (newContent) {
                document.querySelector('#ajax-content-container').innerHTML = newContent.innerHTML;
                window.history.pushState({}, '', url);
                
                // Re-initialize Preline UI components
                if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
                    window.HSStaticMethods.autoInit();
                }
            }
        } catch (e) {
            console.error('AJAX Load Failed:', e);
        } finally {
            this.isLoadingTable = false;
        }
    },
    targetUserId: null,
    targetUserName: '',
    allMachines: [],
    allMachinesCount: 0,
    permissions: {},
    openPermissionModal(user) {
        this.targetUserId = user.id;
        this.targetUserName = user.name;
        this.showPermissionModal = true;
        this.isPermissionsLoading = true;
        this.permissions = {};
        this.allMachines = [];
        this.permissionSearchQuery = '';

        fetch(`/admin/machines/permissions/accounts/${user.id}`)
            .then(res => res.json())
            .then(data => {
                if (data.machines) {
                    this.allMachines = data.machines;
                    this.allMachinesCount = data.machines.length;
                    const tempPermissions = {};
                    data.machines.forEach(m => {
                        tempPermissions[m.id] = (data.assigned_ids || []).includes(m.id);
                    });
                    this.permissions = tempPermissions;
                }
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Failed to load permissions') }}', type: 'error' } }));
            })
            .finally(() => {
                this.isPermissionsLoading = false;
            });
    },
    togglePermission(machineId) {
        this.permissions = { ...this.permissions, [machineId]: !this.permissions[machineId] };
    },
    toggleSelectAll() {
        const filtered = this.allMachines.filter(m => 
            !this.permissionSearchQuery || 
            m.name.toLowerCase().includes(this.permissionSearchQuery.toLowerCase()) || 
            m.serial_no.toLowerCase().includes(this.permissionSearchQuery.toLowerCase())
        );
        if (filtered.length === 0) return;
        const allSelected = filtered.every(m => this.permissions[m.id]);
        filtered.forEach(m => this.permissions[m.id] = !allSelected);
    },
    savePermissions() {
        const machineIds = Object.keys(this.permissions).filter(id => this.permissions[id]);
        
        fetch(`/admin/machines/permissions/accounts/${this.targetUserId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ machine_ids: machineIds })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.showPermissionModal = false;
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Permissions updated successfully') }}', type: 'success' } }));
                this.fetchPage(window.location.href);
            } else {
                throw new Error(data.error || 'Update failed');
            }
        })
        .catch(e => {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
        });
    }
}" @ajax:navigate.window.prevent="fetchPage($event.detail.url)">
    <!-- 1. Header Area -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white tracking-tight font-display">{{ __('Machine Permissions') }}</h1>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">{{
                __('Manage machine access permissions') }}</p>
        </div>
    </div>

    <!-- 2. Main Content Card -->
    <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden">
        <!-- Loading Spinner Overlay -->
        <div x-show="isLoadingTable" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] rounded-3xl" x-cloak>
            
            <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin"></div>
                <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin" style="animation-duration: 3s; direction: reverse;"></div>
                <div class="relative w-8 h-8 flex items-center justify-center">
                    <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
            </div>
            <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">{{ __('Loading Data') }}...</p>
        </div>

        <div id="ajax-content-container" 
             :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': isLoadingTable }">

        <!-- Toolbar & Filters -->
        <div class="flex flex-col md:flex-row items-center justify-between mb-8 gap-4">
            <form method="GET" action="{{ route('admin.machines.permissions') }}"
                class="flex flex-wrap items-center gap-4 w-full md:w-auto"
                @submit.prevent="
                    const url = new URL($el.action, window.location.origin);
                    new FormData($el).forEach((value, key) => {
                        if (value.trim()) url.searchParams.set(key, value);
                        else url.searchParams.delete(key);
                    });
                    fetchPage(url.pathname + url.search);
                ">

                <div class="relative group flex-1 sm:flex-none">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search accounts...') }}"
                        class="luxury-input py-2.5 pl-12 pr-6 block w-full sm:w-64 text-sm font-bold">
                </div>

                @if(auth()->user()->isSystemAdmin())
                <div class="w-full sm:w-72">
                    <x-searchable-select name="company_id" :options="$companies" :selected="request('company_id')"
                        :placeholder="__('All Companies')"
                        x-on:change="$el.closest('form').dispatchEvent(new Event('submit', { cancelable: true }))" />
                </div>
                @endif

                <div class="flex items-center gap-2 flex-none ml-auto sm:ml-0">
                    <button type="submit" class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 group transition-all active:scale-95" title="{{ __('Search') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                    <button type="button" 
                        @click="
                            $el.closest('form').querySelectorAll('input[type=text]').forEach(i => i.value = '');
                            $el.closest('form').querySelectorAll('select').forEach(s => {
                                s.value = ' ';
                                const instance = window.HSSelect?.getInstance(s);
                                if (instance) instance.setValue(' ');
                            });
                            $el.closest('form').dispatchEvent(new Event('submit', { cancelable: true }));
                        "
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 group transition-all active:scale-95" title="{{ __('Reset') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <div class="hidden xl:block overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-y-0">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                            {{ __('Account Info') }}</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                            {{ __('Company Name') }}</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                            {{ __('Authorized Machines') }}</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                            {{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                    @forelse($users_list as $user)
                    <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                        <td class="px-6 py-6 font-display">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm overflow-hidden shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{
                                        $user->name }}</span>
                                    <span
                                        class="text-xs font-mono font-bold text-slate-500 tracking-widest uppercase">{{
                                        $user->username }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6">
                            <span
                                class="px-2.5 py-1 rounded-lg text-xs font-bold border border-sky-100 dark:border-sky-900/30 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 tracking-widest uppercase">
                                {{ $user->company->name ?? __('System') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div
                                class="flex flex-wrap gap-2 justify-center lg:justify-start max-w-[420px] mx-auto lg:mx-0 max-h-[140px] overflow-y-auto pr-2 custom-scrollbar py-1">
                                @forelse($user->machines as $m)
                                <div
                                    class="px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-white/5 hover:border-cyan-500/30 transition-all duration-300">
                                    <span class="text-xs font-black text-slate-700 dark:text-slate-200 leading-tight">{{
                                        $m->name }}</span>
                                </div>
                                @empty
                                <div class="w-full text-center lg:text-left">
                                    <span
                                        class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest opacity-40 italic">--
                                        {{ __('None') }} --</span>
                                </div>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-6 text-right">
                            <button
                                @click="openPermissionModal({{ json_encode(['id' => $user->id, 'name' => $user->name]) }})"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-500 hover:text-white transition-all duration-300 text-xs font-black uppercase tracking-widest shadow-sm shadow-cyan-500/5 group/auth">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>{{ __('Authorize') }}</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-24 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" stroke-width="1.5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="m17 8 5 5m0-5-5 5" />
                                </svg>
                                <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No accounts found') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile & Tablet Card View -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 xl:hidden">
            @forelse($users_list as $user)
            <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
                
                <!-- Card Header -->
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 truncate group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">
                                {{ $user->name }}
                            </h3>
                            <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate mt-0.5">
                                {{ $user->username }}
                            </p>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-[10px] font-bold border border-sky-100 dark:border-sky-900/30 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 tracking-widest uppercase">
                            {{ $user->company->name ?? __('System') }}
                        </span>
                    </div>
                </div>

                <!-- Info Grid (Authorized Machines) -->
                <div class="mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Authorized Machines') }}</span>
                        <span class="text-[10px] font-bold text-cyan-500 bg-cyan-50 dark:bg-cyan-500/10 px-2 py-0.5 rounded-full">{{ $user->machines->count() }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2 max-h-[120px] overflow-y-auto pr-2 custom-scrollbar">
                        @forelse($user->machines as $m)
                        <div class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 leading-none">{{ $m->name }}</span>
                        </div>
                        @empty
                        <div class="w-full flex items-center justify-center py-4">
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest opacity-40 italic">-- {{ __('None') }} --</span>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3">
                    <button type="button" @click="openPermissionModal({{ json_encode(['id' => $user->id, 'name' => $user->name]) }})"
                            class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-cyan-600 dark:text-cyan-400 font-black text-xs uppercase tracking-widest hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        {{ __('Authorize') }}
                    </button>
                </div>
            </div>
            @empty
            <div class="col-span-full py-20 text-center flex flex-col items-center gap-4 bg-white/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800/50 luxury-card">
                <div class="flex flex-col items-center gap-3">
                    <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" stroke-width="1.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m17 8 5 5m0-5-5 5" />
                    </svg>
                    <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No accounts found') }}</p>
                </div>
            </div>
            @endforelse
        </div>
        <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
            @if($users_list->total() > 0)
                {{ $users_list->appends(request()->query())->links('vendor.pagination.luxury') }}
            @else
                <div class="flex items-center justify-between gap-4 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                    <span>{{ __('No records found') }}</span>
                    <span>0 / 0</span>
                </div>
            @endif
        </div>
        </div>
    </div>


    <!-- Machine Permissions Modal -->
    <template x-teleport='body'>
        <div x-show='showPermissionModal' class='fixed inset-0 z-[160] overflow-y-auto' x-cloak>
            <div class='flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0'>
                <div x-show='showPermissionModal' @click='showPermissionModal = false'
                    x-transition:enter='ease-out duration-300' x-transition:enter-start='opacity-0'
                    x-transition:enter-end='opacity-100' x-transition:leave='ease-in duration-200'
                    x-transition:leave-start='opacity-100' x-transition:leave-end='opacity-0'
                    class='fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity'></div>

                <span class='hidden sm:inline-block sm:align-middle sm:h-screen'>&#8203;</span>

                <div x-show='showPermissionModal' x-transition:enter='ease-out duration-300'
                    x-transition:enter-start='opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95'
                    x-transition:enter-end='opacity-100 translate-y-0 sm:scale-100'
                    x-transition:leave='ease-in duration-200'
                    x-transition:leave-start='opacity-100 translate-y-0 sm:scale-100'
                    x-transition:leave-end='opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95'
                    class='inline-block px-8 py-10 text-left align-bottom transition-all transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full overflow-hidden animate-luxury-in'>

                    <div class='flex justify-between items-center mb-8'>
                        <div>
                            <h3 class='text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight'>
                                {{ __('Authorized Machines Management') }}</h3>
                            <div class='flex items-center gap-2 mt-1'>
                                <span class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]'>{{
                                    __('Account') }}:</span>
                                <span class='text-xs font-bold text-cyan-500 uppercase tracking-widest'
                                    x-text='targetUserName'></span>
                            </div>
                        </div>
                        <button @click='showPermissionModal = false'
                            class='text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors bg-slate-50 dark:bg-slate-800 p-2 rounded-xl'>
                            <svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5'
                                    d='M6 18L18 6M6 6l12 12' />
                            </svg>
                        </button>
                    </div>

                    <div class='relative min-h-[400px]'>
                        <div class='mb-6 flex flex-col md:flex-row gap-4 items-center'>
                            <div class='flex-1 relative group w-full'>
                                <span class='absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10'>
                                    <svg class='w-4 h-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors'
                                        viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5'
                                        stroke-linecap='round' stroke-linejoin='round'>
                                        <circle cx='11' cy='11' r='8'></circle>
                                        <line x1='21' y1='21' x2='16.65' y2='16.65'></line>
                                    </svg>
                                </span>
                                <input type='text' x-model='permissionSearchQuery'
                                    placeholder='{{ __("Search machines...") }}'
                                    class='luxury-input py-3 pl-12 pr-6 block w-full text-sm font-bold' @click.stop>
                            </div>
                            <button @click="toggleSelectAll()"
                                class="shrink-0 flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200 dark:border-slate-700 font-black text-xs uppercase tracking-widest">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span
                                    x-text="allMachines.filter(m => !permissionSearchQuery || m.name.toLowerCase().includes(permissionSearchQuery.toLowerCase()) || m.serial_no.toLowerCase().includes(permissionSearchQuery.toLowerCase())).every(m => permissions[m.id]) ? '{{ __('Deselect All') }}' : '{{ __('Select All') }}'"></span>
                            </button>
                        </div>

                        <template x-if='isPermissionsLoading'>
                            <div
                                class='absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-10 rounded-2xl'>
                                <div class='flex flex-col items-center gap-3'>
                                    <div
                                        class='w-10 h-10 border-4 border-cyan-500/20 border-t-cyan-500 rounded-full animate-spin'>
                                    </div>
                                    <span
                                        class='text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.2em] animate-pulse'>{{
                                        __('Syncing Permissions...') }}</span>
                                </div>
                            </div>
                        </template>

                        <div
                            class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[450px] overflow-y-auto pr-2 custom-scrollbar p-1'>
                            <template
                                x-for='machine in allMachines.filter(m => !permissionSearchQuery || m.name.toLowerCase().includes(permissionSearchQuery.toLowerCase()) || m.serial_no.toLowerCase().includes(permissionSearchQuery.toLowerCase()))'
                                :key='machine.id'>
                                <div @click='togglePermission(machine.id)'
                                    :class='permissions[machine.id] ? "border-cyan-500 bg-cyan-500/5 dark:bg-cyan-500/10 ring-1 ring-cyan-500/20" : "border-slate-100 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-600"'
                                    class='p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300 group relative overflow-hidden shadow-sm hover:shadow-md'>
                                    <div class='flex flex-col relative z-10'>
                                        <div class='flex items-center gap-2'>
                                            <div class='w-2 h-2 rounded-full'
                                                :class='permissions[machine.id] ? "bg-cyan-500" : "bg-slate-300 dark:bg-slate-700"'>
                                            </div>
                                            <span class='text-sm font-extrabold truncate'
                                                :class='permissions[machine.id] ? "text-cyan-600 dark:text-cyan-400" : "text-slate-700 dark:text-slate-300"'
                                                x-text='machine.name'></span>
                                        </div>
                                        <span
                                            class='text-[10px] font-mono font-bold text-slate-400 mt-2 tracking-widest uppercase'
                                            x-text='machine.serial_no'></span>
                                    </div>
                                    <div
                                        class='absolute -right-2 -bottom-2 opacity-[0.03] text-slate-900 dark:text-white pointer-events-none group-hover:scale-110 transition-transform duration-700'>
                                        <svg class='w-20 h-20' fill='currentColor' viewBox='0 0 24 24'>
                                            <path
                                                d='M5 2h14c1.1 0 2 .9 2 2v16c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2zm0 2v16h14V4H5zm3 3h8v6H8V7zm0 8h3v2H8v-2zm5 0h3v2h-3v-2z' />
                                        </svg>
                                    </div>
                                    <div class='absolute top-4 right-4 animate-luxury-in'
                                        x-show='permissions[machine.id]'>
                                        <div
                                            class='w-5 h-5 rounded-full bg-cyan-500 flex items-center justify-center shadow-lg shadow-cyan-500/30'>
                                            <svg class='w-3 h-3 text-white' fill='none' stroke='currentColor'
                                                viewBox='0 0 24 24'>
                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='3'
                                                    d='M5 13l4 4L19 7' />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div
                        class='flex flex-col sm:flex-row justify-between items-center mt-10 pt-8 border-t border-slate-100 dark:border-slate-800 gap-6'>
                        <div class='flex items-center gap-3'>
                            <div class='flex -space-x-2'>
                                <template x-for='i in Math.min(3, Object.values(permissions).filter(v => v).length)'
                                    :key='i'>
                                    <div
                                        class='w-6 h-6 rounded-full border-2 border-white dark:border-slate-900 bg-cyan-500 flex items-center justify-center'>
                                        <svg class='w-3 h-3 text-white' fill='currentColor' viewBox='0 0 24 24'>
                                            <path
                                                d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5v-9l6 4.5-6 4.5z' />
                                        </svg>
                                    </div>
                                </template>
                            </div>
                            <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]'>
                                {{ __('Selection') }}: <span class='text-cyan-500 text-xs'
                                    x-text='Object.values(permissions).filter(v => v).length'></span> / <span
                                    x-text='allMachines?.length || 0'></span> {{ __('Devices') }}
                            </p>
                        </div>
                        <div class='flex gap-4 w-full sm:w-auto'>
                            <button @click='showPermissionModal = false'
                                class='flex-1 sm:flex-none btn-luxury-ghost px-8'>{{ __('Cancel') }}</button>
                            <button @click='savePermissions()' class='flex-1 sm:flex-none btn-luxury-primary px-12'
                                :disabled='isPermissionsLoading'>
                                <span>{{ __('Update Authorization') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
