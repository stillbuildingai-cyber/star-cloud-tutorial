@extends('layouts.admin')

@php
    $machinesJson = $machines->map(function($m) {
        return [
            'id' => $m->id,
            'name' => $m->name,
            'serial_no' => $m->serial_no,
            'status' => $m->status, // online / offline
            'firmware_version' => $m->firmware_version ?? '', // 設備目前的版本或風味
            'location' => $m->location ?? '',
        ];
    });
@endphp

@section('content')
<script>
    window.apkVersionApp = function() {
        return {
            isDeleteConfirmOpen: false,
            deleteFormAction: '',
            confirmDelete(action) {
                this.deleteFormAction = action;
                this.isDeleteConfirmOpen = true;
            },
            
            // OTA 派發 Modal 相關狀態
            isPushModalOpen: false,
            pushActionUrl: '',
            selectedVersionName: '',
            selectedFlavor: '',
            searchMachineQuery: '',
            onlyOnlineMachines: false,
            selectedMachineIds: [],
            
            machines: @json($machinesJson),

            async copyDownloadUrl(url) {
                try {
                    await navigator.clipboard.writeText(url);
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __('Download link copied') }}', type: 'success' }
                    }));
                } catch (error) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: url, type: 'info' }
                    }));
                }
            },
            
            openPushModal(versionName, flavor, actionUrl) {
                this.selectedVersionName = versionName;
                this.selectedFlavor = flavor;
                this.pushActionUrl = actionUrl;
                this.selectedMachineIds = [];
                this.searchMachineQuery = '';
                this.onlyOnlineMachines = false;
                this.isPushModalOpen = true;
                // 清空預約時間
                const scheduleInput = document.getElementById('scheduled_at');
                if (scheduleInput) scheduleInput.value = '';
            },
            
            get filteredMachines() {
                return this.machines.filter(m => {
                    const matchesSearch = m.name.toLowerCase().includes(this.searchMachineQuery.toLowerCase()) || 
                                          m.serial_no.toLowerCase().includes(this.searchMachineQuery.toLowerCase()) ||
                                          m.location.toLowerCase().includes(this.searchMachineQuery.toLowerCase());
                    
                    const matchesOnline = !this.onlyOnlineMachines || m.status === 'online';
                    
                    // 可以根據 flavor 進行模糊匹配或若機台 firmware_version 包含特定字串
                    const matchesFlavor = !this.selectedFlavor || 
                                          m.firmware_version.toLowerCase().includes(this.selectedFlavor.toLowerCase()) ||
                                          m.name.toLowerCase().includes(this.selectedFlavor.toLowerCase());
                    
                    return matchesSearch && matchesOnline;
                });
            },
            
            toggleAllMachines(checked) {
                if (checked) {
                    this.selectedMachineIds = this.filteredMachines.map(m => m.id);
                } else {
                    this.selectedMachineIds = [];
                }
            },
            
            isLoadingTable: false,
            async fetchPage(url) {
                if (!url || this.isLoadingTable) return;
                
                this.isLoadingTable = true;
                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (!response.ok) throw new Error('Network response was not ok');
                    
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.querySelector('#ajax-content-container');
                    
                    if (newContent) {
                        document.querySelector('#ajax-content-container').innerHTML = newContent.innerHTML;
                        history.pushState(null, '', url);
                        if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                            window.HSStaticMethods.autoInit();
                        }
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    window.location.href = url;
                } finally {
                    this.isLoadingTable = false;
                }
            }
        };
    };
</script>

