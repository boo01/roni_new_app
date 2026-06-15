@php
    $order = $getRecord();
    $items = $order->items()->with('product.media')->get();
@endphp

<div style="display:flex; flex-direction:column; gap:0.75rem;">
    @forelse($items as $item)
        @php
            $images = $item->product
                ? $item->product->getMedia('images')->map(fn ($m) => $m->getUrl('card'))->values()->all()
                : [];
            $count = count($images);
        @endphp
        <div x-data="{ active: 0, count: {{ $count }}, hover: false }"
             style="display:flex; gap:1rem; padding:0.875rem; border:1px solid rgba(148,163,184,0.25); border-radius:0.75rem; align-items:flex-start;">

            {{-- Image with left/right arrows that appear on hover (only when there is more than one) --}}
            <div style="flex-shrink:0; width:104px;">
                @if($count)
                    <div @mouseenter="hover = true" @mouseleave="hover = false"
                         style="position:relative; width:104px; height:104px; border-radius:0.5rem; overflow:hidden; background:rgba(148,163,184,0.1);">
                        @foreach($images as $i => $url)
                            <img src="{{ $url }}" x-show="active === {{ $i }}" alt=""
                                 style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;">
                        @endforeach

                        @if($count > 1)
                            <button type="button" @click="active = (active - 1 + count) % count"
                                    x-show="hover" x-transition.opacity.duration.150ms
                                    title="{{ __('Previous image') }}"
                                    style="position:absolute; top:40px; left:4px; width:24px; height:24px; display:flex; align-items:center; justify-content:center; border:none; border-radius:999px; background:rgba(0,0,0,0.55); color:#fff; cursor:pointer; padding:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px; height:14px; display:block;"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" /></svg>
                            </button>
                            <button type="button" @click="active = (active + 1) % count"
                                    x-show="hover" x-transition.opacity.duration.150ms
                                    title="{{ __('Next image') }}"
                                    style="position:absolute; top:40px; right:4px; width:24px; height:24px; display:flex; align-items:center; justify-content:center; border:none; border-radius:999px; background:rgba(0,0,0,0.55); color:#fff; cursor:pointer; padding:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px; height:14px; display:block;"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>
                            </button>
                            <span style="position:absolute; bottom:4px; left:50%; transform:translateX(-50%); padding:1px 7px; border-radius:999px; background:rgba(0,0,0,0.6); color:#fff; font-size:10px; line-height:1.4;"
                                  x-text="(active + 1) + ' / ' + count"></span>
                        @endif
                    </div>
                @else
                    <div style="width:104px; height:104px; border-radius:0.5rem; background:rgba(148,163,184,0.12); display:flex; align-items:center; justify-content:center; opacity:0.55; font-size:11px; text-align:center;">
                        {{ __('No image') }}
                    </div>
                @endif
            </div>

            {{-- Name, SKU, chosen options --}}
            <div style="flex:1; min-width:0;">
                <div style="font-weight:600; font-size:0.95rem;">{{ $item->product_name_snapshot }}</div>
                <div style="font-family:ui-monospace,monospace; font-size:12px; opacity:0.6; margin-top:2px;">{{ $item->product_sku_snapshot }}</div>

                @if(!empty($item->options_snapshot))
                    <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px;">
                        @foreach($item->options_snapshot as $opt)
                            <span style="font-size:12px; padding:3px 9px; border-radius:999px; background:rgba(245,158,11,0.16); border:1px solid rgba(245,158,11,0.35);">
                                {{ $opt['attribute_name'] ?? '' }}: <strong>{{ $opt['value_name'] ?? '' }}</strong>
                            </span>
                        @endforeach
                    </div>
                @endif

                @if($item->product)
                    <a href="{{ \App\Filament\Resources\ProductResource::getUrl('edit', ['record' => $item->product_id]) }}"
                       target="_blank"
                       style="display:inline-block; margin-top:8px; font-size:12px; color:rgb(217 119 6); text-decoration:none;">
                        {{ __('Open product') }} →
                    </a>
                @else
                    <div style="margin-top:8px; font-size:12px; opacity:0.5;">{{ __('Product no longer in catalog') }}</div>
                @endif
            </div>

            {{-- Quantity + price --}}
            <div style="text-align:right; flex-shrink:0; white-space:nowrap;">
                <div style="font-size:13px; opacity:0.7;">{{ __('Qty') }}: <strong style="font-size:1.05rem;">{{ $item->quantity }}</strong></div>
                @if($item->unit_price_charged < $item->unit_price_retail)
                    <div style="font-size:12px; opacity:0.5; text-decoration:line-through; margin-top:4px;">₾{{ number_format($item->unit_price_retail, 2) }}</div>
                    <div style="font-size:13px; color:rgb(5 150 105);">₾{{ number_format($item->unit_price_charged, 2) }} / {{ __('unit') }}</div>
                @else
                    <div style="font-size:13px; opacity:0.7; margin-top:4px;">₾{{ number_format($item->unit_price_charged, 2) }} / {{ __('unit') }}</div>
                @endif
                <div style="font-weight:700; margin-top:6px; font-size:1.05rem;">₾{{ number_format($item->line_total, 2) }}</div>
            </div>
        </div>
    @empty
        <div style="opacity:0.6;">{{ __('No products.') }}</div>
    @endforelse
</div>
