<x-guest-layout>
    <!-- Brand Header -->
    <div class="flex items-center justify-center mb-24">
        <x-application-logo />
    </div>

    <!-- Title & Subtitle -->
    <div class="text-center mb-24">
        <h1 class="text-xl font-medium text-ink tracking-tight">Selamat Datang Kembali</h1>
        <p class="text-sm text-ink-secondary mt-6">Masuk ke akun Anda untuk melanjutkan.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-16" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-16">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="Masukkan email Anda" />
            <x-input-error :messages="$errors->get('email')" class="mt-4" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password Anda" />
            <x-input-error :messages="$errors->get('password')" class="mt-4" />
        </div>

        <!-- Remember Me & Lupa Password -->
        <div class="flex items-center justify-between pt-4">
            <label for="remember_me" class="inline-flex items-center gap-10 cursor-pointer select-none text-ink-secondary hover:text-ink transition-colors group">
                <input id="remember_me" type="checkbox" name="remember" class="w-[18px] h-[18px] rounded bg-surface-3 border border-subtle text-accent focus:ring-0 focus:ring-offset-0 cursor-pointer accent-accent transition-colors">
                <span class="text-sm group-hover:text-ink">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs text-ink-tertiary hover:text-accent transition-colors">
                    Lupa password?
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="pt-8">
            <x-primary-button class="w-full">
                Masuk
            </x-primary-button>
        </div>

        <!-- Register Link -->
        <div class="text-center pt-8 text-sm text-ink-secondary">
            Belum punya akun? <a href="{{ route('register') }}" class="text-accent hover:text-accent-hover font-medium transition-colors ml-4">Daftar</a>
        </div>

        <!-- Divider -->
        <div class="relative my-24 flex items-center justify-center">
            <div class="w-full border-t border-subtle"></div>
            <span class="absolute bg-surface-1 px-14 text-xs text-ink-tertiary">atau</span>
        </div>

        <!-- Jelajahi Lagu Tanpa Login -->
        <div class="text-center">
            <a href="{{ route('home') }}" class="text-sm text-accent hover:text-accent-hover transition-colors inline-block">
                Jelajahi lagu tanpa login
            </a>
        </div>
    </form>
</x-guest-layout>
