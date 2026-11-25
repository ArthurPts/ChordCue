# Audit & Rencana Penataan File ChordCue (Project A)

> **Status Audit:** READ-ONLY selesai. Belum ada file kode, asset, atau dokumen aplikasi yang dipindahkan, dihapus, atau diedit.
> **Batasan Keras:** Folder `app/` **TIDAK disentuh** (hanya dibaca untuk melacak referensi/impor).

---

## 0. Aturan Project A yang Memengaruhi Organisasi File

Berdasarkan pembacaan `.agents/rules/CHORDCUE_RULES.md`, `.agents/rules/design-rules.md`, `01-docs/design-system/DESIGN_GUIDE.md`, dan `.agents/skills/frontend-design/SKILL.md`:

| Sumber Aturan | Bagian | Dampaknya terhadap Struktur & Penamaan File |
|---|---|---|
| `CHORDCUE_RULES.md` | §0 (Prinsip Dasar) | Menetapkan path sumber kebenaran dokumen: `01-docs/SPESIFIKASI_SISTEM_ChordCue.md`, `.agents/rules/design-rules.md`, `01-docs/design-system/DESIGN_GUIDE.md`, `resources/css/tokens.css`, dan `01-docs/design/tokens.json` — **path file acuan ini tidak boleh dipindah** tanpa memperbarui aturan. |
| `CHORDCUE_RULES.md` | §4 & §10 no. 1 | **Dilarang mengubah file migration lama** di `database/migrations/`. Perubahan skema hanya boleh lewat migration baru. |
| `CHORDCUE_RULES.md` | §7 (Konfigurasi & Storage) | Konfigurasi aplikasi ditempatkan di `config/chordcue.php`, `config/pronunciation.php`, dan `config/tts.php`. Asset upload/TTS disimpan di `storage/app/public/` dan diakses via `Storage::url()` (dengan symlink `public/storage`). |
| `CHORDCUE_RULES.md` | §8 (Frontend) | Mesin audio Vanilla JS **harus terpisah dari Alpine** (contoh path di aturan: `resources/js/audio/`). Komponen Breeze di `resources/views/components/` dipakai ulang. Ikon dipusatkan di `resources/views/components/icon.blade.php` (tanpa package ikon). Tidak memakai file font eksternal (`resources/fonts/` kosong karena pakai System Font Stack). |
| `CHORDCUE_RULES.md` | §10 no. 3, 6, 10, 11 | Dilarang install package baru tanpa izin; dilarang menghapus/mengubah tes hanya agar lulus; dilarang mengubah kode inti Breeze di luar penyesuaian §4 (`username`, tanpa `email_verified_at`); dilarang menambah/mengubah token desain tanpa izin (`tokens.css` & `tokens.json` harus sinkron). |
| `design-rules.md` | §1 no. 3 & §4 F | Menjaga integritas file di `tests/` dan mempertahankan struktur asli `<x-navbar />` (`resources/views/components/navbar.blade.php`). |
| `frontend-design/SKILL.md` | — | Fokus pada arah estetika, tipografi, dan copywriting UI; **tidak** menentukan struktur folder/penamaan file khusus. |

---

## 1. Struktur Saat Ini & Inventaris Lengkap (Di Luar `app/`)

### 1.1 Pohon Direktori Saat Ini (Di Luar `app/`, `vendor/`, `node_modules/`, `.git/`, `public/build/`, `storage/framework|logs/`)

