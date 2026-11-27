# Arsip Walkthrough Awal Pengembangan (Oktober–November 2026)

> [!NOTE]
> Dokumen historis gabungan dari folder lama `walkthrough-by-AI/`. Isinya mencatat tahap awal (migration, model, auth) dan **sebagian sudah usang**:
> - `ChordDiagramController`, relasi `SongSection::lyricLines()` dan `LyricLine::chordPlacements()` sudah dihapus (relasi aktif: `lines()` dan `placements()`).
> - `DESIGN_SYSTEM.md` sudah digantikan `01-docs/design-system/DESIGN_GUIDE.md` (versi lama ada di `arsip/DESIGN_SYSTEM_OLD.md`).
> - Skema SQL acuan sekarang `01-docs/spesifikasi/chordcue-10table-schema.sql`.

---
## Walkthrough: Pembuatan Migrations, Models, Controllers

Seluruh berkas database migrations, Eloquent models, resource controllers, dan dokumen aturan pengembangan telah berhasil dibuat dan diverifikasi untuk repositori **ChordCue** (Laravel 12).

> **Terakhir diperbarui**: 27 Oktober 2026 — Disesuaikan dengan skema SQL terbaru (`01-docs/chordcue 10 table utama.sql`)

---

### 1. Migrations (Urutan Dependensi & Skema)

Dibuat 9 migration baru dan 1 pembaruan pada migration bawaan Laravel:

| File Migration | Tabel Target | Keterangan |
|---|---|---|
| [0001_01_01_000000_create_users_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/0001_01_01_000000_create_users_table.php) | `users` | `username`, `email`/`password` (255), `role` ENUM, `song_limit` UNSIGNED, `remember_token`, `timestamps` |
| [2026_10_14_000001_create_artists_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000001_create_artists_table.php) | `artists` | `id`, `name` (unique) |
| [2026_10_14_000002_create_categories_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000002_create_categories_table.php) | `categories` | `id`, `name` (unique), `type` (enum: mood, location, genre) |
| [2026_10_14_000003_create_songs_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000003_create_songs_table.php) | `songs` | `id`, `user_id` FK, `title`, `artist_id` FK, `default_key`, `bpm` UNSIGNED, `time_signature_*` TINYINT UNSIGNED DEFAULT 4, `backing_track_path`, `backing_track_offset` DECIMAL(6,3), `visibility` ENUM, `timestamps` |
| [2026_10_14_000004_create_category_song_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000004_create_category_song_table.php) | `category_song` | Pivot table: `category_id` FK, `song_id` FK, composite PK |
| [2026_10_14_000005_create_chords_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000005_create_chords_table.php) | `chords` | `id`, `name` (unique), `pronunciation`, `file_path` |
| [2026_10_14_000006_create_song_sections_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000006_create_song_sections_table.php) | `song_sections` | `id`, `song_id` FK, `name`, `sequence` |
| [2026_10_14_000007_create_lyric_lines_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000007_create_lyric_lines_table.php) | `lyric_lines` | `id`, `song_section_id` FK (bukan `section_id`), `line_number`, `content` |
| [2026_10_14_000008_create_chord_placements_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000008_create_chord_placements_table.php) | `chord_placements` | `id`, `lyric_line_id` FK, `chord_id` FK, `position` nullable, `start_beat` nullable |
| [2026_10_14_000009_create_chord_diagrams_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000009_create_chord_diagrams_table.php) | `chord_diagrams` | `id` (Auto-increment), `chord_id` FK (bukan `chords_id`), `file_path` |

---

### 2. Eloquent Models

Setiap model dikonfigurasi dengan `$fillable`, method modern `casts(): array`, dan relasi dua arah:

- [User.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/User.php) — Relasi `songs(): HasMany`
- [Artist.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/Artist.php) — `$timestamps = false`, relasi `songs(): HasMany`
- [Category.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/Category.php) — `$timestamps = false`, relasi `songs(): BelongsToMany`
- [Song.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/Song.php) — fillable: `backing_track_path` + `backing_track_offset`, relasi `user()`, `artist()`, `categories()`, `sections()`
- [Chord.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/Chord.php) — `$timestamps = false`, relasi `placements(): HasMany`, `diagrams(): HasMany`
- [SongSection.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/SongSection.php) — `$timestamps = false`, relasi `song()`, `lyricLines()` (FK: `song_section_id`)
- [LyricLine.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/LyricLine.php) — `$timestamps = false`, fillable: `song_section_id`, relasi `section()` (FK: `song_section_id`), `chordPlacements()`
- [ChordPlacement.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/ChordPlacement.php) — `$timestamps = false`, relasi `lyricLine()`, `chord()`
- [ChordDiagram.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/ChordDiagram.php) — `$timestamps = false`, fillable: `chord_id`, relasi `chord()` (FK: `chord_id`)