<div class="space-y-4 pb-20" x-data="apkVersionApp()">
    <!-- Header -->
    <x-page-header
        :title="__('APK Versions')"
        :subtitle="__('OTA firmware update and version control')"
    >
        <a href="{{ route('admin.basic-settings.apk-versions.create') }}" class="btn-luxury-primary flex items-center gap-2">
            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            <span>{{ __('Upload New APK') }}</span>
        </a>
    </x-page-header>

    <!-- Content Container with Loading State -->
    <div id="ajax-content-container" 
         class="relative transition-all duration-500 min-h-[400px]"
         :class="isLoadingTable ? 'opacity-40 blur-[2px] pointer-events-none' : 'opacity-100 blur-0'"
         @click="if ($event.target.closest('a') && $event.target.closest('a').href && ($event.target.closest('a').href.includes('page=') || $event.target.closest('a').href.includes('per_page='))) { $event.preventDefault(); fetchPage($event.target.closest('a').href); }">
        
        <!-- Loading Spinner Overlay -->
        <div x-show="isLoadingTable" 
             class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] rounded-3xl" x-cloak>
            <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin"></div>
                <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin" style="animation-duration: 3s; direction: reverse;"></div>
            </div>
            <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">{{ __('Loading Data') }}...</p>
        </div>

        <!-- Main Card -->
        <div class="luxury-card rounded-3xl p-8 animate-luxury-in">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-separate border-spacing-y-0">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                            <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">{{ __('Version Info') }}</th>
                            <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">{{ __('Machine Flavor') }}</th>
                            <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">{{ __('Deployment Status') }}</th>
                            <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">{{ __('Upload Time') }}</th>
                            <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">{{ __('Release Notes') }}</th>
                            <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                        @forelse($versions as $version)
                        <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                            <td class="px-6 py-6 font-extrabold text-slate-800 dark:text-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300">
                                        <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors whitespace-nowrap">
                                            {{ $version->version_name }}
                                        </div>
                                        <div class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mt-0.5">
                                            Code: {{ $version->version_code }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border border-sky-100 dark:border-sky-900/30 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 tracking-widest">
                                    {{ $version->flavor }}
                                </span>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <!-- 準備中 -->
                                    @php
                                        $pendingTooltip = __('Ready to Execute') . ":\n";
                                        if ($version->pending_machines->isEmpty()) {
                                            $pendingTooltip .= "• " . __('No machines in this status');
                                        } else {
                                            foreach($version->pending_machines as $m) {
                                                $pendingTooltip .= "• {$m->name} ({$m->serial_no})\n";
                                            }
                                        }
                                        $pendingTooltip = trim($pendingTooltip);
                                    @endphp
                                    <div class="inline-block tooltip cursor-help" title="{{ $pendingTooltip }}">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold border border-amber-100 dark:border-amber-900/30 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 tracking-widest inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Ready') }}: {{ $version->pending_machines->count() }}
                                        </span>
                                    </div>

                                    <!-- 已完成 -->
                                    @php
                                        $completedTooltip = __('Update Completed') . ":\n";
                                        if ($version->completed_machines->isEmpty()) {
                                            $completedTooltip .= "• " . __('No machines in this status');
                                        } else {
                                            foreach($version->completed_machines as $m) {
                                                $completedTooltip .= "• {$m->name} ({$m->serial_no})\n";
                                            }
                                        }
                                        $completedTooltip = trim($completedTooltip);
                                    @endphp
                                    <div class="inline-block tooltip cursor-help" title="{{ $completedTooltip }}">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold border border-emerald-100 dark:border-emerald-900/30 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 tracking-widest inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            {{ __('Done') }}: {{ $version->completed_machines->count() }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <span class="text-xs font-black text-slate-400 font-mono tracking-widest">
                                    {{ $version->created_at->format('Y-m-d H:i:s') }}
                                </span>
                            </td>
                            <td class="px-6 py-6 max-w-xs" x-data="{ expanded: false }">
                                @if($version->release_notes)
                                    <div :class="expanded ? 'whitespace-normal break-words' : 'truncate'" 
                                         @click="expanded = !expanded" 
                                         class="cursor-pointer"
                                         title="{{ $version->release_notes }}">
                                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400 leading-relaxed">
                                            {{ $version->release_notes }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-sm font-medium text-slate-400 leading-relaxed">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-6 text-right space-x-2 whitespace-nowrap">
                                <!-- OTA 派發按鈕 -->
                                <button type="button" 
                                        @click="openPushModal('{{ $version->version_name }}', '{{ $version->flavor }}', '{{ route('admin.basic-settings.apk-versions.push', $version) }}')"
                                        class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 border border-transparent hover:border-cyan-500/20 transition-all inline-flex" 
                                        title="{{ __('OTA Deploy') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0017.25 4.5H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z"/></svg>
                                </button>
                                
                                <!-- 下載連結 -->
                                <a href="{{ $version->url }}" 
                                   target="_blank"
                                   class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 border border-transparent hover:border-emerald-500/20 transition-all inline-flex" 
                                   title="{{ __('Download APK') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                </a>

                                <!-- 複製下載連結 -->
                                <button type="button"
                                        @click="copyDownloadUrl(@js($version->url))"
                                        class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-indigo-500 hover:bg-indigo-500/5 border border-transparent hover:border-indigo-500/20 transition-all inline-flex"
                                        title="{{ __('Copy Download Link') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75A1.125 1.125 0 013.75 20.625v-9.75c0-.621.504-1.125 1.125-1.125H8.25m2.25-3h8.625c.621 0 1.125.504 1.125 1.125v8.625c0 .621-.504 1.125-1.125 1.125H10.5A1.125 1.125 0 019.375 16.5V7.875c0-.621.504-1.125 1.125-1.125z"/></svg>
                                </button>

                                <!-- 刪除按鈕 -->
                                <form action="{{ route('admin.basic-settings.apk-versions.destroy', $version) }}" method="POST" class="inline-block" @submit.prevent="confirmDelete($el.getAttribute('action'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 border border-transparent hover:border-rose-500/20 transition-all"
                                            title="{{ __('Delete') }}">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-slate-50 dark:bg-slate-800/50 mb-6 border border-slate-100 dark:border-slate-800 shadow-sm">
                                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0017.25 4.5H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z"/></svg>
                                </div>
                                <p class="text-base font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('No versions found') }}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
                {{ $versions->links('vendor.pagination.luxury') }}
            </div>
        </div>
    </div>

    <!-- Global Delete Confirm Modal -->
    <x-delete-confirm-modal :message="__('Are you sure you want to delete this APK version? This action will permanently remove the record and file.')" />

    <!-- OTA 派發模態彈窗 (Custom Luxury Modal using Alpine.js) -->
    <div x-show="isPushModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0" x-cloak>
        
        <!-- 背景遮罩 -->
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="isPushModalOpen = false"></div>
        
        <!-- 彈窗主體 -->
        <div class="relative w-full max-w-2xl mx-auto my-6 px-4 z-50">
            <div class="relative flex flex-col w-full bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl shadow-2xl outline-none focus:outline-none overflow-hidden animate-luxury-in">
                
                <!-- 彈窗 Header -->
                <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <div>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2 font-display">
                            <span class="p-1.5 rounded-lg bg-cyan-500 text-white leading-none">
                                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0017.25 4.5H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z"/></svg>
                            </span>
                            <span>{{ __('OTA Update Deployment') }}</span>
                        </h3>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 tracking-wider uppercase mt-1">
                            {{ __('Deploy version') }}: <span class="text-cyan-500" x-text="selectedVersionName"></span> (<span x-text="selectedFlavor"></span>)
                        </p>
                    </div>
                    <button type="button" 
                            @click="isPushModalOpen = false" 
                            class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                        <svg class="w-6 h-6 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <!-- 彈窗內容 -->
                <div class="p-6 space-y-6 max-h-[60vh] overflow-y-auto">
                    <!-- 搜尋與篩選列 -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                        <div class="relative flex-1 group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors stroke-[2.5]"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </span>
                            <input type="text" 
                                   x-model="searchMachineQuery"
                                   placeholder="{{ __('Search machine name, S/N, or location...') }}"
                                   class="luxury-input py-2 pl-12 pr-6 block w-full text-sm">
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="onlyOnlineMachines" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-cyan-500"></div>
                                <span class="ml-2 text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ __('Online Only') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- 表單與機台選擇清單 -->
                    <form :action="pushActionUrl" method="POST" id="ota-push-form">
                        @csrf

                        <!-- 預約排程時間設定 -->
                        <div class="mb-6 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex-1">
                                    <label for="scheduled_at" class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                                        {{ __('Select Update Time (Optional)') }}
                                    </label>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 leading-normal">
                                        {{ __('Leave blank to deploy immediately, or set a future time to schedule a background release.') }}
                                    </p>
                                </div>
                                <div class="w-full md:w-64 group">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </span>
                                        <input type="text" 
                                               id="scheduled_at"
                                               name="scheduled_at" 
                                               x-init="flatpickr($el, { 
                                                   enableTime: true, 
                                                   dateFormat: 'Y-m-d H:i', 
                                                   time_24hr: true,
                                                   locale: '{{ app()->getLocale() === 'zh_TW' ? 'zh_tw' : (app()->getLocale() === 'ja' ? 'ja' : 'en') }}',
                                                   disableMobile: true
                                               })"
                                               class="luxury-input py-2.5 pl-12 pr-4 block w-full text-sm font-bold tracking-tight cursor-pointer"
                                               placeholder="{{ __('Select future time...') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border border-slate-100 dark:border-slate-800/80 rounded-2xl overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50/50 dark:bg-slate-950/40">
                                    <tr>
                                        <th class="px-6 py-3 w-12 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                            <input type="checkbox" 
                                                   @change="toggleAllMachines($el.checked)"
                                                   :checked="selectedMachineIds.length === filteredMachines.length && filteredMachines.length > 0"
                                                   class="rounded border-slate-300 dark:border-slate-700 text-cyan-500 focus:ring-cyan-500 dark:bg-slate-900">
                                        </th>
                                        <th class="px-6 py-3 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Machine Detail') }}</th>
                                        <th class="px-6 py-3 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Status') }}</th>
                                        <th class="px-6 py-3 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('App Version') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                    <template x-for="machine in filteredMachines" :key="machine.id">
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-200">
                                            <td class="px-6 py-3">
                                                <input type="checkbox" 
                                                       name="machine_ids[]" 
                                                       :value="machine.id"
                                                       x-model="selectedMachineIds"
                                                       class="rounded border-slate-300 dark:border-slate-700 text-cyan-500 focus:ring-cyan-500 dark:bg-slate-900">
                                            </td>
                                            <td class="px-6 py-3">
                                                <div class="text-sm font-extrabold text-slate-800 dark:text-slate-100" x-text="machine.name"></div>
                                                <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5" x-text="'S/N: ' + machine.serial_no"></div>
                                            </td>
                                            <td class="px-6 py-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full" 
                                                          :class="machine.status === 'online' ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"></span>
                                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400" 
                                                          x-text="machine.status === 'online' ? '{{ __('Online') }}' : '{{ __('Offline') }}'"></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                                      :class="machine.firmware_version.toLowerCase().includes(selectedFlavor.toLowerCase()) && selectedFlavor !== '' ? 'bg-cyan-500/10 text-cyan-500 dark:bg-cyan-500/20 border border-cyan-500/20' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-transparent'"
                                                      x-text="machine.firmware_version || '-'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                    
                                    <tr x-show="filteredMachines.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500 font-bold">
                                            {{ __('No machines match your criteria') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                
                <!-- 彈窗 Footer -->
                <div class="flex items-center justify-between p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <div class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                        <span x-text="selectedMachineIds.length"></span> {{ __('Devices Selected') }}
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" 
                                @click="isPushModalOpen = false"
                                class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-extrabold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" 
                                form="ota-push-form"
                                :disabled="selectedMachineIds.length === 0"
                                class="px-5 py-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 text-sm font-extrabold shadow-lg shadow-cyan-500/20 transition-all disabled:opacity-55 disabled:pointer-events-none">
                            {{ __('Deploy Update') }}
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

</div>
@endsection
