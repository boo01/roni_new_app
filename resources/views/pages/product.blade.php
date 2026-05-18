<x-layouts.storefront>
    <x-slot:title>{{ $product->name_ka }}</x-slot:title>

    @php
        $images = $product->getMedia('images');
        $price = $product->priceFor(auth()->user());
        $fmt = fn ($n) => number_format($n, 2, '.', ' ');
    @endphp

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-10">
        <nav class="text-sm text-ink-muted mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
            @if($product->category)
                <span class="mx-1.5 text-ink-faint">/</span>
                <a href="{{ route('category.show', $product->category->slug) }}" class="hover:text-ink">{{ $product->category->name_ka }}</a>
            @endif
            <span class="mx-1.5 text-ink-faint">/</span>
            <span class="text-ink">{{ $product->name_ka }}</span>
        </nav>
    </section>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">
            <div>
                <div class="aspect-square card overflow-hidden bg-slate-50">
                    @if($images->isNotEmpty())
                        <img src="{{ $images->first()->getUrl('card') }}"
                             alt="{{ $product->name_ka }}"
                             class="h-full w-full object-cover">
                    @else
                        <div class="h-full w-full flex items-center justify-center text-ink-faint">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-16">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                            </svg>
                        </div>
                    @endif
                </div>
                @if($images->count() > 1)
                    <div class="mt-3 grid grid-cols-6 gap-2">
                        @foreach($images as $img)
                            <div class="aspect-square rounded-lg overflow-hidden border border-slate-200 bg-slate-50">
                                <img src="{{ $img->getUrl('thumb') }}" alt="" class="h-full w-full object-cover">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <p class="text-sm font-mono text-ink-faint mb-2">{{ $product->sku }}</p>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">{{ $product->name_ka }}</h1>

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

                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <button type="button" class="btn-primary flex-1 cursor-not-allowed opacity-60" disabled title="კალათა იხსნება მალე">
                        კალათაში დამატება
                    </button>
                </div>

                <dl class="mt-10 border-t border-slate-100 pt-6 space-y-2 text-sm">
                    @if($product->category)
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">კატეგორია</dt>
                            <dd class="text-ink">{{ $product->category->name_ka }}</dd>
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
