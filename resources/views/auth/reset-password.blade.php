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
        <h1 class="text-lg font-medium text-ink">Atur Ulang Kata Sandi</h1>
        <p class="text-sm text-ink-secondary mt-6">Masukkan kata sandi baru untuk akun Anda.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-16">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" placeholder="Masukkan email Anda" />
            <x-input-error :messages="$errors->get('email')" class="mt-4" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Password Baru" />
            <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
            <x-input-error :messages="$errors->get('password')" class="mt-4" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" />
            <x-text-input id="password_confirmation" class="block w-full" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi password baru" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-4" />
        </div>

        <div class="pt-8">
            <x-primary-button class="w-full">
                Simpan Kata Sandi Baru
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
