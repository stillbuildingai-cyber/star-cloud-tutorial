<div id="replenishments-table-container">
    <div class="hidden xl:block overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-y-0">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Order No.') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Machine') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Warehouse') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Items') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Status') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Assigned To') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Created By') }}</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                @forelse($orders as $order)
                <tr class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all duration-300">
                    {{-- 訂單編號 --}}
                    <td class="px-6 py-5 cursor-pointer" @click="openOrderDetails('{{ $order->id }}')">
                        <div class="flex items-center gap-x-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div class="flex flex-col group/no">
                                <span class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover/no:text-cyan-600 dark:group-hover/no:text-cyan-400 transition-colors">
                                    {{ $order->order_no }}
                                </span>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $order->created_at->format('Y-m-d H:i:s') }}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    {{-- 機台 --}}
                    <td class="px-6 py-3.5">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $order->machine?->name }}</span>
                            <span class="text-xs font-mono text-slate-400">{{ $order->machine?->serial_no }}</span>
                        </div>
                    </td>
                    {{-- 倉庫 --}}
                    <td class="px-6 py-3.5"><span class="font-bold text-slate-600 dark:text-slate-300">{{ $order->warehouse?->name }}</span></td>
                    {{-- 項目數 --}}
                    <td class="px-6 py-3.5 text-center"><span class="font-black text-slate-800 dark:text-white">{{ $order->items_count }}</span></td>
                    {{-- 狀態標籤 --}}
                    <td class="px-6 py-3.5 text-center">
                        @php
                            $colors = ['pending' => 'amber', 'prepared' => 'cyan', 'delivering' => 'indigo', 'completed' => 'emerald', 'cancelled' => 'slate'];
                            $sColor = $colors[$order->status] ?? 'cyan';
                        @endphp
                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black bg-{{ $sColor }}-500/10 text-{{ $sColor }}-500 border border-{{ $sColor }}-500/20 tracking-widest uppercase">{{ __(ucfirst($order->status)) }}</span>
                    </td>
                    {{-- 指派人員 --}}
                    <td class="px-6 py-3.5">
                        <span class="text-sm font-bold {{ $order->assignee ? 'text-slate-700 dark:text-slate-200' : 'text-slate-400' }}">
                            {{ $order->assignee?->name ?? __('Unassigned') }}
                        </span>
                    </td>
                    {{-- 建立人 --}}
                    <td class="px-6 py-3.5"><span class="text-sm font-bold text-slate-500">{{ $order->creator?->name ?? '-' }}</span></td>
                    {{-- 操作按鈕 --}}
                    <td class="px-6 py-3.5 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-2">
                            {{-- 查看詳情 --}}
                             <button type="button" @click="openOrderDetails('{{ $order->id }}')"
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20 shadow-sm group/btn"
                                title="{{ __('View Details') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.79 0 8.601 3.049 9.964 6.678.045.133.045.278 0 .412C20.601 15.951 16.79 19 12 19c-4.79 0-8.601-3.049-9.964-6.678z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>

                             {{-- 狀態推進按鈕（依當前狀態顯示） --}}
                            @if($order->status === 'pending')
                                <button type="button" @click.stop="advanceStatus('{{ $order->id }}', 'prepared', '{{ $order->assigned_to }}')"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all border border-transparent hover:border-emerald-500/20"
                                    title="{{ __('Confirm Prepare') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @elseif($order->status === 'prepared')
                                <button type="button" @click.stop="advanceStatus('{{ $order->id }}', 'delivering', '{{ $order->assigned_to }}')"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-indigo-500 hover:bg-indigo-500/5 transition-all border border-transparent hover:border-indigo-500/20"
                                    title="{{ __('Start Delivery') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                    </svg>
                                </button>
                            @elseif($order->status === 'delivering')
                                <button type="button" @click.stop="advanceStatus('{{ $order->id }}', 'completed', '{{ $order->assigned_to }}')"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all border border-transparent hover:border-emerald-500/20"
                                    title="{{ __('Confirm Complete') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            @endif

                            {{-- 取消按鈕（非完成/非取消狀態顯示） --}}
                            @if(in_array($order->status, ['pending', 'prepared', 'delivering']))
                                <button type="button" @click.stop="confirmCancel('{{ $order->id }}')"
                                    class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all border border-transparent hover:border-rose-500/20"
                                    title="{{ __('Cancel Order') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No replenishment orders found') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- Card Mode (Mobile & Tablet) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:hidden gap-6 mt-8">
        @forelse($orders as $order)
        <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group"
             @click="openOrderDetails('{{ $order->id }}')">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1 cursor-pointer">
                        <h3 class="text-base sm:text-lg font-black text-slate-800 dark:text-slate-100 break-all group-hover:text-cyan-600 transition-colors tracking-tight">{{ $order->order_no }}</h3>
                        <p class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-widest truncate mt-0.5">{{ $order->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                </div>
                <div class="shrink-0">
                    @php
                        $colors = ['pending' => 'amber', 'prepared' => 'cyan', 'delivering' => 'indigo', 'completed' => 'emerald', 'cancelled' => 'slate'];
                        $sColor = $colors[$order->status] ?? 'cyan';
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-{{ $sColor }}-500/10 text-{{ $sColor }}-500 border border-{{ $sColor }}-500/20 tracking-widest uppercase shadow-sm">
                        {{ __(ucfirst($order->status)) }}
                    </span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                <div class="col-span-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Machine') }}</p>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $order->machine?->name }}</span>
                        <span class="text-[10px] font-mono text-slate-400">{{ $order->machine?->serial_no }}</span>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Warehouse') }}</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">{{ $order->warehouse?->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Items') }}</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $order->items_count }}</p>
                </div>
                <div class="col-span-2 grid grid-cols-2 gap-4 pt-4 border-t border-slate-100 dark:border-slate-800/50">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Assigned To') }}</p>
                        <p class="text-sm font-bold {{ $order->assignee ? 'text-slate-700 dark:text-slate-200' : 'text-slate-400' }}">
                            {{ $order->assignee?->name ?? __('Unassigned') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Created By') }}</p>
                        <p class="text-sm font-bold text-slate-500">{{ $order->creator?->name ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" @click.stop="openOrderDetails('{{ $order->id }}')"
                   class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-[10px] sm:text-xs tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-300 border border-slate-200 dark:border-slate-700 shadow-sm">
                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    {{ __('Details') }}
                </button>
                @if($order->status === 'pending')
                    <button type="button" @click.stop="advanceStatus('{{ $order->id }}', 'prepared', '{{ $order->assigned_to }}')"
                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-emerald-500/10 text-emerald-500 font-black text-xs uppercase tracking-widest hover:bg-emerald-500 hover:text-white transition-all duration-300 border border-emerald-500/20 shadow-sm">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        {{ __('Confirm') }}
                    </button>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full py-20 text-center flex flex-col items-center gap-4 bg-white/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800/50 luxury-card">
            <div class="flex flex-col items-center gap-3">
                <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="text-slate-400 dark:text-slate-500 font-extrabold tracking-widest uppercase text-xs">{{ __('No replenishment orders found') }}</p>
            </div>
        </div>
        @endforelse
    </div>

    <div class="mt-6 py-6 border-t border-slate-50 dark:border-slate-800/50">
        @if($orders->total() > 0)
            {{ $orders->links('vendor.pagination.luxury', ['ajax_navigate_event' => 'ajax:navigate:replenishments']) }}
        @else
            <div class="flex items-center justify-between gap-4 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                <span>{{ __('No records found') }}</span>
                <span>0 / 0</span>
            </div>
        @endif
    </div>
</div>
