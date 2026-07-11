<x-layouts.storefront>
    <x-slot:title>{{ $category->name_ka }}</x-slot:title>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-10 pb-6">
        <nav class="text-sm text-ink-muted mb-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
            @foreach($ancestors as $ancestor)
                <span class="mx-1.5 text-ink-faint">/</span>
                <a href="{{ route('category.show', $ancestor->slug) }}" class="hover:text-ink">{{ $ancestor->name_ka }}</a>
            @endforeach
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

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-12" x-data="{ filtersOpen: false }">

        <button type="button" @click="filtersOpen = !filtersOpen"
                class="btn-outline w-full mb-4 justify-between lg:hidden">
            <span class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                </svg>
                კატეგორიები და ფილტრი
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                 class="size-4 transition-transform" :class="filtersOpen && 'rotate-180'">
                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">

            <aside class="hidden lg:block lg:col-span-1 space-y-5"
                   x-bind:class="{ '!block': filtersOpen }">

                @if($navCategories->isNotEmpty())
                    <div class="card p-4">
                        <h3 class="text-sm font-semibold text-ink mb-3">
                            {{ $navParent->is($category) ? 'ქვეკატეგორიები' : $navParent->name_ka }}
                        </h3>
                        <ul class="space-y-1">
                            @unless($navParent->is($category))
                                <li>
                                    <a href="{{ route('category.show', $navParent->slug) }}"
                                       class="flex items-center justify-between rounded-md px-2 py-1.5 text-sm text-ink-soft hover:bg-slate-50 hover:text-ink transition">
                                        <span>ყველა</span>
                                    </a>
                                </li>
                            @endunless
                            @foreach($navCategories as $nav)
                                <li>
                                    <a href="{{ route('category.show', $nav->slug) }}"
                                       @class([
                                           'flex items-center justify-between rounded-md px-2 py-1.5 text-sm transition',
                                           'bg-slate-100 text-ink font-medium' => $nav->is($category),
                                           'text-ink-soft hover:bg-slate-50 hover:text-ink' => ! $nav->is($category),
                                       ])>
                                        <span>{{ $nav->name_ka }}</span>
                                        <span class="text-xs text-ink-faint">{{ $nav->products_count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="GET" action="{{ route('category.show', $category->slug) }}" class="space-y-5">

                    @if($priceCeiling > 0)
                        <div class="card p-4">
                            <h3 class="text-sm font-semibold text-ink mb-3">ფასი (₾)</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" step="0.01" min="{{ floor($priceFloor) }}" name="price_min"
                                       value="{{ $priceMin !== null ? $priceMin : '' }}"
                                       placeholder="{{ number_format($priceFloor, 0) }}"
                                       class="input py-1.5 text-sm" aria-label="მინ. ფასი">
                                <input type="number" step="0.01" max="{{ ceil($priceCeiling) }}" name="price_max"
                                       value="{{ $priceMax !== null ? $priceMax : '' }}"
                                       placeholder="{{ number_format($priceCeiling, 0) }}"
                                       class="input py-1.5 text-sm" aria-label="მაქს. ფასი">
                            </div>
                        </div>
                    @endif

                    @foreach($availableFilters as $attribute)
                        @if($attribute->values->isEmpty())
                            @continue
                        @endif
                        @php $selected = $selectedAttrs[$attribute->slug] ?? []; @endphp
                        <div class="card p-4">
                            <h3 class="text-sm font-semibold text-ink mb-3">{{ $attribute->name_ka }}</h3>
                            <div class="space-y-2">
                                @foreach($attribute->values as $value)
                                    <label class="flex items-center gap-2 text-sm text-ink-soft cursor-pointer hover:text-ink">
                                        <input type="checkbox"
                                               name="attr[{{ $attribute->slug }}][]"
                                               value="{{ $value->slug }}"
                                               @checked(in_array($value->slug, $selected, true))
                                               class="rounded border-slate-300 text-ink focus:ring-ink">
                                        <span>{{ $value->value_ka }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary text-sm flex-1">გაფილტვრა</button>
                        @if(request()->hasAny(['attr', 'price_min', 'price_max']))
                            <a href="{{ route('category.show', $category->slug) }}" class="btn-ghost text-sm">გასუფთავება</a>
                        @endif
                    </div>
                </form>
            </aside>

            <div class="lg:col-span-3">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <p class="text-sm text-ink-muted">{{ $products->total() }} პროდუქცია</p>
                    <label class="flex items-center gap-2 text-sm shrink-0">
                        <span class="text-ink-muted whitespace-nowrap hidden sm:inline">დალაგება:</span>
                        <select x-data
                                @change="const u = new URL(window.location); $event.target.value === 'default' ? u.searchParams.delete('sort') : u.searchParams.set('sort', $event.target.value); u.searchParams.delete('page'); window.location = u"
                                class="input py-1.5 pr-8 text-sm w-auto">
                            <option value="default" @selected($sort === 'default')>რეკომენდებული</option>
                            <option value="price_asc" @selected($sort === 'price_asc')>ფასი: დაბლიდან მაღლა</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>ფასი: მაღლიდან დაბლა</option>
                            <option value="name_asc" @selected($sort === 'name_asc')>სახელი: ა–ჰ</option>
                            <option value="name_desc" @selected($sort === 'name_desc')>სახელი: ჰ–ა</option>
                        </select>
                    </label>
                </div>
                @if($products->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                        @foreach($products as $product)
                            <x-storefront.product-card :product="$product" />
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $products->links() }}</div>
                @else
                    <div class="card p-10 text-center text-ink-muted">
                        <p>ფილტრით პროდუქცია ვერ მოიძებნა.</p>
                        @if(request()->hasAny(['attr', 'price_min', 'price_max']))
                            <a href="{{ route('category.show', $category->slug) }}" class="btn-outline mt-4">ფილტრის გასუფთავება</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.storefront>
