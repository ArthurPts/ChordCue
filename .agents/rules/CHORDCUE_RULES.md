# Aturan & Standar Pengembangan ChordCue (Laravel 12)

Pedoman wajib bagi AI Agent setiap kali membaca, membuat, atau memodifikasi kode pada repositori ChordCue.

---

## 0. Prinsip Dasar

1. **Sumber kebenaran** perilaku dan data adalah `SPESIFIKASI_SISTEM_ChordCue.md`. ERD dan kamus data di §6 menentukan skema database.
2. Jika spesifikasi ambigu atau permintaan bertentangan dengannya: **tanyakan dulu**, jangan berasumsi, dan jangan mengubah spesifikasi tanpa izin.
3. Kerjakan **sesuai permintaan saja**; jangan menambah fitur atau merombak kode di luar itu.

---

## 1. Tech Stack

| Komponen | Pilihan |
|---|---|
| Framework | Laravel 12.x (PHP ^8.2) |
| Database | MySQL 8 |
| Starter kit | Laravel Breeze (Blade + Alpine.js) |
| CSS | Tailwind CSS |
| Pengujian | Pest PHP |
| Mesin audio | Modul Vanilla JS (Web Audio API), dibundel dengan Vite |
| Text-to-Speech | `edge-tts` (Python), lewat antarmuka `ChordAudioGenerator` |

---

## 2. Gaya Koding Ramah Pemula

Setiap kode PHP yang ditulis atau di-refactor harus:

1. **Perulangan sederhana**: utamakan `foreach` daripada method Collection kompleks (`map`, `reduce`, `flatMap`).
2. **Type safety eksplisit**: type hint pada parameter dan return type (mis. `public function show(Song $song): View`).
3. **Nama variabel jelas**: deskriptif dan cukup panjang (`$activeUserSongs`, bukan `$x`).
4. **Alur Controller linear** dengan komentar pemisah:
   - `// 1. Otorisasi & Validasi Input`
   - `// 2. Query Database`
   - `// 3. Response / Render View`
5. **Hindari kode tersembunyi**: dilarang membuat *magic function* sendiri (`__get`, `__call`, dsb.), *hidden callback*, atau *nested ternary*. Eloquent dan query builder biasa tetap boleh.
6. **Algoritma di luar Controller**: algoritma spesifikasi §7 (transposer, pembentuk pengucapan, dsb.) ditaruh di class kecil pada `app/Services/` dengan gaya yang sama, agar Controller tetap linear dan mudah diuji.

**Contoh Controller:**

```php
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menampilkan katalog lagu publik beserta hasil pencarian judul.
 *
 * Fungsi ini digunakan pada:
 * - Route: GET /songs
 */
public function index(Request $request): View
{
    // 1. Otorisasi & Validasi Input
    $validatedInput = $request->validate([
        'keyword' => ['nullable', 'string', 'max:120'],
    ]);

    // 2. Query Database
    $publicSongsQuery = Song::where('visibility', 'public');
    if (isset($validatedInput['keyword'])) {
        $publicSongsQuery->where('title', 'like', '%' . $validatedInput['keyword'] . '%');
    }
    $publicSongs = $publicSongsQuery->paginate(12);

    // 3. Response / Render View
    return view('songs.index', ['songs' => $publicSongs]);
}
```

---

## 3. Dokumentasi Fungsi (PHPDoc)

Setiap membuat atau mengubah method pada Controller, Model, Service, atau Helper:

- Wajib ada **PHPDoc** (bahasa Indonesia) yang menjelaskan **kegunaan fungsi** dan **di mana fungsi dipanggil** (route, Job, class lain).
- Saat pemanggil berubah, **perbarui daftar pemanggilnya**.

```php
/**
 * Menggeser nama chord sejumlah semitone (Am +3 menjadi Cm).
 *
 * Fungsi ini digunakan pada:
 * - SongTimelineController (GET /songs/{song}/timeline?transpose=N)
 * - ChordLibraryController (filter chord dasar di Pustaka Chord)
 */
public function transposeChordName(string $chordName, int $semitones): string
{
    // ...
}
```

