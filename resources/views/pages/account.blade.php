<x-layouts.storefront>
    <x-slot:title>ჩემი ანგარიში</x-slot:title>
    @php $fmt = fn ($n) => number_format($n, 2, '.', ' '); @endphp

    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-10">
        <nav class="text-sm text-ink-muted mb-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
            <span class="mx-1.5 text-ink-faint">/</span>
            <span class="text-ink">ჩემი ანგარიში</span>
        </nav>

        <div class="flex items-end justify-between flex-wrap gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">გამარჯობა, {{ $user->name }}</h1>
                <p class="text-ink-muted mt-1">
                    @if($user->isB2B())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-deal-soft text-deal mr-1">B2B</span>
                        {{ $user->customerGroup->name }} ჯგუფი
                    @else
                        კერძო პირი
                    @endif
                </p>
            </div>
            <a href="{{ route('profile.edit') }}" class="btn-outline text-sm">პროფილის რედაქტირება</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="card p-5 lg:col-span-1 h-fit">
                <h2 class="text-sm font-semibold text-ink mb-4">საკონტაქტო ინფორმაცია</h2>
                <dl class="text-sm space-y-2">
                    <div>
                        <dt class="text-ink-muted">ელ. ფოსტა</dt>
                        <dd class="text-ink">{{ $user->email }}</dd>
                    </div>
                    @if($user->phone)
                        <div>
                            <dt class="text-ink-muted">ტელეფონი</dt>
                            <dd class="text-ink">{{ $user->phone }}</dd>
                        </div>
                    @endif
                    @if($user->company_name)
                        <div>
                            <dt class="text-ink-muted">კომპანია</dt>
                            <dd class="text-ink">{{ $user->company_name }}</dd>
                        </div>
                    @endif
                    @if($user->address)
                        <div>
                            <dt class="text-ink-muted">მისამართი</dt>
                            <dd class="text-ink">{{ $user->address }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-ink">ჩემი შეკვეთები</h2>
                    <span class="text-xs text-ink-faint">სულ: {{ $orders->total() }}</span>
                </div>

                @if($orders->isEmpty())
                    <div class="card p-10 text-center text-ink-muted">
                        <p>ჯერ შეკვეთა არ გაქვს.</p>
                        <a href="{{ route('home') }}" class="btn-primary mt-6">პროდუქციის დათვალიერება</a>
                    </div>
                @else
                    <div class="card divide-y divide-slate-100">
                        @foreach($orders as $order)
                            <a href="{{ route('account.order', $order->order_number) }}" class="block p-4 sm:p-5 hover:bg-slate-50 transition">
                                <div class="flex items-center justify-between gap-4 flex-wrap">
                                    <div class="min-w-0">
                                        <p class="font-mono text-sm text-ink">{{ $order->order_number }}</p>
                                        <p class="text-xs text-ink-muted mt-0.5">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-ink">₾{{ $fmt($order->total) }}</p>
                                        <span class="inline-flex mt-1 items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            @switch($order->status)
                                                @case('new') bg-amber-50 text-amber-700 @break
                                                @case('contacted') bg-sky-50 text-sky-700 @break
                                                @case('paid')
                                                @case('fulfilled') bg-deal-soft text-deal @break
                                                @case('cancelled') bg-red-50 text-red-700 @break
                                                @default bg-slate-100 text-slate-700
                                            @endswitch
                                        ">{{ $order->status }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $orders->links() }}</div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.storefront>
