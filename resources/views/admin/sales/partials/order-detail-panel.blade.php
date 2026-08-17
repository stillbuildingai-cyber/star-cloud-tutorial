{{-- 側滑面板標題 --}}
<div class="px-5 py-6 sm:px-8 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <h2 class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white font-display flex items-center gap-2 sm:gap-3">
                <div class="p-2 bg-cyan-500/10 rounded-xl">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-cyan-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="truncate">{{ __('Order Details') }}</span>
            </h2>
            <div class="mt-2 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest">
                <span class="text-cyan-600 dark:text-cyan-400 font-mono tracking-tighter text-lg">{{ $order->order_no }}</span>
            </div>
        </div>
        <button type="button" @click="showPanel = false"
            class="bg-white dark:bg-slate-800 rounded-full p-2 text-slate-400 hover:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none transition duration-300 shadow-sm border border-slate-200 dark:border-slate-700">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>

<div class="flex-1 overflow-y-auto p-6 sm:p-8 space-y-10">
    {{-- 基本狀態卡片 --}}
    <div class="grid grid-cols-3 gap-6 p-6 bg-slate-50/50 dark:bg-slate-800/30 rounded-[2rem] border border-slate-100 dark:border-slate-800">
        <div class="space-y-2">
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Payment Status') }}</p>
            <div>
                @php
                    $statusMap = [
                        'pending' => 'pending',
                        'paid' => 'active',
                        'completed' => 'active',
                        'awaiting_pickup' => 'pending',
                        'failed' => 'error',
                        'abandoned' => 'disabled',
                        'cancelled' => 'disabled',
                        'refunded' => 'error',
                    ];
                    $labelMap = [
                        'pending' => __('Pending'),
                        'paid' => __('Paid'),
                        'completed' => __('Completed'),
                        'awaiting_pickup' => __('Awaiting Pickup'),
                        'failed' => __('Failed'),
                        'abandoned' => __('Unpaid'),
                        'cancelled' => __('Cancelled'),
                        'refunded' => __('Refunded'),
                    ];
                    $status = $statusMap[$order->status] ?? 'slate';
                    $label = $labelMap[$order->status] ?? $order->status;
                @endphp
                <x-status-badge :status="$status" :label="$label" size="sm" />
            </div>
        </div>
        <div class="space-y-2">
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Payment Type') }}</p>
            <p class="text-sm font-extrabold text-slate-700 dark:text-slate-200">
                {{ $order->payment_type_label }}
            </p>
        </div>
        <div class="space-y-2">
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Dispense Status') }}</p>
            <div>
                {{-- 非付款成功(未完成/支付失敗/未支付)的單從未出貨，出貨狀態無意義 → 顯示 '--' --}}
                @if(! $order->hasDeliveryOutcome())
                <span class="text-sm font-extrabold text-slate-300 dark:text-slate-700 tracking-widest">--</span>
                @else
                @php
                    $deliveryStatusMap = [
                        0 => ['label' => __('Dispense Failed'), 'color' => 'rose'],
                        1 => ['label' => __('Dispense Success'), 'color' => 'emerald'],
                        2 => ['label' => __('Partial Dispense Success'), 'color' => 'amber'],
                    ];
                    $ds = $deliveryStatusMap[$order->delivery_status] ?? ['label' => __('Unknown'), 'color' => 'slate'];
                @endphp
                <x-status-badge :color="$ds['color']" :label="$ds['label']" size="sm" />
                @endif
            </div>
        </div>
    </div>

    {{-- 交易資訊 --}}
    <section class="space-y-4">
        <div class="flex items-center gap-2 px-1">
            <div class="w-1.5 h-4 bg-cyan-500 rounded-full"></div>
            <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Transaction Info') }}</h3>
        </div>
        <div class="grid grid-cols-2 gap-8 p-8 bg-slate-50/50 dark:bg-slate-800/30 rounded-[2rem] border border-slate-100 dark:border-slate-800">
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Transaction Time') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono tracking-tight">{{ $order->created_at->format('Y-m-d H:i:s') }}</p>
            </div>
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Machine Name') }}</p>
                <p class="text-sm font-extrabold text-slate-700 dark:text-slate-200">{{ $order->machine->name ?? __('Unknown') }}</p>
            </div>
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Machine Serial') }}</p>
                <p class="text-xs font-black text-slate-400 dark:text-slate-500 font-mono uppercase tracking-widest">{{ $order->machine->serial_no ?? '---' }}</p>
            </div>
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Machine Flow ID') }}</p>
                {{-- 顯示去前綴序號的乾淨流水號（DB 仍存完整 serial+flow_id 唯一值供後台判斷） --}}
                <p class="text-xs font-black text-slate-400 dark:text-slate-500 font-mono uppercase tracking-widest">{{ $order->display_flow_id }}</p>
            </div>
        </div>
    </section>

    {{-- 管理者備註（訂單內部註記） --}}
    <section class="space-y-4"
        x-data="{
            editing: false,
            saving: false,
            value: @js($order->remark ?? ''),
            original: @js($order->remark ?? ''),
            cancel() { this.value = this.original; this.editing = false; },
            save() {
                this.saving = true;
                fetch(@js(route('admin.sales.orders.remark', $order)), {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()), 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ remark: this.value })
                }).then(r => { if (!r.ok) throw 0; return r.json(); })
                  .then(d => { this.original = this.value; this.editing = false; window.showToast?.((d && d.message) || @js(__('Remark updated')), 'success'); })
                  .catch(() => window.showToast?.(@js(__('Operation failed')), 'error'))
                  .finally(() => this.saving = false);
            }
        }">
        <div class="flex items-center justify-between gap-2 px-1">
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-4 bg-cyan-500 rounded-full"></div>
                <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Remark') }}</h3>
            </div>
            <button type="button" x-show="!editing" @click="editing = true"
                class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-widest hover:underline">{{ __('Edit') }}</button>
        </div>
        <div class="p-6 bg-slate-50/50 dark:bg-slate-800/30 rounded-[2rem] border border-slate-100 dark:border-slate-800">
            <template x-if="!editing">
                <p class="text-sm font-bold whitespace-pre-wrap break-words"
                    :class="value ? 'text-slate-600 dark:text-slate-300' : 'text-slate-300 dark:text-slate-600'"
                    x-text="value || @js(__('No remark'))"></p>
            </template>
            <template x-if="editing">
                <div class="space-y-3">
                    <textarea x-model="value" rows="3" maxlength="1000"
                        placeholder="{{ __('Enter remark (optional)') }}"
                        class="luxury-input w-full text-sm py-2.5 px-4 resize-none"></textarea>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="cancel()" :disabled="saving"
                            class="px-5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest disabled:opacity-60">{{ __('Cancel') }}</button>
                        <button type="button" @click="save()" :disabled="saving"
                            class="px-5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-600 text-white font-black text-xs uppercase tracking-widest disabled:opacity-60">{{ __('Save') }}</button>
                    </div>
                </div>
            </template>
        </div>
    </section>

    {{-- 員工卡資訊 --}}
    @if($order->payment_type == 41 && $order->staffCardLog && $order->staffCardLog->staffCard)
    <section class="space-y-4">
        <div class="flex items-center gap-2 px-1">
            <div class="w-1.5 h-4 bg-cyan-500 rounded-full"></div>
            <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Staff Card Info') }}</h3>
        </div>
        <div class="grid grid-cols-2 gap-8 p-8 bg-slate-50/50 dark:bg-slate-800/30 rounded-[2rem] border border-slate-100 dark:border-slate-800">
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Staff Name') }}</p>
                <p class="text-sm font-extrabold text-slate-700 dark:text-slate-200">{{ $order->staffCardLog->staffCard->name }}</p>
            </div>
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Employee ID') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono tracking-tight">{{ $order->staffCardLog->staffCard->employee_id }}</p>
            </div>
            <div class="space-y-1 col-span-2">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Card UID') }}</p>
                <p class="text-xs font-black text-slate-400 dark:text-slate-500 font-mono uppercase tracking-widest">{{ $order->staffCardLog->staffCard->card_uid }}</p>
            </div>
            @if($order->staffCardLog->staffCard->notes)
            <div class="space-y-1 col-span-2">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Notes') }}</p>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ $order->staffCardLog->staffCard->notes }}</p>
            </div>
            @endif
        </div>
    </section>
    @endif

    @if($order->payment_type == 42 && !empty($order->member_barcode))
    <section class="space-y-4">
        <div class="flex items-center gap-2 px-1">
            <div class="w-1.5 h-4 bg-cyan-500 rounded-full"></div>
            <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Pickup Recipient Info') }}</h3>
        </div>
        <div class="grid grid-cols-2 gap-8 p-8 bg-slate-50/50 dark:bg-slate-800/30 rounded-[2rem] border border-slate-100 dark:border-slate-800">
            <div class="space-y-1 col-span-2">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Pickup Recipient (Prescription Slip)') }}</p>
                <p class="text-sm font-extrabold text-slate-700 dark:text-slate-200">{{ $order->masked_pickup_recipient }}</p>
            </div>
        </div>
    </section>
    @endif

    {{-- 商品明細 --}}
    <section class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-4 bg-cyan-500 rounded-full"></div>
                <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Product Details') }}</h3>
            </div>
            <span class="text-[10px] font-black text-cyan-500 bg-cyan-500/10 px-2 py-0.5 rounded-md uppercase tracking-widest">{{ $order->items->count() }} {{ __('Items') }}</span>
        </div>
        
        <div class="space-y-3">
            @foreach($order->items as $item)
            <div class="luxury-card p-4 rounded-2xl flex items-center gap-4 group/item transition-all hover:border-cyan-500/30" 
                 x-data="{ imgFailed: {{ (isset($item->product->image_url) && $item->product->image_url) ? 'false' : 'true' }} }">
                <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-800 flex-shrink-0 overflow-hidden border border-slate-200 dark:border-slate-700 group-hover:scale-105 transition-transform flex items-center justify-center relative">
                    @if(isset($item->product->image_url) && $item->product->image_url)
                        <img src="{{ $item->product->image_url }}" class="w-full h-full object-cover" x-show="!imgFailed" x-on:error="imgFailed = true">
                    @endif
                    
                    <div x-show="imgFailed" 
                         class="absolute inset-0 flex items-center justify-center bg-slate-50 dark:bg-slate-800/50">
                        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600 group-hover/item:text-cyan-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-base font-extrabold text-slate-800 dark:text-white truncate tracking-tight">{{ $item->product_name }}</p>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">${{ number_format($item->price, 0) }} × {{ $item->quantity }}</span>
                        @if(!empty($item->slot_no))
                        {{-- 消費者選擇的貨道/格子（不管出貨成功失敗都會記錄） --}}
                        <span class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-widest">{{ __('Slot') }}: {{ $item->slot_no }}</span>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-base font-black text-slate-800 dark:text-white tracking-tight">${{ number_format($item->subtotal, 0) }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- 金額彙總 --}}
        <div class="mt-6 p-8 rounded-[2rem] bg-slate-50/50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800 relative overflow-hidden group">
            <!-- Decorative Gradient -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-500/10 blur-[50px] -mr-16 -mt-16 group-hover:bg-cyan-500/20 transition-colors duration-500"></div>
            
            <div class="relative space-y-4">
                <div class="flex justify-between items-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">
                    <span>{{ __('Subtotal') }}</span>
                    <span class="font-mono text-slate-600 dark:text-slate-300">${{ number_format($order->total_amount + $order->discount_amount, 0) }}</span>
                </div>
                <div class="flex justify-between items-center text-[10px] font-black text-rose-500 uppercase tracking-[0.2em]">
                    <span>{{ __('Discount') }}</span>
                    <span class="font-mono">-${{ number_format($order->discount_amount, 0) }}</span>
                </div>
                <div class="pt-4 mt-2 border-t border-slate-100 dark:border-slate-800/50 flex justify-between items-baseline">
                    <span class="text-sm font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ __('Total') }}</span>
                    <div class="text-right">
                        <span class="text-3xl font-black text-cyan-600 dark:text-cyan-400 tracking-tighter font-display">${{ number_format($order->total_amount, 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 出貨紀錄 --}}
    @if($order->dispenseRecords->count() > 0)
    <section class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Dispense Status') }}</h3>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[2rem] overflow-hidden shadow-sm">
            <table class="w-full text-left border-separate border-spacing-y-0">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">{{ __('Slot') }}</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">{{ __('Product') }}</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                    @foreach($order->dispenseRecords as $dispense)
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                        <td class="px-6 py-4">
                            <span class="text-xs font-black text-cyan-600 dark:text-cyan-400 font-mono">#{{ str_pad($dispense->slot_no, 2, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $dispense->product->localized_name ?? __('Unknown Product') }}</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @php
                                $dStatusMap = [
                                    'success' => 'active',
                                    'failed' => 'error',
                                    'pending' => 'pending',
                                    '1' => 'active',
                                    '0' => 'error',
                                ];
                                $dLabelMap = [
                                    'success' => __('Success'),
                                    'failed' => __('Failed'),
                                    'pending' => __('Pending'),
                                    '1' => __('Success'),
                                    '0' => __('Failed'),
                                ];
                                $statusValue = (string)($dispense->dispense_status ?? 'pending');
                                $ds = $dStatusMap[$statusValue] ?? 'slate';
                                $dl = $dLabelMap[$statusValue] ?? $statusValue;
                            @endphp
                            <x-status-badge :status="$ds" :label="$dl" size="xs" />
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    {{-- 發票資訊 --}}
    @if($order->invoice)
    <section class="space-y-4">
        <div class="flex items-center gap-2 px-1">
            <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
            <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Invoice Information') }}</h3>
        </div>
        <div class="bg-indigo-50/30 dark:bg-indigo-500/5 border border-indigo-100 dark:border-indigo-500/20 rounded-[2rem] p-8 group relative overflow-hidden">
            <!-- Decorative Accent -->
            <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity duration-500">
                <svg class="w-24 h-24 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>

            @php
                // 以發票狀態機顯示（pending/issued/failed/void）；舊資料無 status 時以發票號回推
                $invStatus = $order->invoice->status ?: (empty($order->invoice->invoice_no) ? 'failed' : 'issued');
                $invStatusMap = [
                    'pending' => ['label' => __('Pending'), 'color' => 'amber'],
                    'issued' => ['label' => __('Issued'), 'color' => 'emerald'],
                    'failed' => ['label' => __('Failed'), 'color' => 'rose'],
                    'void' => ['label' => __('Void'), 'color' => 'slate'],
                ];
                $invBadge = $invStatusMap[$invStatus] ?? ['label' => $invStatus, 'color' => 'slate'];
                // 僅「統編發票」可列印：本系統開立時 Print=1 僅給帶統編者；
                // 捐贈、載具、一般無統編 B2C 皆為 Print=0，綠界 InvoicePrint 會回查無資料而失敗。
                $invPrintable = !empty($order->invoice->metadata['business_tax_id'] ?? '');
            @endphp
            <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <p class="text-xl font-black text-slate-800 dark:text-white font-mono tracking-tighter">{{ $order->invoice->invoice_no ?: '---' }}</p>
                        <x-status-badge :color="$invBadge['color']" :label="$invBadge['label']" size="xs" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Issued At') }}</p>
                        <p class="text-sm font-bold text-slate-600 dark:text-slate-400 font-mono tracking-tight">
                            {{ optional($order->invoice->invoice_date)->format('Y-m-d') ?: '---' }}
                        </p>
                    </div>
                </div>

                @if($invStatus === 'issued')
                <div class="flex items-center gap-3">
                    {{-- 列印：僅統編發票可列印（載具／捐贈／一般無統編 B2C 為 Print=0，綠界查無資料） --}}
                    @if($invPrintable)
                    <form method="POST" action="{{ route('admin.sales.invoices.print', $order->invoice) }}" target="_blank">
                        @csrf
                        <button type="submit" class="flex-1 sm:flex-none px-6 py-3 rounded-xl bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 hover:bg-indigo-500 hover:text-white hover:border-indigo-500 transition-all duration-300 shadow-sm">
                            {{ __('Print Invoice') }}
                        </button>
                    </form>
                    @else
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 px-2">{{ __('This invoice has no printable copy') }}</span>
                    @endif
                    {{-- 作廢：僅平台系統管理員可見可用（後端亦有 403 守衛）。使用自製確認框 UI --}}
                    @if(auth()->user()->isSystemAdmin())
                    <form id="invoice-void-form-{{ $order->invoice->id }}" method="POST" action="{{ route('admin.sales.invoices.void', $order->invoice) }}" class="hidden">
                        @csrf
                    </form>
                    <button type="button" @click="confirmVoidInvoice('invoice-void-form-{{ $order->invoice->id }}')"
                        class="flex-1 sm:flex-none px-6 py-3 rounded-xl bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all duration-300 shadow-sm">
                        {{ __('Void Invoice') }}
                    </button>
                    @endif
                </div>
                @endif
            </div>

            {{-- 發票備註（管理者內部註記；作廢視窗輸入的備註也會顯示於此） --}}
            <div class="relative mt-6 pt-6 border-t border-indigo-100/70 dark:border-indigo-500/20"
                x-data="{
                    editing: false,
                    saving: false,
                    value: @js($order->invoice->remark ?? ''),
                    original: @js($order->invoice->remark ?? ''),
                    cancel() { this.value = this.original; this.editing = false; },
                    save() {
                        this.saving = true;
                        fetch(@js(route('admin.sales.invoices.remark', $order->invoice)), {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()), 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ remark: this.value })
                        }).then(r => { if (!r.ok) throw 0; return r.json(); })
                          .then(d => { this.original = this.value; this.editing = false; window.showToast?.((d && d.message) || @js(__('Remark updated')), 'success'); })
                          .catch(() => window.showToast?.(@js(__('Operation failed')), 'error'))
                          .finally(() => this.saving = false);
                    }
                }">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Remark') }}</p>
                    <button type="button" x-show="!editing" @click="editing = true"
                        class="text-[10px] font-black text-indigo-500 dark:text-indigo-400 uppercase tracking-widest hover:underline">{{ __('Edit') }}</button>
                </div>
                <template x-if="!editing">
                    <p class="text-sm font-bold whitespace-pre-wrap break-words"
                        :class="value ? 'text-slate-600 dark:text-slate-300' : 'text-slate-300 dark:text-slate-600'"
                        x-text="value || @js(__('No remark'))"></p>
                </template>
                <template x-if="editing">
                    <div class="space-y-3">
                        <textarea x-model="value" rows="3" maxlength="1000"
                            placeholder="{{ __('Enter remark (optional)') }}"
                            class="luxury-input w-full text-sm py-2.5 px-4 resize-none"></textarea>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" @click="cancel()" :disabled="saving"
                                class="px-5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest disabled:opacity-60">{{ __('Cancel') }}</button>
                            <button type="button" @click="save()" :disabled="saving"
                                class="px-5 py-2 rounded-xl bg-indigo-500 hover:bg-indigo-600 text-white font-black text-xs uppercase tracking-widest disabled:opacity-60">{{ __('Save') }}</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </section>
    @endif
</div>
