<x-layouts.storefront>
    <x-slot:title>{{ $product->name_ka }}</x-slot:title>

    @php
        $images = $product->getMedia('images');
        $price = $product->priceFor(auth()->user());
        $fmt = fn ($n) => number_format($n, 2, '.', ' ');
    @endphp

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-10">
        @php $primaryCat = $product->primaryCategory(); @endphp
        <nav class="text-sm text-ink-muted mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
            @if($primaryCat)
                <span class="mx-1.5 text-ink-faint">/</span>
                <a href="{{ route('category.show', $primaryCat->slug) }}" class="hover:text-ink">{{ $primaryCat->name_ka }}</a>
            @endif
            <span class="mx-1.5 text-ink-faint">/</span>
            <span class="text-ink">{{ $product->name_ka }}</span>
        </nav>
    </section>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">
            <div>
                @if($images->isNotEmpty())
                    @php
                        $gallery = $images->map(function ($i) {
                            $dims = @getimagesize($i->getPath()) ?: [1200, 1200];
                            return [
                                'full' => $i->getUrl(),
                                'card' => $i->getUrl('card'),
                                'thumb' => $i->getUrl('thumb'),
                                'width' => $dims[0],
                                'height' => $dims[1],
                            ];
                        })->values()->all();
                    @endphp
                    <div class="pswp-gallery" x-data="{ active: 0 }">
                        <div class="relative aspect-square card overflow-hidden bg-slate-50 cursor-zoom-in"
                             @click="$root.querySelectorAll('a.pswp-link')[active].click()"
                             role="button"
                             aria-label="ფოტოს გადიდება">
                            @foreach($gallery as $i => $img)
                                <img src="{{ $img['card'] }}"
                                     alt="{{ $product->name_ka }}"
                                     x-show="active === {{ $i }}"
                                     class="absolute inset-0 h-full w-full object-cover transition-opacity duration-150">
                            @endforeach
                            <div class="absolute bottom-2 right-2 size-9 rounded-full bg-white/85 text-ink flex items-center justify-center shadow-card backdrop-blur-sm pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                                </svg>
                            </div>
                        </div>

                        @foreach($gallery as $img)
                            <a class="pswp-link"
                               href="{{ $img['full'] }}"
                               data-pswp-width="{{ $img['width'] }}"
                               data-pswp-height="{{ $img['height'] }}"
                               hidden></a>
                        @endforeach

                        @if(count($gallery) > 1)
                            <div class="mt-3 grid grid-cols-6 gap-2">
                                @foreach($gallery as $i => $img)
                                    <button type="button" @click.stop="active = {{ $i }}"
                                            :class="active === {{ $i }} ? 'ring-2 ring-ink ring-offset-1' : 'border border-slate-200 hover:border-slate-300'"
                                            class="aspect-square rounded-lg overflow-hidden bg-slate-50 cursor-pointer transition"
                                            aria-label="ფოტო {{ $i + 1 }}">
                                        <img src="{{ $img['thumb'] }}" alt="" class="h-full w-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="aspect-square card overflow-hidden bg-slate-50 flex items-center justify-center text-ink-faint">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-16">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                        </svg>
                    </div>
                @endif
            </div>

            <div>
                <p class="text-sm font-mono text-ink-faint mb-2">{{ $product->sku }}</p>
                <h1 class="font-mt text-2xl sm:text-3xl font-bold tracking-tight text-ink">@mt($product->name_ka)</h1>

                <div class="mt-6 flex items-baseline gap-3">
                    @if($price['has_discount'])
                        <span class="text-xl text-ink-faint line-through">₾{{ $fmt($price['retail']) }}</span>
                        <span class="text-3xl font-bold text-deal">₾{{ $fmt($price['charged']) }}</span>
                        <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-deal-soft text-deal">
                            B2B ფასი
                        </span>
                    @else
                        <span class="text-3xl font-bold text-ink">₾{{ $fmt($price['charged']) }}</span>
                    @endif
                </div>

                @auth
                    @if(!auth()->user()->isB2B())
                        <p class="mt-2 text-xs text-ink-muted">კერძო პირის ფასი</p>
                    @endif
                @else
                    <p class="mt-2 text-xs text-ink-muted">კერძო პირის ფასი. <a href="{{ route('login') }}" class="underline hover:text-ink">შესვლა</a> კომპანიის ფასისთვის.</p>
                @endauth

                @if($product->description_ka)
                    <div class="mt-6 prose prose-slate prose-sm max-w-none text-ink-soft">
                        {!! nl2br(e($product->description_ka)) !!}
                    </div>
                @endif

                <form method="POST" action="{{ route('cart.add', $product) }}" class="mt-8 flex flex-col sm:flex-row gap-3">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn-primary flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272" />
                        </svg>
                        კალათაში დამატება
                    </button>
                    <a href="{{ route('cart.show') }}" class="btn-outline">კალათის ნახვა</a>
                </form>

                <dl class="mt-10 border-t border-slate-100 pt-6 space-y-2 text-sm">
                    @if($product->categories->isNotEmpty())
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-muted">{{ $product->categories->count() > 1 ? 'კატეგორიები' : 'კატეგორია' }}</dt>
                            <dd class="text-ink text-right">
                                @foreach($product->categories as $c)
                                    <a href="{{ route('category.show', $c->slug) }}" class="hover:underline">{{ $c->name_ka }}</a>@if(!$loop->last), @endif
                                @endforeach
                            </dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">კოდი</dt>
                        <dd class="text-ink font-mono">{{ $product->sku }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>
</x-layouts.storefront>
