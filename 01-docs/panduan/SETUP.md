# Panduan Setup & Perintah Penting ChordCue

Dokumen ini disesuaikan dengan kondisi repositori **ChordCue** saat ini (Laravel 12.69+, MySQL 8, Blade + Alpine.js, Tailwind CSS, Vite, `edge-tts` via `config/tts.php` dan `App\Jobs\GenerateChordAudio`).

> **Catatan migrasi dari catatan lama (`catatan.txt` / prototipe `test-tts`):**
> Proyek ini sudah **tidak memakai** `PYTHON_BIN`, `scripts/edge_tts_stream.py`, maupun `App\Jobs\GenerateChordAudioJob`. Konfigurasi Python/TTS sekarang sepenuhnya melalui `TTS_*` di `.env` ([config/tts.php](../../config/tts.php)) dan `App\Jobs\GenerateChordAudio`.

---

## 1. Tech Stack

- **Backend & Database:** Laravel 12 (`php ^8.2`), MySQL 8
- **Autentikasi & UI:** Laravel Breeze (`blade` + Alpine.js), Tailwind CSS v3, Vite
- **Pengujian:** Pest PHP v3 & Node test runner (`tests/js/*.test.mjs`)
- **Text-to-Speech (TTS):** `edge-tts` (Python CLI), dipanggil oleh `App\Jobs\GenerateChordAudio`

---

## 2. Mengapa File `.env` dan `public/storage` Tidak Ada Setelah `git clone` / `git pull`?

Dalam [.gitignore](../../.gitignore), file `.env` dan symlink `/public/storage` sengaja dikecualikan agar kredensial lokal dan symlink OS tidak ikut ter-commit ke repositori. Sebagai gantinya, repositori menyediakan template [.env.example](../../.env.example).

---

## 3. Langkah Setup Awal (Setelah Clone / Pull Pertama Kali)

### 1. Duplikasi `.env` dari `.env.example`
```powershell
copy .env.example .env
```

### 2. Periksa Pengaturan Penting di `.env`
[.env.example](../../.env.example) sudah menyertakan seluruh variabel default ChordCue. Pastikan nilai berikut sesuai lingkungan lokal Anda:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chordcue
DB_USERNAME=root
DB_PASSWORD=

# Bawaan .env.example adalah 'sync' (job TTS dieksekusi langsung tanpa worker).
# Ubah ke 'database' jika ingin memproses TTS secara asynchronous di background worker.
QUEUE_CONNECTION=sync

# Perintah Python edge-tts (dipecah spasi di config/tts.php):
#   Windows (py launcher) : TTS_COMMAND="py -m edge_tts"
#   Python di PATH        : TTS_COMMAND="python -m edge_tts"
#   Linux/macOS           : TTS_COMMAND="python3 -m edge_tts"
#   Global pip            : TTS_COMMAND=edge-tts
TTS_COMMAND="py -m edge_tts"
TTS_VOICE=id-ID-ArdiNeural
TTS_RATE=+0%
TTS_ENABLED=true
TTS_TRIES=3
TTS_TIMEOUT=60
TTS_JOB_TIMEOUT=90
TTS_DISK=public
TTS_DIRECTORY=chords/audio

