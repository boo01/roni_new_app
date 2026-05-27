@php
    $audience = \App\Support\Audience::current();
    $headerCategories = \App\Models\Category::query()
        ->visibleTo($audience)
        ->where('show_in_header', true)
        ->with(['children' => fn ($q) => $q->visibleTo($audience)->orderBy('sort_order')->orderBy('name_ka')])
        ->orderBy('header_sort_order')
        ->orderBy('name_ka')
        ->get();
    $cartCount = app(\App\Services\Cart::class)->totalQuantity();
@endphp
<header class="sticky top-0 z-30 bg-white/85 backdrop-blur border-b border-slate-100">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-3 sm:gap-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 group shrink-0">
                <span class="text-xl font-bold tracking-tight text-ink">Roni<span class="text-deal">5</span></span>
            </a>

            <div class="flex-1 min-w-0">
                <livewire:search-box />
            </div>

            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                @auth
                    <a href="{{ route('account') }}" class="hidden sm:inline text-sm text-ink-muted hover:text-ink">{{ auth()->user()->name }}</a>
                    @if(auth()->user()->isB2B())
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-deal-soft text-deal">B2B</span>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button class="btn-ghost text-sm hidden sm:inline-flex">გასვლა</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-outline text-sm hidden sm:inline-flex">შესვლა</a>
                @endauth

                <a href="{{ route('cart.show') }}" class="relative btn-ghost p-2.5" aria-label="კალათა">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    @if($cartCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-ink text-white text-[10px] font-semibold flex items-center justify-center">{{ $cartCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        @if($headerCategories->isNotEmpty())
            <nav class="-mx-4 px-4 pb-3 pt-1 hidden md:flex items-center gap-1" aria-label="Categories">
                @foreach($headerCategories as $category)
                    <div class="relative shrink-0" x-data="{ open: false }"
                         @mouseenter="open = true" @mouseleave="open = false">
                        <a href="{{ route('category.show', $category->slug) }}"
                           @class([
                               'flex items-center gap-1 px-3 py-1.5 rounded-md text-sm font-medium transition whitespace-nowrap',
                               'text-ink bg-slate-50' => request()->routeIs('category.show') && request()->route('slug') === $category->slug,
                               'text-ink-soft hover:text-ink hover:bg-slate-50' => ! (request()->routeIs('category.show') && request()->route('slug') === $category->slug),
                           ])>
                            {{ $category->name_ka }}
                            @if($category->children->isNotEmpty())
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5 text-ink-faint">
                                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </a>

                        @if($category->children->isNotEmpty())
                            <div x-show="open" x-cloak x-transition.opacity.duration.150ms
                                 class="absolute left-0 top-full z-40 pt-1 w-56">
                                <div class="rounded-xl border border-slate-200 bg-white shadow-card-hover p-2 max-h-[70vh] overflow-y-auto">
                                    @foreach($category->children as $child)
                                        <a href="{{ route('category.show', $child->slug) }}"
                                           class="block rounded-md px-3 py-1.5 text-sm text-ink-soft hover:bg-slate-50 hover:text-ink transition">
                                            {{ $child->name_ka }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </nav>

            {{-- Mobile: flat scrollable list of top-level categories --}}
            <nav class="-mx-4 px-4 pb-3 pt-1 flex md:hidden items-center gap-1 overflow-x-auto" aria-label="Categories">
                @foreach($headerCategories as $category)
                    <a href="{{ route('category.show', $category->slug) }}"
                       class="px-3 py-1.5 rounded-md text-sm font-medium text-ink-soft hover:text-ink hover:bg-slate-50 transition whitespace-nowrap shrink-0">
                        {{ $category->name_ka }}
                    </a>
                @endforeach
            </nav>
        @endif
    </div>
</header>

@if(session('status'))
    <div class="bg-deal-soft text-deal text-sm text-center py-2 px-4" role="status">{{ session('status') }}</div>
@endif
