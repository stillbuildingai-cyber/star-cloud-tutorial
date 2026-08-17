<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-screen">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>{{ __('Pass Code Ticket') }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;900&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 flex flex-col items-center justify-start pt-8 pb-12 px-6 antialiased">
    <div class="w-full max-w-sm animate-luxury-in">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-black text-slate-800 dark:text-white tracking-tight mb-2 font-display uppercase italic">{{ __('Pass Code') }}</h1>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Scan to authorize testing or maintenance') }}</p>
        </div>

        <div class="relative group">
            <div class="absolute -inset-4 bg-gradient-to-tr from-cyan-500/20 to-emerald-500/20 blur-3xl opacity-50 transition-opacity duration-500 group-hover:opacity-100"></div>
            <div class="relative luxury-card rounded-[3rem] overflow-hidden border-slate-200/50 dark:border-slate-700/50 shadow-2xl bg-white dark:bg-slate-900">
                <div class="px-8 pt-10 pb-6 border-b border-dashed border-slate-100 dark:border-slate-800 relative">
                    <div class="absolute -left-3 -bottom-3 w-6 h-6 bg-slate-50 dark:bg-slate-950 rounded-full border-r border-slate-100 dark:border-slate-800"></div>
                    <div class="absolute -right-3 -bottom-3 w-6 h-6 bg-slate-50 dark:bg-slate-950 rounded-full border-l border-slate-100 dark:border-slate-800"></div>

                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-2 flex items-center justify-center overflow-hidden">
                            <svg class="w-6 h-6 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight leading-tight mb-1">
                                {{ $passCode->machine->name }}
                            </h2>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="text-center flex-1">
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Purpose') }}</span>
                            <span class="text-sm font-black text-slate-800 dark:text-white">{{ $passCode->name ?? __('Testing') }}</span>
                        </div>
                        <div class="w-px h-8 bg-slate-100 dark:bg-slate-800"></div>
                        <div class="text-center flex-1">
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Status') }}</span>
                            <span class="text-xs font-black px-3 py-1 rounded-full bg-cyan-500/10 text-cyan-500 uppercase tracking-tighter">{{ __('Active') }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-10 flex flex-col items-center">
                    <div class="p-6 bg-white rounded-[2.5rem] shadow-xl border border-slate-100 mb-8 transform transition-transform duration-500 group-hover:scale-[1.02]">
                        <x-qr-code :data="$passCode->code" :dynamic="false" size="300" class="w-48 h-48 sm:w-64 sm:h-64" />
                    </div>

                    <div class="text-center space-y-2 mb-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">{{ __('Pass Code') }}</p>
                        <p class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter font-display italic leading-none">{{ $passCode->code }}</p>
                    </div>
                </div>

                <div class="px-8 py-6 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 text-center">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">{{ __('Expiry Time') }}</p>
                    <p class="text-sm font-black text-slate-600 dark:text-slate-300">
                        {{ $passCode->expires_at ? $passCode->expires_at->format('Y/m/d H:i') : __('Permanent') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
