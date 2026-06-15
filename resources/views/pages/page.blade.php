<x-layouts.storefront>
    <x-slot:title>{{ $page->title_ka }}</x-slot:title>

    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
        <nav class="text-sm text-ink-muted mb-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
            <span class="mx-1.5 text-ink-faint">/</span>
            <span class="text-ink">{{ $page->title_ka }}</span>
        </nav>

        <h1 class="font-mt text-2xl sm:text-3xl font-bold tracking-tight text-ink mb-6">@mt($page->title_ka)</h1>

        @if($page->body_ka)
            <div class="prose prose-slate max-w-none text-ink-soft leading-relaxed">{!! $page->body_ka !!}</div>
        @endif
    </section>
</x-layouts.storefront>
