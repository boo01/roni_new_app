@php $fmt = fn ($n) => number_format($n, 2, '.', ' '); @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ახალი შეკვეთა #{{ $order->order_number }}</title>
</head>
<body style="font-family: -apple-system, system-ui, sans-serif; max-width: 600px; margin: 24px auto; color: #0F172A; padding: 0 16px;">
    <h2 style="margin: 0 0 12px;">ახალი შეკვეთა #{{ $order->order_number }}</h2>
    <p style="color: #475569; margin: 0 0 20px;">მიღებულია {{ $order->created_at->format('d.m.Y H:i') }}.</p>

    <table style="width: 100%; border-collapse: collapse; margin: 0 0 20px;">
        <tr><td style="padding: 6px 0; color: #64748B;">მომხმარებელი</td><td style="padding: 6px 0; text-align: right;"><strong>{{ $order->customer_snapshot['name'] }}</strong></td></tr>
        @if(!empty($order->customer_snapshot['company_name']))
            <tr><td style="padding: 6px 0; color: #64748B;">კომპანია</td><td style="padding: 6px 0; text-align: right;">{{ $order->customer_snapshot['company_name'] }}</td></tr>
        @endif
        <tr><td style="padding: 6px 0; color: #64748B;">ელ. ფოსტა</td><td style="padding: 6px 0; text-align: right;">{{ $order->customer_snapshot['email'] }}</td></tr>
        <tr><td style="padding: 6px 0; color: #64748B;">ტელეფონი</td><td style="padding: 6px 0; text-align: right;">{{ $order->customer_snapshot['phone'] ?? '—' }}</td></tr>
        <tr><td style="padding: 6px 0; color: #64748B;">მისამართი</td><td style="padding: 6px 0; text-align: right;">{{ $order->customer_snapshot['address'] ?? '—' }}</td></tr>
    </table>

    <div style="background: #F8FAFC; border-radius: 8px; padding: 16px 20px; margin: 0 0 20px; text-align: center;">
        <p style="margin: 0; color: #64748B; font-size: 13px;">სულ</p>
        <p style="margin: 4px 0 0; font-size: 22px; font-weight: bold;">₾{{ $fmt($order->total) }}</p>
        @if($order->discount_total > 0)
            <p style="margin: 4px 0 0; color: #047857; font-size: 12px;">B2B ფასდაკლება: ₾{{ $fmt($order->discount_total) }}</p>
        @endif
    </div>

    @if($order->notes)
        <p style="margin: 0 0 8px;"><strong>მომხმარებლის შენიშვნა:</strong></p>
        <p style="margin: 0 0 20px; padding: 12px; background: #F8FAFC; border-radius: 6px; color: #334155;">{{ $order->notes }}</p>
    @endif

    <p style="margin: 0 0 8px;">ინვოისი თანდართულია PDF ფაილში.</p>

    <p style="margin: 24px 0;">
        <a href="{{ url('/admin/orders/' . $order->id) }}"
           style="display: inline-block; background: #0F172A; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500;">შეკვეთის ნახვა ადმინში</a>
    </p>

    <hr style="border: 0; border-top: 1px solid #E2E8F0; margin: 28px 0 12px;">
    <p style="color: #94A3B8; font-size: 12px; margin: 0;">Roni5 — საკანცელარიო და საოფისე ნივთები</p>
</body>
</html>
