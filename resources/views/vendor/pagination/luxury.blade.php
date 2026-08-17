@if ($paginator->total() > 0)
    <div class="flex flex-col lg:flex-row items-center justify-between w-full gap-6">
        {{-- Left Side: Limit Selector & Info --}}
        <div class="order-2 lg:order-1 flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
            {{-- Limit Selector --}}
            <div class="flex items-center gap-3 bg-slate-50/50 dark:bg-slate-900/50 px-3 py-1.5 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm leading-none">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1 leading-none">{{ __('Show') }}</span>
                <div class="relative group flex items-center">
                    @php
                        $currentLimit = $paginator->perPage();
                        $limits = [10, 25, 50, 100];
                        $event = $ajax_navigate_event ?? 'ajax:navigate';
                        $perPageKey = $per_page_param ?? 'per_page';
                        $pageKey = $page_param ?? 'page';
                    @endphp
                    <select onchange="{ const params = new URLSearchParams(window.location.search); params.set('{{ $perPageKey }}', this.value); params.delete('{{ $pageKey }}'); const url = window.location.pathname + '?' + params.toString(); const evt = new CustomEvent('{{ $event }}', { detail: { url: url }, bubbles: true, cancelable: true }); if (this.dispatchEvent(evt)) { window.location.href = url; } }"
                        class="h-7 pl-2 pr-7 rounded-lg bg-white dark:bg-slate-800 border-none text-[11px] font-black text-slate-600 dark:text-slate-300 appearance-none focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all cursor-pointer shadow-sm leading-none py-0 !bg-none">
                        @foreach($limits as $l)
                            <option value="{{ $l }}" {{ $currentLimit == $l ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-1.5 pointer-events-none text-cyan-500/50 group-hover:text-cyan-500 transition-colors">
                        <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            {{-- Total Items Info --}}
            <p class="text-[10px] sm:text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center sm:text-left whitespace-nowrap">
                <span class="hidden xs:inline">{{ __('Showing') }}</span>
                <span class="text-slate-600 dark:text-slate-300">{{ $paginator->firstItem() }}</span>
                {{ __('to') }}
                <span class="text-slate-600 dark:text-slate-300">{{ $paginator->lastItem() }}</span>
                <span class="hidden xs:inline">{{ __('of') }}</span>
                <span class="inline xs:hidden">/</span>
                <span class="text-slate-600 dark:text-slate-300">{{ $paginator->total() }}</span>
                {{ __('items') }}
            </p>
        </div>

        <div class="order-1 lg:order-2 flex items-center gap-x-1.5 sm:gap-x-2">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="h-9 inline-flex items-center gap-x-1.5 sm:gap-x-2 px-3 sm:px-4 rounded-xl text-[10px] sm:text-xs font-black bg-slate-50 dark:bg-slate-800 text-slate-300 dark:text-slate-600 border border-slate-100 dark:border-slate-800 cursor-not-allowed">
                    <svg class="size-3.5 sm:size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden xxs:inline">{{ __('Previous') }}</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   onclick="{ event.preventDefault(); const url = this.href; const evt = new CustomEvent('{{ $event }}', { detail: { url: url }, bubbles: true, cancelable: true }); if (this.dispatchEvent(evt)) { window.location.href = url; } }"
                   class="h-9 inline-flex items-center gap-x-1.5 sm:gap-x-2 px-3 sm:px-4 rounded-xl text-[10px] sm:text-xs font-black bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm group">
                    <svg class="size-3.5 sm:size-4 text-cyan-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden xxs:inline">{{ __('Previous') }}</span>
                </a>
            @endif

            {{-- Unified Quick Jump Selection (Desktop & Mobile) --}}
            <div class="relative group">
                <select onchange="{ const url = this.value; const evt = new CustomEvent('{{ $event }}', { detail: { url: url }, bubbles: true, cancelable: true }); if (this.dispatchEvent(evt)) { window.location.href = url; } }"
                    class="h-9 pl-4 pr-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-[11px] sm:text-xs font-black text-slate-600 dark:text-slate-300 appearance-none focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 outline-none transition-all cursor-pointer shadow-sm hover:border-slate-300 dark:hover:border-slate-600 !bg-none {{ $paginator->lastPage() <= 1 ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                    {{ $paginator->lastPage() <= 1 ? 'disabled' : '' }}>
                    @for ($i = 1; $i <= $paginator->lastPage(); $i++)
                        <option value="{{ $paginator->url($i) }}" {{ $i == $paginator->currentPage() ? 'selected' : '' }}>
                            {{ $i }} / {{ $paginator->lastPage() }}
                        </option>
                    @endfor
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-cyan-500/50 group-hover:text-cyan-500 transition-colors">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   onclick="{ event.preventDefault(); const url = this.href; const evt = new CustomEvent('{{ $event }}', { detail: { url: url }, bubbles: true, cancelable: true }); if (this.dispatchEvent(evt)) { window.location.href = url; } }"
                   class="h-9 inline-flex items-center gap-x-1.5 sm:gap-x-2 px-3 sm:px-4 rounded-xl text-[10px] sm:text-xs font-black bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm group">
                    <span class="hidden xxs:inline">{{ __('Next') }}</span>
                    <svg class="size-3.5 sm:size-4 text-cyan-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="h-9 inline-flex items-center gap-x-1.5 sm:gap-x-2 px-3 sm:px-4 rounded-xl text-[10px] sm:text-xs font-black bg-slate-50 dark:bg-slate-800 text-slate-300 dark:text-slate-600 border border-slate-100 dark:border-slate-800 cursor-not-allowed">
                    <span class="hidden xxs:inline">{{ __('Next') }}</span>
                    <svg class="size-3.5 sm:size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </div>
@endif
