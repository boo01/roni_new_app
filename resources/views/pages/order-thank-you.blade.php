<x-layouts.storefront>
    <x-slot:title>შეკვეთა მიღებულია</x-slot:title>

    <section class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-16 text-center">
        <div class="size-14 mx-auto rounded-full bg-deal-soft text-deal flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>

        <h1 class="mt-6 text-2xl sm:text-3xl font-bold tracking-tight text-ink">გმადლობთ შეკვეთისთვის!</h1>
        <p class="mt-3 text-ink-muted">თქვენი შეკვეთა <span class="font-mono text-ink">{{ $order->order_number }}</span> წარმატებით მიღებულია. მაღაზიის წარმომადგენელი მალე დაგიკავშირდებათ.</p>

        <div class="card mt-8 p-5 text-left">
            <div class="flex items-baseline justify-between mb-4">
                <span class="text-sm text-ink-muted">სულ გადასახდელი</span>
                <span class="text-2xl font-bold text-ink">₾{{ number_format($order->total, 2, '.', ' ') }}</span>
            </div>
            <a href="{{ route('order.invoice', $order->order_number) }}"
               class="btn-outline w-full" target="_blank" rel="noopener">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                ინვოისის ჩამოტვირთვა
            </a>
        </div>

        <a href="{{ route('home') }}" class="btn-ghost mt-6 inline-flex">მთავარზე დაბრუნება</a>
    </section>
</x-layouts.storefront>
