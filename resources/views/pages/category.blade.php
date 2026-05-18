<x-layouts.storefront>
    <x-slot:title>{{ $category->name_ka }}</x-slot:title>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-10 pb-6">
        <nav class="text-sm text-ink-muted mb-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
            <span class="mx-1.5 text-ink-faint">/</span>
            <span class="text-ink">{{ $category->name_ka }}</span>
        </nav>
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <h1 class="font-mt text-2xl sm:text-3xl font-bold tracking-tight text-ink">@mt($category->name_ka)</h1>
            <p class="text-sm text-ink-muted">{{ $products->total() }} პროდუქცია</p>
        </div>
        @if($category->description_ka)
            <p class="mt-2 max-w-3xl text-ink-muted">{{ $category->description_ka }}</p>
        @endif
    </section>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-12">
        @if($products->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($products as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @else
            <div class="text-center py-16 text-ink-muted">ამ კატეგორიაში პროდუქცია ჯერ არ არის.</div>
        @endif
    </section>
</x-layouts.storefront>
