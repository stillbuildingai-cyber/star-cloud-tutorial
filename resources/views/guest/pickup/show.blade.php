<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-screen">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>{{ __('Pickup Ticket') }} - {{ config('app.name') }}</title>
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
    @php
        // 取貨碼可能綁「商品」(product_id，slot_no 為 null，貨道刷碼當下挑) 或綁「貨道」(舊版)。
        // 優先用直接綁定的商品；否則回退用 slot_no 對應貨道的商品。
        $slot = $pickupCode->slot_no
            ? $pickupCode->machine->slots->where('slot_no', $pickupCode->slot_no)->first()
            : null;
        $product = $pickupCode->product ?? $slot?->product;
    @endphp

    <div class="w-full max-w-sm animate-luxury-in">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-black text-slate-800 dark:text-white tracking-tight mb-2 font-display uppercase italic">{{ __('Pickup Code') }}</h1>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Scan to pick up your product') }}</p>
        </div>

        <div class="relative group">
            <div class="absolute -inset-4 bg-gradient-to-tr from-cyan-500/20 to-emerald-500/20 blur-3xl opacity-50 transition-opacity duration-500 group-hover:opacity-100"></div>
            <div class="relative luxury-card rounded-[3rem] overflow-hidden border-slate-200/50 dark:border-slate-700/50 shadow-2xl bg-white dark:bg-slate-900">
                <div class="px-8 pt-10 pb-6 border-b border-dashed border-slate-100 dark:border-slate-800 relative">
                    <div class="absolute -left-3 -bottom-3 w-6 h-6 bg-slate-50 dark:bg-slate-950 rounded-full border-r border-slate-100 dark:border-slate-800"></div>
                    <div class="absolute -right-3 -bottom-3 w-6 h-6 bg-slate-50 dark:bg-slate-950 rounded-full border-l border-slate-100 dark:border-slate-800"></div>

                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-2 flex items-center justify-center overflow-hidden">
                            @if($product && $product->image_url)
                                <img src="{{ $product->image_url }}" class="w-full h-full object-cover" alt="{{ $product->name }}">
                            @else
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight leading-tight mb-1">
                                {{ $product?->name ?? __('Unknown Product') }}
                            </h2>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $pickupCode->machine->name ?? $pickupCode->machine->serial_no }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="text-center flex-1">
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Slot') }}</span>
                            <span class="text-2xl font-black text-slate-800 dark:text-white font-display">{{ $pickupCode->slot_no ?: '—' }}</span>
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
                        <x-qr-code :data="$pickupCode->code" :dynamic="false" size="300" class="w-48 h-48 sm:w-64 sm:h-64" />
                    </div>

                    <div class="text-center space-y-2 mb-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">{{ __('Pickup Code') }}</p>
                        <p class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter font-display italic leading-none">{{ $pickupCode->code }}</p>
                    </div>
                </div>

                <div class="px-8 py-6 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 text-center">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">{{ __('Expiry Time') }}</p>
                    <p class="text-sm font-black text-slate-600 dark:text-slate-300">
                        {{ $pickupCode->expires_at->format('Y/m/d H:i') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
