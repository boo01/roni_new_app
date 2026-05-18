<x-layouts.storefront>
    <x-slot:title>შეკვეთა {{ $order->order_number }}</x-slot:title>
    @php $fmt = fn ($n) => number_format($n, 2, '.', ' '); @endphp

    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-10">
        <nav class="text-sm text-ink-muted mb-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
            <span class="mx-1.5 text-ink-faint">/</span>
            <a href="{{ route('account') }}" class="hover:text-ink">ჩემი ანგარიში</a>
            <span class="mx-1.5 text-ink-faint">/</span>
            <span class="text-ink">{{ $order->order_number }}</span>
        </nav>

        <div class="flex items-start justify-between flex-wrap gap-3 mb-8">
            <div>
                <p class="text-sm text-ink-muted">შეკვეთა</p>
                <h1 class="font-mt text-2xl font-bold text-ink font-mono">{{ $order->order_number }}</h1>{{-- order_number is Latin/digits, no @mt needed --}}
                <p class="text-xs text-ink-muted mt-1">{{ $order->created_at->format('d.m.Y H:i') }}</p>
            </div>
            <a href="{{ route('order.invoice', $order->order_number) }}" class="btn-outline text-sm" target="_blank" rel="noopener">ინვოისი (PDF)</a>
        </div>

        <div class="card divide-y divide-slate-100">
            @foreach($order->items as $item)
                <div class="p-4 flex items-baseline gap-4">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-ink">{{ $item->product_name_snapshot }}</p>
                        <p class="text-xs text-ink-faint font-mono">{{ $item->product_sku_snapshot }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-ink">
                            ₾{{ $fmt($item->unit_price_charged) }}
                            <span class="text-ink-faint">× {{ $item->quantity }}</span>
                        </p>
                        <p class="text-sm font-semibold text-ink mt-1">₾{{ $fmt($item->line_total) }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card p-5 mt-5">
            <dl class="space-y-2 text-sm">
                @if($order->discount_total > 0)
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">საცალო ჯამი</dt>
                        <dd class="text-ink-faint line-through">₾{{ $fmt($order->subtotal_retail) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">B2B ფასდაკლება</dt>
                        <dd class="text-deal">− ₾{{ $fmt($order->discount_total) }}</dd>
                    </div>
                @endif
                <div class="flex items-baseline justify-between border-t border-slate-100 pt-3 mt-3">
                    <dt class="text-sm font-medium text-ink">სულ</dt>
                    <dd class="text-xl font-bold text-ink">₾{{ $fmt($order->total) }}</dd>
                </div>
            </dl>
        </div>

        @if($order->notes)
            <div class="card p-5 mt-5">
                <p class="text-xs text-ink-muted mb-1">შენიშვნა</p>
                <p class="text-sm text-ink">{{ $order->notes }}</p>
            </div>
        @endif
    </section>
</x-layouts.storefront>
