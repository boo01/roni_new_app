@props(['product'])
@php
    $price = $product->priceFor(auth()->user());
    $fmt = fn ($n) => number_format($n, 2, '.', ' ');
@endphp
<div class="flex items-baseline gap-2">
    @if($price['has_discount'])
        <span class="text-base text-ink-faint line-through">₾{{ $fmt($price['retail']) }}</span>
        <span class="text-lg font-semibold text-deal">₾{{ $fmt($price['charged']) }}</span>
    @else
        <span class="text-lg font-semibold text-ink">₾{{ $fmt($price['charged']) }}</span>
    @endif
</div>
