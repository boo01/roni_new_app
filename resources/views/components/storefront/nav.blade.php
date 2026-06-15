@php
    $audience = \App\Support\Audience::current();
    $menu = \App\Support\Menu::header($audience);
    $cartCount = app(\App\Services\Cart::class)->totalQuantity();
    $settings = \App\Models\SiteSetting::current();
@endphp
<header class="sticky top-0 z-30 bg-white/85 backdrop-blur border-b border-slate-100">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-3 sm:gap-6">
            @php $brand = $settings->meta_title ?: 'Roni5'; @endphp
            <a href="{{ route('home') }}" class="flex items-center gap-2 group shrink-0" aria-label="{{ $brand }}">
                @if($settings->logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->logo) }}"
                         alt="{{ $brand }}" class="h-8 w-auto max-w-[150px] object-contain">
                @else
                    <span class="text-xl font-bold tracking-tight text-ink">Roni<span class="text-deal">5</span></span>
                @endif
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

                <a href="{{ route('cart.show') }}" class="relative btn-ghost p-2.5" aria-label="კალათა"
                   x-data="{ count: {{ $cartCount }} }"
                   @cart-updated.window="count = $event.detail.count">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    <span x-show="count > 0"
                          style="{{ $cartCount > 0 ? '' : 'display:none' }}"
                          class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-ink text-white text-[10px] font-semibold flex items-center justify-center"
                          x-text="count">{{ $cartCount }}</span>
                </a>
            </div>
        </div>

        @if($menu->isNotEmpty())
            <nav class="-mx-4 px-4 pb-3 pt-1 hidden md:flex items-center gap-1" aria-label="Menu">
                @foreach($menu as $node)
                    @php $active = url()->current() === $node['url']; @endphp
                    <div class="relative shrink-0" x-data="{ open: false }"
                         @mouseenter="open = true" @mouseleave="open = false">
                        <a href="{{ $node['url'] }}" @if($node['target']) target="{{ $node['target'] }}" rel="noopener" @endif
                           @class([
                               'flex items-center gap-1 px-3 py-1.5 rounded-md text-sm font-medium transition whitespace-nowrap',
                               'text-ink bg-slate-50' => $active,
                               'text-ink-soft hover:text-ink hover:bg-slate-50' => ! $active,
                           ])>
                            {{ $node['label'] }}
                            @if(!empty($node['children']))
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5 text-ink-faint">
                                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </a>

                        @if(!empty($node['children']))
                            <div x-show="open" x-cloak x-transition.opacity.duration.150ms
                                 class="absolute left-0 top-full z-40 pt-1 w-56">
                                <div class="rounded-xl border border-slate-200 bg-white shadow-card-hover p-2 max-h-[70vh] overflow-y-auto">
                                    @foreach($node['children'] as $child)
                                        <a href="{{ $child['url'] }}" @if($child['target']) target="{{ $child['target'] }}" rel="noopener" @endif
                                           class="block rounded-md px-3 py-1.5 text-sm text-ink-soft hover:bg-slate-50 hover:text-ink transition">
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </nav>

            {{-- Mobile: flat scrollable list of top-level items --}}
            <nav class="-mx-4 px-4 pb-3 pt-1 flex md:hidden items-center gap-1 overflow-x-auto" aria-label="Menu">
                @foreach($menu as $node)
                    <a href="{{ $node['url'] }}" @if($node['target']) target="{{ $node['target'] }}" rel="noopener" @endif
                       class="px-3 py-1.5 rounded-md text-sm font-medium text-ink-soft hover:text-ink hover:bg-slate-50 transition whitespace-nowrap shrink-0">
                        {{ $node['label'] }}
                    </a>
                @endforeach
            </nav>
        @endif
    </div>
</header>

@if(session('status'))
    <div class="bg-deal-soft text-deal text-sm text-center py-2 px-4" role="status">{{ session('status') }}</div>
@endif