```text
chordcue/
├── .editorconfig
├── .env
├── .env.example
├── .gitattributes
├── .gitignore
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── package-lock.json
├── phpunit.xml
├── PLAN.md
├── postcss.config.js
├── README.md
├── tailwind.config.js
├── vite.config.js
├── .agents/
│   ├── rules/
│   │   ├── CHORDCUE_RULES.md
│   │   └── design-rules.md
│   └── skills/
│       └── frontend-design/
│           ├── LICENSE.txt
│           └── SKILL.md
├── 01-docs/
│   ├── beat-editor-ref.png
│   ├── catatan.txt
│   ├── chordcue-10table-schema.sql
│   ├── PROJECT_B_ANALYSIS.md
│   ├── simple-editor-ref.png
│   ├── SPESIFIKASI_SISTEM_ChordCue.md
│   ├── design/
│   │   ├── dark_blue_palette_music_companion.html
│   │   ├── tokens.json
│   │   └── ui-refactor-copy-old.txt
│   ├── design-system/
│   │   ├── design-changelog.md
│   │   ├── DESIGN_GUIDE.md
│   │   ├── DESIGN_SYSTEM_OLD.md
│   │   ├── ui-refactor-plan.md
│   │   └── mockups/
│   │       ├── player-option-a.html
│   │       └── player-option-b.html
│   └── walkthrough-by-AI/
│       ├── 01-walkthrough migration, model, controller, agent rule, template aturan.md
│       └── 02-Walkthrough Login, Register, Profile Page, dan Blank Homepage.md
├── bootstrap/
│   ├── app.php
│   └── providers.php
├── config/
│   ├── app.php, auth.php, cache.php, chordcue.php, database.php, filesystems.php,
│   ├── logging.php, mail.php, pronunciation.php, queue.php, services.php, session.php, tts.php
├── database/
│   ├── .gitignore
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/                      (12 file migrasi Laravel & ChordCue)
│   └── seeders/
│       ├── ArtistSeeder.php, CategorySeeder.php, ChordcueSeeder.php, ChordSeeder.php,
│       ├── DatabaseSeeder.php, DummyChordPlacementSeeder.php, DummySongSeeder.php, UserSeeder.php
│       ├── lyrics/dummy-lirik.txt
│       └── sql/dummy_chord_placements.sql
├── public/
│   ├── .htaccess
│   ├── favicon.ico                      (0 byte)
│   ├── index.php
│   ├── robots.txt
│   └── storage -> ../storage/app/public (symlink)
├── resources/
│   ├── css/
│   │   ├── app.css
│   │   └── tokens.css
│   ├── js/
│   │   ├── app.js
│   │   ├── beat-editor.js               <-- lepas di root js/
│   │   ├── bootstrap.js
│   │   ├── editor-shared.js             <-- lepas di root js/
│   │   ├── player.js                    <-- lepas di root js/
│   │   ├── simple-editor.js             <-- lepas di root js/
│   │   └── audio/
│   │       └── audio-engine.js
│   └── views/
│       ├── dashboard.blade.php          <-- tidak pernah dirender
│       ├── my-songs.blade.php
│       ├── welcome.blade.php
│       ├── admin/
│       │   ├── songs/index.blade.php
│       │   └── users/index.blade.php
│       ├── auth/
│       │   ├── confirm-password.blade.php, forgot-password.blade.php, login.blade.php,
│       │   ├── register.blade.php, reset-password.blade.php, verify-email.blade.php
│       ├── chords/
│       │   └── index.blade.php
│       ├── components/                  (22 file datar — campur Breeze primitives + domain songs/chords)
│       │   ├── application-logo.blade.php, auth-session-status.blade.php, danger-button.blade.php,
│       │   ├── dropdown.blade.php, dropdown-link.blade.php, icon.blade.php, input-error.blade.php,
│       │   ├── input-label.blade.php, modal.blade.php, nav-link.blade.php (0 refs),
│       │   ├── navbar.blade.php, pagination.blade.php, primary-button.blade.php,
│       │   ├── responsive-nav-link.blade.php, secondary-button.blade.php, text-input.blade.php,
│       │   ├── chord-card.blade.php, chord-diagram.blade.php, create-chord-modal.blade.php,
│       │   ├── edit-chord-modal.blade.php, song-card.blade.php, create-song-modal.blade.php
│       ├── layouts/
│       │   ├── app.blade.php
│       │   ├── guest.blade.php
│       │   └── navigation.blade.php     <-- 13 byte (`<x-navbar />`), 0 refs
│       ├── profile/
│       │   ├── edit.blade.php
│       │   └── partials/
│       │       ├── delete-user-form.blade.php
│       │       ├── update-password-form.blade.php
│       │       └── update-profile-information-form.blade.php
│       └── songs/
│           ├── editor-beat.blade.php
│           ├── editor-simple.blade.php
│           ├── player.blade.php
│           └── partials/
│               ├── editor-chord-sidebar.blade.php
│               └── editor-top-panel.blade.php
├── routes/
│   ├── auth.php
│   ├── console.php
│   └── web.php
├── storage/app/public/
│   ├── backing-tracks/
│   │   └── atnVSGrZY9yczKo8Y4NbGZQ31vmY6iGM2pqu0hSk.mp3  (dipakai song#24 di DB)
│   ├── chords/audio/                    (50 file .mp3; 48 tercatat di DB, 2 ekstra di disk)
│   └── diagrams/
│       └── No_Image_Available.jpg
└── tests/
    ├── Pest.php, TestCase.php
    ├── Feature/ (14 file) + Feature/Auth/ (6 file)
    ├── Unit/ (ExampleTest.php, MusicServicesTest.php)
    └── js/ (beat-grid.test.mjs, timing.test.mjs)
```

### 1.2 Inventaris Terkelompok Berdasarkan Tipe

| Kategori | File |
|---|---|
| **Blade Components (`resources/views/components/`)** | **Shared/Breeze UI (16):** `application-logo`, `auth-session-status`, `danger-button`, `dropdown`, `dropdown-link`, `icon`, `input-error`, `input-label`, `modal`, `nav-link` *(unused)*, `navbar`, `pagination`, `primary-button`, `responsive-nav-link`, `secondary-button`, `text-input`.<br>**Domain Chords (4):** `chord-card`, `chord-diagram`, `create-chord-modal`, `edit-chord-modal`.<br>**Domain Songs (2):** `song-card`, `create-song-modal`. |
| **Blade Layouts, Pages & Partials (`resources/views/`)** | **Layouts (3):** `layouts/app`, `layouts/guest`, `layouts/navigation` *(unused)*.<br>**Pages (14):** `welcome`, `my-songs`, `dashboard` *(unused)*, `chords/index`, `songs/player`, `songs/editor-simple`, `songs/editor-beat`, `admin/songs/index`, `admin/users/index`, `profile/edit`, `auth/{login,register,forgot-password,reset-password,confirm-password,verify-email}`.<br>**Partials (5):** `profile/partials/{delete-user-form,update-password-form,update-profile-information-form}`, `songs/partials/{editor-top-panel,editor-chord-sidebar}`. |
| **Alpine Components / Hooks (`resources/js/`)** | `app.js` (entrypoint), `bootstrap.js` (Breeze axios init), `player.js`, `editor-shared.js`, `simple-editor.js`, `beat-editor.js`. |
| **JS Audio Engine / Utils (`resources/js/audio/`)** | `resources/js/audio/audio-engine.js` (Vanilla JS Web Audio API scheduler + pure timing helpers `measureSpeechEnd`, `computeAnchors`, `computeChordScheduleTime`). *(Catatan: pure helpers `barOfBeat`, `buildBeatGrid` ada di `beat-editor.js`; `mergeEditorMixins`, `createSharedEditorState` di `editor-shared.js`.)* |
| **Styles (`resources/css/`)** | `resources/css/tokens.css` (86 CSS custom variables), `resources/css/app.css` (Tailwind layers + `.ui-progress` & `.ui-slider`). |
| **Types** | *Tidak ada* (proyek menggunakan JavaScript murni + JSDoc, tanpa TypeScript). |
| **Resources / Assets** | **Images:** `storage/app/public/diagrams/No_Image_Available.jpg`, `public/favicon.ico` (0B), `01-docs/{beat-editor-ref.png,simple-editor-ref.png}`.<br>**Audio:** `storage/app/public/chords/audio/*.mp3` (50 file), `storage/app/public/backing-tracks/atnVSGrZY9yczKo8Y4NbGZQ31vmY6iGM2pqu0hSk.mp3`.<br>**Fonts:** *Tidak ada* (System Font Stack sesuai `design-rules.md`).<br>**JSON / Data:** `01-docs/design/tokens.json`, `database/seeders/lyrics/dummy-lirik.txt`, `database/seeders/sql/dummy_chord_placements.sql`. |
| **Config & Tooling** | **Laravel `config/` (13):** `app`, `auth`, `cache`, `chordcue`, `database`, `filesystems`, `logging`, `mail`, `pronunciation`, `queue`, `services`, `session`, `tts`.<br>**Root/Build:** `vite.config.js`, `tailwind.config.js`, `postcss.config.js`, `package.json`, `composer.json`, `phpunit.xml`, `.env`, `.env.example`, `bootstrap/{app,providers}.php`. |
| **Routes, Database & Tests** | `routes/{web,auth,console}.php`, `database/factories/UserFactory.php`, 12 `database/migrations/*.php`, 8 `database/seeders/*.php`, 22 Pest tests (`tests/Feature`, `tests/Unit`) + 2 Node JS tests (`tests/js/*.mjs`). |
| **Docs & Agent Rules** | `.agents/rules/{CHORDCUE_RULES,design-rules}.md`, `.agents/skills/frontend-design/*`, `PLAN.md`, `README.md`, dan 17 file di `01-docs/`. |

