# Walkthrough: Pembuatan Migrations, Models, Controllers & Template Aturan ChordCue

Seluruh berkas database migrations, Eloquent models, resource controllers, dan dokumen aturan pengembangan telah berhasil dibuat dan diverifikasi untuk repositori **ChordCue** (Laravel 12).

---

## 1. Migrations (Urutan Dependensi & Skema)

Dibuat 9 migration baru dan 1 pembaruan pada migration bawaan Laravel:

| File Migration | Tabel Target | Keterangan |
|---|---|---|
| [0001_01_01_000000_create_users_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/0001_01_01_000000_create_users_table.php) | `users` | Disesuaikan dengan skema: `username`, `email`, `password`, `role` (enum: user, admin), `song_limit`, `timestamps` |
| [2026_10_14_000001_create_artists_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000001_create_artists_table.php) | `artists` | `id`, `name` |
| [2026_10_14_000002_create_categories_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000002_create_categories_table.php) | `categories` | `id`, `name` (unique), `type` (enum: mood, location, genre) |
| [2026_10_14_000003_create_songs_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000003_create_songs_table.php) | `songs` | `id`, `user_id` (FK), `title`, `artist_id` (FK), `default_key`, `bpm`, `time_signature_*`, `file_path`, `visibility` (enum: public, private), `timestamps` |
| [2026_10_14_000004_create_category_song_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000004_create_category_song_table.php) | `category_song` | Pivot table: `category_id` (FK), `song_id` (FK), composite primary key |
| [2026_10_14_000005_create_chords_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000005_create_chords_table.php) | `chords` | `id`, `name` (unique), `pronunciation`, `file_path` |
| [2026_10_14_000006_create_song_sections_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000006_create_song_sections_table.php) | `song_sections` | `id`, `song_id` (FK), `name`, `sequence` |
| [2026_10_14_000007_create_lyric_lines_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000007_create_lyric_lines_table.php) | `lyric_lines` | `id`, `section_id` (FK), `line_number`, `content` |
| [2026_10_14_000008_create_chord_placements_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000008_create_chord_placements_table.php) | `chord_placements` | `id`, `lyric_line_id` (FK), `chord_id` (FK), `position`, `start_beat` |
| [2026_10_14_000009_create_chord_diagrams_table.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/migrations/2026_10_14_000009_create_chord_diagrams_table.php) | `chord_diagrams` | `id` (Auto-increment), `chords_id` (FK), `file_path` |

---

## 2. Eloquent Models

Setiap model dikonfigurasi dengan `$fillable`, method modern `casts(): array`, dan relasi dua arah:

- [User.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/User.php) — Relasi `songs(): HasMany`
- [Artist.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/Artist.php) — `$timestamps = false`, relasi `songs(): HasMany`
- [Category.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/Category.php) — `$timestamps = false`, relasi `songs(): BelongsToMany`
- [Song.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/Song.php) — Relasi `user(): BelongsTo`, `artist(): BelongsTo`, `categories(): BelongsToMany`, `sections(): HasMany`
- [Chord.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/Chord.php) — `$timestamps = false`, relasi `placements(): HasMany`, `diagrams(): HasMany`
- [SongSection.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/SongSection.php) — `$timestamps = false`, relasi `song(): BelongsTo`, `lyricLines(): HasMany`
- [LyricLine.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/LyricLine.php) — `$timestamps = false`, relasi `section(): BelongsTo`, `chordPlacements(): HasMany`
- [ChordPlacement.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/ChordPlacement.php) — `$timestamps = false`, relasi `lyricLine(): BelongsTo`, `chord(): BelongsTo`
- [ChordDiagram.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/ChordDiagram.php) — `$timestamps = false`, relasi `chord(): BelongsTo`

---

## 3. Resource Controllers (Template Lengkap 7 Method)

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

## 4. File Template & Aturan Pengembangan

Dibuat 2 berkas yang menjadi acuan standar dan aturan tetap untuk proyek ini:

1. [TEMPLATE_ATURAN.md](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/TEMPLATE_ATURAN.md) — Diletakkan di root project, memuat standar arsitektur, checklist wajib sebelum modifikasi kode, skema relasi, serta **template kosongan (blueprint)** untuk Model, Controller (Resource), dan Migration.
2. [.agents/rules/CHORDCUE_RULES.md](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/.agents/rules/CHORDCUE_RULES.md) — Didaftarkan sebagai **Antigravity Rule**, otomatis dimuat oleh asisten AI pada setiap sesi di project ini.

---

## 5. Hasil Verifikasi

1. **Linting & Sintaks PHP**: Seluruh berkas Model, Migration, dan Controller telah dites dengan `php -l` dengan hasil **0 error**.
2. **Status Migrasi**: `php artisan migrate:status` mendeteksi seluruh migrasi dalam urutan dependensi yang benar.
3. **Refleksi Model & Relasi**: `php artisan model:show Song`, `php artisan model:show User`, dan `php artisan model:show Chord` berhasil membaca seluruh atribut, cast, dan relasi Eloquent.
