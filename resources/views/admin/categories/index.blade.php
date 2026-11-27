<x-app-layout>
    @php
        $initialModal = old('_modal', '');
        $initialEditId = old('_edit_id', '');
        $initialEditName = old('_modal') === 'edit' ? old('name', '') : '';
        $initialEditType = old('_modal') === 'edit' ? old('type', $activeType) : $activeType;
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
                type: @js($initialEditType),
            },
            deleteTarget: {
                id: null,
                name: '',
                songsCount: 0,
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
                    type: item.type,
                };
                this.showEditModal = true;
            },
            openDelete(item) {
                this.submitting = false;
                this.deleteTarget = {
                    id: item.id,
                    name: item.name,
                    songsCount: Number(item.songsCount || 0),
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
        class="max-w-content mx-auto w-full px-16 md:px-24 py-24 sm:py-32 pb-[88px] sm:pb-[88px] mb-24"
    >
        <!-- Header & Primary Action -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-16">
            <div>
                <h1 class="text-display font-medium text-ink leading-heading">
                    Kelola Kategori
                </h1>
                <p class="text-body text-ink-secondary mt-4">
                    {{ $activeMeta['description'] }}
                </p>
            </div>

            <button
                type="button"
                @click="openCreate()"
                class="h-[40px] px-20 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-body font-medium inline-flex items-center justify-center gap-8 transition-colors cursor-pointer shrink-0"
            >
                <x-icon name="plus" class="w-icon-control h-icon-control shrink-0" />
                <span>Tambah {{ $activeMeta['label'] }}</span>
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

        <!-- Segmented Type Selector (Genre / Mood / Lokasi) -->
        <nav aria-label="Pilih tipe kategori" class="mt-20 p-6 rounded-md bg-surface-1 border border-subtle grid grid-cols-1 sm:grid-cols-3 gap-6">
            @foreach ($categoryTypes as $typeKey => $meta)
                @php
                    $isActive = ($activeType === $typeKey);
                @endphp
                <a
                    href="{{ route('admin.categories.index', ['type' => $typeKey]) }}"
                    aria-current="{{ $isActive ? 'page' : 'false' }}"
                    class="flex items-center justify-between gap-12 px-14 py-12 rounded-sm border transition-colors {{ $isActive ? 'bg-accent-soft-bg border-accent/40 text-accent-soft-text' : 'bg-surface-2/40 hover:bg-surface-2 border-transparent hover:border-subtle text-ink-secondary hover:text-ink' }}"
                >
                    <div class="flex items-center gap-10 min-w-0">
                        <div class="w-[32px] h-[32px] rounded-sm flex items-center justify-center shrink-0 {{ $isActive ? 'bg-accent text-accent-on' : 'bg-surface-3 border border-subtle text-ink-secondary' }}">
                            <x-icon name="{{ $meta['icon'] }}" class="w-icon-control h-icon-control" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-nav-title font-medium truncate {{ $isActive ? 'text-ink' : 'text-ink-secondary' }}">
                                {{ $meta['label'] }}
                            </div>
                            <div class="text-caption truncate {{ $isActive ? 'text-accent-soft-text' : 'text-ink-tertiary' }}">
                                Daftar {{ $meta['singular'] }}
                            </div>
                        </div>
                    </div>

                    <span class="px-10 py-4 rounded-full font-mono text-caption font-medium shrink-0 {{ $isActive ? 'bg-accent text-accent-on' : 'bg-surface-3 border border-subtle text-ink-secondary' }}">
                        {{ $typeCounts[$typeKey] ?? 0 }}
                    </span>
                </a>
            @endforeach
        </nav>

        <!-- Search Toolbar -->
        <form method="GET" action="{{ route('admin.categories.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-12 mt-20 p-14 sm:p-16 rounded-lg bg-surface-1 border border-subtle">
            <input type="hidden" name="type" value="{{ $activeType }}">

            <div class="relative flex-1">
                <button
                    type="submit"
                    class="absolute inset-y-0 left-0 pl-14 flex items-center text-ink-secondary hover:text-accent transition-colors cursor-pointer"
                    aria-label="Cari {{ $activeMeta['singular'] }}"
                >
                    <x-icon name="search" class="w-icon-control h-icon-control" />
                </button>
                <input
                    type="text"
                    name="q"
                    value="{{ $searchKeyword }}"
                    placeholder="{{ $activeMeta['search_hint'] }}"
                    aria-label="{{ $activeMeta['search_hint'] }}"
                    class="w-full h-[40px] pl-[38px] pr-[36px] bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-tertiary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-body transition-colors"
                >
                @if ($searchKeyword !== '')
                    <a
                        href="{{ route('admin.categories.index', ['type' => $activeType]) }}"
                        class="absolute inset-y-0 right-0 pr-14 flex items-center text-ink-secondary hover:text-ink transition-colors"
                        aria-label="Hapus pencarian"
                    >
                        <x-icon name="x" class="w-icon-chip h-icon-chip" />
                    </a>
                @endif
            </div>

            <div class="flex items-center justify-between sm:justify-end gap-12 shrink-0">
                <span class="text-caption font-mono text-ink-tertiary">
                    {{ $categories->total() }} {{ $activeMeta['singular'] }}
                </span>
                <button
                    type="submit"
                    class="h-[40px] px-16 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-body font-medium transition-colors cursor-pointer"
                >
                    Cari
                </button>
            </div>
        </form>

        <!-- Reusable Category Data Table -->
        <div class="mt-20 bg-surface-1 border border-subtle rounded-lg overflow-hidden">
            @if ($categories->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-subtle bg-surface-2/60 text-ink-secondary text-caption font-medium">
                                <th scope="col" class="py-12 px-16 sm:px-20">Nama {{ $activeMeta['label'] }}</th>
                                <th scope="col" class="py-12 px-16 sm:px-20">Tipe</th>
                                <th scope="col" class="py-12 px-16 sm:px-20 font-mono">Digunakan di Lagu</th>
                                <th scope="col" class="py-12 px-16 sm:px-20 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-subtle/50">
                            @foreach ($categories as $category)
                                <tr class="hover:bg-surface-2/40 transition-colors">
                                    <td class="py-14 px-16 sm:px-20 whitespace-nowrap">
                                        <div class="flex items-center gap-10">
                                            <div class="w-[32px] h-[32px] rounded-sm bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                                                <x-icon name="{{ $activeMeta['icon'] }}" class="w-icon-control h-icon-control" />
                                            </div>
                                            <span class="text-body font-medium text-ink">{{ $category->name }}</span>
                                        </div>
                                    </td>

                                    <td class="py-14 px-16 sm:px-20 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-6 px-10 py-4 rounded-full text-caption font-medium border border-accent/40 bg-accent-soft-bg text-accent-soft-text">
                                            {{ $activeMeta['label'] }}
                                        </span>
                                    </td>

                                    <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-body font-mono text-ink-secondary">
                                        {{ $category->songs_count }} lagu
                                    </td>

                                    <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-8">
                                            <button
                                                type="button"
                                                @click="openEdit({
                                                    id: {{ $category->id }},
                                                    name: @js($category->name),
                                                    type: @js($category->type)
                                                })"
                                                class="h-[32px] px-12 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-caption font-medium inline-flex items-center gap-6 transition-colors cursor-pointer"
                                            >
                                                <x-icon name="pencil" class="w-icon-chip h-icon-chip text-ink-secondary" />
                                                <span>Edit</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDelete({
                                                    id: {{ $category->id }},
                                                    name: @js($category->name),
                                                    songsCount: {{ $category->songs_count }}
                                                })"
                                                class="h-[32px] px-12 rounded-sm bg-error-tint/70 hover:bg-error-tint border border-error/30 text-error text-caption font-medium inline-flex items-center gap-6 transition-colors cursor-pointer"
                                            >
                                                <x-icon name="trash" class="w-icon-chip h-icon-chip" />
                                                <span>Hapus</span>
                                            </button>
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
                        <x-icon name="{{ $activeMeta['icon'] }}" class="w-icon-nav h-icon-nav" />
                    </div>
                    @if ($searchKeyword !== '')
                        <h2 class="text-title font-medium text-ink">
                            {{ $activeMeta['label'] }} tidak ditemukan
                        </h2>
                        <p class="text-body text-ink-secondary mt-6">
                            Tidak ada {{ $activeMeta['singular'] }} yang cocok dengan kata kunci "{{ $searchKeyword }}".
                        </p>
                        <div class="mt-16 flex items-center justify-center gap-10">
                            <a
                                href="{{ route('admin.categories.index', ['type' => $activeType]) }}"
                                class="h-[40px] px-20 rounded-sm bg-surface-2 hover:bg-surface-3 border border-subtle text-ink text-body font-medium inline-flex items-center justify-center transition-colors"
                            >
                                Reset Pencarian
                            </a>
                        </div>
                    @else
                        <h2 class="text-title font-medium text-ink">
                            Belum ada {{ $activeMeta['label'] }}
                        </h2>
                        <p class="text-body text-ink-secondary mt-6">
                            Tambahkan {{ $activeMeta['singular'] }} pertama agar dapat dipilih pada katalog dan editor lagu.
                        </p>
                        <div class="mt-16">
                            <button
                                type="button"
                                @click="openCreate()"
                                class="h-[40px] px-20 rounded-sm bg-accent hover:bg-accent-hover text-accent-on text-body font-medium inline-flex items-center gap-8 transition-colors cursor-pointer"
                            >
                                <x-icon name="plus" class="w-icon-control h-icon-control" />
                                <span>Tambah {{ $activeMeta['label'] }}</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Pagination -->
        <x-pagination :paginator="$categories" />

        <!-- Modal Tambah Kategori -->
        <div
            x-show="showCreateModal"
            x-cloak
            class="fixed inset-0 z-modal overflow-y-auto"
            aria-labelledby="create-category-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-overlay backdrop-blur-sm" @click="closeAll()"></div>

            <div class="flex min-h-full items-center justify-center p-16 sm:p-24">
                <div class="relative w-full max-w-dialog rounded-lg bg-surface-1 border border-subtle p-24 text-left">
                    <div class="flex items-start justify-between gap-12">
                        <div>
                            <h2 id="create-category-title" class="text-heading font-medium text-ink leading-heading">
                                Tambah {{ $activeMeta['label'] }}
                            </h2>
                            <p class="text-body text-ink-secondary mt-4">
                                Tambahkan data {{ $activeMeta['singular'] }} baru ke tabel kategori master.
                            </p>
                        </div>
                        <span class="px-10 py-4 rounded-full text-caption font-medium border border-accent/40 bg-accent-soft-bg text-accent-soft-text shrink-0">
                            {{ $activeMeta['label'] }}
                        </span>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.categories.store') }}"
                        @submit="submitting = true"
                        class="mt-20 space-y-16"
                    >
                        @csrf
                        <input type="hidden" name="_modal" value="create">
                        <input type="hidden" name="type" value="{{ $activeType }}">

                        <div class="space-y-6">
                            <label for="create_category_name" class="block text-caption font-medium text-ink-secondary">
                                Nama {{ $activeMeta['label'] }}
                            </label>
                            <input
                                id="create_category_name"
                                type="text"
                                name="name"
                                value="{{ $initialCreateName }}"
                                required
                                maxlength="120"
                                placeholder="{{ $activeMeta['placeholder'] }}"
                                class="w-full h-[40px] px-14 bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-tertiary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-body transition-colors"
                            >
                            @if ($initialModal === 'create')
                                @error('name')
                                    <p class="text-caption text-error">{{ $message }}</p>
                                @enderror
                                @error('type')
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
                                <span x-show="!submitting">Simpan {{ $activeMeta['label'] }}</span>
                                <span x-show="submitting" x-cloak>Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Edit Kategori -->
        <div
            x-show="showEditModal"
            x-cloak
            class="fixed inset-0 z-modal overflow-y-auto"
            aria-labelledby="edit-category-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-overlay backdrop-blur-sm" @click="closeAll()"></div>

            <div class="flex min-h-full items-center justify-center p-16 sm:p-24">
                <div class="relative w-full max-w-dialog rounded-lg bg-surface-1 border border-subtle p-24 text-left">
                    <div>
                        <h2 id="edit-category-title" class="text-heading font-medium text-ink leading-heading">
                            Edit {{ $activeMeta['label'] }}
                        </h2>
                        <p class="text-body text-ink-secondary mt-4">
                            Perubahan nama atau tipe langsung diterapkan pada semua lagu yang menggunakan kategori ini.
                        </p>
                    </div>

                    <form
                        method="POST"
                        :action="'{{ url('/admin/categories') }}/' + editTarget.id"
                        @submit="submitting = true"
                        class="mt-20 space-y-16"
                    >
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_modal" value="edit">
                        <input type="hidden" name="_edit_id" :value="editTarget.id">

                        <div class="space-y-6">
                            <label for="edit_category_name" class="block text-caption font-medium text-ink-secondary">
                                Nama Kategori
                            </label>
                            <input
                                id="edit_category_name"
                                type="text"
                                name="name"
                                x-model="editTarget.name"
                                required
                                maxlength="120"
                                placeholder="{{ $activeMeta['placeholder'] }}"
                                class="w-full h-[40px] px-14 bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-tertiary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-body transition-colors"
                            >
                            @if ($initialModal === 'edit')
                                @error('name')
                                    <p class="text-caption text-error">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="space-y-6">
                            <label for="edit_category_type" class="block text-caption font-medium text-ink-secondary">
                                Tipe Kategori
                            </label>
                            <div class="relative">
                                <select
                                    id="edit_category_type"
                                    name="type"
                                    x-model="editTarget.type"
                                    class="w-full h-[40px] px-14 pr-[36px] bg-surface-2 border border-subtle rounded-sm text-ink text-body appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                                >
                                    @foreach ($categoryTypes as $typeKey => $meta)
                                        <option value="{{ $typeKey }}">{{ $meta['label'] }}</option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                                    <x-icon name="chevron-down" class="w-icon-control h-icon-control" />
                                </div>
                            </div>
                            @if ($initialModal === 'edit')
                                @error('type')
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

        <!-- Modal Konfirmasi Hapus Kategori -->
        <div
            x-show="showDeleteModal"
            x-cloak
            class="fixed inset-0 z-modal overflow-y-auto"
            aria-labelledby="delete-category-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-overlay backdrop-blur-sm" @click="closeAll()"></div>

            <div class="flex min-h-full items-center justify-center p-16 sm:p-24">
                <div class="relative w-full max-w-dialog rounded-lg bg-surface-1 border border-subtle p-24 text-left space-y-16">
                    <div>
                        <h2 id="delete-category-title" class="text-heading font-medium text-ink leading-heading">
                            Hapus {{ $activeMeta['label'] }}
                        </h2>
                        <p class="text-body text-ink-secondary mt-8 leading-base">
                            Anda yakin ingin menghapus {{ $activeMeta['singular'] }}
                            <strong class="text-ink font-medium" x-text="deleteTarget.name"></strong>?
                        </p>
                        <template x-if="deleteTarget.songsCount > 0">
                            <p class="text-caption text-warning mt-10 p-10 rounded-sm bg-warning-tint border border-warning/30">
                                Kategori ini sedang digunakan pada <span class="font-mono" x-text="deleteTarget.songsCount"></span> lagu dan akan dilepas dari lagu-lagu tersebut tanpa menghapus lagunya.
                            </p>
                        </template>
                    </div>

                    <form
                        method="POST"
                        :action="'{{ url('/admin/categories') }}/' + deleteTarget.id"
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
                            <span x-show="!submitting">Hapus {{ $activeMeta['label'] }}</span>
                            <span x-show="submitting" x-cloak>Menghapus...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
