@extends('layouts.admin')

@php
$routeName = request()->route()->getName();
$baseRoute = str_contains($routeName, 'sub-account-roles') ? 'admin.data-config.sub-account-roles' : 'admin.permission.roles';
@endphp

@section('content')
<div class="space-y-4 pb-20" x-data="{
    isLoadingTable: false,
    isWarningModalOpen: false,
    deleteWarningMsg: '',
    isDeleteConfirmOpen: false,
    deleteFormAction: '',
    triggerDeleteWarning(msg) { this.deleteWarningMsg = msg; this.isWarningModalOpen = true; },
    confirmDelete(action) { this.deleteFormAction = action; this.isDeleteConfirmOpen = true; },
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
                setTimeout(() => {
                    container.querySelectorAll('select[data-hs-select]').forEach(sel => {
                        const inst = window.HSSelect?.getInstance(sel);
                        if (inst && sel.value) inst.setValue(sel.value);
                    });
                }, 100);
            }
        } catch (e) { console.error('Fetch failed:', e); window.location.href = url; } finally { this.isLoadingTable = false; }
    }
}">

    <x-page-header
        :title="$title"
        :subtitle="__('Define and manage security roles and permissions.')"
    >
        <a href="{{ route($baseRoute . '.create') }}" class="btn-luxury-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            <span>{{ __('Add Role') }}</span>
        </a>
    </x-page-header>

    <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden">
        <x-luxury-spinner show="isLoadingTable" />

        <div id="ajax-roles-container"
             :class="{ 'opacity-30 pointer-events-none': isLoadingTable }"
             @ajax:navigate:roles.prevent="fetchTabContent($event.detail.url, 'ajax-roles-container')">
            @include('admin._shared.role-list', [
                'roles'     => $roles,
                'baseRoute' => $baseRoute,
                'companies' => $companies,
            ])
        </div>
    </div>

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

    <x-delete-confirm-modal :message="__('Are you sure you want to delete this role? This action cannot be undone.')" />

</div>
@endsection