---

## 4. Konvensi Laravel, Database & Migration

| Aspek | Aturan |
|---|---|
| Skema | Lewat **migration Laravel**, bukan import SQL manual |
| Tabel | Jamak, snake_case (`chord_placements`); primary key `$table->id()` |
| Foreign key | `{model_tunggal}_id` (`song_section_id`, `chord_id`) lewat `foreignId(...)->constrained()` |
| Aturan hapus | `cascadeOnDelete()` / `restrictOnDelete()` sesuai spesifikasi §6.2 |
| Pivot | `category_song`: kunci utama gabungan, tanpa `id` |
| Timestamps | `$table->timestamps()` **hanya** di `users` dan `songs`; model lain `public $timestamps = false;` |
| String | Panjang bawaan 255 kecuali ditentukan kamus data |
| Model | Definisikan `$fillable`, `$timestamps`, dan relasi eksplisit dengan foreign key bawaan |

### Penyesuaian Breeze

- Kolom `username` menggantikan `name`: sesuaikan form dan validasi registrasi, profil, `$fillable`, `UserFactory`, seeder, dan tes Auth. Login tetap dengan **email**.
- `users` memuat `remember_token` (dipakai "Remember me" dan reset password Breeze) dan **tidak** memuat `email_verified_at`; jangan mengisinya di factory/seeder.
- Jika `remember_token` dihapus, ubah juga checkbox di `login.blade.php`, `LoginRequest::authenticate()`, dan `NewPasswordController`.

---

## 5. Penghapusan Data & File

`ON DELETE CASCADE` menghapus record turunan tetapi **tidak** file. Karena kode tersembunyi dilarang (§2), file dibersihkan lewat **method eksplisit**, bukan Observer/event:

- `Song::deleteWithFiles()`: hapus file backing track, lalu lagu.
- `Chord::deleteWithFiles()`: tolak jika masih dipakai `chord_placements`; jika boleh, `foreach` diagram → `ChordDiagram::deleteWithFiles()`, hapus file audio, lalu chord.
- `ChordDiagram::deleteWithFiles()`: hapus file gambar, lalu record.
- `User::deleteWithSongs()`: `foreach` lagu → `Song::deleteWithFiles()`, lalu user.
- **Dilarang** memanggil `->delete()` langsung pada keempat model itu di luar method di atas.

---

## 6. Keunikan Koordinat Placement

Keunikan `start_beat` (per lagu) dan `position` (per baris) ditegakkan aplikasi, bukan constraint database (spesifikasi §7.3):

- Periksa dan simpan dalam satu `DB::transaction`; awali dengan `lockForUpdate()` pada baris `songs`.
- Cek `start_beat` pada lagu yang sama (join `lyric_lines` → `song_sections`) dan `position` pada `lyric_line_id` yang sama.
- Bila bertabrakan tanpa `force`: balas `409` beserta id placement yang bertabrakan. Dengan `force: true`: hapus placement lama dan simpan yang baru dalam transaksi yang sama.

---

## 7. Konfigurasi & Penyimpanan File

- Baca pengaturan lewat `config('chordcue.…')`; nilai bawaan ditulis di `config/chordcue.php`, tanpa variabel `.env` kustom. Aturan pengucapan ada di `config/pronunciation.php` (dibaca `PronunciationGenerator`).
- Akses file lewat `Storage` facade dan simpan **path relatif**. URL dibentuk dengan `Storage::url()` / `temporaryUrl()`; jangan memakai `public_path()`, `asset('storage/...')`, atau path absolut.
- Nama file diacak (`hashName()`); validasi MIME dan ekstensi di server (backing track MP3/WAV/OGG, diagram PNG/JPG/JPEG).

---

## 8. Frontend: Blade, Alpine, Tailwind, Mesin Audio

