@props(['product'])
@php
    $thumb = $product->getFirstMediaUrl('images', 'thumb');
    $requiresOptions = $product->hasRequiredOptions();
@endphp
<div class="card-hoverable group relative overflow-hidden">
    <a href="{{ route('product.show', $product->slug) }}" class="block aspect-square bg-slate-50 overflow-hidden" aria-label="{{ $product->name_ka }}">
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
    </a>

    <div class="p-4">
        <a href="{{ route('product.show', $product->slug) }}" class="block">
            <h3 class="text-sm font-medium text-ink line-clamp-2 min-h-[2.5rem]">{{ $product->name_ka }}</h3>
            <p class="mt-1 text-xs text-ink-faint font-mono">{{ $product->sku }}</p>
        </a>

        {{-- Price on the left, add-to-cart on the right --}}
        <div class="mt-2 flex items-center justify-between gap-2">
            <div class="min-w-0">
                <x-storefront.price-block :product="$product" />
            </div>

            @if($requiresOptions)
                <a href="{{ route('product.show', $product->slug) }}"
                   title="აირჩიე ვარიანტი"
                   aria-label="აირჩიე ვარიანტი"
                   class="shrink-0 size-9 flex items-center justify-center rounded-full border border-slate-300 text-ink-soft hover:border-ink hover:text-ink transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                </a>
            @else
                <form method="POST" action="{{ route('cart.add', $product) }}" data-cart-add class="shrink-0">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit"
                            title="კალათაში დამატება"
                            aria-label="კალათაში დამატება"
                            class="size-9 flex items-center justify-center rounded-full bg-ink text-white hover:bg-ink-soft transition cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272" />
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
