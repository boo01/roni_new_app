<x-layouts.storefront>
    <x-slot:title>მთავარი</x-slot:title>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-12 pb-8">
        <h1 class="font-mt text-3xl sm:text-4xl font-bold tracking-tight text-ink">@mt('საკანცელარიო და საოფისე ნივთები')</h1>
        <p class="mt-3 max-w-2xl text-ink-muted">აღმოაჩინე საუკეთესო პროდუქცია ერთიანი ფასით კერძო პირებისთვის და სპეციალური ფასით კომპანიებისთვის.</p>
    </section>

    @if($categories->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-8">
            <h2 class="text-lg font-semibold text-ink mb-4">კატეგორიები</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($categories as $category)
                    <div class="card p-4">
                        <a href="{{ route('category.show', $category->slug) }}"
                           class="font-mt text-base font-semibold text-ink hover:text-deal transition">
                            {{ $category->name_ka }}
                        </a>
                        @if($category->children->isNotEmpty())
                            <ul class="mt-2 space-y-0.5">
                                @foreach($category->children->take(6) as $child)
                                    <li>
                                        <a href="{{ route('category.show', $child->slug) }}"
                                           class="block text-sm text-ink-muted hover:text-ink transition py-0.5">
                                            {{ $child->name_ka }}
                                        </a>
                                    </li>
                                @endforeach
                                @if($category->children->count() > 6)
                                    <li>
                                        <a href="{{ route('category.show', $category->slug) }}"
                                           class="block text-sm text-deal hover:underline py-0.5">
                                            ყველა ({{ $category->children->count() }}) →
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </div>
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