---

## 1.3 Peta Dependensi (Siapa Mengimpor / Mereferensikan File Di Luar `app/`)

| File / Kelompok | Direferensikan / Diimpor Oleh (termasuk `app/`) |
|---|---|
| `resources/js/app.js` | `vite.config.js:7`, `resources/views/layouts/app.blade.php:11`, `resources/views/layouts/guest.blade.php:11` (`@vite`) |
| `resources/js/bootstrap.js` | `resources/js/app.js:1` (`import './bootstrap'`) |
| `resources/js/audio/audio-engine.js` | `resources/js/app.js:4`, `resources/js/player.js:1`, `tests/js/timing.test.mjs`, `tests/Feature/PlayerPageTest.php:266,400,425` (`resource_path`) |
| `resources/js/player.js` | `resources/js/app.js:5`, `resources/views/songs/player.blade.php:17` (`x-data="chordsheetPlayer(...)"`), `tests/Feature/PlayerPageTest.php:240,267,350,399,424` |
| `resources/js/editor-shared.js` | `resources/js/simple-editor.js:1`, `resources/js/beat-editor.js:1`, `tests/Feature/SimpleEditorTest.php:156` |
| `resources/js/simple-editor.js` | `resources/js/app.js:6`, `resources/views/songs/editor-simple.blade.php:3` (`x-data="simpleEditor(...)"`), `tests/Feature/SimpleEditorTest.php:155` |
| `resources/js/beat-editor.js` | `resources/js/app.js:7`, `resources/views/songs/editor-beat.blade.php:3` (`x-data="beatEditor(...)"`), `tests/js/beat-grid.test.mjs:3`, `tests/Feature/BeatEditorTest.php:130` |
| `resources/css/app.css` | `vite.config.js:7`, `resources/views/layouts/app.blade.php:11`, `resources/views/layouts/guest.blade.php:11` |
| `resources/css/tokens.css` | `resources/css/app.css:1` (`@import './tokens.css'`) |
| `resources/views/layouts/app.blade.php` | `app/View/Components/AppLayout.php:15`, semua 10 halaman ber-`<x-app-layout>` |
| `resources/views/layouts/guest.blade.php` | `app/View/Components/GuestLayout.php:15`, semua 6 halaman auth ber-`<x-guest-layout>` |
| `resources/views/layouts/navigation.blade.php` | **TIDAK ADA (0 referensi)** |
| `resources/views/dashboard.blade.php` | **TIDAK ADA (0 pemanggilan `view('dashboard')`; `routes/web.php:64` langsung redirect ke `home`)** |
| `resources/views/components/nav-link.blade.php` | **TIDAK ADA (0 referensi `<x-nav-link>`)** |
| `resources/views/components/song-card.blade.php` | `resources/views/welcome.blade.php:133,152`, `resources/views/my-songs.blade.php:126` |
| `resources/views/components/create-song-modal.blade.php` | `resources/views/my-songs.blade.php:155` |
| `resources/views/components/chord-card.blade.php` | `resources/views/chords/index.blade.php:109` |
| `resources/views/components/chord-diagram.blade.php` | `resources/views/components/chord-card.blade.php:30` |
| `resources/views/components/create-chord-modal.blade.php` | `resources/views/chords/index.blade.php:205` |
| `resources/views/components/edit-chord-modal.blade.php` | `resources/views/chords/index.blade.php:206` |
| `resources/views/components/navbar.blade.php` | `resources/views/layouts/app.blade.php:16` (plus file mati `layouts/navigation.blade.php:1`) |
| `resources/views/components/pagination.blade.php` | `welcome`, `my-songs`, `chords/index`, `admin/songs/index`, `admin/users/index` |
| `resources/views/components/icon.blade.php` | 22 file Blade (127 pemanggilan `<x-icon name="..." />`) |
| `resources/views/components/{application-logo,auth-session-status,danger-button,dropdown,dropdown-link,input-error,input-label,modal,primary-button,responsive-nav-link,secondary-button,text-input}.blade.php` | Halaman auth (`resources/views/auth/*`), `profile/partials/*`, dan `components/navbar.blade.php` |
| `resources/views/songs/partials/editor-top-panel.blade.php` | `songs/editor-simple.blade.php:6`, `songs/editor-beat.blade.php:6`, `tests/Feature/{Simple,Beat}EditorTest.php` |
| `resources/views/songs/partials/editor-chord-sidebar.blade.php` | `songs/editor-simple.blade.php:276`, `songs/editor-beat.blade.php:164`, `tests/Feature/{Simple,Beat}EditorTest.php` |
| `resources/views/songs/{player,editor-simple,editor-beat}.blade.php` | `app/Http/Controllers/SongController.php:38,121,143`, `tests/Feature/{PlayerPage,SimpleEditor,BeatEditor}Test.php` |
| `resources/views/auth/verify-email.blade.php` | `app/Http/Controllers/Auth/EmailVerificationPromptController.php:19` (terdaftar di `routes/auth.php:39`) |
| `config/chordcue.php` | `app/Http/Controllers/SongAudioController.php:30`, `app/Http/Controllers/SongController.php:296`, `resources/views/songs/player.blade.php:11-13`, `resources/views/songs/partials/editor-top-panel.blade.php:214`, `tests/Feature/PlayerPageTest.php` |
| `config/tts.php` | `app/Jobs/GenerateChordAudio.php`, `app/Models/Chord.php`, `database/seeders/ChordSeeder.php:86` |
| `config/pronunciation.php` | `app/Services/PronunciationGenerator.php:234` |
| `config/{app,auth,cache,database,filesystems,logging,mail,queue,services,session}.php` | Konvensi inti Laravel Framework (`config/app.php` juga dibaca di `layouts/{app,guest}.blade.php`; `config/filesystems.php` diuji di `PlayerPageTest.php`) |
| `routes/{web,auth,console}.php` | `bootstrap/app.php:9-10`, `routes/web.php:192` (`require __DIR__.'/auth.php'`) |
| `database/factories/UserFactory.php` | `app/Models/User.php:6,14` |
| `database/seeders/*.php` | `database/seeders/DatabaseSeeder.php`, `tests/Feature/DummyChordPlacementSeederTest.php` |

