@php $fmt = fn ($n) => number_format($n, 2, '.', ' '); @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>თქვენი შეკვეთა #{{ $order->order_number }} მიღებულია</title>
</head>
<body style="font-family: -apple-system, system-ui, sans-serif; max-width: 600px; margin: 24px auto; color: #0F172A; padding: 0 16px;">
    <h2 style="margin: 0 0 12px;">გმადლობთ შეკვეთისთვის!</h2>
    <p style="margin: 0 0 16px; color: #334155;">თქვენი შეკვეთა <strong style="font-family: monospace;">#{{ $order->order_number }}</strong> მიღებულია. მაღაზიის წარმომადგენელი მალე დაგიკავშირდებათ.</p>

    <div style="background: #F8FAFC; border-radius: 8px; padding: 16px 20px; margin: 0 0 20px; text-align: center;">
        <p style="margin: 0; color: #64748B; font-size: 13px;">სულ</p>
        <p style="margin: 4px 0 0; font-size: 22px; font-weight: bold;">₾{{ $fmt($order->total) }}</p>
    </div>

    <p style="margin: 0 0 16px;">ინვოისი თანდართულია PDF ფაილში.</p>
    <p style="margin: 0 0 8px; color: #64748B; font-size: 13px;">თუ კითხვა გაქვთ — გვიპასუხეთ ამ წერილზე.</p>

    <hr style="border: 0; border-top: 1px solid #E2E8F0; margin: 28px 0 12px;">
    <p style="color: #94A3B8; font-size: 12px; margin: 0;">Roni5 — საკანცელარიო და საოფისე ნივთები</p>
</body>
</html>
