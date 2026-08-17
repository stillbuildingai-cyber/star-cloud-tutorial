<div class="space-y-6">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-x-4">
            <div class="size-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 shadow-lg shadow-cyan-500/5">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">{{ __('Login History') }}</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">{{ __('Your recent account activity') }}</p>
            </div>
        </div>
    </div>

    <div class="relative">
        <div class="space-y-6">
            @forelse($user->loginLogs()->latest()->take(10)->get() as $log)
                <div class="relative group">
                    
                    <div class="luxury-card p-5 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 hover:bg-white dark:hover:bg-slate-800/80 transition-all duration-300">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-x-3 mb-2">
                                    <span class="text-sm font-black text-slate-700 dark:text-slate-200 font-mono tracking-tight">{{ $log->ip_address }}</span>
                                    <span class="size-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                    <span class="text-[10px] font-black text-cyan-500 dark:text-cyan-400 uppercase tracking-widest bg-cyan-500/5 px-2 py-0.5 rounded-md border border-cyan-500/10">
                                        {{ __('Success') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-x-2 text-xs font-medium text-slate-400 dark:text-slate-500">
                                    <div class="flex items-center gap-x-1.5 px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50">
                                        @if($log->device_type === 'mobile')
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        @elseif($log->device_type === 'tablet')
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        @else
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        @endif
                                        <span class="font-bold text-slate-500 dark:text-slate-400">{{ $log->platform ?: 'Unknown OS' }}</span>
                                    </div>
                                    <span class="size-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                    <span class="text-slate-500 dark:text-slate-400">
                                        {{ $log->browser ?: 'Unknown Browser' }}
                                    </span>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">
                                    {{ $log->login_at->format('Y/m/d') }}
                                </p>
                                <p class="text-xs font-bold text-slate-400 dark:text-slate-500">
                                    {{ $log->login_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="luxury-card p-12 text-center rounded-[2rem] border-dashed border-2 border-slate-100 dark:border-slate-800">
                    <div class="size-16 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-4">
                        <svg class="size-8 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm font-bold text-slate-400">{{ __('No login history yet') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