### 1.4 Relasi Resource / Asset & Cara Pemanggilannya

| Resource / Asset | Pemakai / Konsumen | Cara Referensi |
|---|---|---|
| `storage/app/public/diagrams/No_Image_Available.jpg` | `app/Http/Controllers/ChordController.php:79`, `app/Models/Chord.php:64,76`, `resources/views/chords/index.blade.php:183`, `resources/views/components/chord-diagram.blade.php:7`, `tests/Feature/ChordTest.php:17,94` | Path string DB (`diagrams/No_Image_Available.jpg`), `Storage::disk('public')->url(...)`, dan `asset('storage/diagrams/No_Image_Available.jpg')` |
| `storage/app/public/chords/audio/*.mp3` (48 file aktif di DB) | `app/Models/Chord.php` (`audio_url`), `app/Jobs/GenerateChordAudio.php`, `app/Http/Controllers/SongTimelineController.php`, `resources/js/audio/audio-engine.js` | Path dinamis dari `chords.file_path` → `Storage::disk(config('tts.disk'))->url(...)` → `fetch(url)` Web Audio API |
| `storage/app/public/chords/audio/{csharp7,dmaj7}.mp3` (2 file ekstra) | Tidak tercatat di `chords.file_path` pada DB MySQL saat ini | Kemungkinan sisa hasil tes transpose/TTS sebelumnya (**UNSURE**) |
| `storage/app/public/backing-tracks/atnVSGrZY9yczKo8Y4NbGZQ31vmY6iGM2pqu0hSk.mp3` | `songs` ID #24 (`test song`) di MySQL lokal → `SongController::prepareEditorViewData()`, `SongTimelineController::show()` | Path dinamis dari `songs.backing_track_path` → `Storage::disk('public')->url(...)` |
| `database/seeders/lyrics/dummy-lirik.txt` | `database/seeders/ChordcueSeeder.php:69` | `database_path('seeders/lyrics/dummy-lirik.txt')` |
| `database/seeders/sql/dummy_chord_placements.sql` | `database/seeders/DummyChordPlacementSeeder.php:22` | `database_path('seeders/sql/dummy_chord_placements.sql')` |
| `01-docs/design/tokens.json` | Referensi dokumentasi sinkronisasi di `resources/css/tokens.css:3` dan `.agents/rules/CHORDCUE_RULES.md:12` | Referensi komentar & dokumentasi (tidak di-bundle runtime) |
| `public/favicon.ico` & `public/robots.txt` | Browser / crawler (konvensi web standar) | Permintaan HTTP implisit (`/favicon.ico`, `/robots.txt`) |

---

## 2. Struktur yang Diusulkan (Tree & Alasan Tiap Folder)

Prinsip penataan: **minimal, jelas per domain, patuh konvensi Laravel/Breeze & `CHORDCUE_RULES.md`**, tanpa menyentuh `app/` dan tanpa mengubah logika aplikasi.

```text
resources/
├── css/                                 # (Tetap) Stylesheet & design tokens
│   ├── app.css
│   └── tokens.css
├── js/
│   ├── app.js                           # (Tetap) Entrypoint Vite utama
│   ├── bootstrap.js                     # (Tetap / lihat UNSURE soal axios)
│   ├── audio/                           # (Tetap, sesuai CHORDCUE_RULES §8) Mesin audio Vanilla JS
│   │   └── audio-engine.js
│   └── alpine/                          # (BARU) Seluruh modul komponen Alpine.js terpisah dari entrypoint & engine
│       ├── beat-editor.js
│       ├── editor-shared.js
│       ├── player.js
│       └── simple-editor.js
└── views/
    ├── my-songs.blade.php               # (Tetap) Halaman utama user
    ├── welcome.blade.php                # (Tetap) Katalog publik
    ├── admin/                           # (Tetap) Halaman konsol admin
    ├── auth/                            # (Tetap) Halaman autentikasi Breeze
    ├── chords/                          # (Tetap) Halaman Pustaka Chord
    ├── components/                      # (Tetap untuk shared & Breeze UI primitives; hapus nav-link.blade.php)
    │   ├── application-logo.blade.php
    │   ├── auth-session-status.blade.php
    │   ├── danger-button.blade.php
    │   ├── dropdown.blade.php
    │   ├── dropdown-link.blade.php
    │   ├── icon.blade.php
    │   ├── input-error.blade.php
    │   ├── input-label.blade.php
    │   ├── modal.blade.php
    │   ├── navbar.blade.php
    │   ├── pagination.blade.php
    │   ├── primary-button.blade.php
    │   ├── responsive-nav-link.blade.php
    │   ├── secondary-button.blade.php
    │   ├── text-input.blade.php
    │   ├── chords/                      # (BARU) Komponen khusus domain Chord (terpisah dari Breeze primitives)
    │   │   ├── chord-card.blade.php
    │   │   ├── chord-diagram.blade.php
    │   │   ├── create-chord-modal.blade.php
    │   │   └── edit-chord-modal.blade.php
    │   └── songs/                       # (BARU) Komponen khusus domain Song (terpisah dari Breeze primitives)
    │       ├── create-song-modal.blade.php
    │       └── song-card.blade.php
    ├── layouts/                         # (Tetap; hapus navigation.blade.php yang yatim)
    │   ├── app.blade.php
    │   └── guest.blade.php
    ├── profile/                         # (Tetap) Halaman & partial profil Breeze
    └── songs/                           # (Tetap) Halaman Player, Simple Editor, Beat Editor & partial editor
        ├── editor-beat.blade.php
        ├── editor-simple.blade.php
        ├── player.blade.php
        └── partials/
            ├── editor-chord-sidebar.blade.php
            └── editor-top-panel.blade.php
```

