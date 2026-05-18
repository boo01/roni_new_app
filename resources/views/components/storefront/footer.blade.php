@php
    $footerPages = \App\Models\Page::query()
        ->where('is_published', true)
        ->whereIn('slug', ['about', 'contact'])
        ->orderByRaw("CASE WHEN slug = 'about' THEN 0 ELSE 1 END")
        ->get();
@endphp
<footer class="mt-24 border-t border-slate-100">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-ink-muted">© {{ now()->year }} Roni5 — საკანცელარიო და საოფისე ნივთები</p>

            <nav class="flex items-center gap-1 text-sm" aria-label="Footer">
                @foreach($footerPages as $page)
                    <a href="{{ route($page->slug === 'about' ? 'page.about' : 'page.contact') }}"
                       class="px-3 py-1.5 rounded-md text-ink-soft hover:text-ink hover:bg-slate-50 transition">
                        {{ $page->title_ka }}
                    </a>
                @endforeach
            </nav>

            <p class="text-xs text-ink-faint">ფასი მითითებულია ლარში (₾)</p>
        </div>
    </div>
</footer>
