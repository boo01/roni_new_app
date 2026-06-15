<div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 pt-10 pb-16">
    @php $fmt = fn ($n) => number_format($n, 2, '.', ' '); @endphp

    <nav class="text-sm text-ink-muted mb-3" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
        <span class="mx-1.5 text-ink-faint">/</span>
        <span class="text-ink">კალათა</span>
    </nav>

    <h1 class="font-mt text-2xl sm:text-3xl font-bold tracking-tight text-ink mb-8">@mt('კალათა')</h1>

    @if(empty($summary['lines']))
        <div class="card p-10 text-center text-ink-muted">
            <p>კალათა ცარიელია.</p>
            <a href="{{ route('home') }}" class="btn-primary mt-6">პროდუქციის დათვალიერება</a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 card divide-y divide-slate-100">
                @foreach($summary['lines'] as $line)
                    @php
                        $product = $line['product'];
                        $thumb = $product->getFirstMediaUrl('images', 'thumb');
                    @endphp
                    <div class="p-4 sm:p-5 flex gap-4 items-start" wire:key="cart-line-{{ $line['line_key'] }}">
                        <div class="size-20 sm:size-24 rounded-lg overflow-hidden bg-slate-50 shrink-0">
                            @if($thumb)
                                <img src="{{ $thumb }}" alt="{{ $product->name_ka }}" class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full flex items-center justify-center text-ink-faint">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <a href="{{ route('product.show', $product->slug) }}" class="block">
                                <h3 class="text-sm font-medium text-ink line-clamp-2">{{ $product->name_ka }}</h3>
                                <p class="mt-0.5 text-xs text-ink-faint font-mono">{{ $product->sku }}</p>
                            </a>
                            <x-storefront.option-tags :options="$line['options']" />
                            <div class="mt-2 flex items-center gap-2">
                                @if($line['has_discount'])
                                    <span class="text-xs text-ink-faint line-through">₾{{ $fmt($line['unit_retail']) }}</span>
                                @endif
                                <span class="text-sm font-medium {{ $line['has_discount'] ? 'text-deal' : 'text-ink' }}">₾{{ $fmt($line['unit_charged']) }}</span>
                                <span class="text-xs text-ink-faint">× ერთეული</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <div class="flex items-center gap-1">
                                <button type="button"
                                        wire:click="updateQuantity('{{ $line['line_key'] }}', {{ max(1, $line['quantity'] - 1) }})"
                                        class="size-7 rounded-md border border-slate-200 text-ink-soft hover:bg-slate-50 transition cursor-pointer"
                                        aria-label="შემცირება">−</button>
                                <input type="number" min="1" max="999" value="{{ $line['quantity'] }}"
                                       wire:change="updateQuantity('{{ $line['line_key'] }}', $event.target.value)"
                                       class="w-14 text-center input py-1.5">
                                <button type="button"
                                        wire:click="updateQuantity('{{ $line['line_key'] }}', {{ $line['quantity'] + 1 }})"
                                        class="size-7 rounded-md border border-slate-200 text-ink-soft hover:bg-slate-50 transition cursor-pointer"
                                        aria-label="გაზრდა">+</button>
                            </div>
                            <p class="text-sm font-semibold text-ink">₾{{ $fmt($line['line_total']) }}</p>
                            <button type="button" wire:click="remove('{{ $line['line_key'] }}')"
                                    class="text-xs text-ink-muted hover:text-red-600 transition cursor-pointer">წაშლა</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card p-5 h-fit sticky top-20">
                <h2 class="text-sm font-semibold text-ink mb-4">ჯამი</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">ერთეულების რაოდენობა</dt>
                        <dd class="text-ink">{{ array_sum(array_column($summary['lines'], 'quantity')) }}</dd>
                    </div>
                    @if($summary['discount_total'] > 0)
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">საცალო ფასი</dt>
                            <dd class="text-ink-faint line-through">₾{{ $fmt($summary['subtotal_retail']) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">B2B ფასდაკლება</dt>
                            <dd class="text-deal">− ₾{{ $fmt($summary['discount_total']) }}</dd>
                        </div>
                    @endif
                </dl>
                <div class="border-t border-slate-100 mt-4 pt-4 flex items-baseline justify-between">
                    <span class="text-sm font-medium text-ink">სულ</span>
                    <span class="text-xl font-bold text-ink">₾{{ $fmt($summary['total']) }}</span>
                </div>
                <a href="{{ route('checkout.show') }}" class="btn-primary w-full mt-6">გადახდაზე გადასვლა</a>
                <a href="{{ route('home') }}" class="btn-ghost w-full mt-2">ყიდვის გაგრძელება</a>
            </div>
        </div>
    @endif
</div>
