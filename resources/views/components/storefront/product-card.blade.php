@props(['product'])
@php
    $thumb = $product->getFirstMediaUrl('images', 'thumb');
@endphp
<a href="{{ route('product.show', $product->slug) }}"
   class="card-hoverable group block overflow-hidden">
    <div class="aspect-square bg-slate-50 overflow-hidden">
        @if($thumb)
            <img src="{{ $thumb }}"
                 alt="{{ $product->name_ka }}"
                 loading="lazy"
                 class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
        @else
            <div class="h-full w-full flex items-center justify-center text-ink-faint">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-12">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
        @endif
    </div>
    <div class="p-4">
        <h3 class="text-sm font-medium text-ink line-clamp-2 min-h-[2.5rem]">{{ $product->name_ka }}</h3>
        <p class="mt-1 text-xs text-ink-faint font-mono">{{ $product->sku }}</p>
        <div class="mt-2">
            <x-storefront.price-block :product="$product" />
        </div>
    </div>
</a>