**Alasan tiap folder baru:**
- `resources/js/alpine/`: Memisahkan 4 modul UI Alpine (`player.js`, `editor-shared.js`, `simple-editor.js`, `beat-editor.js`) dari file entrypoint root (`app.js`, `bootstrap.js`) dan dari mesin audio Vanilla JS (`resources/js/audio/audio-engine.js`) sehingga batas arsitektur `CHORDCUE_RULES.md` §8 terlihat jelas dari struktur folder.
- `resources/views/components/chords/` dan `resources/views/components/songs/`: Memisahkan komponen domain ChordCue dari 15 komponen UI primitif/layout Breeze di `resources/views/components/`, sehingga folder `components/` tidak lagi datar dan campur-aduk (22 file dalam 1 folder).

---

## 3. Daftar Pemindahan File (Move List)

| # | Path Lama | Path Baru | File yang Impor / Referensinya Wajib Diperbarui |
|---|---|---|---|
| 1 | `resources/js/player.js` | `resources/js/alpine/player.js` | `resources/js/app.js` (`import './alpine/player'`), `resources/js/alpine/player.js` (`import { AudioEngine } from '../audio/audio-engine'`), `tests/Feature/PlayerPageTest.php` (5 baris `resource_path('js/player.js')` → `'js/alpine/player.js'`) |
| 2 | `resources/js/editor-shared.js` | `resources/js/alpine/editor-shared.js` | `resources/js/alpine/simple-editor.js` (tetap `./editor-shared.js`), `resources/js/alpine/beat-editor.js` (tetap `./editor-shared.js`), `tests/Feature/SimpleEditorTest.php:156` (`resource_path`) |
| 3 | `resources/js/simple-editor.js` | `resources/js/alpine/simple-editor.js` | `resources/js/app.js` (`import './alpine/simple-editor'`), `tests/Feature/SimpleEditorTest.php:155` (`resource_path`) |
| 4 | `resources/js/beat-editor.js` | `resources/js/alpine/beat-editor.js` | `resources/js/app.js` (`import './alpine/beat-editor'`), `tests/js/beat-grid.test.mjs:3` (`../../resources/js/alpine/beat-editor.js`), `tests/Feature/BeatEditorTest.php:130` (`resource_path`) |
| 5 | `resources/views/components/song-card.blade.php` | `resources/views/components/songs/song-card.blade.php` | `resources/views/welcome.blade.php:133,152` (`<x-songs.song-card>`), `resources/views/my-songs.blade.php:126` (`<x-songs.song-card>`) |
| 6 | `resources/views/components/create-song-modal.blade.php` | `resources/views/components/songs/create-song-modal.blade.php` | `resources/views/my-songs.blade.php:155` (`<x-songs.create-song-modal>`) |
| 7 | `resources/views/components/chord-card.blade.php` | `resources/views/components/chords/chord-card.blade.php` | `resources/views/chords/index.blade.php:109` (`<x-chords.chord-card>`) |
| 8 | `resources/views/components/chord-diagram.blade.php` | `resources/views/components/chords/chord-diagram.blade.php` | `resources/views/components/chords/chord-card.blade.php:30` (`<x-chords.chord-diagram>`) |
| 9 | `resources/views/components/create-chord-modal.blade.php` | `resources/views/components/chords/create-chord-modal.blade.php` | `resources/views/chords/index.blade.php:205` (`<x-chords.create-chord-modal>`) |
| 10 | `resources/views/components/edit-chord-modal.blade.php` | `resources/views/components/chords/edit-chord-modal.blade.php` | `resources/views/chords/index.blade.php:206` (`<x-chords.edit-chord-modal>`) |

> **Catatan tentang `tests/`:** Pemindahan #1–#4 memerlukan pembaruan string `resource_path('js/...')` di `PlayerPageTest.php`, `SimpleEditorTest.php`, `BeatEditorTest.php`, dan `tests/js/beat-grid.test.mjs` (hanya pembaruan path impor/file, **bukan** perubahan assertion). Lihat Pertanyaan #1 di Bagian 7 jika Anda ingin memastikan hal ini disetujui sejalan dengan `design-rules.md` §1 no. 3.

---

## 4. Daftar Penghapusan File Tidak Terpakai (Delete List)

Hanya file yang **100% terverifikasi 0 referensi runtime, route, controller, Blade, JS, maupun test** di seluruh repositori (termasuk `app/`):

| # | Path File | Bukti Tidak Terpakai (Apa yang Dicari) |
|---|---|---|
| 1 | `resources/views/layouts/navigation.blade.php` | Berisi hanya 1 baris `<x-navbar />` (13 byte). Pencarian `layouts.navigation` dan `navigation` di `app/`, `resources/`, `routes/`, `tests/` menghasilkan **0 hits**. `resources/views/layouts/app.blade.php:16` langsung memanggil `<x-navbar />`. |
| 2 | `resources/views/components/nav-link.blade.php` | Pencarian `x-nav-link` dan `components.nav-link` di seluruh proyek (`app/`, `resources/`, `routes/`, `tests/`) menghasilkan **0 hits**. `navbar.blade.php` memakai tag `<a>` langsung di desktop dan `<x-responsive-nav-link>` di mobile. |
| 3 | `resources/views/dashboard.blade.php` | Pencarian `view('dashboard')` / `view("dashboard")` di seluruh proyek menghasilkan **0 hits**. `routes/web.php:64-66` mendefinisikan `GET /dashboard` hanya sebagai `return redirect()->route('home');`. Selain itu file ini memuat class `rounded-xl` (baris 10) yang tidak valid di `tailwind.config.js`. |
| 4 | `storage/app/public/chords/audio/csharp7.mp3` | Ditambahkan sesuai Keputusan #4 pengguna. 0 referensi di tabel `chords.file_path` (DB lokal hanya memuat 48 chord) maupun di seluruh kode proyek. |
| 5 | `storage/app/public/chords/audio/dmaj7.mp3` | Ditambahkan sesuai Keputusan #4 pengguna. 0 referensi di tabel `chords.file_path` maupun di seluruh kode proyek. |