- **Tailwind** untuk seluruh gaya; pakai ulang komponen Blade bawaan Breeze.
- **Alpine.js** untuk state antarmuka: komponen kecil, tanpa logika panjang di `x-data`.
- **Mesin audio** (metronom, count-in, penjadwal, backing track) adalah modul Vanilla JS terpisah (mis. `resources/js/audio/`) yang **tidak bergantung pada Alpine**; Alpine hanya memanggilnya.
- Ikuti spesifikasi §7.4 dan §10.1: bunyi dijadwalkan lewat `AudioContext.currentTime`, bukan dipicu `setTimeout`/`setInterval` (timer hanya untuk lookahead); preload sebelum Play aktif; `AudioContext.resume()` dari klik Play; satu `GainNode` per sumber.
- AJAX memakai `fetch` dengan token CSRF; bentuk respons mengikuti spesifikasi §9.

---

## 9. Keandalan

- Chord hasil transpose dibuat dengan `Chord::firstOrCreate(['name' => ...], [...])`; antrikan job TTS **hanya jika** `wasRecentlyCreated`.
- Job audio: `$tries = 3`; isi `chords.file_path` hanya setelah file tersimpan; kegagalan akhir tercatat di `failed_jobs`.
- Semua aksi tulis melewati Policy; jangan mengandalkan pemeriksaan UI saja.

---

## 10. Larangan Keras (Don'ts)

AI Agent **DILARANG KERAS**:

1. **Mengubah file migration lama.** Perubahan skema lewat migration baru (`php artisan make:migration ...`), kecuali pengguna secara eksplisit memerintahkan penulisan ulang skema awal.
2. **Memakai inline CSS** (`style="..."`, termasuk `:style` Alpine). Jika ada nilai dinamis yang tak bisa dengan class Tailwind, tanyakan dulu.
3. **Menginstal package baru tanpa izin** (`composer require` / `npm install`). Utamakan fitur bawaan Laravel dan Breeze.
4. **Mengubah integrasi Edge-TTS sembarangan.** Panggilan Python ada di balik `ChordAudioGenerator`; minta persetujuan untuk perubahan.
5. **Mengubah spesifikasi** tanpa izin.
6. **Mengubah/menghapus tes** hanya agar lulus.
7. **Memanggil `env()` di luar config** atau menaruh path/URL file lokal secara hardcode.
8. **Menulis raw SQL dengan input pengguna tanpa binding.**
9. **Melewati Policy** atau pemeriksaan role pada aksi tulis dan rute admin.
10. **Mengubah kode inti Breeze** di luar penyesuaian pada bagian 4.

---

## 11. Workflow Pengerjaan Fitur Baru

1. **Baca spesifikasi** bagian terkait; tanyakan bila ambigu.
2. **Migration**: pastikan skema sesuai spesifikasi §6.
3. **Model**: `$fillable`, `$timestamps`, relasi jelas.
4. **Policy dan Service** bila perlu (otorisasi, algoritma §7).
5. **Controller & Route**: logika linear, komentar alur, PHPDoc.
6. **View (Blade + Alpine.js)** dengan komponen Breeze dan Tailwind.
7. **Pest**: buat/perbarui tes, jalankan `php artisan test`, pastikan lulus sebelum menyatakan selesai.
8. **Ringkas perubahan** ke pengguna: file yang dibuat/diubah dan hal yang perlu diputuskan.

---

## 12. Cakupan Pengujian (Pest)

Tes minimal seiring fitur dibangun:

- Policy akses (guest/user/admin, lagu publik/privat).
- Kuota: penuh, slot kembali setelah hapus, admin tanpa batas.
- Validasi offset minimal 1 birama.
- Koordinat placement: independen, tabrakan, timpa, lepas, hapus.
- Grid Editor Beat (§7.2) dan jadwal sesi Player (§7.4).
- Transposer (§7.5) dan `PronunciationGenerator` (§7.6).
- Filter Pustaka Chord (§5.11).
- Respons timeline: placement lengkap, `audio_ready`, transpose.
- Penghapusan berantai beserta file (bagian 5).
- Registrasi dan login dengan `username` dan `email`.

Tes ditulis sederhana (siapkan data, jalankan aksi, periksa hasil) dan mengikuti gaya bagian 2.
