<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-10">
            <x-icon name="books" class="w-6 h-6 text-accent" />
            <h1 class="text-lg font-medium text-ink">Pustaka Chord</h1>
        </div>
    </x-slot>

    <div class="py-24">
        <div class="max-w-content mx-auto px-16 md:px-24">
            <div class="p-32 bg-surface-1 border border-subtle rounded-xl text-center">
                <div class="w-12 h-12 rounded-xl bg-surface-2 border border-subtle flex items-center justify-center text-accent mx-auto mb-16">
                    <x-icon name="books" class="w-6 h-6" />
                </div>
                <h2 class="text-base font-medium text-ink">Pustaka Referensi Chord</h2>
                <p class="text-sm text-ink-secondary mt-6 max-w-dialog mx-auto">
                    Halaman ini adalah template untuk pustaka chord. Di sini akan ditampilkan daftar chord, diagram penempatan jari, dan pratinjau audio cue.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>

