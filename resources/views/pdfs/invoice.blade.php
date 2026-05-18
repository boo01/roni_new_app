<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ინვოისი {{ $order->order_number }}</title>
    <style>
        @page { margin: 32px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0F172A; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #64748B; }
        .row { display: flex; justify-content: space-between; align-items: flex-start; }
        .label { color: #64748B; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; border-bottom: 1px solid #E2E8F0; padding: 8px 6px; font-weight: 500; }
        td { padding: 10px 6px; border-bottom: 1px solid #F1F5F9; vertical-align: top; }
        .num { text-align: right; }
        .strike { text-decoration: line-through; color: #94A3B8; font-size: 10px; }
        .deal { color: #047857; }
        .totals { margin-top: 12px; width: 240px; margin-left: auto; }
        .totals td { padding: 4px 0; border: 0; }
        .totals .grand { font-weight: bold; font-size: 14px; border-top: 1px solid #E2E8F0; padding-top: 8px; }
        .sku { font-family: DejaVu Sans Mono, monospace; font-size: 10px; color: #64748B; }
        .header-bar { border-bottom: 2px solid #0F172A; padding-bottom: 12px; margin-bottom: 20px; }
        .status-pill { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #F1F5F9; font-size: 10px; color: #334155; }
    </style>
</head>
<body>
    <div class="header-bar row">
        <div>
            <h1>Roni5</h1>
            <p class="muted" style="margin: 0;">საკანცელარიო და საოფისე ნივთები</p>
        </div>
        <div style="text-align: right;">
            <p class="label">ინვოისი</p>
            <p style="margin: 0; font-size: 14px; font-weight: bold;">{{ $order->order_number }}</p>
            <p class="muted" style="margin: 4px 0 0;">{{ $order->created_at->format('d.m.Y H:i') }}</p>
        </div>
    </div>

    <div class="row" style="margin-bottom: 16px;">
        <div style="width: 48%;">
            <p class="label" style="margin-bottom: 4px;">გადასახდელია</p>
            <p style="margin: 0; font-weight: bold;">{{ $order->customer_snapshot['name'] }}</p>
            @if(!empty($order->customer_snapshot['company_name']))
                <p style="margin: 2px 0;">{{ $order->customer_snapshot['company_name'] }}</p>
            @endif
            @if(!empty($order->customer_snapshot['company_tax_id']))
                <p style="margin: 2px 0;" class="muted">საგ. კოდი: {{ $order->customer_snapshot['company_tax_id'] }}</p>
            @endif
            @if(!empty($order->customer_snapshot['address']))
                <p style="margin: 2px 0;">{{ $order->customer_snapshot['address'] }}</p>
            @endif
            <p style="margin: 2px 0;" class="muted">{{ $order->customer_snapshot['email'] }} · {{ $order->customer_snapshot['phone'] ?? '' }}</p>
        </div>
        <div style="width: 48%; text-align: right;">
            <p class="label" style="margin-bottom: 4px;">სტატუსი</p>
            <span class="status-pill">{{ $order->status }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>პროდუქცია</th>
                <th class="num" style="width: 60px;">რაოდენ.</th>
                <th class="num" style="width: 100px;">ერთეული</th>
                <th class="num" style="width: 100px;">ჯამი</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name_snapshot }}<br>
                        <span class="sku">{{ $item->product_sku_snapshot }}</span>
                    </td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">
                        @if($item->unit_price_charged < $item->unit_price_retail)
                            <span class="strike">₾{{ number_format($item->unit_price_retail, 2) }}</span><br>
                            <span class="deal">₾{{ number_format($item->unit_price_charged, 2) }}</span>
                        @else
                            ₾{{ number_format($item->unit_price_charged, 2) }}
                        @endif
                    </td>
                    <td class="num">₾{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        @if($order->discount_total > 0)
            <tr>
                <td class="muted">საცალო ჯამი</td>
                <td class="num strike">₾{{ number_format($order->subtotal_retail, 2) }}</td>
            </tr>
            <tr>
                <td class="muted">B2B ფასდაკლება</td>
                <td class="num deal">− ₾{{ number_format($order->discount_total, 2) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>სულ</td>
            <td class="num">₾{{ number_format($order->total, 2) }}</td>
        </tr>
    </table>

    @if($order->notes)
        <div style="margin-top: 24px;">
            <p class="label">შენიშვნა</p>
            <p>{{ $order->notes }}</p>
        </div>
    @endif

    <p class="muted" style="margin-top: 32px; text-align: center; font-size: 10px;">
        გმადლობთ ნდობისთვის! ფასები მითითებულია ლარში (₾).
    </p>
</body>
</html>
