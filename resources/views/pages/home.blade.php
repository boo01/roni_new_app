<x-layouts.storefront>
    <x-slot:title>მთავარი</x-slot:title>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-12 pb-8">
        <h1 class="font-mt text-3xl sm:text-4xl font-bold tracking-tight text-ink">@mt('საკანცელარიო და საოფისე ნივთები')</h1>
        <p class="mt-3 max-w-2xl text-ink-muted">აღმოაჩინე საუკეთესო პროდუქცია ერთიანი ფასით კერძო პირებისთვის და სპეციალური ფასით კომპანიებისთვის.</p>
    </section>

    @if($categories->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-8"
                 x-data="{ scrollBy(dir) { $refs.track.scrollBy({ left: dir * Math.round($refs.track.clientWidth * 0.8), behavior: 'smooth' }) } }">
            <div class="flex items-end justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-ink">კატეგორიები</h2>
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('categories') }}" class="text-sm font-medium text-ink-muted hover:text-ink mr-1">ყველას ნახვა →</a>
                    <button type="button" @click="scrollBy(-1)" aria-label="წინა"
                            class="hidden sm:flex size-8 items-center justify-center rounded-full border border-slate-200 text-ink-soft hover:bg-slate-50 transition cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" /></svg>
                    </button>
                    <button type="button" @click="scrollBy(1)" aria-label="შემდეგი"
                            class="hidden sm:flex size-8 items-center justify-center rounded-full border border-slate-200 text-ink-soft hover:bg-slate-50 transition cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
            </div>
            <div x-ref="track"
                 class="flex gap-3 sm:gap-4 overflow-x-auto snap-x snap-mandatory pb-2 -mx-1 px-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach($categories as $category)
                    <a href="{{ route('category.show', $category->slug) }}"
                       class="snap-start shrink-0 w-36 sm:w-44 card-hoverable overflow-hidden">
                        <div class="aspect-[4/3] bg-slate-50 overflow-hidden">
                            @if($category->image_url)
                                <img src="{{ $category->image_url }}" alt="{{ $category->name_ka }}" loading="lazy"
                                     class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full flex items-center justify-center text-ink-faint">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="p-3">
                            <p class="text-sm font-medium text-ink-soft line-clamp-1">{{ $category->name_ka }}</p>
                            <p class="mt-0.5 text-xs text-ink-faint">{{ $category->total_products }} პროდუქცია</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($latestProducts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-end justify-between mb-4">
                <h2 class="text-lg font-semibold text-ink">ახალი პროდუქცია</h2>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($latestProducts as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @else
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 text-center">
            <p class="text-ink-muted">პროდუქცია ჯერ არ არის დამატებული.</p>
        </section>
    @endif
</x-layouts.storefront>