---

### 3. Resource Controllers (Template Lengkap 7 Method)

Dibuat menggunakan `php artisan make:controller --resource`:

- [ArtistController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/ArtistController.php)
- [CategoryController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/CategoryController.php)
- [SongController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/SongController.php)
- [ChordController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/ChordController.php)
- [SongSectionController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/SongSectionController.php)
- [LyricLineController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/LyricLineController.php)
- [ChordPlacementController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/ChordPlacementController.php)
- [ChordDiagramController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/ChordDiagramController.php)
- [UserController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/UserController.php)

---

### 4. Hasil Verifikasi

1. **Linting & Sintaks PHP**: Seluruh berkas Model dan Migration dites dengan `php -l` — **0 error**.
2. **Status Migrasi**: `php artisan migrate:status` mendeteksi seluruh 12 migration dalam urutan dependensi yang benar.
3. **Refleksi Model & Relasi**: `php artisan model:show Song`, `model:show User`, `model:show Chord` berhasil membaca atribut, cast, dan relasi.

---

### 5. Perubahan dari SQL Versi Sebelumnya (14 Okt → 27 Okt 2026)

| File | Perubahan |
|---|---|
| `artists` migration | Tambah `unique()` pada `name` |
| `users` migration | `email`/`password` → `VARCHAR(255)`, `song_limit` → UNSIGNED, tambah `remember_token` |
| `songs` migration | `bpm` → UNSIGNED, `time_signature_*` → TINYINT UNSIGNED NOT NULL DEFAULT 4, `file_path` → `backing_track_path`, tambah `backing_track_offset DECIMAL(6,3)` |
| `lyric_lines` migration | FK `section_id` → `song_section_id` |
| `chord_placements` migration | `position` & `start_beat` → nullable |
| `chord_diagrams` migration | FK `chords_id` → `chord_id` |
| `Song` model | Update fillable & casts sesuai kolom baru |
| `LyricLine` model | fillable `song_section_id`, relasi FK diupdate |
| `SongSection` model | `lyricLines()` FK diupdate ke `song_section_id` |
| `ChordDiagram` model | fillable `chord_id`, relasi FK diupdate |

---

## Walkthrough: Login, Register, Profile Page, dan Blank Homepage (ChordCue)

Seluruh implementasi untuk halaman **Login**, **Register**, **Profile Page**, dan **Blank Homepage** telah selesai dikerjakan sesuai aturan arsitektur [CHORDCUE_RULES.md](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/.agents/rules/CHORDCUE_RULES.md) dan sistem desain [DESIGN_SYSTEM.md](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/.agents/rules/DESIGN_SYSTEM.md).

---

### 1. Perubahan yang Dilakukan

### A. Design System & Frontend Foundation
- [tailwind.config.js](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/tailwind.config.js): Mengganti konfigurasi default Tailwind dengan pemetaan token murni sesuai `DESIGN_SYSTEM.md` §14.2 (palet bawaan dihapus, ukuran berbasis token piksel, tanpa bayangan/gradien).
- [resources/css/app.css](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/css/app.css): Mengimpor `./tokens.css` di baris pertama, mendefinisikan `@layer base` (body latar `bg-base` dan outline ring fokus), serta styling `.ui-progress` dan `.ui-slider`.
- [resources/views/components/icon.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/icon.blade.php): Komponen icon Tabler outline SVG (`music`, `user`, `mail`, `lock`, `check`, `alert-circle`, `trash`, `logout`, `chevron-down`, `x`, dll.) tanpa ketergantungan library pihak ketiga.
- **Komponen Blade Breeze Direstyle**:
  - [primary-button.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/primary-button.blade.php): `bg-accent hover:bg-accent-hover text-accent-on h-control px-14 py-8 rounded-sm`
  - [secondary-button.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/secondary-button.blade.php): `bg-transparent hover:bg-surface-2 border border-subtle text-ink`
  - [danger-button.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/danger-button.blade.php): `bg-transparent hover:bg-error-tint border border-error text-error`
  - [text-input.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/text-input.blade.php): `bg-surface-3 border border-subtle text-ink placeholder-ink-tertiary focus:border-accent`
  - [input-label.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/input-label.blade.php), [input-error.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/input-error.blade.php), [modal.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/modal.blade.php), [dropdown.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/dropdown.blade.php), [dropdown-link.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/dropdown-link.blade.php), [nav-link.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/nav-link.blade.php), [responsive-nav-link.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/responsive-nav-link.blade.php), [application-logo.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/application-logo.blade.php).
  - [layouts/guest.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/layouts/guest.blade.php) & [layouts/app.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/layouts/app.blade.php): Menghapus CDN Google/Bunny fonts eksternal dan menetapkan Dark Theme murni.
  - [layouts/navigation.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/layouts/navigation.blade.php): Menampilkan navbar dengan brand ChordCue, username `Auth::user()->username`, dan menu responsive.

