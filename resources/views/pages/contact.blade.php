<x-layouts.storefront>
    <x-slot:title>{{ $page->title_ka }}</x-slot:title>

    @php
        $locations = collect($page->contact_locations ?? [])->filter(fn ($l) => ! empty($l['name'] ?? null));
    @endphp

    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-12">
        <nav class="text-sm text-ink-muted mb-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
            <span class="mx-1.5 text-ink-faint">/</span>
            <span class="text-ink">{{ $page->title_ka }}</span>
        </nav>

        <h1 class="font-mt text-2xl sm:text-3xl font-bold tracking-tight text-ink mb-2">@mt($page->title_ka)</h1>

        @if($page->body_ka)
            <p class="text-ink-muted leading-relaxed mb-8 whitespace-pre-line">{{ $page->body_ka }}</p>
        @endif

        @if($page->contact_phone || $page->contact_email)
            <div class="card p-5 mb-8 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                @if($page->contact_phone)
                    <div>
                        <p class="text-xs text-ink-muted uppercase tracking-wide mb-1">ტელეფონი</p>
                        <a href="tel:{{ $page->contact_phone }}" class="text-ink hover:underline">{{ $page->contact_phone }}</a>
                    </div>
                @endif
                @if($page->contact_email)
                    <div>
                        <p class="text-xs text-ink-muted uppercase tracking-wide mb-1">ელ. ფოსტა</p>
                        <a href="mailto:{{ $page->contact_email }}" class="text-ink hover:underline">{{ $page->contact_email }}</a>
                    </div>
                @endif
            </div>
        @endif

        @if($locations->isNotEmpty())
            <h2 class="text-lg font-semibold text-ink mb-4">ფილიალები</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($locations as $location)
                    <article class="card overflow-hidden">
                        @if(! empty($location['embed_url']))
                            <div class="aspect-[16/10] bg-slate-100">
                                <iframe src="{{ $location['embed_url'] }}"
                                        class="h-full w-full"
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        title="{{ $location['name'] }} რუკაზე"
                                        allowfullscreen></iframe>
                            </div>
                        @endif
                        <div class="p-5 space-y-2 text-sm">
                            <h3 class="font-semibold text-ink">{{ $location['name'] }}</h3>
                            @if(! empty($location['address']))
                                <p class="text-ink-soft">{{ $location['address'] }}</p>
                            @endif
                            @if(! empty($location['phone']))
                                <p>
                                    <a href="tel:{{ $location['phone'] }}" class="text-ink hover:underline">{{ $location['phone'] }}</a>
                                </p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.storefront>
