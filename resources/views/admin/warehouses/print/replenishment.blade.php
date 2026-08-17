<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Replenishment Order') }} - {{ $order->order_no }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Vite setup) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #fff;
            color: #0f172a;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .font-display {
            font-family: 'Outfit', sans-serif;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 12px;
            }
            .print-border {
                border-color: #94a3b8 !important;
            }
            .print-bg-gray {
                background-color: #f1f5f9 !important;
            }
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
        }
    </style>
</head>
<body class="p-6 sm:p-12 min-h-screen flex flex-col justify-between">
    
    <!-- Container -->
    <div class="max-w-4xl mx-auto w-full flex-1">
        
        <!-- Top Operations (No Print) -->
        <div class="no-print flex items-center justify-between gap-4 mb-8 p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-200/50 dark:border-slate-800/50">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-cyan-500 animate-pulse"></span>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ __('Ready to print') }}</span>
            </div>
            <div class="flex gap-3">
                <button onclick="window.print()" class="px-5 py-2.5 bg-cyan-500 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-cyan-600 transition-all active:scale-95 flex items-center gap-2 shadow-lg shadow-cyan-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l-.024-.03c-1.285-1.56-1.93-3.19-1.93-5.166C4.766 4.902 6.713 3 9.12 3c2.407 0 4.354 1.902 4.354 4.624 0 1.977-.645 3.607-1.93 5.167-.004.005-.008.01-.012.015L9.12 15.652l-2.4-1.832z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 12h2m-2 4h2m-2-8h2M3 12h2m-2 4h2m-2-8h2M12 18H8.5c-.83 0-1.5-.67-1.5-1.5V14m8.5 4H16m0 0v-4.5c0-.83-.67-1.5-1.5-1.5H11" />
                    </svg>
                    {{ __('Print') }}
                </button>
                <button onclick="window.close()" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95">
                    {{ __('Close') }}
                </button>
            </div>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6 pb-6 border-b-2 border-slate-900 print-border">
            <div>
                <!-- Brand -->
                <p class="text-xs font-black text-cyan-600 uppercase tracking-[0.3em] font-display mb-1">StillBuilding Life System</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Tenant: {{ Auth::user()->company?->name ?? __('Platform Operator') }}</p>
                
                <!-- Document Title -->
                <h1 class="text-3xl font-black text-slate-900 tracking-tight font-display uppercase">{{ __('Replenishment Order') }}</h1>
                <p class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">{{ __('Vending Machine Product Replenishment') }}</p>
            </div>
            
            <div class="text-left sm:text-right flex flex-col sm:items-end">
                <!-- Status Badge -->
                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest border border-slate-900 print-border text-slate-900 mb-3 w-fit">
                    {{ __($order->status) }}
                </span>
                
                <!-- Mono Order No -->
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Order Number') }}</p>
                <p class="text-2xl font-black text-slate-900 font-mono tracking-tighter">{{ $order->order_no }}</p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 py-8 border-b border-slate-200">
            
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Source Warehouse') }}</p>
                <p class="text-sm font-extrabold text-slate-800">{{ $order->warehouse?->name ?? '-' }}</p>
            </div>
            
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Target Machine') }}</p>
                <p class="text-sm font-extrabold text-slate-800">{{ $order->machine?->name ?? '-' }} <span class="text-xs font-mono font-bold text-slate-400 tracking-widest uppercase">({{ $order->machine?->serial_no }})</span></p>
            </div>

            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Created By') }}</p>
                <p class="text-sm font-bold text-slate-700">{{ $order->creator?->name ?? __('System') }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Assigned Personnel') }}</p>
                <p class="text-sm font-bold text-slate-700">{{ $order->assignee?->name ?? __('Unassigned') }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Created At') }}</p>
                <p class="text-sm font-mono font-bold text-slate-600">{{ $order->created_at->timezone('Asia/Taipei')->format('Y-m-d H:i:s') }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Print Time') }}</p>
                <p class="text-sm font-mono font-bold text-slate-600">{{ now()->timezone('Asia/Taipei')->format('Y-m-d H:i:s') }}</p>
            </div>

            @if($order->note)
            <div class="col-span-1 md:col-span-2 space-y-1 pt-2 border-t border-dashed border-slate-200">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Note') }}</p>
                <p class="text-xs font-bold text-slate-600 italic">{{ $order->note }}</p>
            </div>
            @endif
        </div>

        <!-- Items Table -->
        <div class="py-8">
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em] mb-4 font-display">{{ __('Replenishment Details') }} ({{ $items->count() }} {{ __('Items') }})</h3>
            
            <table class="w-full text-left border-collapse table-fixed">
                <thead>
                    <tr class="border-b-2 border-slate-900 print-border bg-slate-50 print-bg-gray">
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest w-16 text-center whitespace-nowrap">
                            {{ __('Slot') }}
                        </th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest w-16 text-center whitespace-nowrap">
                            {{ __('Image') }}
                        </th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest min-w-[200px] whitespace-nowrap">
                            {{ __('Product Name') }}
                        </th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest w-28 whitespace-nowrap">
                            {{ __('Barcode') }}
                        </th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest w-24 text-right whitespace-nowrap">
                            {{ __('Stock Snap') }}
                        </th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest w-24 text-right whitespace-nowrap">
                            {{ __('Replenish Qty') }}
                        </th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest w-20 text-center no-print whitespace-nowrap">
                            {{ __('Check') }}
                        </th>
                        <!-- 列印時顯示的手寫格 -->
                        <th class="hidden print:table-cell px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest w-24 text-center whitespace-nowrap">
                            {{ __('Actual Qty') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($items as $index => $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 font-mono font-black text-slate-900 text-center text-sm">
                            <span class="inline-block px-2 py-1 rounded bg-slate-100 print-bg-gray border border-slate-200 print-border">
                                {{ $item->slot_no }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="mx-auto w-10 h-10 rounded-lg border border-slate-200 print-border overflow-hidden bg-slate-50 flex items-center justify-center shrink-0">
                                @if($item->product?->image_url)
                                    <img src="{{ $item->product->image_url }}" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 min-w-[200px] break-words">
                            <p class="text-sm font-extrabold text-slate-800 tracking-tight whitespace-normal">{{ $item->product?->localized_name ?? __('Unknown') }}</p>
                            <p class="text-[9px] font-mono font-bold text-slate-400 uppercase tracking-widest mt-0.5">ID: {{ $item->product_id }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs font-bold text-slate-500 break-all">
                            {{ $item->product?->barcode ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-right text-xs font-bold text-slate-500 font-mono whitespace-nowrap">
                            {{ $item->current_stock }} / {{ $item->max_stock }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <p class="text-lg font-black text-cyan-600 font-display tracking-tight">x{{ $item->quantity }}</p>
                        </td>
                        <!-- UI 頁面上的 check 欄位 -->
                        <td class="px-4 py-3 text-center no-print">
                            <div class="inline-flex items-center justify-center w-5 h-5 border-2 border-slate-300 rounded-md"></div>
                        </td>
                        <!-- 列印時的手寫框 -->
                        <td class="hidden print:table-cell px-4 py-3 text-center vertical-middle">
                            <div class="mx-auto w-12 h-6 border border-slate-400 print-border rounded"></div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Print Footer Warning (No Print) -->
        <div class="no-print mt-4 p-4 bg-amber-500/10 border border-amber-500/20 text-amber-600 rounded-2xl flex items-start gap-4 font-bold text-xs">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="font-extrabold uppercase tracking-widest mb-1">{{ __('Print Notice') }}</p>
                <p>{{ __('Please make sure to enable "Background graphics" in your browser print settings to print product images and styled components correctly.') }}</p>
            </div>
        </div>
    </div>

    <!-- Sign-off Section -->
    <div class="max-w-4xl mx-auto w-full mt-12 pt-8 border-t border-slate-200">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div class="space-y-6">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Prepared By') }}</p>
                <div class="mx-auto w-32 border-b border-slate-400 print-border h-8"></div>
            </div>
            <div class="space-y-6">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Warehouse Stock Out') }}</p>
                <div class="mx-auto w-32 border-b border-slate-400 print-border h-8"></div>
            </div>
            <div class="space-y-6">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Courier/Replenisher') }}</p>
                <div class="mx-auto w-32 border-b border-slate-400 print-border h-8"></div>
            </div>
            <div class="space-y-6">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Machine Inbound Confirmation') }}</p>
                <div class="mx-auto w-32 border-b border-slate-400 print-border h-8"></div>
            </div>
        </div>
        
        <div class="mt-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            StillBuilding Life © 2026. All rights reserved.
        </div>
    </div>

    <!-- Auto Print Script -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            // 自動觸發列印
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
