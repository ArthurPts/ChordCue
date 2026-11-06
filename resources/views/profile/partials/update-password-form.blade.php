<section>
    <header class="mb-20">
        <h2 class="text-base sm:text-lg font-bold text-ink tracking-tight">
            Perbarui Kata Sandi
        </h2>

        <p class="mt-2 text-xs sm:text-sm text-ink-secondary">
            Pastikan akun Anda menggunakan kata sandi yang kuat dan aman.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-16">
        @csrf
        @method('put')

        <div class="space-y-4">
            <x-input-label for="update_password_current_password" value="Kata Sandi Saat Ini" class="text-xs font-semibold text-ink-secondary" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="block w-full" autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-4" />
        </div>

        <div class="space-y-4">
            <x-input-label for="update_password_password" value="Kata Sandi Baru" class="text-xs font-semibold text-ink-secondary" />
            <x-text-input id="update_password_password" name="password" type="password" class="block w-full" autocomplete="new-password" placeholder="Minimal 8 karakter" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-4" />
        </div>

        <div class="space-y-4">
            <x-input-label for="update_password_password_confirmation" value="Konfirmasi Kata Sandi Baru" class="text-xs font-semibold text-ink-secondary" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="block w-full" autocomplete="new-password" placeholder="Ulangi kata sandi baru" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-4" />
        </div>

        <div class="flex items-center gap-12 pt-8">
            <x-primary-button>Simpan Kata Sandi</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-xs font-semibold text-success flex items-center gap-6"
                >
                    <x-icon name="check" class="h-icon-chip w-icon-chip text-success" />
                    <span>Kata sandi berhasil diperbarui.</span>
                </p>
            @endif
        </div>
    </form>
</section>
