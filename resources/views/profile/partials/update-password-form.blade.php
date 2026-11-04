<section>
    <header class="mb-20">
        <h2 class="text-base font-medium text-ink">
            Perbarui Kata Sandi
        </h2>

        <p class="mt-4 text-sm text-ink-secondary">
            Pastikan akun Anda menggunakan kata sandi yang kuat dan aman.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-16">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Kata Sandi Saat Ini" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="block w-full" autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-4" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Kata Sandi Baru" />
            <x-text-input id="update_password_password" name="password" type="password" class="block w-full" autocomplete="new-password" placeholder="Minimal 8 karakter" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-4" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Konfirmasi Kata Sandi Baru" />
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
                    class="text-caption text-success flex items-center gap-4"
                >
                    <x-icon name="check" class="h-icon-chip w-icon-chip text-success" />
                    <span>Kata sandi berhasil diperbarui.</span>
                </p>
            @endif
        </div>
    </form>
</section>
