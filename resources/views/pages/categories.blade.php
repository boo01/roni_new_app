<x-layouts.storefront>
    <x-slot:title>კატეგორიები</x-slot:title>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-10 pb-6">
        <nav class="text-sm text-ink-muted mb-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
            <span class="mx-1.5 text-ink-faint">/</span>
            <span class="text-ink">კატეგორიები</span>
        </nav>
        <h1 class="font-mt text-2xl sm:text-3xl font-bold tracking-tight text-ink">@mt('ყველა კატეგორია')</h1>
    </section>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-16">
        @if($roots->isEmpty())
            <div class="card p-10 text-center text-ink-muted">
                <p>კატეგორიები ჯერ არ არის.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($roots as $root)
                    <div class="card p-5">
                        <a href="{{ route('category.show', $root->slug) }}"
                           class="block text-base font-semibold text-ink hover:text-deal transition">
                            {{ $root->name_ka }}
                        </a>
                        @if($root->children->isNotEmpty())
                            <ul class="mt-3 space-y-1.5">
                                @foreach($root->children as $child)
                                    <li>
                                        <a href="{{ route('category.show', $child->slug) }}"
                                           class="flex items-center justify-between gap-2 text-sm text-ink-soft hover:text-ink transition">
                                            <span class="line-clamp-1">{{ $child->name_ka }}</span>
                                            <span class="text-xs text-ink-faint shrink-0">{{ $child->products_count }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.storefront>