### B. Backend Logic & Model
- [app/Models/User.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/User.php): Menambahkan method `deleteWithSongs()` untuk menghapus seluruh lagu dan file fisik secara eksplisit.
- [app/Models/Song.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/Song.php): Menambahkan method `deleteWithFiles()` untuk menghapus backing track fisik sebelum record dihapus.
- [RegisteredUserController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/Auth/RegisteredUserController.php):
  - Mengganti validasi `name` menjadi `username` (unik, max 120, alpha_dash).
  - Alur linear terstruktur: `1. Otorisasi & Validasi Input`, `2. Query Database`, `3. Response / Render View`.
  - Dilengkapi dokumentasi PHPDoc bahasa Indonesia.
- [ProfileUpdateRequest.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Requests/ProfileUpdateRequest.php): Memvalidasi `username` dan `email` unik dengan mengabaikan ID user yang sedang login.
- [ProfileController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/ProfileController.php):
  - Menghapus referensi `email_verified_at` yang tidak ada pada database.
  - Menggunakan `$user->deleteWithSongs()` pada aksi hapus akun.
- [routes/web.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/routes/web.php): Menamai route root `home`, menghapus middleware `verified` pada `/dashboard`, dan mengarahkan dashboard ke beranda.

### C. Halaman Pengguna (Views)
- [resources/views/welcome.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/welcome.blade.php): Halaman beranda awal (*blank homepage*) yang bersih dengan Dark Blue Design System, navbar navigasi dinamis (status guest vs auth), serta kanvas sambutan yang siap untuk integrasi Katalog Lagu.
- [resources/views/auth/login.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/auth/login.blade.php): Formulir login (email, password, remember me, link register, link forgot password) dengan gaya kartu `bg-surface-1 border border-subtle rounded-lg`.
- [resources/views/auth/register.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/auth/register.blade.php): Formulir pendaftaran akun (username, email, kata sandi, konfirmasi kata sandi) dengan umpan balik error token semantik.
- [resources/views/profile/edit.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/profile/edit.blade.php) & partials: Formulir pembaruan data profil (`username` & `email`), pembaruan kata sandi, dan modal hapus akun dengan tombol konfirmasi bahaya (*danger button*).

### D. Testing & Factories
- [database/factories/UserFactory.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/factories/UserFactory.php): Menghasilkan `username` unik dan menghapus kolom `name` serta `email_verified_at`.
- [tests/Feature/Auth/RegistrationTest.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/tests/Feature/Auth/RegistrationTest.php): Diperbarui untuk menguji pendaftaran dengan field `username`.
- [tests/Feature/Auth/AuthenticationTest.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/tests/Feature/Auth/AuthenticationTest.php): Diperbarui untuk redirect ke rute `home`.
- [tests/Feature/ProfileTest.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/tests/Feature/ProfileTest.php): Diperbarui untuk menguji pembaruan field `username`.
- [tests/Feature/Auth/EmailVerificationTest.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/tests/Feature/Auth/EmailVerificationTest.php): Ditandai skipped karena verifikasi email out-of-scope pada spesifikasi ChordCue.

---

### 2. Hasil Verifikasi

### A. Pengujian Otomatis (Pest PHP)
```
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\Auth\AuthenticationTest (4 tests)
   WARN  Tests\Feature\Auth\EmailVerificationTest (1 skipped: intentionally out-of-scope)
   PASS  Tests\Feature\Auth\PasswordConfirmationTest (3 tests)
   PASS  Tests\Feature\Auth\PasswordResetTest (4 tests)
   PASS  Tests\Feature\Auth\PasswordUpdateTest (2 tests)
   PASS  Tests\Feature\Auth\RegistrationTest (2 tests)
   PASS  Tests\Feature\ExampleTest (1 test)
   PASS  Tests\Feature\ProfileTest (4 tests)

Tests:    1 skipped, 21 passed (50 assertions)
Duration: 27.54s
```

### B. Kompilasi Aset Frontend (Vite)
```
✓ 59 modules transformed.
public/build/manifest.json              0.33 kB │ gzip:  0.17 kB
public/build/assets/app-CSVh3feE.css   21.80 kB │ gzip:  4.98 kB
public/build/assets/app-WC-ZjLzv.js   106.90 kB │ gzip: 38.68 kB
✓ built in 3.34s
```

### C. Pengecekan Sintaks PHP (Lint)
Seluruh berkas PHP baru dan yang dimodifikasi telah diperiksa dengan `php -l` tanpa kesalahan sintaks.

