<div class="relative w-full max-w-md"
     x-data="{ focused: false }"
     @click.outside="focused = false; $wire.close()">
    <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
             class="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-faint">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input type="search"
               wire:model.live.debounce.250ms="query"
               @focus="focused = true; $wire.set('open', $wire.query.length >= 3)"
               placeholder="ძებნა (კოდით ან სახელით)"
               class="input pl-9 pr-3 py-2 text-sm w-full"
               aria-label="ძებნა">
    </div>

    @if($open && mb_strlen(trim($query), 'UTF-8') >= 3)
        <div class="absolute z-40 mt-1 w-full card divide-y divide-slate-100 max-h-96 overflow-y-auto">
            @forelse($results as $product)
                @php
                    $thumb = $product->getFirstMediaUrl('images', 'thumb');
                    $price = $product->priceFor(auth()->user());
                    $fmt = fn ($n) => number_format($n, 2, '.', ' ');
                @endphp
                <a href="{{ route('product.show', $product->slug) }}"
                   class="flex items-center gap-3 p-3 hover:bg-slate-50 transition cursor-pointer">
                    <div class="size-10 rounded-lg overflow-hidden bg-slate-50 shrink-0">
                        @if($thumb)
                            <img src="{{ $thumb }}" alt="" class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full flex items-center justify-center text-ink-faint">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-ink truncate">{{ $product->name_ka }}</p>
                        <p class="text-xs font-mono text-ink-faint">{{ $product->sku }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        @if($price['has_discount'])
                            <p class="text-xs text-ink-faint line-through">₾{{ $fmt($price['retail']) }}</p>
                            <p class="text-sm font-medium text-deal">₾{{ $fmt($price['charged']) }}</p>
                        @else
                            <p class="text-sm font-medium text-ink">₾{{ $fmt($price['charged']) }}</p>
                        @endif
                    </div>
                </a>
            @empty
                <div class="p-4 text-sm text-ink-muted text-center">პროდუქცია ვერ მოიძებნა</div>
            @endforelse
        </div>
    @endif
</div>
