@if(session('code_batch'))
    @php($batch = session('code_batch'))
    <div class="luxury-card rounded-3xl p-6 border border-emerald-500/30 bg-emerald-500/5 flex flex-col sm:flex-row sm:items-center gap-4 animate-luxury-in">
        <div class="flex items-center gap-3 flex-1">
            <div class="w-11 h-11 rounded-2xl bg-emerald-500/15 text-emerald-500 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-black text-slate-800 dark:text-white">{{ __('Batch generated successfully') }}</p>
                <p class="text-xs font-bold text-slate-500">
                    {{ __('Batch No') }}: <span class="font-mono text-cyan-600 dark:text-cyan-400">{{ $batch['batch_no'] }}</span>
                    · {{ $batch['count'] }} {{ __('codes') }}
                </p>
            </div>
        </div>
        {{-- download 屬性：讓全域連結攔截器（layouts/admin）跳過「導航載入遮罩」，
             否則檔案下載不會換頁、遮罩不會關閉，畫面會卡在模糊狀態。 --}}
        <a href="{{ $batch['download_url'] }}" download
            class="btn-luxury-primary whitespace-nowrap flex items-center justify-center gap-2">
            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 12l4.5 4.5m0 0l4.5-4.5M12 3v13.5" />
            </svg>
            {{ __('Download List (CSV)') }}
        </a>
    </div>
@endif
