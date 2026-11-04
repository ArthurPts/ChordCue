<x-guest-layout>
    <!-- Brand Header -->
    <div class="flex items-center justify-center gap-10 mb-16">
        <div class="h-9 w-9 rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-accent">
            <x-icon name="music" class="h-5 w-5" />
        </div>
        <span class="text-xl font-medium tracking-tight text-ink">ChordCue</span>
    </div>

    <!-- Title & Subtitle -->
    <div class="text-center mb-24">
        <h1 class="text-lg font-medium text-ink">Lupa Kata Sandi?</h1>
        <p class="text-sm text-ink-secondary mt-6">Masukkan email Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-16" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-16">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus placeholder="Masukkan email Anda" />
            <x-input-error :messages="$errors->get('email')" class="mt-4" />
        </div>

        <div class="pt-8">
            <x-primary-button class="w-full">
                Kirim Tautan Reset Kata Sandi
            </x-primary-button>
        </div>

        <div class="text-center pt-8 text-sm text-ink-secondary">
            Ingat kata sandi Anda? <a href="{{ route('login') }}" class="text-accent hover:text-accent-hover font-medium transition-colors ml-2">Masuk</a>
        </div>
    </form>
</x-guest-layout>
