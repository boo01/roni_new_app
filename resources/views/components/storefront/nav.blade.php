@php
    $categories = \App\Models\Category::query()
        ->where('is_active', true)
        ->whereNull('parent_id')
        ->orderBy('sort_order')
        ->orderBy('name_ka')
        ->get();
@endphp
<header class="sticky top-0 z-30 bg-white/85 backdrop-blur border-b border-slate-100">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <span class="text-xl font-bold tracking-tight text-ink">Roni<span class="text-deal">5</span></span>
            </a>

            <nav class="hidden md:flex items-center gap-1 flex-1 mx-6 overflow-x-auto" aria-label="Categories">
                @foreach($categories as $category)
                    <a href="{{ route('category.show', $category->slug) }}"
                       class="px-3 py-2 rounded-md text-sm font-medium text-ink-soft hover:text-ink hover:bg-slate-50 transition whitespace-nowrap {{ request()->routeIs('category.show') && request()->route('slug') === $category->slug ? 'text-ink bg-slate-50' : '' }}">
                        {{ $category->name_ka }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <span class="hidden sm:inline text-sm text-ink-muted">{{ auth()->user()->name }}</span>
                    @if(auth()->user()->isB2B())
                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-deal-soft text-deal">B2B</span>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button class="btn-ghost text-sm">გასვლა</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-outline text-sm">შესვლა</a>
                @endauth
            </div>
        </div>

        <nav class="md:hidden -mx-4 px-4 pb-3 flex items-center gap-1 overflow-x-auto" aria-label="Categories mobile">
            @foreach($categories as $category)
                <a href="{{ route('category.show', $category->slug) }}"
                   class="px-3 py-1.5 rounded-md text-sm font-medium text-ink-soft hover:text-ink hover:bg-slate-50 transition whitespace-nowrap shrink-0">
                    {{ $category->name_ka }}
                </a>
            @endforeach
        </nav>
    </div>
</header>
