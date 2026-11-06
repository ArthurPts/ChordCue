<x-guest-layout>
    <!-- Brand Header -->
    <div class="flex items-center justify-center mb-20">
        <x-application-logo />
    </div>

    <!-- Title & Subtitle -->
    <div class="text-center mb-24">
        <h1 class="text-xl sm:text-2xl font-extrabold text-ink tracking-tight">Buat Akun ChordCue</h1>
        <p class="text-xs sm:text-sm text-ink-secondary mt-4">Daftar untuk mulai menyimpan dan memainkan lagu favorit Anda.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-16">
        @csrf

        <!-- Username -->
        <div class="space-y-4">
            <x-input-label for="username" value="Username" class="text-xs font-semibold text-ink-secondary" />
            <x-text-input id="username" class="block w-full" type="text" name="username" :value="old('username')" required autofocus autocomplete="username" placeholder="Masukkan username Anda" />
            <x-input-error :messages="$errors->get('username')" class="mt-4" />
        </div>

        <!-- Email Address -->
        <div class="space-y-4">
            <x-input-label for="email" value="Email" class="text-xs font-semibold text-ink-secondary" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autocomplete="email" placeholder="Masukkan email Anda" />
            <x-input-error :messages="$errors->get('email')" class="mt-4" />
        </div>

        <!-- Password -->
        <div class="space-y-4">
            <x-input-label for="password" value="Password" class="text-xs font-semibold text-ink-secondary" />
            <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="new-password" placeholder="Masukkan password Anda" />
            <x-input-error :messages="$errors->get('password')" class="mt-4" />
        </div>

        <!-- Confirm Password -->
        <div class="space-y-4">
            <x-input-label for="password_confirmation" value="Konfirmasi Password" class="text-xs font-semibold text-ink-secondary" />
            <x-text-input id="password_confirmation" class="block w-full" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Konfirmasi password Anda" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-4" />
        </div>

        <!-- Submit Button -->
        <div class="pt-8">
            <x-primary-button class="w-full">
                Buat Akun
            </x-primary-button>
        </div>

        <!-- Login Link -->
        <div class="text-center pt-8 text-xs sm:text-sm text-ink-secondary">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-accent hover:text-accent-hover font-semibold transition-colors ml-4">Masuk</a>
        </div>
    </form>
</x-guest-layout>
