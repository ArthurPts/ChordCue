<x-app-layout>
    @php
        $initialModal = old('_modal', '');
        $initialEditId = old('_edit_id', '');
        $initialEditName = old('_modal') === 'edit' ? old('name', '') : '';
        $initialCreateName = old('_modal') === 'create' ? old('name', '') : '';
    @endphp

    <div
        x-data="{
            showCreateModal: {{ $initialModal === 'create' ? 'true' : 'false' }},
            showEditModal: {{ $initialModal === 'edit' ? 'true' : 'false' }},
            showDeleteModal: false,
            submitting: false,
            editTarget: {
                id: @js($initialEditId),
                name: @js($initialEditName),
            },
            deleteTarget: {
                id: null,
                name: '',
            },
            openCreate() {
                this.submitting = false;
                this.showCreateModal = true;
            },
            openEdit(item) {
                this.submitting = false;
                this.editTarget = {
                    id: item.id,
                    name: item.name,
                };
                this.showEditModal = true;
            },
            openDelete(item) {
                this.submitting = false;
                this.deleteTarget = {
                    id: item.id,
                    name: item.name,
                };
                this.showDeleteModal = true;
            },
            closeAll() {
                if (this.submitting) return;
                this.showCreateModal = false;
                this.showEditModal = false;
                this.showDeleteModal = false;
            },
        }"
        @keydown.escape.window="closeAll()"
        class="max-w-content mx-auto w-full px-16 md:px-24 py-24 sm:py-32 pb-[88px]"
    >
        <!-- Header & Primary Action -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-16">
            <div>
                <h1 class="text-display font-medium text-ink leading-heading">
                    Kelola Artis
                </h1>
                <p class="text-body text-ink-secondary mt-4">
                    Kelola daftar artis master untuk katalog lagu, pencarian, dan editor ChordCue.
                </p>
            </div>

            <button
                type="button"
                @click="openCreate()"
                class="h-[40px] px-20 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-body font-medium inline-flex items-center justify-center gap-8 transition-colors cursor-pointer shrink-0"
            >
                <x-icon name="plus" class="w-icon-control h-icon-control shrink-0" />
                <span>Tambah Artis</span>
            </button>
        </div>

        <!-- Flash Feedback Alerts -->
        @if (session('status'))
            <div role="status" class="mt-16 p-14 rounded-md bg-success-tint border border-success/30 text-success text-body flex items-center gap-10">
                <x-icon name="check" class="w-icon-control h-icon-control shrink-0" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="mt-16 p-14 rounded-md bg-error-tint border border-error/30 text-error text-body flex items-center gap-10">
                <x-icon name="alert-circle" class="w-icon-control h-icon-control shrink-0" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Stat Cards Row (2 Cards: Total Artis, Dipakai di Lagu) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-12 sm:gap-16 mt-20">
            <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 flex items-center gap-16">
                <div class="w-[44px] h-[44px] rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                    <x-icon name="disc" class="w-icon-control h-icon-control" />
                </div>
                <div>
                    <span class="text-caption font-medium text-ink-secondary">Total Artis</span>
                    <div class="mt-4 flex items-baseline gap-10">
                        <span class="text-display font-medium font-mono text-ink leading-heading">{{ $totalArtists }}</span>
                        <span class="text-caption text-ink-tertiary">Terdaftar di katalog</span>
                    </div>
                </div>
            </div>

            <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 flex items-center gap-16">
                <div class="w-[44px] h-[44px] rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                    <x-icon name="music" class="w-icon-control h-icon-control" />
                </div>
                <div>
                    <span class="text-caption font-medium text-ink-secondary">Artis Terpakai</span>
                    <div class="mt-4 flex items-baseline gap-10">
                        <span class="text-display font-medium font-mono text-ink leading-heading">{{ $usedArtistsCount }}</span>
                        <span class="text-caption text-ink-tertiary">Memiliki lagu aktif</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Toolbar -->
        <form method="GET" action="{{ route('admin.artists.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-12 mt-20 p-14 sm:p-16 rounded-lg bg-surface-1 border border-subtle">
            <div class="relative flex-1">
                <button
                    type="submit"
                    class="absolute inset-y-0 left-0 pl-14 flex items-center text-ink-secondary hover:text-accent transition-colors cursor-pointer"
                    aria-label="Cari artis"
                >
                    <x-icon name="search" class="w-icon-control h-icon-control" />
                </button>
                <input
                    type="text"
                    name="q"
                    value="{{ $searchKeyword }}"
                    placeholder="Cari nama artis..."
                    aria-label="Cari nama artis"
                    class="w-full h-[40px] pl-[38px] pr-[36px] bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-tertiary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-body transition-colors"
                >
                @if ($searchKeyword !== '')
                    <a
                        href="{{ route('admin.artists.index') }}"
                        class="absolute inset-y-0 right-0 pr-14 flex items-center text-ink-secondary hover:text-ink transition-colors"
                        aria-label="Hapus pencarian"
                    >
                        <x-icon name="x" class="w-icon-chip h-icon-chip" />
                    </a>
                @endif
            </div>

            <div class="flex items-center justify-between sm:justify-end gap-12 shrink-0">
                <span class="text-caption font-mono text-ink-tertiary">
                    {{ $artists->total() }} artis
                </span>
                <button
                    type="submit"
                    class="h-[40px] px-16 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-body font-medium transition-colors cursor-pointer"
                >
                    Cari
                </button>
            </div>
        </form>

        <!-- Artists Data Table -->
        <div class="mt-20 bg-surface-1 border border-subtle rounded-lg overflow-hidden">
            @if ($artists->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-subtle bg-surface-2/60 text-ink-secondary text-caption font-medium">
                                <th scope="col" class="py-12 px-16 sm:px-20">Nama Artis</th>
                                <th scope="col" class="py-12 px-16 sm:px-20 font-mono">Jumlah Lagu</th>
                                <th scope="col" class="py-12 px-16 sm:px-20">Status</th>
                                <th scope="col" class="py-12 px-16 sm:px-20 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-subtle/50">
                            @foreach ($artists as $artist)
                                <tr class="hover:bg-surface-2/40 transition-colors">
                                    <td class="py-14 px-16 sm:px-20 whitespace-nowrap">
                                        <div class="flex items-center gap-10">
                                            <div class="w-[32px] h-[32px] rounded-full bg-surface-3 border border-subtle flex items-center justify-center text-accent shrink-0 text-caption font-medium uppercase">
                                                {{ mb_strtoupper(mb_substr($artist->name, 0, 1)) }}
                                            </div>
                                            <span class="text-body font-medium text-ink">{{ $artist->name }}</span>
                                        </div>
                                    </td>

                                    <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-body font-mono text-ink-secondary">
                                        {{ $artist->songs_count }} lagu
                                    </td>

                                    <td class="py-14 px-16 sm:px-20 whitespace-nowrap">
                                        @if ($artist->songs_count > 0)
                                            <span class="inline-flex items-center px-10 py-4 rounded-full text-caption font-medium border border-accent/40 bg-accent-soft-bg text-accent-soft-text">
                                                Dipakai di Lagu
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-10 py-4 rounded-full text-caption font-medium border border-subtle bg-surface-2 text-ink-secondary">
                                                Belum Dipakai
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-8">
                                            <button
                                                type="button"
                                                @click="openEdit({
                                                    id: {{ $artist->id }},
                                                    name: @js($artist->name)
                                                })"
                                                class="h-[32px] px-12 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-caption font-medium inline-flex items-center gap-6 transition-colors cursor-pointer"
                                            >
                                                <x-icon name="pencil" class="w-icon-chip h-icon-chip text-ink-secondary" />
                                                <span>Edit</span>
                                            </button>

                                            @if ($artist->songs_count > 0)
                                                <button
                                                    type="button"
                                                    disabled
                                                    title="Tidak dapat dihapus karena masih digunakan pada {{ $artist->songs_count }} lagu"
                                                    class="h-[32px] px-12 rounded-sm bg-error-tint/40 border border-error/20 text-error/60 text-caption font-medium inline-flex items-center gap-6 opacity-50 cursor-not-allowed"
                                                >
                                                    <x-icon name="trash" class="w-icon-chip h-icon-chip" />
                                                    <span>Hapus</span>
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    @click="openDelete({
                                                        id: {{ $artist->id }},
                                                        name: @js($artist->name)
                                                    })"
                                                    class="h-[32px] px-12 rounded-sm bg-error-tint/70 hover:bg-error-tint border border-error/30 text-error text-caption font-medium inline-flex items-center gap-6 transition-colors cursor-pointer"
                                                >
                                                    <x-icon name="trash" class="w-icon-chip h-icon-chip" />
                                                    <span>Hapus</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State -->
                <div class="py-32 px-16 text-center max-w-[420px] mx-auto">
                    <div class="w-[44px] h-[44px] rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-ink-tertiary mx-auto mb-12">
                        <x-icon name="disc" class="w-icon-nav h-icon-nav" />
                    </div>
                    @if ($searchKeyword !== '')
                        <h2 class="text-title font-medium text-ink">
                            Artis tidak ditemukan
                        </h2>
                        <p class="text-body text-ink-secondary mt-6">
                            Tidak ada artis yang cocok dengan kata kunci "{{ $searchKeyword }}".
                        </p>
                        <div class="mt-16">
                            <a
                                href="{{ route('admin.artists.index') }}"
                                class="h-[40px] px-20 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-body font-medium inline-flex items-center justify-center transition-colors"
                            >
                                Reset Pencarian
                            </a>
                        </div>
                    @else
                        <h2 class="text-title font-medium text-ink">
                            Belum ada Artis
                        </h2>
                        <p class="text-body text-ink-secondary mt-6">
                            Tambahkan artis pertama agar tersedia saat membuat atau memfilter lagu.
                        </p>
                        <div class="mt-16">
                            <button
                                type="button"
                                @click="openCreate()"
                                class="h-[40px] px-20 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-body font-medium inline-flex items-center gap-8 transition-colors cursor-pointer"
                            >
                                <x-icon name="plus" class="w-icon-control h-icon-control" />
                                <span>Tambah Artis</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Pagination -->
        <x-pagination :paginator="$artists" />

        <!-- Modal Tambah Artis -->
        <div
            x-show="showCreateModal"
            x-cloak
            class="fixed inset-0 z-modal overflow-y-auto"
            aria-labelledby="create-artist-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-overlay backdrop-blur-sm" @click="closeAll()"></div>

            <div class="flex min-h-full items-center justify-center p-16 sm:p-24">
                <div class="relative w-full max-w-dialog rounded-lg bg-surface-1 border border-subtle p-24 text-left">
                    <div>
                        <h2 id="create-artist-title" class="text-heading font-medium text-ink leading-heading">
                            Tambah Artis
                        </h2>
                        <p class="text-body text-ink-secondary mt-4">
                            Masukkan nama artis baru untuk daftar master ChordCue.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.artists.store') }}"
                        @submit="submitting = true"
                        class="mt-20 space-y-16"
                    >
                        @csrf
                        <input type="hidden" name="_modal" value="create">

                        <div class="space-y-6">
                            <label for="create_artist_name" class="block text-caption font-medium text-ink-secondary">
                                Nama Artis
                            </label>
                            <input
                                id="create_artist_name"
                                type="text"
                                name="name"
                                value="{{ $initialCreateName }}"
                                required
                                maxlength="120"
                                placeholder="contoh: Sheila on 7"
                                class="w-full h-[40px] px-14 bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-tertiary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-body transition-colors"
                            >
                            @if ($initialModal === 'create')
                                @error('name')
                                    <p class="text-caption text-error">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="pt-16 border-t border-subtle flex items-center justify-end gap-12">
                            <button
                                type="button"
                                @click="closeAll()"
                                :disabled="submitting"
                                class="h-[40px] px-20 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-body font-medium transition-colors cursor-pointer disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="h-[40px] px-24 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-body font-medium transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <span x-show="!submitting">Simpan Artis</span>
                                <span x-show="submitting" x-cloak>Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Edit Artis -->
        <div
            x-show="showEditModal"
            x-cloak
            class="fixed inset-0 z-modal overflow-y-auto"
            aria-labelledby="edit-artist-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-overlay backdrop-blur-sm" @click="closeAll()"></div>

            <div class="flex min-h-full items-center justify-center p-16 sm:p-24">
                <div class="relative w-full max-w-dialog rounded-lg bg-surface-1 border border-subtle p-24 text-left">
                    <div>
                        <h2 id="edit-artist-title" class="text-heading font-medium text-ink leading-heading">
                            Edit Artis
                        </h2>
                        <p class="text-body text-ink-secondary mt-4">
                            Perubahan nama artis akan langsung diperbarui pada seluruh lagu terkait.
                        </p>
                    </div>

                    <form
                        method="POST"
                        :action="'{{ url('/admin/artists') }}/' + editTarget.id"
                        @submit="submitting = true"
                        class="mt-20 space-y-16"
                    >
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_modal" value="edit">
                        <input type="hidden" name="_edit_id" :value="editTarget.id">

                        <div class="space-y-6">
                            <label for="edit_artist_name" class="block text-caption font-medium text-ink-secondary">
                                Nama Artis
                            </label>
                            <input
                                id="edit_artist_name"
                                type="text"
                                name="name"
                                x-model="editTarget.name"
                                required
                                maxlength="120"
                                placeholder="contoh: Sheila on 7"
                                class="w-full h-[40px] px-14 bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-tertiary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-body transition-colors"
                            >
                            @if ($initialModal === 'edit')
                                @error('name')
                                    <p class="text-caption text-error">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="pt-16 border-t border-subtle flex items-center justify-end gap-12">
                            <button
                                type="button"
                                @click="closeAll()"
                                :disabled="submitting"
                                class="h-[40px] px-20 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-body font-medium transition-colors cursor-pointer disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="h-[40px] px-24 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-body font-medium transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <span x-show="!submitting">Simpan Perubahan</span>
                                <span x-show="submitting" x-cloak>Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Artis -->
        <div
            x-show="showDeleteModal"
            x-cloak
            class="fixed inset-0 z-modal overflow-y-auto"
            aria-labelledby="delete-artist-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-overlay backdrop-blur-sm" @click="closeAll()"></div>

            <div class="flex min-h-full items-center justify-center p-16 sm:p-24">
                <div class="relative w-full max-w-dialog rounded-lg bg-surface-1 border border-subtle p-24 text-left space-y-16">
                    <div>
                        <h2 id="delete-artist-title" class="text-heading font-medium text-ink leading-heading">
                            Hapus Artis
                        </h2>
                        <p class="text-body text-ink-secondary mt-8 leading-base">
                            Anda yakin ingin menghapus artis
                            <strong class="text-ink font-medium" x-text="deleteTarget.name"></strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>

                    <form
                        method="POST"
                        :action="'{{ url('/admin/artists') }}/' + deleteTarget.id"
                        @submit="submitting = true"
                        class="pt-16 border-t border-subtle flex items-center justify-end gap-12"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="button"
                            @click="closeAll()"
                            :disabled="submitting"
                            class="h-[40px] px-20 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-body font-medium transition-colors cursor-pointer disabled:opacity-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="h-[40px] px-20 rounded-sm bg-error-tint hover:bg-error/25 border border-error/40 text-error text-body font-medium transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <span x-show="!submitting">Hapus Artis</span>
                            <span x-show="submitting" x-cloak>Menghapus...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
