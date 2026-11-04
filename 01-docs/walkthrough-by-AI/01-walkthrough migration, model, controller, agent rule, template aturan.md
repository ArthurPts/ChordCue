# Walkthrough: Pembuatan Migrations, Models, Controllers

Seluruh berkas database migrations, Eloquent models, resource controllers, dan dokumen aturan pengembangan telah berhasil dibuat dan diverifikasi untuk repositori **ChordCue** (Laravel 12).

> **Terakhir diperbarui**: 27 Oktober 2026 — Disesuaikan dengan skema SQL terbaru (`01-docs/chordcue 10 table utama.sql`)

---

## 1. Migrations (Urutan Dependensi & Skema)

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

## 2. Eloquent Models

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

## 4. Hasil Verifikasi

1. **Linting & Sintaks PHP**: Seluruh berkas Model dan Migration dites dengan `php -l` — **0 error**.
2. **Status Migrasi**: `php artisan migrate:status` mendeteksi seluruh 12 migration dalam urutan dependensi yang benar.
3. **Refleksi Model & Relasi**: `php artisan model:show Song`, `model:show User`, `model:show Chord` berhasil membaca atribut, cast, dan relasi.

---

## 5. Perubahan dari SQL Versi Sebelumnya (14 Okt → 27 Okt 2026)

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
