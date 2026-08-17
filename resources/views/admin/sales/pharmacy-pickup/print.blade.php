<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('menu.sales.pharmacy-pickup') }} {{ $order->order_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Microsoft JhengHei", "PingFang TC", "Noto Sans TC", sans-serif; margin: 0; background: #f3f4f6; color: #111827; }
        .actions { text-align: center; margin: 16px auto; }
        .btn { display: inline-block; padding: 9px 22px; background: #06b6d4; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .ticket { width: 360px; margin: 12px auto 28px; background: #fff; padding: 22px 24px; border: 1px solid #e5e7eb; border-radius: 8px; }
        .head { text-align: center; border-bottom: 2px dashed #9ca3af; padding-bottom: 12px; margin-bottom: 12px; }
        .head h1 { font-size: 22px; margin: 0 0 4px; letter-spacing: 4px; }
        .head .sub { font-size: 12px; color: #6b7280; }
        .row { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; margin: 4px 0; }
        .row .k { color: #6b7280; white-space: nowrap; }
        .row .v { font-weight: 600; text-align: right; word-break: break-all; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0 6px; font-size: 13px; }
        th, td { text-align: left; padding: 6px 4px; border-bottom: 1px solid #f0f0f0; }
        th { color: #6b7280; font-weight: 700; border-bottom: 1px solid #d1d5db; }
        th.qty, td.qty { text-align: center; width: 56px; }
        .code-box { text-align: center; margin: 16px 0 4px; }
        .code-box .label { font-size: 12px; color: #6b7280; }
        .code-box .code { font-size: 32px; font-weight: 800; letter-spacing: 8px; font-family: "Courier New", monospace; }
        .qr { text-align: center; margin: 8px 0; }
        .qr svg { width: 200px; height: 200px; }
        .note { font-size: 12px; color: #4b5563; text-align: center; margin-top: 12px; border-top: 2px dashed #9ca3af; padding-top: 12px; line-height: 1.6; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .ticket { border: none; margin: 0 auto; }
        }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button class="btn" onclick="window.print()">{{ __('Print') }}</button>
    </div>

    <div class="ticket">
        <div class="head">
            <h1>{{ __('menu.sales.pharmacy-pickup') }}</h1>
            <div class="sub">{{ $order->machine?->name }}@if($order->machine?->location) · {{ $order->machine->location }}@endif</div>
        </div>

        <div class="row"><span class="k">{{ __('Order No.') }}</span><span class="v">{{ $order->order_no }}</span></div>
        @if($order->pricing_slip_no)
        <div class="row"><span class="k">{{ __('Pricing Slip No.') }}</span><span class="v">{{ $order->pricing_slip_no }}</span></div>
        @endif
        <div class="row"><span class="k">{{ __('Created') }}</span><span class="v">{{ $order->created_at?->format('Y-m-d H:i') }}</span></div>
        @if($order->pickupCode?->expires_at)
        <div class="row"><span class="k">{{ __('Valid Until') }}</span><span class="v">{{ $order->pickupCode->expires_at->format('Y-m-d H:i') }}</span></div>
        @endif

        <table>
            <thead>
                <tr><th>{{ __('Medicine') }}</th><th class="qty">{{ __('Quantity') }}</th></tr>
            </thead>
            <tbody>
                @foreach($order->items as $it)
                <tr><td>{{ $it->product_name }}</td><td class="qty">{{ $it->quantity }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <div class="code-box">
            <div class="label">{{ __('Pickup Code') }}</div>
            <div class="code">{{ $order->pickupCode?->code ?? '--------' }}</div>
        </div>

        @if($qrSvg)
        <div class="qr">{!! $qrSvg !!}</div>
        @endif

        <div class="note">{{ __('Scan the QR code or enter the pickup code at the machine to collect your medicine.') }}</div>
    </div>
</body>
</html>
