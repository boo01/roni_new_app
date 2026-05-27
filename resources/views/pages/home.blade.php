<x-layouts.storefront>
    <x-slot:title>მთავარი</x-slot:title>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-12 pb-8">
        <h1 class="font-mt text-3xl sm:text-4xl font-bold tracking-tight text-ink">@mt('საკანცელარიო და საოფისე ნივთები')</h1>
        <p class="mt-3 max-w-2xl text-ink-muted">აღმოაჩინე საუკეთესო პროდუქცია ერთიანი ფასით კერძო პირებისთვის და სპეციალური ფასით კომპანიებისთვის.</p>
    </section>

    @if($categories->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-8">
            <h2 class="text-lg font-semibold text-ink mb-4">კატეგორიები</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 sm:gap-3">
                @foreach($categories as $category)
                    <a href="{{ route('category.show', $category->slug) }}"
                       class="card-hoverable px-3 py-4 text-center text-sm font-medium text-ink-soft hover:text-ink">
                        {{ $category->name_ka }}
                        <span class="block mt-1 text-xs text-ink-faint font-normal">{{ $category->total_products }}</span>
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
