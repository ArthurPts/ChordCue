<x-guest-layout>
    <div class="mb-16 text-sm text-ink-secondary">
        Terima kasih telah mendaftar. Sebelum memulai, silakan verifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirimkan.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-16 font-medium text-sm text-success">
            Tautan verifikasi baru telah dikirimkan ke alamat email yang Anda berikan saat pendaftaran.
        </div>
    @endif

    <div class="mt-16 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Kirim Ulang Email Verifikasi
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm text-ink-secondary hover:text-ink transition-colors focus:outline-none">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
