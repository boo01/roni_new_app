<div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 pt-10 pb-16">
    @php $fmt = fn ($n) => number_format($n, 2, '.', ' '); @endphp

    <nav class="text-sm text-ink-muted mb-3" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-ink">მთავარი</a>
        <span class="mx-1.5 text-ink-faint">/</span>
        <a href="{{ route('cart.show') }}" class="hover:text-ink">კალათა</a>
        <span class="mx-1.5 text-ink-faint">/</span>
        <span class="text-ink">გადახდა</span>
    </nav>

    <h1 class="font-mt text-2xl sm:text-3xl font-bold tracking-tight text-ink mb-8">@mt('გადახდა')</h1>

    <form wire:submit="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4">საკონტაქტო ინფორმაცია</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="name">სახელი და გვარი *</label>
                        <input wire:model="name" id="name" type="text" class="input" required>
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="email">ელ. ფოსტა *</label>
                        <input wire:model="email" id="email" type="email" class="input" required>
                        @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="phone">ტელეფონი *</label>
                        <input wire:model="phone" id="phone" type="tel" class="input" required>
                        @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4">კომპანიის ინფორმაცია <span class="font-normal text-ink-faint">(ნებაყოფლობითი)</span></h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="company_name">კომპანიის სახელი</label>
                        <input wire:model="company_name" id="company_name" type="text" class="input">
                    </div>
                    <div>
                        <label class="label" for="company_tax_id">საგადასახადო კოდი</label>
                        <input wire:model="company_tax_id" id="company_tax_id" type="text" class="input">
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4">მისამართი</h2>
                <div>
                    <label class="label" for="address">მისამართი *</label>
                    <input wire:model="address" id="address" type="text" class="input" required>
                    @error('address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="mt-4">
                    <label class="label" for="notes">დამატებითი ინფორმაცია</label>
                    <textarea wire:model="notes" id="notes" rows="3" class="input"></textarea>
                </div>
            </div>
        </div>

        <div class="card p-5 h-fit sticky top-20">
            <h2 class="text-sm font-semibold text-ink mb-4">თქვენი შეკვეთა</h2>
            <ul class="divide-y divide-slate-100 -mx-1">
                @foreach($summary['lines'] as $line)
                    <li class="px-1 py-2 flex justify-between gap-2 text-sm">
                        <span class="text-ink-soft min-w-0 truncate">
                            {{ $line['product']->name_ka }}
                            <span class="text-ink-faint">× {{ $line['quantity'] }}</span>
                        </span>
                        <span class="text-ink whitespace-nowrap">₾{{ $fmt($line['line_total']) }}</span>
                    </li>
                @endforeach
            </ul>
            @if($summary['discount_total'] > 0)
                <div class="border-t border-slate-100 mt-3 pt-3 flex justify-between text-sm">
                    <span class="text-ink-muted">B2B ფასდაკლება</span>
                    <span class="text-deal">− ₾{{ $fmt($summary['discount_total']) }}</span>
                </div>
            @endif
            <div class="border-t border-slate-100 mt-3 pt-3 flex items-baseline justify-between">
                <span class="text-sm font-medium text-ink">სულ</span>
                <span class="text-xl font-bold text-ink">₾{{ $fmt($summary['total']) }}</span>
            </div>

            <button type="submit" class="btn-primary w-full mt-6" wire:loading.attr="disabled">
                <span wire:loading.remove>შეკვეთის გაგზავნა</span>
                <span wire:loading>გაგზავნა...</span>
            </button>
            <p class="text-xs text-ink-faint mt-3 text-center">გაგზავნის შემდეგ მაღაზიის წარმომადგენელი დაგიკავშირდებათ</p>
        </div>
    </form>
</div>