*(Semua item UNSURE lainnya diputuskan untuk **tetap dipertahankan** sesuai keputusan pengguna pada Bagian 7.)*

---

## 5. Daftar Dead Code di Dalam File yang Terpakai (Di Luar `app/`)

| # | File | Kode Mati yang Dihapus | Bukti |
|---|---|---|---|
| 1 | `resources/js/player.js` | Properti state `playbackState` (baris 29) beserta 6 penugasan `this.playbackState = ...` (baris 104, 116, 377, 401, 442, 459) | Pencarian `\bplaybackState\b` di `resources/` dan `tests/`: hanya ditulis 7 kali di `player.js`, **0 kali dibaca** di mana pun (UI memakai `isPlaying`, `isPreloading`, `isStarting`). |
| 2 | `resources/js/editor-shared.js` | Properti state `backingTrackPath` (baris 52) beserta 2 penugasan `this.backingTrackPath = ...` (baris 261, 369) | Pencarian `\bbackingTrackPath\b` di `resources/` dan `tests/`: hanya ditulis 3 kali di `editor-shared.js`, **0 kali dibaca** (UI memakai `backingTrackUrl`). |
| 3 | `resources/js/audio/audio-engine.js` | Field `rawDuration` (baris 768) dan `hasPlayedAudio` (baris 773 & 981) pada objek elemen `this.scheduledChords` | Pencarian `\brawDuration\b` dan `\bhasPlayedAudio\b` di seluruh proyek: hanya ditulis di `audio-engine.js`, **0 kali dibaca** di mana pun. |
| 4 | `resources/js/app.js` | `import { AudioEngine } from './audio/audio-engine';` (baris 4) dan `window.AudioEngine = AudioEngine;` (baris 9) | Pencarian `window.AudioEngine` di seluruh proyek: **hanya 1 hit** (baris definisinya sendiri di `app.js:9`). `player.js` mengimpor `AudioEngine` langsung secara ESM. *(Catatan: hapus hanya jika Anda tidak memakainya untuk inspeksi manual lewat DevTools console.)* |
| 5 | `resources/views/components/icon.blade.php` | Entri SVG `'book'` (baris 13) dan `'mail'` (baris 20) di array `$iconPaths` | Pencarian `name="book"`, `name='book'`, `name="mail"`, `name='mail'` di seluruh `resources/views/`: **0 hits**. |
| 6 | `resources/views/components/chord-card.blade.php` | Deklarasi prop tidak terpakai `'selected' => false,` di blok `@props([...])` (baris 3) | Pencarian `$selected` di dalam `chord-card.blade.php`: **0 hits** (status terpilih dikendalikan Alpine lewat `selectedChord.id`). |
| 7 | `resources/views/songs/partials/editor-top-panel.blade.php` & `editor-chord-sidebar.blade.php` | Blok `@props([...])` di baris 1–6 / 1–4 pada kedua file partial | Kedua file dipanggil via `@include('songs.partials....', [...])`, bukan sebagai komponen `<x-...>`; directive `@props` tidak aktif pada `@include` dan semua variabel sudah dioper eksplisit oleh caller. |

---

## 6. Komentar Penjelas untuk Ditinjau (Dipertahankan)

Sesuai aturan pelestarian komentar penjelas:

1. **`resources/js/player.js` baris 29 (`// 'stopped' | 'preloading' | 'playing'`):**
   - Menempel langsung pada `playbackState` yang akan dihapus; nilai status tersebut hanya menjelaskan enum `playbackState` yang sudah digantikan oleh `isPlaying` / `isPreloading` → aman dihapus bersamaan jika `playbackState` dihapus.
2. **`resources/css/tokens.css` baris 3, 106–116 (`--breakpoint-*`, `--layout-ratio-*`):**
   - Variabel CSS ini tidak dipanggil lewat `var(...)` di CSS/Tailwind, **tetapi** membawa komentar arsitektur penting:
     - `Keep in sync with tokens.json (same names, same values). Change both together.`
     - `Tulis angka langsung di @media (variabel CSS tidak berlaku di media query).`
     - `Porsi bg-base (60%), surface (30%), batas maksimum aksen (10%).`
   - **Usulan:** **PERTAHANKAN** seluruh token dan komentarnya agar tetap sinkron dengan `01-docs/design/tokens.json` dan `CHORDCUE_RULES.md` §10 no. 11.
3. **`config/chordcue.php` baris 14–21 (`chord_diagram_max_mb`):**
   - Memiliki docblock penjelas spesifikasi §3.3 (`Batas ukuran maksimal satu file gambar diagram chord`). Walau `ChordController` saat ini masih hardcode `max:2048` (di dalam `app/`, tidak boleh disentuh), key & komentarnya di `config/chordcue.php` **PERTAHANKAN**.
4. **`resources/views/songs/player.blade.php:30` & `resources/js/player.js:196-210` (`setOffset` / `@backing-track-offset-updated.window`):**
   - `setOffset` terhubung ke custom window event `@backing-track-offset-updated.window` dan `AudioEngine::setBackingTrackOffset()` (diuji secara kontrak di `PLAN.md` Batch 0/1 sebagai dukungan live offset update). Tidak ada emitter event di `player.blade.php`, namun ini merupakan hook fungsional yang disyaratkan Batch 0/1 → **PERTAHANKAN** (tidak dianggap dead code).

---

## 7. Daftar UNSURE & Pertanyaan Keputusan untuk Anda

Item berikut **TIDAK** dimasukkan ke Delete List / Dead Code List karena berpotensi masih dibutuhkan oleh tooling, Breeze, arsip dokumentasi, atau data lokal:

