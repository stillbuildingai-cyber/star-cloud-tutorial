@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-20" x-data="{
    isDeleteConfirmOpen: false,
    deleteFormAction: '',
    confirmDelete(action) {
        this.deleteFormAction = action;
        this.isDeleteConfirmOpen = true;
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
    },
    init() {
        window.addEventListener('popstate', () => {
            this.fetchPage(window.location.href);
        });
    }
}">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight font-display">{{ __('Payment Configuration') }}</h1>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">{{ __('Merchant payment gateway settings management') }}</p>
        </div>
        <div>
            <a href="{{ route('admin.basic-settings.payment-configs.create') }}" class="btn-luxury-primary flex items-center gap-2">
                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span>{{ __('Create Config') }}</span>
            </a>
        </div>
    </div>

    <!-- Content Container with Loading State -->
    <div id="ajax-content-container" 
         class="relative transition-all duration-500 min-h-[400px]"
         :class="isLoadingTable ? 'opacity-40 blur-[2px] pointer-events-none' : 'opacity-100 blur-0'"
         @ajax:navigate.prevent.stop="fetchPage($event.detail.url)"
         @click="if ($event.target.closest('a') && $event.target.closest('a').href && ($event.target.closest('a').href.includes('page=') || $event.target.closest('a').href.includes('per_page='))) { $event.preventDefault(); fetchPage($event.target.closest('a').href); }">
        
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
                <!-- 外圈：快速旋轉 -->
                <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin"></div>
                <!-- 內圈：反向慢速旋轉 -->
                <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin" style="animation-duration: 3s; direction: reverse;"></div>
                <!-- 核心：脈衝圖示 -->
                <div class="relative w-8 h-8 flex items-center justify-center">
                    <svg class="w-6 h-6 text-cyan-500 animate-pulse stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
            </div>
            <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">{{ __('Loading Data') }}...</p>
        </div>

        <!-- Main Card -->
        <div class="luxury-card rounded-3xl p-8 animate-luxury-in">
            <!-- Toolbar & Filters -->
            <div class="flex items-center justify-between mb-8">
                <form method="GET" action="{{ route('admin.basic-settings.payment-configs.index') }}" 
                      class="relative group"
                      @submit.prevent="fetchPage($el.action + '?' + new URLSearchParams(new FormData($el)).toString())">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors stroke-[2.5]"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search configurations...') }}"
                        class="luxury-input py-2.5 pl-12 pr-6 block w-64">
                </form>
            </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-y-0">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">{{ __('Configuration Name') }}</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">{{ __('Belongs To') }}</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">{{ __('Last Updated') }}</th>
                        <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                    @forelse($paymentConfigs as $config)
                    <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                        <td class="px-6 py-6 font-extrabold text-slate-800 dark:text-slate-100">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <div>
                                    <div class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors whitespace-nowrap">{{ $config->name }}</div>
                                    <div class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mt-0.5">ID: {{ str_pad($config->id, 5, '0', STR_PAD_LEFT) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6">
                             <span class="px-2.5 py-1 rounded-lg text-xs font-bold border border-sky-100 dark:border-sky-900/30 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 tracking-widest">
                                {{ $config->company->name ?? __('System') }}
                            </span>
                        </td>
                        <td class="px-6 py-6">
                            <div class="text-xs font-black text-slate-400 dark:text-slate-400/80 uppercase tracking-widest leading-none">
                                {{ $config->updated_at->format('Y/m/d H:i') }}
                            </div>
                        </td>
                        <td class="px-6 py-6 text-right space-x-2">
                             <a href="{{ route('admin.basic-settings.payment-configs.edit', $config) }}" 
                                class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 border border-transparent hover:border-cyan-500/20 transition-all inline-flex" title="{{ __('Edit') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                            </a>
                            <form action="{{ route('admin.basic-settings.payment-configs.destroy', $config) }}" method="POST" class="inline-block" @submit.prevent="confirmDelete($el.getAttribute('action'))">
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
                        <td colspan="4" class="px-6 py-20 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-slate-50 dark:bg-slate-800/50 mb-6 border border-slate-100 dark:border-slate-800 shadow-sm">
                                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75-6.18c0-.424.344-.766.766-.766h.996c.422 0 .766.342.766.766v.996c0 .422-.344.766-.766.766h-.996a.766.766 0 0 1-.766-.766v-.996ZM3.562 18.125c0 .422.344.766.766.766h.996c.422 0 .766-.344.766-.766v-.996c0-.422-.344-.766-.766-.766h-.996a.766.766 0 0 0-.766.766v.996ZM3.562 13.125c0 .422.344.766.766.766h.996c.422 0 .766-.344.766-.766v-.996c0-.422-.344-.766-.766-.766h-.996a.766.766 0 0 0-.766.766v.996Z"/></svg>
                            </div>
                            <p class="text-base font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('No configurations found') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
            {{ $paymentConfigs->links('vendor.pagination.luxury') }}
        </div>
    </div>
</div>

    <!-- Global Delete Confirm Modal -->
    <x-delete-confirm-modal :message="__('Are you sure you want to delete this configuration? This action cannot be undone.')" />
</div>
@endsection
