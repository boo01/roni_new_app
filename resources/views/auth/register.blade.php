<x-layouts.storefront>
    <x-slot:title>რეგისტრაცია</x-slot:title>

    <section class="mx-auto max-w-md px-4 py-12">
        <div class="card p-6 sm:p-8">
            <h1 class="font-mt text-2xl font-bold text-ink">@mt('რეგისტრაცია')</h1>
            <p class="mt-1 text-sm text-ink-muted">შექმენი ანგარიში მარტივი შესყიდვისთვის. კომპანიის ანგარიშები იხსნება მაღაზიის ადმინისტრაციით.</p>

            <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="label" for="name">სახელი და გვარი</label>
                    <input id="name" name="name" type="text" required autofocus class="input" value="{{ old('name') }}">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="email">ელ. ფოსტა</label>
                    <input id="email" name="email" type="email" required class="input" value="{{ old('email') }}">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="password">პაროლი</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="input">
                    @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="password_confirmation">გაიმეორე პაროლი</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
                </div>
                <button type="submit" class="btn-primary w-full">რეგისტრაცია</button>
            </form>

            <p class="mt-6 text-center text-sm text-ink-muted">
                უკვე გყავს ანგარიში?
                <a href="{{ route('login') }}" class="text-ink underline">შესვლა</a>
            </p>
        </div>
    </section>
</x-layouts.storefront>
