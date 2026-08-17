@extends('layouts.admin')

@php
$routeName = request()->route()->getName();
$baseRoute = str_contains($routeName, 'sub-accounts') ? 'admin.data-config.sub-accounts' : 'admin.permission.accounts';

$tab = request('tab', 'accounts');
$roleSelectConfig = [
    "placeholder" => __('Select Role'),
    "hasSearch" => true,
    "searchPlaceholder" => __('Search Role...'),
    "isHidePlaceholder" => false,
    "searchClasses" => "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
    "searchWrapperClasses" => "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
    "toggleClasses" => "hs-select-toggle luxury-select-toggle",
    "dropdownClasses" => "hs-select-menu w-full bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[100] animate-luxury-in",
    "optionClasses" => "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
    "optionTemplate" => '<div class="flex items-center justify-between w-full"><span data-title></span><span class="hs-select-active-indicator hidden text-cyan-500"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></div>'
];
@endphp

@section('content')
<div class="space-y-2 pb-20 relative" x-data="accountManager({
        roles: @js($roles),
        errors: @js($errors->any()),
        oldValues: {
            method: @js(old('_method')),
            id: @js(old('id')),
            name: @js(old('name')),
            username: @js(old('username')),
            email: @js(old('email')),
            phone: @js(old('phone')),
            company_id: @js(old('company_id', auth()->user()->isSystemAdmin() ? '' : auth()->user()->company_id)),
            role: @js(old('role', '')),
            status: @js(old('status', 1))
        },
        activeTab: @js($tab),
        roleSelectConfig: @js($roleSelectConfig)
    })"
    @ajax:navigate:accounts.prevent="fetchTabContent($event.detail.url, 'ajax-accounts-container')"
    @ajax:navigate:roles.prevent="fetchTabContent($event.detail.url, 'ajax-roles-container')">

    <x-luxury-spinner show="isLoadingTable" />

    {{-- Header --}}
    <x-page-header 
        :title="$title"
        :subtitle="__('Manage your company\'s sub-accounts and role permissions')"
    >
        <div x-show="activeTab === 'accounts'" x-cloak>
            <button @click="openCreateModal()" class="btn-luxury-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span>{{ __('Add Account') }}</span>
            </button>
        </div>
        <div x-show="activeTab === 'roles'" x-cloak>
            <a href="{{ route('admin.data-config.sub-account-roles.create') }}" class="btn-luxury-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span>{{ __('Add Role') }}</span>
            </a>
        </div>
    </x-page-header>

    {{-- Tab 導覽列 --}}
    @if(request()->routeIs('admin.data-config.sub-accounts') && auth()->user()->can('menu.data-config.sub-accounts'))
        <x-tab-nav model="activeTab">
            <x-tab-nav-item value="accounts" :label="__('Account List')" model="activeTab" />
            <x-tab-nav-item value="roles" :label="__('Role Management')" model="activeTab" />
        </x-tab-nav>
    @endif

    {{-- 帳號列表 Tab --}}
    <div x-show="activeTab === 'accounts'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak>
        <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden">
            <div id="ajax-accounts-container"
                 :class="{ 'opacity-30 pointer-events-none': isLoadingTable }">
                @include('admin._shared.account-list', [
                    'users'     => $users,
                    'baseRoute' => $baseRoute,
                    'companies' => $companies,
                ])
            </div>
        </div>
    </div>

    {{-- 角色管理 Tab --}}
    <div x-show="activeTab === 'roles'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak>
        <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden">
            <div id="ajax-roles-container"
                 :class="{ 'opacity-30 pointer-events-none': isLoadingTable }">
                @include('admin._shared.role-list', [
                    'roles'      => $paginated_roles,
                    'baseRoute'  => 'admin.data-config.sub-account-roles',
                    'listRoute'  => 'admin.data-config.sub-accounts',
                    'companies'  => $companies,
                ])
            </div>
        </div>
    </div>

    {{-- User Modal --}}
    <div x-show="showModal" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="showModal = false">
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block px-8 py-10 text-left align-bottom transition-all transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full overflow-visible">

                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight"
                        x-text="editing ? '{{ __('Edit Account') }}' : '{{ __('Add Account') }}'"></h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form x-ref="accountForm"
                    :action="!editing ? '{{ route($baseRoute . '.store') }}' : '{{ route($baseRoute) }}/' + currentUser.id"
                    method="POST" class="space-y-6">
                    @csrf
                    <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Full Name') }} <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" x-model="currentUser.name" required class="luxury-input @error('name') border-rose-500 @enderror" placeholder="{{ __('e.g. John Doe') }}">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Username') }} <span class="text-rose-500">*</span></label>
                            <input type="text" name="username" x-model="currentUser.username" required class="luxury-input @error('username') border-rose-500 @enderror" placeholder="{{ __('e.g. johndoe') }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Email') }}</label>
                            <input type="email" name="email" x-model="currentUser.email" class="luxury-input @error('email') border-rose-500 @enderror" placeholder="{{ __('john@example.com') }}">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Phone') }}</label>
                            <input type="text" name="phone" x-model="currentUser.phone" class="luxury-input @error('phone') border-rose-500 @enderror">
                        </div>
                    </div>

                    @if(auth()->user()->isSystemAdmin())
                    <div class="space-y-2 relative z-10 focus-within:z-30">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Affiliation') }}</label>
                        <x-searchable-select id="modal-account-company" name="company_id"
                            placeholder="{{ __('SYSTEM') }}" x-model="currentUser.company_id"
                            @change="currentUser.company_id = $event.target.value; updateRoleSelect()">
                            @foreach($companies as $company)
                            <option value="{{ $company->id }}" data-title="{{ $company->name }}">{{ $company->name }}</option>
                            @endforeach
                        </x-searchable-select>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10 focus-within:z-30">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Role') }} <span class="text-rose-500">*</span></label>
                            <div id="role-select-wrapper" class="relative">{{-- Dynamic --}}</div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Status') }}</label>
                            <x-searchable-select id="modal-account-status" name="status" x-model="currentUser.status" :hasSearch="false">
                                <option value="1">{{ __('Active') }}</option>
                                <option value="0">{{ __('Disabled') }}</option>
                            </x-searchable-select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">
                            <span x-text="editing ? '{{ __('New Password (leave blank to keep current)') }}' : '{{ __('Password') }}'"></span>
                            <template x-if="!editing"><span class="text-rose-500">*</span></template>
                        </label>
                        <div x-data="{ show: false }" class="relative">
                            <input :type="show ? 'text' : 'password'" name="password" :required="!editing"
                                class="luxury-input w-full pr-12 @error('password') border-rose-500 @enderror" placeholder="••••••••">
                            <button type="button" @click="show = !show"
                                class="absolute inset-y-0 end-0 flex items-center z-20 px-4 cursor-pointer text-slate-400 hover:text-cyan-500 transition-colors">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end gap-x-4 pt-8">
                        <button type="button" @click="showModal = false" class="btn-luxury-ghost px-8">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn-luxury-primary px-12">
                            <span x-text="editing ? '{{ __('Update') }}' : '{{ __('Create') }}'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modals --}}
    <x-delete-confirm-modal :message="__('Are you sure you want to delete this account? This action cannot be undone.')" />
    <x-status-confirm-modal :title="__('Confirm Account Deactivation')" :message="__('Are you sure you want to deactivate this account? After deactivating, this account will no longer be able to log in to the system.')" />
    <form x-ref="statusToggleForm" :action="toggleFormAction" method="POST" class="hidden">
        @csrf
        @method('PATCH')
    </form>

    {{-- Delete Warning Modal --}}
    <div x-show="isWarningModalOpen" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div x-show="isWarningModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isWarningModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="isWarningModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                class="inline-block p-8 text-left align-bottom transition-all transform bg-white dark:bg-slate-900 rounded-3xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100 dark:border-slate-800">
                <div class="flex items-start gap-6">
                    <div class="flex items-center justify-center w-12 h-12 bg-rose-100 dark:bg-rose-500/10 rounded-2xl text-rose-600 dark:text-rose-400 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight font-display">{{ __('Cannot Delete Role') }}</h3>
                        <p class="mt-2 text-sm font-bold text-slate-500 dark:text-slate-400" x-text="deleteWarningMsg"></p>
                    </div>
                </div>
                <div class="mt-8 flex justify-end">
                    <button type="button" @click="isWarningModalOpen = false" class="btn-luxury-ghost px-8">{{ __('Got it') }}</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('accountManager', (initData) => ({
            showModal: initData.errors,
            editing: initData.oldValues.method === 'PUT' || (initData.oldValues.id && initData.errors),
            allRoles: initData.roles,
            currentUser: {
                id: initData.oldValues.id || '',
                name: initData.oldValues.name || '',
                username: initData.oldValues.username || '',
                email: initData.oldValues.email || '',
                phone: initData.oldValues.phone || '',
                company_id: initData.oldValues.company_id || '',
                role: initData.oldValues.role || '',
                status: initData.oldValues.status || 1
            },
            activeTab: initData.activeTab || initData.tab || 'accounts',
            roleSelectConfig: initData.roleSelectConfig,
            isDeleteConfirmOpen: false,
            deleteFormAction: '',
            isStatusConfirmOpen: false,
            toggleFormAction: '',
            statusToggleSource: 'list',
            isWarningModalOpen: false,
            deleteWarningMsg: '',

            triggerDeleteWarning(msg) {
                this.deleteWarningMsg = msg;
                this.isWarningModalOpen = true;
            },

            isLoadingTable: false,

            async fetchTabContent(url, containerId) {
                if (!url || this.isLoadingTable) return;
                this.isLoadingTable = true;
                try {
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) throw new Error('Network error');
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContent = doc.getElementById(containerId);
                    const container = document.getElementById(containerId);
                    if (newContent && container) {
                        container.innerHTML = newContent.innerHTML;
                        window.history.pushState({}, '', url);
                        if (window.HSStaticMethods?.autoInit) window.HSStaticMethods.autoInit();
                        if (window.Alpine?.initTree) window.Alpine.initTree(container);
                        // Preline HSSelect 重新 init 後，強制同步原生 select 的選取值至視覺元件
                        this.$nextTick(() => {
                            container.querySelectorAll('select[data-hs-select]').forEach(sel => {
                                const inst = window.HSSelect?.getInstance(sel);
                                if (inst && sel.value) inst.setValue(sel.value);
                            });
                        });
                    }
                } catch (e) {
                    console.error('AJAX Load Failed:', e);
                    window.location.href = url;
                } finally {
                    this.isLoadingTable = false;
                }
            },

            confirmDelete(action) {
                this.deleteFormAction = action;
                this.isDeleteConfirmOpen = true;
            },

            submitConfirmedForm() {
                if (this.statusToggleSource === 'list') {
                    this.$refs.statusToggleForm.submit();
                } else {
                    this.$refs.accountForm.submit();
                }
            },

            get filteredRoles() {
                const companyId = this.currentUser.company_id;
                if (!companyId || companyId.toString().trim() === '' || companyId === ' ') {
                    return this.allRoles.filter(r => !r.company_id || r.company_id.toString().trim() === '');
                } else {
                    let companyRoles = this.allRoles.filter(r => r.company_id == companyId);
                    return companyRoles.length > 0 ? companyRoles
                        : this.allRoles.filter(r => (!r.company_id || r.company_id.toString().trim() === '') && r.name !== 'super-admin');
                }
            },

            openCreateModal() {
                this.editing = false;
                const roles = this.filteredRoles;
                this.currentUser = {
                    id: '', name: '', username: '', email: '', phone: '',
                    company_id: initData.oldValues.company_id || '',
                    role: roles.length > 0 ? roles[0].name : '',
                    status: 1
                };
                this.showModal = true;
                this.$nextTick(() => this.updateRoleSelect());
            },

            openEditModal(user) {
                this.editing = true;
                this.currentUser = { ...user, company_id: user.company_id || ' ', role: user.roles?.length > 0 ? user.roles[0].name : '', status: user.status };
                this.showModal = true;
                this.$nextTick(() => {
                    this.syncSelect('modal-account-company', this.currentUser.company_id);
                    this.syncSelect('modal-account-status', this.currentUser.status);
                    this.updateRoleSelect();
                });
            },

            syncSelect(id, value) {
                this.$nextTick(() => {
                    const el = document.getElementById(id);
                    if (el) {
                        const valStr = (value !== undefined && value !== null && value.toString().trim() !== '') ? value.toString() : ' ';
                        el.value = valStr;
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                        window.HSSelect?.getInstance(el)?.setValue(valStr);
                    }
                });
            },

            updateRoleSelect() {
                this.$nextTick(() => {
                    const wrapper = document.getElementById('role-select-wrapper');
                    if (!wrapper) return;
                    const cleanConfig = JSON.parse(JSON.stringify(this.roleSelectConfig), (k, v) => typeof v === 'string' ? v.replace(/\r?\n|\r/g, ' ').trim() : v);
                    const roles = this.filteredRoles;
                    if (roles.length > 0 && !roles.find(r => r.name === this.currentUser.role)) {
                        this.currentUser.role = roles[0].name;
                    } else if (roles.length === 0) { this.currentUser.role = ''; }
                    wrapper.querySelectorAll('select').forEach(s => { try { window.HSSelect?.getInstance(s)?.destroy(); } catch(e){} });
                    wrapper.innerHTML = '';
                    const selectEl = document.createElement('select');
                    selectEl.name = 'role';
                    selectEl.id = 'modal-account-role-' + Date.now();
                    selectEl.className = 'hidden';
                    selectEl.setAttribute('data-hs-select', JSON.stringify(cleanConfig));
                    if (roles.length === 0) {
                        const opt = document.createElement('option'); opt.value = ''; opt.textContent = '{{ __("No roles available") }}'; opt.disabled = true; opt.selected = true; selectEl.appendChild(opt);
                    } else {
                        roles.forEach(r => { const opt = document.createElement('option'); opt.value = r.name; opt.textContent = r.name; opt.setAttribute('data-title', r.name); if (r.name === this.currentUser.role) opt.selected = true; selectEl.appendChild(opt); });
                    }
                    wrapper.appendChild(selectEl);
                    selectEl.addEventListener('change', e => { this.currentUser.role = e.target.value; });
                    this._roleGeneration = (this._roleGeneration || 0) + 1;
                    const gen = this._roleGeneration;
                    const waitForHSSelect = (n=0) => { if (gen !== this._roleGeneration) return; const s = window.HSSelect?.getInstance(selectEl); if (s) s.setValue(this.currentUser.role || ''); else if (n < 20) setTimeout(() => waitForHSSelect(n+1), 50); };
                    const initPreline = (n=0) => { if (gen !== this._roleGeneration) return; if (window.HSStaticMethods?.autoInit) { try { window.HSStaticMethods.autoInit(['select']); waitForHSSelect(); } catch(e){} } else if (n < 50) setTimeout(() => initPreline(n+1), 50); };
                    initPreline();
                });
            },

            init() {
                // Tab 切換時同步 URL（?tab=accounts / ?tab=roles）
                this.$watch('activeTab', tab => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tab);
                    window.history.pushState({ tab }, '', url.toString());
                });
                // 瀏覽器上一頁/下一頁時還原 Tab
                window.addEventListener('popstate', (e) => {
                    const tab = e.state?.tab || new URLSearchParams(window.location.search).get('tab') || 'accounts';
                    this.activeTab = tab;
                });
                this.$watch('currentUser.company_id', (value) => {
                    this.syncSelect('modal-account-company', value);
                    if (this.filteredRoles.length > 0 && !this.filteredRoles.find(r => r.name === this.currentUser.role)) {
                        this.currentUser.role = this.filteredRoles[0].name;
                    }
                    this.updateRoleSelect();
                });
                this.$watch('currentUser.status', value => this.syncSelect('modal-account-status', value));
                this.$nextTick(() => this.updateRoleSelect());
            }
        }));
    });
</script>
@endsection