| # | Item | Alasan UNSURE | Pertanyaan untuk Anda |
|---|---|---|---|
| 1 | **Pemindahan `resources/js/*.js` → `resources/js/alpine/*.js`** (memperbarui path string di 4 file `tests/`) | `design-rules.md` §1 no. 3 melarang mengubah file di `tests/`, sementara `PlayerPageTest`, `SimpleEditorTest`, `BeatEditorTest`, dan `beat-grid.test.mjs` membaca path `resource_path('js/...')` secara langsung. | Apakah Anda menyetujui pembaruan **hanya path file** di 4 file tes tersebut agar `resources/js/alpine/` bisa diterapkan, atau lebih baik file JS Alpine dibiarkan di `resources/js/`? |
| 2 | **`resources/js/bootstrap.js` & `axios` + `@tailwindcss/vite` di `package.json`** | `axios` hanya diimpor di `bootstrap.js` dan tidak pernah dipakai (semua AJAX pakai `fetch`). `@tailwindcss/vite` (v4) ada di `package.json` tetapi tidak dipakai di `vite.config.js` (proyek pakai Tailwind v3 + `postcss.config.js`). Namun `bootstrap.js` adalah default Breeze dan menghapus paket mengubah `package-lock.json`. | Apakah Anda ingin menghapus `axios`/`bootstrap.js` dan `@tailwindcss/vite` dari `package.json`, atau membiarkannya saja agar tidak menyentuh dependensi npm/Breeze? |
| 3 | **`resources/views/auth/verify-email.blade.php` & `routes/auth.php` (rute `verify-email`)** | Email verification dinonaktifkan di ChordCue (`User` tidak implement `MustVerifyEmail`, `EmailVerificationTest` di-skip), tetapi `app/Http/Controllers/Auth/EmailVerificationPromptController.php` (di dalam `app/`, tidak boleh disentuh) masih mengembalikan `view('auth.verify-email')`. | Biarkan tetap ada agar konsisten dengan Breeze controller di `app/`? *(Rekomendasi: **Biarkan**.)* |
| 4 | **`01-docs/` dokumen historis / mockup / referensi (13 file)** (`DESIGN_SYSTEM_OLD.md`, `ui-refactor-copy-old.txt`, `ui-refactor-plan.md`, `mockups/player-option-{a,b}.html`, `dark_blue_palette_music_companion.html`, `catatan.txt`, `chordcue-10table-schema.sql`, `PROJECT_B_ANALYSIS.md`, `beat-editor-ref.png`, `simple-editor-ref.png`, `walkthrough-by-AI/*`) | Nol referensi dari kode runtime, tetapi sangat mungkin dibutuhkan sebagai arsip/lampiran Tugas Akhir Anda. *(File aktif `SPESIFIKASI_SISTEM_ChordCue.md`, `DESIGN_GUIDE.md`, `tokens.json`, `design-changelog.md` wajib tetap di tempatnya.)* | Apakah file-file lama di `01-docs/` ini dibiarkan saja, dipindah ke `01-docs/archive/`, atau ada yang ingin dihapus? |
| 5 | **2 file audio MP3 ekstra di `storage/app/public/chords/audio/`** (`csharp7.mp3`, `dmaj7.mp3`) | Ada 50 file `.mp3` di disk, tetapi hanya 48 yang tercatat di `chords.file_path` pada DB MySQL lokal (kemungkinan sisa tes transpose sebelumnya). | Apakah kedua file `.mp3` tersebut ingin dihapus atau dibiarkan sebagai cache audio lokal? |
| 6 | **`resources/views/components/chord-diagram.blade.php` prop `'large' => false`** | Tidak pernah dipanggil dengan `:large="true"` oleh satu-satunya pemanggil (`chord-card.blade.php`), tetapi mungkin disiapkan sebagai varian ukuran komponen. | Hapus cabang `$large` atau biarkan sebagai opsi komponen? |
| 7 | **`tests/Unit/ExampleTest.php` & `tests/Feature/ExampleTest.php`** | Stub bawaan Laravel yang ikut berjalan di `php artisan test`. `Feature/ExampleTest.php` menguji `GET / -> 200` (bernilai smoke test), sedangkan `Unit/ExampleTest.php` hanya `expect(true)->toBeTrue()`. | Biarkan keduanya atau hapus `tests/Unit/ExampleTest.php`? |
| 8 | **`public/favicon.ico` (0 byte)** | Browser selalu meminta `/favicon.ico`; file kosong 0 byte menghindari 404 log di server lokal. | Biarkan file kosong ini (atau ganti ikon nanti di luar tugas ini)? *(Rekomendasi: **Biarkan**.)* |

