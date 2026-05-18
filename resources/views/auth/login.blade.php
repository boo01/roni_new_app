<x-layouts.storefront>
    <x-slot:title>შესვლა</x-slot:title>

    <section class="mx-auto max-w-md px-4 py-12">
        <div class="card p-6 sm:p-8">
            <h1 class="text-2xl font-bold text-ink">შესვლა</h1>
            <p class="mt-1 text-sm text-ink-muted">გაიარე ავტორიზაცია შენი ანგარიშით</p>

            @if (session('status'))
                <div class="mt-4 text-sm text-deal">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="label" for="email">ელ. ფოსტა</label>
                    <input id="email" name="email" type="email" required autofocus autocomplete="username"
                           class="input" value="{{ old('email') }}">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="password">პაროლი</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
                    @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-ink-soft">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-ink focus:ring-ink">
                        დამიმახსოვრე
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-ink-soft hover:text-ink underline">დაგავიწყდა პაროლი?</a>
                    @endif
                </div>
                <button type="submit" class="btn-primary w-full">შესვლა</button>
            </form>

            <p class="mt-6 text-center text-sm text-ink-muted">
                ანგარიში არ გაქვს?
                <a href="{{ route('register') }}" class="text-ink underline">დარეგისტრირდი</a>
            </p>
        </div>
    </section>
</x-layouts.storefront>
