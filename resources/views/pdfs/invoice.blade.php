@php
    // Inline the Georgian font as data URIs so dompdf embeds it reliably
    // regardless of chroot / file-path resolution.
    $fontRegular = base64_encode(file_get_contents(public_path('fonts/NotoSansGeorgian-Regular.ttf')));
    $fontBold = base64_encode(file_get_contents(public_path('fonts/NotoSansGeorgian-Bold.ttf')));
    $fmt = fn ($n) => number_format((float) $n, 2, '.', ' ');
    $statusLabels = [
        'new' => 'ახალი',
        'contacted' => 'დაკავშირებული',
        'paid' => 'გადახდილი',
        'fulfilled' => 'შესრულებული',
        'cancelled' => 'გაუქმებული',
    ];
    $statusLabel = $statusLabels[$order->status] ?? $order->status;
@endphp
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <title>ინვოისი {{ $order->order_number }}</title>
    <style>
        @font-face {
            font-family: 'Geo';
            font-weight: 400;
            font-style: normal;
            src: url('data:font/truetype;charset=utf-8;base64,{{ $fontRegular }}') format('truetype');
        }
        @font-face {
            font-family: 'Geo';
            font-weight: 700;
            font-style: normal;
            src: url('data:font/truetype;charset=utf-8;base64,{{ $fontBold }}') format('truetype');
        }

        @page { margin: 32px 36px; }
        * { font-family: 'Geo', sans-serif; }
        body { font-size: 11px; color: #0F172A; line-height: 1.5; margin: 0; }
        .muted { color: #64748B; }
        .label { color: #64748B; font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; }
        .r { text-align: right; }
        .num { text-align: right; white-space: nowrap; }

        /* Two-column blocks use tables — dompdf has no flexbox. */
        .head { width: 100%; border-bottom: 2px solid #0F172A; padding-bottom: 14px; margin-bottom: 18px; }
        .head td { vertical-align: top; }
        .brand { font-size: 24px; font-weight: 700; letter-spacing: -0.01em; }
        .inv-no { font-size: 14px; font-weight: 700; margin-top: 2px; }

        .bill { width: 100%; margin-bottom: 18px; }
        .bill td { vertical-align: top; }
        .bill .name { font-weight: 700; }
        .status-pill { display: inline-block; padding: 3px 10px; border-radius: 999px; background: #F1F5F9; font-size: 10px; color: #334155; }

        table.items { width: 100%; border-collapse: collapse; }
        table.items th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #64748B; border-bottom: 1px solid #CBD5E1; padding: 0 8px 8px; font-weight: 700; }
        table.items td { padding: 9px 8px; border-bottom: 1px solid #F1F5F9; vertical-align: top; }
        .img-cell { width: 40px; padding-right: 0 !important; }
        .thumb { width: 36px; height: 36px; border-radius: 5px; border: 1px solid #E2E8F0; }
        .pname { font-weight: 700; }
        .sku { font-size: 10px; color: #94A3B8; }
        .opts { margin-top: 3px; font-size: 10px; color: #475569; }
        .opts .ok { color: #94A3B8; }
        .strike { text-decoration: line-through; color: #94A3B8; font-size: 10px; }
        .deal { color: #047857; }

        table.totals { width: 250px; margin-top: 14px; margin-left: auto; border-collapse: collapse; }
        table.totals td { padding: 4px 0; }
        table.totals .grand td { font-weight: 700; font-size: 14px; border-top: 1.5px solid #0F172A; padding-top: 9px; }

        .notes { margin-top: 22px; padding: 12px 14px; background: #F8FAFC; border-radius: 8px; }
        .foot { margin-top: 28px; text-align: center; font-size: 10px; color: #94A3B8; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <div class="brand">Roni<span style="color:#047857;">5</span></div>
                <div class="muted" style="margin-top:2px;">საკანცელარიო და საოფისე ნივთები</div>
            </td>
            <td class="r">
                <div class="label">ინვოისი</div>
                <div class="inv-no">{{ $order->order_number }}</div>
                <div class="muted" style="margin-top:2px;">{{ $order->created_at->format('d.m.Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="bill">
        <tr>
            <td style="width:60%;">
                <div class="label" style="margin-bottom:5px;">გადასახდელია</div>
                <div class="name">{{ $order->customer_snapshot['name'] ?? '' }}</div>
                @if(!empty($order->customer_snapshot['company_name']))
                    <div>{{ $order->customer_snapshot['company_name'] }}</div>
                @endif
                @if(!empty($order->customer_snapshot['company_tax_id']))
                    <div class="muted">საგ. კოდი: {{ $order->customer_snapshot['company_tax_id'] }}</div>
                @endif
                @if(!empty($order->customer_snapshot['address']))
                    <div>{{ $order->customer_snapshot['address'] }}</div>
                @endif
                <div class="muted">
                    {{ $order->customer_snapshot['email'] ?? '' }}@if(!empty($order->customer_snapshot['phone'])) · {{ $order->customer_snapshot['phone'] }}@endif
                </div>
            </td>
            <td class="r" style="width:40%;">
                <div class="label" style="margin-bottom:5px;">სტატუსი</div>
                <span class="status-pill">{{ $statusLabel }}</span>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="img-cell"></th>
                <th>პროდუქცია</th>
                <th class="num" style="width:48px;">რაოდ.</th>
                <th class="num" style="width:92px;">ერთეული</th>
                <th class="num" style="width:92px;">ჯამი</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                @php
                    // Inline the thumbnail as a data URI (dompdf embeds these
                    // reliably; bare file paths can fail chroot resolution).
                    $imgData = null;
                    if ($media = $item->product?->getFirstMedia('images')) {
                        foreach ([$media->getPath('thumb'), $media->getPath()] as $path) {
                            if ($path && is_file($path)) {
                                $mime = @mime_content_type($path) ?: 'image/jpeg';
                                if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
                                    $imgData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                                    break;
                                }
                            }
                        }
                    }
                @endphp
                <tr>
                    <td class="img-cell">
                        @if($imgData)
                            <img src="{{ $imgData }}" class="thumb" alt="">
                        @endif
                    </td>
                    <td>
                        <div class="pname">{{ $item->product_name_snapshot }}</div>
                        <div class="sku">{{ $item->product_sku_snapshot }}</div>
                        @if(!empty($item->options_snapshot))
                            <div class="opts">
                                @foreach($item->options_snapshot as $opt)
                                    <span class="ok">{{ $opt['attribute_name'] ?? '' }}:</span> {{ $opt['value_name'] ?? '' }}@if(!$loop->last) &nbsp;·&nbsp; @endif
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">
                        @if($item->unit_price_charged < $item->unit_price_retail)
                            <span class="strike">₾{{ $fmt($item->unit_price_retail) }}</span><br>
                            <span class="deal">₾{{ $fmt($item->unit_price_charged) }}</span>
                        @else
                            ₾{{ $fmt($item->unit_price_charged) }}
                        @endif
                    </td>
                    <td class="num">₾{{ $fmt($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        @if($order->discount_total > 0)
            <tr>
                <td class="muted">საცალო ჯამი</td>
                <td class="num strike">₾{{ $fmt($order->subtotal_retail) }}</td>
            </tr>
            <tr>
                <td class="muted">B2B ფასდაკლება</td>
                <td class="num deal">− ₾{{ $fmt($order->discount_total) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>სულ</td>
            <td class="num">₾{{ $fmt($order->total) }}</td>
        </tr>
    </table>

    @if($order->notes)
        <div class="notes">
            <div class="label" style="margin-bottom:3px;">შენიშვნა</div>
            <div>{{ $order->notes }}</div>
        </div>
    @endif

    <div class="foot">
        გმადლობთ ნდობისთვის! ფასები მითითებულია ლარში (₾).
    </div>
</body>
</html>
