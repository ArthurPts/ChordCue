<section class="space-y-16">
    <header class="mb-20">
        <h2 class="text-base font-medium text-error">
            Hapus Akun
        </h2>

        <p class="mt-4 text-sm text-ink-secondary">
            Setelah akun dihapus, semua data dan lagu milik Anda akan dihapus secara permanen. Harap pastikan kembali sebelum melanjutkan.
        </p>
    </header>

    <div>
        <x-danger-button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >Hapus Akun Saya</x-danger-button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="space-y-16">
            @csrf
            @method('delete')

            <h2 class="text-base font-medium text-ink">
                Apakah Anda yakin ingin menghapus akun?
            </h2>

            <p class="text-sm text-ink-secondary">
                Tindakan ini tidak dapat dibatalkan. Seluruh lagu dan data akun akan terhapus. Masukkan kata sandi Anda untuk mengonfirmasi.
            </p>

            <div>
                <x-input-label for="delete_account_password" value="Kata Sandi" class="sr-only" />

                <x-text-input
                    id="delete_account_password"
                    name="password"
                    type="password"
                    class="block w-full"
                    placeholder="Masukkan kata sandi untuk konfirmasi"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-4" />
            </div>

            <div class="flex justify-end gap-12 pt-8">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-danger-button>
                    Hapus Akun Permanen
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