**Keputusan Pengguna (Diterapkan):**
1. **Path tes JS (Pertanyaan #1):** Disetujui — perbarui string `resource_path('js/...')` dan impor path pada 4 file tes (hanya path, tanpa ubah assertion) di Batch 1.
2. **`bootstrap.js`, `axios`, `@tailwindcss/vite` (Pertanyaan #2):** Biarkan apa adanya.
3. **`01-docs/` (Pertanyaan #4):** Biarkan apa adanya (tidak dipindah / tidak dihapus).
4. **File audio ekstra (Pertanyaan #5):** Hapus `storage/app/public/chords/audio/csharp7.mp3` dan `dmaj7.mp3` di Batch 2.
5. **Prop `large` & `Unit/ExampleTest.php` (Pertanyaan #6–#7):** Hapus prop `large` tidak terpakai pada `chord-diagram.blade.php` di Batch 3; biarkan `tests/Unit/ExampleTest.php`.
6. **Item #3 (`verify-email.blade.php`) & #8 (`public/favicon.ico`):** Biarkan apa adanya sesuai rekomendasi.

---

## 8. Usulan Pembagian Batch Eksekusi

Urutan disusun agar aplikasi, Vite build, dan seluruh tes **tetap hijau di akhir setiap batch**:

### Batch 1 — Pemindahan File & Pembaruan Referensi (Moves Only) — `[SELESAI — Commit 9d2db74]`
- Memindahkan `resources/js/{player,editor-shared,simple-editor,beat-editor}.js` → `resources/js/alpine/` dan memperbarui impor di `resources/js/app.js`, `resources/js/alpine/player.js`, `tests/js/beat-grid.test.mjs`, serta 3 file Pest test (`PlayerPageTest`, `SimpleEditorTest`, `BeatEditorTest`).
- Memindahkan 4 komponen chord → `resources/views/components/chords/` dan 2 komponen lagu → `resources/views/components/songs/`, lalu memperbarui tag `<x-chords.*>` dan `<x-songs.*>` pada `welcome.blade.php`, `my-songs.blade.php`, `chords/index.blade.php`, dan `chord-card.blade.php`.
- `01-docs/` dibiarkan utuh sesuai Keputusan #3.
- **Verifikasi:** `npm.cmd run build` PASS, `php artisan view:cache` PASS, `node --test` 10/10 PASS, `php vendor/bin/pest` 117 passed / 1 skipped (`EmailVerificationTest`) / 743 assertions.

### Batch 2 — Penghapusan File Tidak Terpakai (Delete Unused Files) — `[SELESAI & DISETUJUI]`
- Menghapus 5 file tidak terpakai (0 referensi di seluruh repo termasuk `app/`):
  1. `resources/views/layouts/navigation.blade.php`
  2. `resources/views/components/nav-link.blade.php`
  3. `resources/views/dashboard.blade.php`
  4. `storage/app/public/chords/audio/csharp7.mp3` *(Keputusan #4)*
  5. `storage/app/public/chords/audio/dmaj7.mp3` *(Keputusan #4)*
- **Import/referensi diubah:** 0 file (tidak ada file yang mengacu pada 5 file tersebut; folder `app/` tidak tersentuh).
- **Verifikasi:** `npm.cmd run build` PASS (`app-9SS6nRFs.css` 35.76 kB, `app-DfDty69E.js` 152.54 kB), `php artisan view:cache` PASS, `php artisan route:list` PASS, `node --test` 10/10 PASS, `php vendor/bin/pest` 117 passed / 1 skipped (`EmailVerificationTest`) / 743 assertions.

### Batch 3 — Pembersihan Dead Code di File Terpakai (Remove Dead Code) — `[SELESAI & DISETUJUI]`
- Menghapus dead code pada daftar Bagian 5 (+ Keputusan #5) di 9 file (+ 1 file caller partial diperbarui):
  1. `resources/js/alpine/player.js`: properti `playbackState` (dan komentar enumnya) + 6 penugasan `this.playbackState = ...`
  2. `resources/js/alpine/editor-shared.js`: properti `backingTrackPath` + 2 penugasan `this.backingTrackPath = ...`
  3. `resources/js/audio/audio-engine.js`: field `rawDuration`, `hasPlayedAudio` (beserta variabel lokal `hasScheduledAudio` yang hanya mengisi `hasPlayedAudio`), dan reset `c.hasPlayedAudio = false`
  4. `resources/js/app.js`: impor tidak terpakai `import { AudioEngine } from './audio/audio-engine';` dan `window.AudioEngine = AudioEngine;`
  5. `resources/views/components/icon.blade.php`: entri SVG tidak terpakai `'book'` dan `'mail'`
  6. `resources/views/components/chords/chord-card.blade.php`: prop tidak terpakai `'selected' => false`
  7. `resources/views/components/chords/chord-diagram.blade.php`: prop tidak terpakai `'large' => false` dan cabang kondisionalnya *(Keputusan #5)*
  8. `resources/views/songs/partials/editor-top-panel.blade.php`: blok `@props([...])` pada partial `@include`
  9. `resources/views/songs/partials/editor-chord-sidebar.blade.php`: blok `@props([...])` pada partial `@include` (disertai penambahan `'mode' => 'simple'` secara eksplisit pada pemanggil `@include` di `resources/views/songs/editor-simple.blade.php` agar `$mode` terdefinisi konsisten seperti di `editor-beat.blade.php`)
- Seluruh komentar penjelas di Bagian 6 tetap dijaga utuh; folder `app/` tidak tersentuh.
- **Verifikasi:** `Residue hits: 0`, `npm.cmd run build` PASS (`app-9SS6nRFs.css` 35.76 kB, `app-Dfgq0c-3.js` 152.11 kB), `php artisan view:cache` PASS, `php artisan route:list` PASS (53 routes), `node --test` 10/10 PASS, `php vendor/bin/pest` 117 passed / 1 skipped (`EmailVerificationTest`) / 743 assertions (24.67s), `DB/Disk chord audio: 50/50 (0 extra)`.

---

## 9. Rencana Verifikasi Tiap Batch

Setiap selesai satu batch, jalankan seluruh rangkaian verifikasi berikut dari direktori `c:\xampp\new\htdocs\LearnLaravel\chordcue`:

1. **Build Frontend (Vite + Tailwind CSS):**
   ```powershell
   npm.cmd run build
   ```
   *(Memastikan semua impor ESM, Tailwind content scan pada folder komponen baru, dan bundle manifest terkompilasi tanpa error.)*

2. **Compile Check View Blade & Cache Clear:**
   ```powershell
   php artisan view:clear; php artisan view:cache; php artisan view:clear
   ```
   *(Memverifikasi seluruh file Blade, `@include`, dan `<x-chords.*>` / `<x-songs.*>` terkompilasi tanpa missing component/view error.)*

3. **PHP Syntax / Lint Check (untuk file PHP di luar `app/` yang tersentuh):**
   ```powershell
   php vendor/bin/pint --test routes config database tests
   ```
   *(Atau `php -l` pada setiap file PHP yang diubah jika Pint tidak diinginkan mengubah gaya.)*

4. **Unit Tests JavaScript (Node Test Runner):**
   ```powershell
   node --test tests/js/timing.test.mjs tests/js/beat-grid.test.mjs
   ```
   *(Harus `10 passed, 0 failed`.)*

5. **Full Automated Test Suite (Pest PHP):**
   ```powershell
   php vendor/bin/pest
   ```
   *(Harus tetap `115 passed, 1 skipped` [`EmailVerificationTest`], `0 failed`.)*

6. **Smoke Run Aplikasi:**
   ```powershell
   php artisan route:list
   ```
   *(Dan verifikasi halaman `/`, `/my/songs`, `/chords`, `/songs/{id}`, `/songs/{id}/editor/simple`, `/songs/{id}/editor/beat` saat server berjalan via `php artisan serve` / `php artisan serve:all`.)*
