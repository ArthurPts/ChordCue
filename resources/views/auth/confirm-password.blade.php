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
        <h1 class="text-lg font-medium text-ink">Konfirmasi Kata Sandi</h1>
        <p class="text-sm text-ink-secondary mt-6">Area ini memerlukan konfirmasi kata sandi sebelum melanjutkan.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-16">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="Masukkan password Anda" />
            <x-input-error :messages="$errors->get('password')" class="mt-4" />
        </div>

        <div class="pt-8">
            <x-primary-button class="w-full">
                Konfirmasi
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
