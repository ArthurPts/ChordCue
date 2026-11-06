<section>
    <header class="mb-20">
        <h2 class="text-base sm:text-lg font-bold text-ink tracking-tight">
            Informasi Akun
        </h2>

        <p class="mt-2 text-xs sm:text-sm text-ink-secondary">
            Perbarui informasi profil dan alamat email akun Anda.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-16">
        @csrf
        @method('patch')

        <div class="space-y-4">
            <x-input-label for="username" value="Username" class="text-xs font-semibold text-ink-secondary" />
            <x-text-input id="username" name="username" type="text" class="block w-full" :value="old('username', $user->username)" required autofocus autocomplete="username" placeholder="Masukkan username Anda" />
            <x-input-error class="mt-4" :messages="$errors->get('username')" />
        </div>

        <div class="space-y-4">
            <x-input-label for="email" value="Email" class="text-xs font-semibold text-ink-secondary" />
            <x-text-input id="email" name="email" type="email" class="block w-full" :value="old('email', $user->email)" required autocomplete="email" placeholder="Masukkan email Anda" />
            <x-input-error class="mt-4" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-12 pt-8">
            <x-primary-button>Simpan</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-xs font-semibold text-success flex items-center gap-6"
                >
                    <x-icon name="check" class="h-icon-chip w-icon-chip text-success" />
                    <span>Perubahan berhasil disimpan.</span>
                </p>
            @endif
        </div>
    </form>
</section>