# Pengaturan aplikasi ChordCue (lihat config/chordcue.php)
CHORDCUE_BACKING_TRACK_MAX_MB=15
CHORDCUE_CHORD_DIAGRAM_MAX_MB=2
CHORDCUE_CUE_MARGIN_MS=1000
CHORDCUE_PLAYER_POLL_INTERVAL_SECONDS=3
CHORDCUE_PLAYER_POLL_TIMEOUT_SECONDS=60
```

> **Catatan:** Jangan sisipkan tanda kutip di dalam nilai `TTS_COMMAND` jika executable Python tidak mengandung spasi (mis. `"C:/Python313/python.exe -m edge_tts"`), karena `config/tts.php` memecah `TTS_COMMAND` berdasarkan spasi.

### 3. Install Dependensi PHP & Generate App Key
```powershell
composer install
php artisan key:generate
```

### 4. Siapkan Database, Migration & Seeder
Pastikan **MySQL** (misalnya lewat XAMPP) sudah aktif dan database `chordcue` sudah dibuat, lalu jalankan:
```powershell
php artisan migrate --seed
```
*(Gunakan `php artisan migrate` jika hanya ingin membuat skema tanpa data awal.)*

**Akun bawaan hasil seeder** (`UserSeeder`, semua memakai password `password`):
- **Admin:** `admin@chordcue.test` (`song_limit: 100`)
- **User biasa:** `budi@chordcue.test` (`song_limit: 5`), dan beberapa akun dummy lain termasuk `tamu@chordcue.test` (`song_limit: 1`)

### 5. Buat Symlink Storage (Wajib untuk Audio, Diagram & Backing Track)
File audio TTS chord (`chords/audio`), gambar diagram chord (`diagrams/`), dan backing track disimpan di disk `public` (`storage/app/public/`). Jalankan perintah berikut agar dapat diakses oleh browser:
```powershell
php artisan storage:link
```

### 6. Install & Uji `edge-tts` (Python)
Pasang `edge-tts` melalui salah satu perintah berikut:
```powershell
pip install edge-tts
# atau (disarankan di Windows dengan Python launcher)
py -m pip install edge-tts
# atau jika terjadi error / versi lama
py -m pip install --upgrade edge-tts
```
Jika menggunakan executable Python tertentu yang tidak terdaftar di `PATH`:
```powershell
"C:\Path\Ke\python.exe" -m pip install edge-tts
```

**Uji langsung `edge-tts` di terminal** untuk memastikan koneksi dan suara berfungsi (hapus `tes-suara.mp3` setelah selesai):
```powershell
py -m edge_tts --text "Halo, ini adalah tes suara." --voice id-ID-ArdiNeural --write-media tes-suara.mp3
```
*(Pilihan suara lain: `id-ID-GadisNeural`, `en-US-AriaNeural`, `en-US-GuyNeural`.)*

### 7. Install Dependensi Frontend (Node / Vite)
```powershell
npm install
npm run build        # build aset produksi; gunakan "npm run dev" saat development
```

---

## 4. Menjalankan Aplikasi & Queue Worker

- Jika `QUEUE_CONNECTION=sync` (bawaan `.env.example`), cukup jalankan `php artisan serve` (plus `npm run dev` jika sedang mengembangkan tampilan).
- Jika `QUEUE_CONNECTION=database`, web server dan queue worker harus sama-sama berjalan agar job `GenerateChordAudio` diproses:

```powershell
# Opsi 1 (Rekomendasi ChordCue): web server + queue:listen sekaligus
# Membaca ulang .env terbaru & memakai tries/timeout dari config/tts.php
php artisan serve:all

# Opsi tambahan serve:all:
php artisan serve:all --host=0.0.0.0 --port=8080
php artisan serve:all --no-worker   # web server saja

# Opsi 2 (Composer dev script): serve + queue:listen + npm run dev sekaligus
composer dev

# Opsi 3 (Manual di terminal terpisah):
php artisan serve
php artisan queue:work --verbose
```

---

## 5. Perintah Audio, Pelafalan & Pemulihan Antrean

### Pembuatan Audio TTS & Pemulihan
```powershell
# Buat audio TTS untuk seluruh chord yang kolom chords.file_path-nya masih NULL
php artisan chords:generate-audio

# Buat ulang semua file audio (termasuk yang sudah ada)
php artisan chords:generate-audio --force

# Eksekusi langsung secara sinkron tanpa antrean/worker
php artisan chords:generate-audio --sync

# Eksekusi ulang antrean job yang gagal (dari tabel failed_jobs)
php artisan queue:retry all
```

### Penghitungan Ulang Teks Pelafalan (`config/pronunciation.php`)
```powershell
# Pratinjau perubahan teks pronunciation tanpa mengubah database (dry-run)
php artisan chords:regenerate-pronunciation

# Terapkan pembaruan pronunciation ke tabel chords (tanpa mengubah name/file_path)
php artisan chords:regenerate-pronunciation --apply
```

### Uji Manual Satu Chord via Tinker
Untuk mengantrekan atau menjalankan ulang pembuatan audio untuk ID chord tertentu lewat `php artisan tinker` (ganti `1` dengan `chords.id` target):
```php
// Antrekan (sesuai QUEUE_CONNECTION)
App\Jobs\GenerateChordAudio::dispatch(1);

// Atau jalankan paksa secara sinkron sekarang juga (force = true)
App\Jobs\GenerateChordAudio::dispatchSync(1, true);
```

---

## 6. Pengujian Otomatis

```powershell
# Seluruh test suite Laravel (Pest)
php artisan test

# Unit test modul JS (auto-scroll, beat grid, timing & sync audio)
node --test tests/js/auto-scroll.test.mjs tests/js/beat-grid.test.mjs tests/js/timing.test.mjs
```
