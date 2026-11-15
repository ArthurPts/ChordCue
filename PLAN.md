# Rencana Implementasi Bertahap ChordCue (Project A)
### Implementasi Halaman Player, Simple Editor, dan Beat Editor Berdasarkan Spesifikasi Sistem & Design Rules

Dokumen ini disusun sebagai panduan pengerjaan implementasi untuk Project A (`chordcue`). Perilaku Project B (`test-tts`) diperlakukan murni sebagai **referensi fungsional/perilaku (WHAT)**, bukan implementasi teknis (HOW). Seluruh konfigurasi, konvensi kode, arsitektur data, dan aturan desain visual Project A selalu menjadi **sumber kebenaran tunggal**.

---

## 0. Ketetapan Parameter Audio & Timing Inti (Berdasarkan Klarifikasi)

1. **Titik Awal Backing Track (Beat 0)**:
   - Beat 0 **TIDAK DIANGGAP** sebagai detik 0 dari file audio backing track.
   - Beat 0 diselaraskan secara presisi dengan `t0 + backing_track_offset`.
   - Ketetapan ini memungkinkan musisi mengompensasi jeda hening (*silence/latency*) fisik pada awal file audio apa pun secara akurat.
2. **Kemandirian Offset terhadap Perubahan BPM**:
   - Perubahan BPM sesi maupun BPM lagu **TIDAK MENGUBAH** nilai `backing_track_offset`.
   - `backing_track_offset` merepresentasikan durasi fisik bantalan audio dalam detik/milidetik (`seconds/ms`) dan bersifat konstan terlepas dari fluktuasi tempo musikal.

---

## 1. Penemuan & Ringkasan Aturan serta Skills Project A

### 1.1 Lokasi File Aturan dan Skills (FACT)
- **Main Rule File**: `c:\xampp\new\htdocs\LearnLaravel\chordcue\.agents\rules\CHORDCUE_RULES.md`
- **Design Rule File**: `c:\xampp\new\htdocs\LearnLaravel\chordcue\.agents\rules\design-rules.md` (didukung oleh `01-docs\design-system\DESIGN_GUIDE.md`)
- **Workspace Skill**: `c:\xampp\new\htdocs\LearnLaravel\chordcue\.agents\skills\frontend-design\SKILL.md` (`frontend-design`: Panduan desain visual terarah, bebas klise AI, berkarakter audio profesional "Stage & Tape Deck").

---

### 1.2 Ringkasan Aturan: Do's & Don'ts

#### A. Main Rules (`CHORDCUE_RULES.md`)
| Kategori | DO (Wajib Dilakukan) | DON'T (Dilarang Keras) |
|---|---|---|
| **Gaya Koding** | 1. Gunakan perulangan sederhana `foreach` (hindari `map`, `reduce`, `flatMap`).<br>2. Type hint eksplisit pada semua parameter dan return type.<br>3. Alur Controller linear dengan 3 blok komentar baku (`// 1. Otorisasi & Validasi`, `// 2. Query Database`, `// 3. Response / Render View`).<br>4. Pisahkan algoritma musik kompleks ke dalam class mandiri di `app/Services/`. | 1. Dilarang membuat magic method sendiri (`__get`, `__call`), hidden callback, atau nested ternary.<br>2. Dilarang menumpuk logika algoritma atau query berat di dalam Controller. |
| **Dokumentasi** | 1. Setiap method di Controller, Model, Service, dan Helper wajib memiliki PHPDoc dalam **Bahasa Indonesia**.<br>2. Wajib mencantumkan rute, Job, atau class pemanggil fungsi tersebut, dan wajib diperbarui jika pemanggil berubah. | Dilarang membiarkan method baru/modifikasi tanpa PHPDoc deskriptif dan rincian pemanggil. |
| **Database & Eloquent** | 1. Skema hanya dimodifikasi lewat file migrasi Laravel baru.<br>2. `$table->timestamps()` **hanya** untuk tabel `users` dan `songs`; model lain wajib `public $timestamps = false;`.<br>3. Definisikan `$fillable`, `$casts`, dan relasi model secara eksplisit. | 1. Dilarang mengubah file migrasi lama yang sudah berjalan.<br>2. Dilarang menulis raw SQL dengan parameter pengguna tanpa prepared binding. |
| **File & Penghapusan** | 1. Hapus record yang memiliki file fisik HANYA lewat method model eksplisit: `Song::deleteWithFiles()`, `Chord::deleteWithFiles()`, `ChordDiagram::deleteWithFiles()`, `User::deleteWithSongs()`.<br>2. Gunakan `Storage` facade dengan path relatif; bentuk URL lewat `Storage::url()` atau `temporaryUrl()`. | 1. Dilarang memanggil `->delete()` langsung pada model-model tersebut di luar method pembersih eksplisit.<br>2. Dilarang memakai `public_path()`, `asset('storage/...')`, atau hardcoded path lokal. |
| **Koordinat Placement** | 1. Keunikan `start_beat` (per lagu) dan `position` (per baris) ditegakkan di aplikasi lewat `DB::transaction` diawali `lockForUpdate()` pada row lagu.<br>2. Kembalikan respons `409 Conflict` jika tabrakan; jika klien mengirim `force: true`, hapus placement lama dan simpan yang baru secara atomik. | Dilarang menimpa data koordinat tanpa validasi transaksi atau tanpa konfirmasi `force`. |
| **Konfigurasi** | 1. Baca konfigurasi lewat `config('chordcue....')` dan `config('tts....')`. | Dilarang memanggil `env()` di luar file `config/*.php`. |
| **Teks UI & Lokalisasi** | Tulis seluruh teks antarmuka (copy) langsung dalam **Bahasa Indonesia** di file Blade. | Dilarang keras memakai fungsi translasi `{{ __('...') }}` atau `__()` karena sistem hanya berfokus pada Bahasa Indonesia. |
| **Keandalan TTS** | Chord hasil transpose dibuat via `Chord::firstOrCreate()`; antrikan Job TTS **hanya jika** `wasRecentlyCreated = true`. Job dicoba maksimal 3 kali. Kolom `chords.file_path` diisi hanya setelah file fisik terverifikasi ada dan ukurannya > 0 byte. | Dilarang menjalankan TTS inline yang memblokir respons HTTP. Dilarang mengisi `file_path` jika proses TTS gagal atau menghasilkan file 0 byte. |

#### B. Visual Design Rules (`design-rules.md`)
| Elemen | Aturan Wajib (DO) | Larangan Keras (DON'T) |
|---|---|---|
| **Karakter Visual** | "Stage & Tape Deck" — Menggabungkan workstation audio profesional dengan kehangatan perangkat keras analog studio. | Dilarang keras: klise AI (ambient glow/blob hitam di background, gradien neon sembarangan, bayangan seragam). Hindari emoji pada antarmuka. |
| **Inline Styling** | Gunakan utilitas Tailwind token murni (`bg-surface-1`, `border-subtle`, `text-ink`, dsb.). | Dilarang keras atribut `style="..."` atau `:style` Alpine di Blade. |
| **Tema Warna** | **Dark Theme Only** secara eksklusif (`bg-base: #0A0F1A`, `bg-surface-1: #111826`, `bg-surface-2: #182133`, `bg-surface-3: #212C42`, `border-subtle: #2A3752`, `accent: #5B8DEF`). | Dilarang membuat toggle mode terang (light mode) atau menggunakan warna hex arbitrer (`bg-[#...]`) maupun warna bawaan Tailwind (`bg-gray-800`). |
| **Border Radius** | Gunakan **hanya** whitelist 4 skala:<br>• `rounded-sm`: 8px (tombol, input, dropdown)<br>• `rounded-md`: 10px (navbar wrapper, dropzone)<br>• `rounded-lg`: 12px (kartu konten, modal)<br>• `rounded-full`: 9999px (badge pill, radio). | Dilarang memakai `rounded-xl` atau `rounded-2xl` karena Tailwind config Project A telah menghapusnya (akan menghasilkan sudut siku tajam 0px). |
| **Tipografi** | Gunakan System Font Stack bawaan OS:<br>• Teks UI: `font-sans`<br>• Metrik Audio (BPM, Time Signature, Chord, Fret, Beat, Durasi, Offset): wajib `font-mono`. | Dilarang menyematkan web font eksternal (Google Fonts CDN, dsb.). |
| **Tombol & Kontrol** | Tinggi tombol modal/formulir: `h-[40px]`; tombol tabel: `h-[32px]`. Lingkaran radio standar: `w-[18px] h-[18px] rounded-full` dengan dot `w-[8px] h-[8px]`. Nudge buttons offset: `font-mono rounded-sm px-8 py-4`. | Dilarang ukuran radio mikro (`w-4` adalah 4px di custom config). |
| **Paginasi** | Halaman dengan `<x-pagination>` melayang di bawah (`fixed bottom-0 z-sticky`) wajib memiliki `pb-[88px]` pada kontainer utama. | Dilarang membiarkan kartu/konten terbawah tertutup oleh fixed pagination bar. |

---

### 1.3 Penggunaan Skill `frontend-design`
Skill `frontend-design` wajib digunakan saat merancang atau memperbarui antarmuka pengguna pada 3 halaman target (Player, Simple Editor, Beat Editor). Tujuannya adalah memastikan:
1. Setiap elemen UI terasa seperti perangkat pro-audio/studio tape counter, bukan tampilan generik AI.
2. Penempatan aksen warna `#5B8DEF` digunakan secara hemat dan terarah (fokus pada tombol aksi utama, active beat highlight, dan playhead).
3. Tata letak grid birama dan penempatan chord di atas huruf lirik memiliki rasio kontras visual AA, spasi ritmis yang rapi, dan responsif terhadap interaksi keyboard/pointer musisi.

---

## 2. Analisis Kondisi Project A Saat Ini

### 2.1 Arsitektur & Tech Stack
- **Framework**: Laravel 12.69.1 (PHP ^8.2), MySQL 8 (database lokal), SQLite (testing in-memory).
- **Frontend Stack**: Laravel Breeze (Blade + Alpine.js v3.4.2), Tailwind CSS (kustom token palet gelap), Vite 7.
- **Audio Engine**: Modul Vanilla JS (Web Audio API) terpisah dari Alpine.js.
- **TTS Engine**: `edge-tts` (Python CLI) dieksekusi via `Symfony\Component\Process\Process` di dalam queue Job `App\Jobs\GenerateChordAudio`.

### 2.2 Status Komponen yang Sudah Dibangun (FACT)
1. **Model & Migrasi**:
   - `Song`: Memiliki `backing_track_path`, `backing_track_offset` (decimal 6,3), relasi sections, artist, categories, dan method `deleteWithFiles()` (`app/Models/Song.php`).
   - `Chord`: Memiliki kolom `name`, `pronunciation`, `file_path`, dan fret data SVG fallback (`app/Models/Chord.php`).
   - `ChordPlacement`: Memiliki `lyric_line_id`, `chord_id`, `position` (nullable), dan `start_beat` (nullable) (`app/Models/ChordPlacement.php`).
   - `SongSection`, `LyricLine`, `Artist`, `Category`, `User`: Tabel dan model migrasi sudah siap.
2. **Pustaka & Pengaturan TTS**:
   - `config/tts.php` sudah terdefinisi (`command`, `voice = id-ID-ArdiNeural`, `rate = -10%`, `timeout = 60`, `disk = public`, `directory = chords/audio`).
   - `app/Jobs/GenerateChordAudio.php` sudah ada: menerima parameter `int $chordId, bool $force = false`, memvalidasi bahwa file hasil eksekusi benar-benar ada dan `filesize > 0`, serta memperbarui `chords.file_path` secara aman.
   - Command `php artisan chords:generate-audio` dan `php artisan serve:all` sudah tersedia di `app/Console/Commands/`.
3. **Antarmuka yang Sudah Selesai**:
   - `welcome.blade.php` (katalog publik dengan filter pencarian dan pagination melayang).
   - `my-songs.blade.php` (daftar lagu pribadi pengguna dan modal pembuatan lagu).
   - `chords/index.blade.php` (katalog Pustaka Chord dengan fret SVG).
   - `admin/songs/index.blade.php` dan `admin/users/index.blade.php` (tabel manajemen admin).

### 2.3 Status Komponen yang Belum Ada / Kurang (GAPS) (FACT)
1. **Konfigurasi Spesifikasi**:
   - `config/chordcue.php` **belum ada** (dibutuhkan untuk `chordcue.tts_voice`, `backing_track_max_mb`, `cue_margin_ms`).
   - `config/pronunciation.php` **belum ada** (dibutuhkan untuk pemetaan fonetik simbol chord ke kata ucapan).
2. **Service Algoritma (§7.5 & §7.6)**:
   - `app/Services/ChordTransposer.php` **belum ada** (parser akar, aksidental, sufiks, slash bass, modulasi semitone).
   - `app/Services/PronunciationGenerator.php` **belum ada** (pembentuk teks pengucapan fonetik otomatis untuk chord baru).
3. **Endpoint & Controller Musik**:
   - Route `GET /songs/{song}` (Player Blade) dan `GET /songs/{song}/timeline?transpose=N` (Timeline AJAX JSON dengan `backing_track_offset`) **belum dibuat**.
   - `SongAudioController.php` **belum ada** (dibutuhkan untuk endpoint `PATCH /songs/{song}/backing-track/offset` dengan validasi rentang `-10.000` s/d `+10.000` detik, step 0.001s).
   - `ChordPlacementController.php`, `SongSectionController.php`, dan `LyricLineController.php` masih berupa skeleton kosong (stub bawaan Laravel).
4. **Halaman Blade yang Diminta**:
   - `resources/views/songs/player.blade.php` **belum ada**.
   - `resources/views/songs/editor-simple.blade.php` **belum ada**.
   - `resources/views/songs/editor-beat.blade.php` **belum ada**.
5. **Mesin Audio Frontend**:
   - Direktori `resources/js/audio/` **belum ada** (penjadwal Web Audio API, metronom count-in, pemutar cuing audio chord, dan sinkronisasi live offset backing track tanpa audio dropout).
6. **Kepatuhan Aturan di Model Eksisting**:
   - `Chord::getDiagramUrlAttribute()` di `app/Models/Chord.php` masih menggunakan `asset('storage/' . ...)` yang melanggar aturan §7 `CHORDCUE_RULES.md`.
   - Model `Chord` belum memiliki method `deleteWithFiles()` (wajib membersihkan diagram dan audio chord sebelum dihapus).
   - Model `ChordDiagram` belum memiliki method `deleteWithFiles()`.

---

## 3. Analisis Adaptasi TTS: Project B vs Project A

| Aspek | Project B (`test-tts`) | Project A (`chordcue`) Sumber Kebenaran | Adaptasi yang Diperlukan |
|---|---|---|---|
| **Lokasi Config** | `config/services.php` (`services.tts.*`) | `config/tts.php` dan `config/chordcue.php` | Satukan konfigurasi: `tts.php` untuk parameter eksekusi CLI `edge-tts`, dan `chordcue.php` untuk aturan spesifikasi aplikasi (`cue_margin_ms`, default voice). |
| **Suara Default** | `en-US-AriaNeural` (hardcoded di B) | Spesifikasi §3.3 menyebut `en-US-AriaNeural`, sedangkan `config/tts.php` menyetel `id-ID-ArdiNeural`. | Gunakan `config('chordcue.tts_voice', 'id-ID-ArdiNeural')` secara terpusat agar pengucapan konsisten. |
| **Cara Eksekusi CLI** | Menjalankan script Python wrapper via `Process` dengan membaca stdout stream. Menghasilkan file 0 byte jika error. | Memanggil binary/modul `edge-tts --write-media <absolute-path>` langsung via Symfony `Process` di dalam Job. | Pertahankan implementasi Project A di `app/Jobs/GenerateChordAudio.php` karena sudah memvalidasi `filesize > 0` dan tidak menghasilkan orphan files. |
| **Penamaan File & Path** | Disimpan di `public/storage/chord-audio/chord_{id}_{safeName}.mp3`. Bergantung pada ID chord database. | Disimpan di `storage/app/public/chords/audio/{slug}.mp3` berbasis nama chord deterministik (mis. `fsharpm.mp3`). | Gunakan `GenerateChordAudio::fileName($chord->name)` milik Project A. Bebas duplikasi ID dan kompatibel di Linux/Windows. |
| **Pengucapan Chord Transpose** | B langsung memakai nama simbol (mis. `"C#m"`, `"G#7"`) sebagai teks TTS. Edge-TTS membaca simbol `#` sebagai kata *"hash"* atau diabaikan! | Algoritma `PronunciationGenerator` (§7.6) memetakan simbol via `config/pronunciation.php` (mis. `F#m7` → `"F sharp minor 7"`, `G/B` → `"G over B"`). | Buat `config/pronunciation.php` dan `app/Services/PronunciationGenerator.php`. Terapkan saat auto-create chord hasil transpose. |
| **Pemanggilan Job Transpose** | B memanggil synchronous job di dalam request HTTP API transpose (`POST /api/songs/{id}/transpose`). | A memproses transpose secara read-only pada `GET /songs/{song}/timeline?transpose=N`. Jika chord belum ada, simpan dengan `Chord::firstOrCreate()` dan dispatch asynchronous Job **hanya jika** `wasRecentlyCreated`. | Terapkan di `SongTimelineController`: kembalikan status `audio_ready: false` jika ada chord yang audionya sedang digenerate di queue worker. |
| **Akses URL Audio** | URL hardcoded atau menggunakan path absolut statis. | Path relatif tersimpan di database; URL diakses murni via `Storage::disk(config('tts.disk'))->url($chord->file_path)`. | Tambahkan accessor `getAudioUrlAttribute()` pada model `Chord`. |
| **Konsumsi di Halaman** | **Player**: Memutar audio aba-aba.<br>**Simple & Beat Editor**: Tidak memutar audio chord. | Sama persis: hanya halaman Player yang mengunduh dan menjadwalkan audio aba-aba chord. | Sediakan timeline data dengan preload buffer untuk Player; editor hanya membutuhkan daftar nama chord. |

---

## 4. Perbandingan 3 Halaman: Project B vs Project A

### 4.1 Halaman Player
| Fitur / Komponen | Perilaku Project B | Spesifikasi Project A | Status di Project A |
|---|---|---|---|
| **Rute & Akses** | `GET /songs/{id}/player` | `GET /songs/{song}` (Web Controller, akses Guest jika publik, pemilik/admin jika privat) | **MISSING** (belum terdaftar di `routes/web.php`) |
| **Sumber Data Timeline** | `GET /api/songs/{id}/timeline` | `GET /songs/{song}/timeline?transpose=N` (Web session + CSRF, format §9.3 dengan `backing_track_offset`) | **MISSING** (controller dan respons JSON belum ada) |
| **Placement yang Dimainkan** | Memutar semua placement yang memiliki `start_beat != null` meskipun tidak punya lirik/huruf. | Hanya memutar placement **lengkap** (`position !== null && start_beat !== null`). | **DIFFERS** (harus difilter ketat di query timeline) |
| **Timing Aba-aba Chord** | Trigger statis 1.0 detik sebelum beat tanpa memperhitungkan panjang audio klip. | `waktuBeat - durasiKlip - chordcue.cue_margin_ms` (durasi klip presisi + 50ms jeda aman sebelum ketukan). | **DIFFERS** (wajib dihitung di Web Audio API scheduler) |
| **Highlight Chord Sheet** | Chord di-highlight saat audio bicara *sebelum* ketukan. | Chord aktif di-highlight **tepat pada ketukan (`waktuBeat`) sampai ketukan chord berikutnya**. Setelah lewat, masuk status "Sudah dimainkan". | **DIFFERS** (mengikuti timeline beat sesungguhnya) |
| **Backing Track & Offset** | File diputar di detik 0 tanpa offset birama. | Backing track diselaraskan dengan `t0 + backing_track_offset`. Count-in metronom dimulai pada `t0 + offset - countIn`; lagu mulai di `t0 + offset`. | **DIFFERS** (rumus jadwal §7.4 wajib diimplementasikan) |
| **Sinkronisasi Tempo Sesi** | Jika tempo diubah, backing track tetap dimainkan (mengakibatkan out-of-sync parah). | Jika BPM sesi ≠ BPM lagu, backing track **otomatis di-mute** tanpa distorsi pitch dan tanpa mengubah `backing_track_offset`; aktif kembali jika tempo kembali ke BPM lagu. | **DIFFERS** (aturan mute otomatis di frontend audio engine) |
| **Desain Visual** | Palet terang/krem, font CDN Google, banyak inline styling CSS. | Desain "Stage & Tape Deck" gelap murni (`#0A0F1A`), System Fonts, token Tailwind, tanpa inline style. | **DIFFERS** (wajib mengikuti `design-rules.md`) |

---

### 4.2 Halaman Simple Editor
| Fitur / Komponen | Perilaku Project B | Spesifikasi Project A | Status di Project A |
|---|---|---|---|
| **Rute & Hak Akses** | `GET /songs/{id}/editor/simple` | `GET /songs/{song}/editor/simple` (Pemilik & Admin) | **MISSING** |
| **Kemandirian Koordinat** | Menjatuhkan chord ke posisi huruf **menghapus `start_beat`** yang sudah ada sebelumnya. | `position` dan `start_beat` bersifat **independen**. Mengubah posisi huruf TIDAK PERNAH menghapus beat yang sudah diatur. | **DIFFERS** (prinsip utama §5.4 Project A) |
| **Kontrol Offset Backing Track** | Tidak ada kontrol offset interaktif di toolbar. | **Bar Backing Track eksplisit**: Input numerik taktil (`font-mono`, desimal 3 angka) dengan tombol fine-tune nudge (`-100ms`, `-10ms`, `+10ms`, `+100ms`, `Reset`). Terhubung ke `PATCH /songs/{song}/backing-track/offset`. | **MISSING** |
| **Daftar Belum Ditempatkan** | Tidak terorganisir per baris dengan jelas. | Placement dengan `position === null` masuk ke daftar "belum ditempatkan" di samping tiap baris lirik. | **MISSING** |
| **Aksi Lepas (Release)** | Tidak ada tombol lepas; user harus menghapus seluruh chord. | Aksi khusus `POST /placements/{placement}/release` dengan `coordinate: 'position'` untuk mengembalikan chord ke drawer tanpa menghapus data beat. | **MISSING** |
| **Katalog Chord Editor** | Muncul modal prompt untuk menambah chord kustom sembarangan ke DB. | User hanya memilih dari daftar master `chords` yang ada (dikelola admin). Dilengkapi filter pencarian realtime. | **DIFFERS** |
| **Resolusi Tabrakan** | Menimpa langsung di klien. | Server memeriksa tabrakan huruf per baris dalam transaksi atomik (`lockForUpdate()`), membalas `409 Conflict`, dan menimpa jika dikonfirmasi `force: true`. | **MISSING** |

---

### 4.3 Halaman Beat Editor
| Fitur / Komponen | Perilaku Project B | Spesifikasi Project A | Status di Project A |
|---|---|---|---|
| **Rute & Hak Akses** | `GET /songs/{id}/editor/beat` | `GET /songs/{song}/editor/beat` (Pemilik & Admin) | **MISSING** |
| **Penomoran Beat Global** | 0-indexed (`start_beat = 0`). | **1-indexed global beat** (`start_beat >= 1`). Bar ke-`k`, beat ke-`j` dihitung dengan rumus §7.1. | **DIFFERS** |
| **Kontrol Offset Backing Track** | Tidak ada kontrol offset taktil. | **Bar Backing Track eksplisit**: Input numerik taktil (`font-mono`) dengan tombol fine-tune nudge (`-100ms`, `-10ms`, `+10ms`, `+100ms`, `Reset`). Mengatur jeda awal tanpa menggeser grid beat lagu. | **MISSING** |
| **Perhitungan Grid Baris** | Tabel datar terus menerus dari awal hingga akhir lagu. | Grid dihitung dinamis **per baris lirik** (§7.2): dari `barAwal` sampai `barAkhirTerpakai + 1` (bar kosong sebagai jeda tampilan untuk chord berikutnya). | **MISSING** (algoritma §7.2 belum ada) |
| **Daftar Belum Ditempatkan** | Chord yang belum berketukan sering hilang dari tampilan. | Placement pada baris yang memiliki `position` namun `start_beat === null` muncul di daftar "belum ditempatkan" di samping baris tersebut. | **MISSING** |
| **Aksi Lepas (Release)** | Tidak ada; drag out menghapus chord permanen. | Endpoint `POST /placements/{placement}/release` dengan `coordinate: 'start_beat'`. Posisi huruf pada lirik tetap utuh. | **MISSING** |
| **Tabrakan Beat** | Terkadang mengizinkan 2 chord pada beat yang sama atau menimpa tanpa konfirmasi. | Tabrakan beat global (lintas baris) memicu `409 Conflict`. User menyetujui timpa → hapus placement lama secara atomik. | **MISSING** |

---

## 5. Rencana Kerja Bertahap (Batched Implementation Plan)

### Justifikasi Pengusulan "Batch 0: Audio & Timeline Foundation"
Sebelum Halaman Player (Batch 1) dapat dikerjakan dan diuji, sistem memerlukan fondasi backend dan frontend audio:
1. Player membutuhkan endpoint `GET /songs/{song}/timeline?transpose=N` yang mematuhi skema §9.3 dan secara eksplisit menyertakan `backing_track_offset`.
2. Endpoint timeline membutuhkan service `ChordTransposer` (§7.5) dan `PronunciationGenerator` (§7.6) untuk menghitung chord baru saat transpose.
3. Backend membutuhkan `SongAudioController` untuk melayani update `PATCH /songs/{song}/backing-track/offset` dengan validasi rentang `-10.000` s/d `+10.000` detik (step 0.001s) dan otorisasi `can:update,song`.
4. Model `Chord` membutuhkan accessor URL audio publik (`getAudioUrlAttribute()`) berbasis Laravel Storage.
5. Player membutuhkan modul frontend Vanilla JS Web Audio API (`resources/js/audio/audio-engine.js`) yang mampu menangani preloading buffer, lookahead scheduling, sinkronisasi `t0 + backing_track_offset`, dan *live offset adjustment* tanpa memutus pemutaran aktif.
Jika fondasi ini digabungkan langsung ke Batch 1, cakupan Batch 1 akan menjadi terlalu besar dan sulit diverifikasi secara modular. Oleh karena itu, **Batch 0** diusulkan sebagai fondasi mutlak.

---

### BATCH 0: Audio & Timeline Foundation [SELESAI / COMPLETED]
**Tujuan**: Menyediakan konfigurasi aplikasi, service musik inti, perbaikan model, endpoint timeline AJAX, endpoint API offset backing track, dan mesin audio Vanilla JS Web Audio API yang siap dikonsumsi oleh Player dan Editor.

- **Status**: **SELESAI (COMPLETED)**
- **Scope**:
  - Konfigurasi `config/chordcue.php` (nilai aplikasi terhubung ke variabel lingkungan .env dengan fallback default) dan konsolidasi penuh seluruh pengaturan TTS ke `config/tts.php` (termasuk disk storage).
  - Konfigurasi `config/pronunciation.php` komprehensif (mendukung variasi ekstensi, alterasi, diminished, augmented, suspended, slash chord, serta pelafalan nada 'A' sebagai "Ae").
  - Service `ChordTransposer` (algoritma parsing, modulasi semitone, penulisan sharp/flat).
  - Service `PronunciationGenerator` (algoritma greedy pembentukan teks pengucapan fonetik, penanganan khusus nada 'A' -> 'Ae' dan dekomposisi modular).
  - Penambahan accessor `audio_url` pada model `Chord` dan perbaikan `deleteWithFiles()` pada `Chord` dan `ChordDiagram`.
  - Pembuatan policy `SongPolicy` untuk otorisasi `view`, `update`, dan `delete` lagu.
  - Pembuatan `SongTimelineController` dan route `GET /songs/{song}/timeline` yang secara eksplisit mengembalikan `backing_track_offset` pada skema respons DTO JSON.
  - Pembuatan `SongAudioController` dan route `PATCH /songs/{song}/backing-track/offset` dengan validasi rentang `-10.000` sampai `+10.000` detik (step 0.001s / 3 desimal) di bawah otorisasi `can:update,song`.
  - Pembuatan modul Vanilla JS `resources/js/audio/audio-engine.js` (Web Audio API scheduler, metronome, chord cue buffer player, backing track sync terikat `t0 + backing_track_offset`, dan fungsi live offset adjustment tanpa menghentikan playback).
  - Pest Unit & Feature Tests untuk Batch 0 (22 pengujian baru, 150 asersi, 100% lulus; seluruh test suite proyek 79 passed, 341 asersi).
- **Out of Scope**:
  - Antarmuka visual Player Blade (masuk Batch 1).
  - Logika drag-and-drop editor dan UI bar backing track (masuk Batch 2 & 3).
- **Langkah Kerja Rinci**:
  1. Buat `config/chordcue.php` dengan kunci: `tts_voice`, `backing_track_max_mb`, `chord_diagram_max_mb`, `cue_margin_ms`.
  2. Buat `config/pronunciation.php` berisi kamus pemetaan simbol musik ke teks fonetik.
  3. Implementasikan `app/Services/ChordTransposer.php` sesuai algoritma §7.5 lengkap dengan PHPDoc Bahasa Indonesia.
  4. Implementasikan `app/Services/PronunciationGenerator.php` sesuai algoritma §7.6 lengkap dengan PHPDoc Bahasa Indonesia.
  5. Perbarui model `Chord` dan `ChordDiagram`: tambahkan `audio_url` accessor via `Storage::disk(config('tts.disk'))->url(...)`, dan lengkapi method `deleteWithFiles()`.
  6. Buat `app/Policies/SongPolicy.php` untuk memvalidasi hak akses lagu publik vs privat dan hak edit pemilik/admin.
  7. Perbarui model `SongSection` dan `LyricLine` dengan alias relasi `lines()` dan `placements()` sesuai skema §9.3.
  8. Buat `app/Http/Controllers/SongAudioController.php` dengan alur linear:
     - `// 1. Otorisasi & Validasi Input`: Pastikan user berhak mengedit lagu (`can:update,song`), validasi `offset` berupa angka (`numeric`, `between:-10.000,10.000`, max 3 decimal places).
     - `// 2. Query Database`: Update nilai `backing_track_offset` pada model `Song`.
     - `// 3. Response / Render View`: Kembalikan JSON 200 `{ "success": true, "backing_track_offset": ... }`.
  9. Buat `app/Http/Controllers/SongTimelineController.php` dengan alur linear 3 blok komentar:
     - Query lagu, sections, lines, dan placements lengkap (`position !== null && start_beat !== null`).
     - Bentuk struktur respons JSON §9.3 dengan field eksplisit:
       `song.backing_track_offset` dan `song.backing_track.offset`.
     - Jalankan transpose read-only jika `?transpose=N` diberikan, dan dispatch asynchronous TTS Job jika ada chord baru yang belum ada di tabel `chords`.
  10. Daftarkan route di `routes/web.php`:
     - `GET /songs/{song}/timeline` (akses sesuai visibilitas lagu).
     - `PATCH /songs/{song}/backing-track/offset` (middleware `auth`, `can:update,song`).
  11. Rancang modul JS di `resources/js/audio/audio-engine.js`:
     - Scheduler Web Audio API berbasis `AudioContext.currentTime`.
     - Penyelarasan beat 0 ke `t0 + backing_track_offset`.
     - Implementasi method `setBackingTrackOffset(newOffset)`: menyesuaikan anchor waktu playback backing track secara dinamis saat lagu sedang berjalan tanpa audio dropout/glitch.
     - Metronom aksen bar dan pemutar buffer aba-aba chord dengan jeda `cue_margin_ms`.
  12. Tulis pengujian Pest:
       - `tests/Feature/TimelineTest.php`: verifikasi respons JSON timeline memuat `backing_track_offset`.
       - `tests/Feature/SongAudioOffsetTest.php`: verifikasi validasi offset (-10.000 s/d +10.000, step 0.001s), otorisasi 403 untuk non-pemilik, dan respons 200 untuk pemilik/admin.
       - `tests/Unit/MusicServicesTest.php`: pengujian unit transposer dan pengucapan fonetik.
- **File Dibuat / Dimodifikasi**:
  - *Create*: `config/chordcue.php`, `config/pronunciation.php`
  - *Create*: `app/Services/ChordTransposer.php`, `app/Services/PronunciationGenerator.php`
  - *Create*: `app/Policies/SongPolicy.php`
  - *Create*: `app/Http/Controllers/SongAudioController.php`
  - *Create*: `app/Http/Controllers/SongTimelineController.php`
  - *Create*: `resources/js/audio/audio-engine.js`
  - *Modify*: `app/Models/Chord.php`, `app/Models/ChordDiagram.php`, `app/Models/SongSection.php`, `app/Models/LyricLine.php`, `routes/web.php`
  - *Create*: `tests/Feature/TimelineTest.php`, `tests/Feature/SongAudioOffsetTest.php`, `tests/Unit/MusicServicesTest.php`
- **Aturan & Skills yang Berlaku**:
  - `CHORDCUE_RULES.md` §2 (gaya ramah pemula, linear controller), §3 (PHPDoc Bahasa Indonesia), §5 (penghapusan file via method eksplisit), §7 (Storage relative path), §8 (modul Vanilla JS terpisah dari Alpine), §9 (firstOrCreate & wasRecentlyCreated queue dispatch).
- **Adaptasi TTS**:
  - Menghubungkan pembuatan chord hasil transpose dengan `PronunciationGenerator` dan antrean `GenerateChordAudio`.
- **Kriteria Penerimaan (Checklist Verifikasi)**:
  - [x] `GET /songs/{song}/timeline` mengembalikan JSON sesuai struktur §9.3 dengan HTTP 200 dan memuat `backing_track_offset`. (PASS)
  - [x] `PATCH /songs/{song}/backing-track/offset` berhasil mengupdate offset jika nilainya di antara `-10.000` dan `+10.000`. (PASS)
  - [x] `PATCH /songs/{song}/backing-track/offset` mengembalikan 422 jika offset > 10.000, < -10.000, atau bukan format desimal valid. (PASS)
  - [x] `PATCH /songs/{song}/backing-track/offset` mengembalikan 403 jika diakses oleh user yang bukan pemilik lagu atau admin. (PASS)
  - [x] Transpose parameter `?transpose=2` menggeser chord (misal `C` → `D`, `Am` → `Bm`). (PASS)
  - [x] Chord baru yang belum ada di database otomatis dibuat dengan `pronunciation` fonetik dan memicu queue TTS. (PASS)
  - [x] Method `setBackingTrackOffset` pada `audio-engine.js` memperbarui offset live tanpa memutus konteks audio. (PASS)
  - [x] Seluruh pengujian Pest di Batch 0 lulus 100%. (PASS: 22 passed, 150 assertions; seluruh suite 79 passed, 341 assertions)

---

### BATCH 1: Player Page [SELESAI / COMPLETED]
**Tujuan**: Membangun halaman latihan interaktif (`/songs/{song}`) dengan tampilan Stage & Tape Deck, chord sheet dinamis, metronom, aba-aba TTS, sinkronisasi backing track (terikat `t0 + backing_track_offset`), dan kontrol transpose/tempo sesi.

- **Status**: **SELESAI (COMPLETED)**
- **Scope**:
  - Route `GET /songs/{song}` dan method `SongController::show()`.
  - View Blade `resources/views/songs/player.blade.php`.
  - Integrasi komponen Alpine.js untuk UI player dengan modul `audio-engine.js`.
  - Tampilan chord sheet interaktif dengan 3 status visual (Normal, Aktif, Sudah Dimainkan).
  - Kontrol tape deck: Play/Stop, BPM Sesi slider/input, Transpose stepper (−11 s/d +11), toggle Mute Metronom, Mute Chord Audio, Mute Backing Track.
  - Penanganan status kesiapan audio (`audio_ready: false`) dengan polling otomatis setiap 3 detik (maks 60 detik).
  - Penanganan backing track offset: sinkronisasi `t0 + backing_track_offset`, count-in dimulai pada `t0 + offset - countIn`.
  - Auto-mute backing track jika BPM sesi ≠ BPM lagu tanpa mengubah nilai `backing_track_offset`.
  - Live adjustment support: jika offset berubah, audio engine menyesuaikan tanpa breaking active playback.
  - Pengujian Pest untuk otorisasi, render Player, dan sinkronisasi offset.
- **Out of Scope**:
  - Form pengeditan lirik, section, dan penempatan chord (masuk Batch 2 & 3).
  - Kontrol pengeditan offset backing track (masuk Batch 2 & 3 di Editor).
- **Langkah Kerja Rinci**:
  1. Implementasikan method `show(Song $song)` pada `app/Http/Controllers/SongController.php` dengan otorisasi Policy (publik terbuka untuk guest, privat untuk pemilik/admin).
  2. Daftarkan route `GET /songs/{song}` di `routes/web.php`.
  3. Buat view `resources/views/songs/player.blade.php` dengan tata letak visual "Stage & Tape Deck":
     - Panel kontrol atas/bawah bertema rack hardware studio (`bg-surface-1`, `border-subtle`).
     - Tampilan metrik BPM, Time Signature, Key, dan Offset dalam `font-mono`.
     - Slider tempo dan kontrol transpose tactile (`rounded-sm`).
     - Indikator status audio (badge kesiapan audio & loading state).
  4. Sambungkan Alpine.js dengan `audio-engine.js`:
     - Load data timeline via `fetch('/songs/{song}/timeline?transpose=' + currentTranspose)`.
     - Inisialisasi offset dari `timelineData.song.backing_track_offset`.
     - Playback mengaktifkan `AudioContext.resume()`.
     - Metronom count-in dijadwalkan pada `t0 + offset - countIn`; lagu dan beat 1 dimulai pada `t0 + offset`.
     - Event sync untuk memperbarui kelas CSS chord aktif dan chord sudah dimainkan berdasarkan timing audio.
  5. Terapkan aturan tempo sesi: jika BPM sesi diubah dari BPM asli lagu, auto-mute tombol backing track tanpa merubah offset fisik audio.
  6. Terapkan logika polling kesiapan audio jika `audio_ready === false`.
  7. Tulis pengujian Pest di `tests/Feature/PlayerPageTest.php` mencakup skenario offset playback dan otorisasi.
- **File Dibuat / Dimodifikasi**:
  - *Modify*: `app/Http/Controllers/SongController.php`, `config/chordcue.php`, `.env`, `.env.example`, `config/filesystems.php`, `routes/web.php`, `resources/views/components/song-card.blade.php`, `resources/views/components/icon.blade.php`, `resources/js/audio/audio-engine.js`, `resources/js/app.js`
  - *Create*: `resources/js/player.js`, `resources/views/songs/player.blade.php`
  - *Create*: `tests/Feature/PlayerPageTest.php`, `tests/js/timing.test.mjs`
- **Aturan & Skills yang Berlaku**:
  - `CHORDCUE_RULES.md` §8 (Alpine tidak memuat audio logic; fetch CSRF; bahasa UI murni Bahasa Indonesia tanpa `__()`), `design-rules.md` (tema gelap `#0A0F1A`, whitelist radius, `font-mono` untuk metrik, no inline CSS), skill `frontend-design`.
- **Adaptasi TTS**:
  - Memanfaatkan audio URL yang dihasilkan Batch 0. Memutar klip dengan lead time `cue_margin_ms` (default `1000` ms / `1.0` dtk via `CHORDCUE_CUE_MARGIN_MS`) sebelum beat melalui helper `computeChordScheduleTime()` dengan clamp `minStartTime = max(t0, prevBeatTime)`.
- **UI Changes Sesuai Design Rule**:
  - Mengganti gaya krem/ungu Project B menjadi palet Rack Studio gelap murni: Latar `bg-base` (`#0A0F1A`), kartu lirik `bg-surface-1` (`#111826`), baris kontrol `bg-surface-2` (`#182133`).
  - Chord aktif disorot dengan aksen `bg-accent text-accent-on font-bold rounded-sm`.
  - Chord sudah dimainkan diberi warna redup `text-ink-tertiary` tanpa glowing blur klise AI.
- **Kriteria Penerimaan (Checklist Verifikasi)**:
  - [x] Rute `GET /songs/{song}` dapat diakses oleh guest jika lagu publik, dan menolak (403) jika privat milik orang lain. (PASS: diverifikasi di `PlayerPageTest`)
  - [x] Sinkronisasi offset dan count-in metronom selalu mencapai nol tepat bersamaan pada beat 1 lagu (baik dengan maupun tanpa file backing track) tanpa jeda/dropout: jika offset >= 1 bar, metronom mulai saat offset tersisa 1 bar; jika offset < 1 bar, metronom mulai duluan dan offset/backing track mulai saat bar tersisa sebesar offset. (PASS: diimplementasikan melalui `computeAnchors()` di [audio-engine.js](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/js/audio/audio-engine.js) dan diverifikasi unit test nyata `tests/js/timing.test.mjs`)
  - [x] Lirik tidak berubah warna saat pergantian chord dan selalu tampil kontras putih terang (`text-white`), hanya lencana chord yang bertransisi status visual (Normal, Aktif, Sudah Dimainkan). (PASS: diverifikasi di `PlayerPageTest`)
  - [x] Mengubah BPM sesi me-mute backing track secara otomatis tanpa mengubah nilai `backing_track_offset`. (PASS: diimplementasikan di `player.js`, `audio-engine.js`, dan diverifikasi di `tests/js/timing.test.mjs`)
  - [x] Klip audio TTS chord dijadwalkan dengan lead time reaksi `cue_margin_ms` (default `1000` ms / `1.0` dtk, dapat diatur via `CHORDCUE_CUE_MARGIN_MS`) menggunakan rumus `Tstart = max(minStartTime, Tbeat - D_speech - M_cue)` lewat helper bersama `computeChordScheduleTime()` (dipakai di `play()` maupun `recalibrateScheduledEvents()`) dengan `minStartTime = max(t0, prevBeatTime)`, deteksi akhir ucapan aktual (`measureSpeechEnd` pada ambang `-40 dBFS`), serta konstanta `chord_cue_trim_ms` di `config/chordcue.php`. (PASS: diverifikasi unit test nyata `tests/js/timing.test.mjs` & `PlayerPageTest`)
  - [x] Tampilan chord sheet menyorot chord aktif tepat pada ketukannya dan berpindah ke "sudah dimainkan" setelahnya. (PASS: diimplementasikan via reactive callback `onChordStateChange`)
  - [x] Mengubah Transpose ke +1 memuat ulang timeline transpose tanpa menyentuh database lagu. (PASS: diverifikasi di `PlayerPageTest`)
  - [x] Seluruh teks antarmuka ditulis langsung dalam Bahasa Indonesia tanpa `{{ __('...') }}`. (PASS: diverifikasi di `PlayerPageTest`)
  - [x] Lirik tetap tampil kontras (`text-white`) meski tanpa chord placements; pesan status peringatan terpusat di LCD tanpa duplikasi; slider BPM (`0..200`) selaras presisi dengan bullet (`40..240`); metronom tetap dapat diputar meski tanpa chord (`isContinuousMetronome`) sementara lagu ber-chord mewajibkan audio chord siap (`canPlay()`); informasi offset (`ms` & `s`) tampil aktif. (PASS: diverifikasi di `PlayerPageTest`)
  - [x] Seluruh pengujian otomatis lulus 100%. (PASS: Node timing tests 7 passed; `PlayerPageTest` 19 passed; seluruh Pest suite 101 passed, 1 skipped, 490 assertions)

---

### BATCH 2: Simple Editor Page
**Tujuan**: Membangun halaman Editor Simple (`/songs/{song}/editor/simple`) untuk mengelola section, baris lirik, penempatan chord di atas huruf lirik (`position`), serta bilah kontrol Backing Track dengan penyesuaian offset taktil (`font-mono` & fine-tune nudge buttons).

- **Scope**:
  - Route `GET /songs/{song}/editor/simple` dan view `resources/views/songs/editor-simple.blade.php`.
  - Bilah Backing Track khusus:
    - Input numerik taktil dalam `font-mono` (desimal 3 digit, step 0.001s).
    - Tombol fine-tune nudge: `-100ms`, `-10ms`, `+10ms`, `+100ms`, dan `Reset` (kembali ke offset awal tersimpan).
    - Terhubung secara AJAX ke `PATCH /songs/{song}/backing-track/offset`.
  - Implementasi controller AJAX: `SongSectionController`, `LyricLineController`, dan `ChordPlacementController`.
  - Drawer / sidebar master chord dengan pencarian instan (filter nama chord).
  - Tampilan baris lirik interaktif dengan slot karakter untuk drag-and-drop.
  - Daftar "belum ditempatkan" (unplaced chords) per baris untuk placement dengan `position === null`.
  - Aksi penempatan (`position`), pemindahan, pelepasan (`release`), dan penghapusan placement.
  - Deteksi tabrakan huruf per baris dalam transaksi atomik dengan `409 Conflict` dan opsi timpa (`force: true`).
  - Aturan pelepasan otomatis koordinat jika lirik dipotong lebih pendek dari posisi chord tersimpan.
  - Pengujian Pest untuk operasi CRUD, bilah Backing Track offset, dan manipulasi Simple Editor.
- **Out of Scope**:
  - Penjadwalan birama dan grid beat (masuk Batch 3).
  - Playback audio aba-aba (hanya ada di Player).
- **Langkah Kerja Rinci**:
  1. Lengkapi `SongSectionController`: implementasikan `store`, `update`, `destroy` sesuai rute §9.2 dengan otorisasi Policy pemilik/admin.
  2. Lengkapi `LyricLineController`: implementasikan `store`, `update`, `destroy`. Pada saat update isi baris, jika panjang baris baru lebih pendek dari `position` placement yang ada, set `position = null` (lepas otomatis sesuai §5.4).
  3. Lengkapi `ChordPlacementController`:
     - `store`: buat placement baru dengan `position` terisi dan `start_beat = null`.
     - `update`: perbarui `position` saja. Gunakan `DB::transaction` dan `lockForUpdate()`. Validasi keunikan `position` pada `lyric_line_id` yang sama. Jika tabrakan tanpa `force`, kirim `409 Conflict`. Jika `force: true`, hapus placement lama.
     - `release`: endpoint `POST /placements/{placement}/release` yang mengosongkan `position = null` tanpa mengubah `start_beat`.
     - `destroy`: hapus record placement seutuhnya.
  4. Rancang bilah Backing Track di `resources/views/songs/editor-simple.blade.php`:
     - Kontainer `bg-surface-1 border border-subtle rounded-lg p-16 mb-16`.
     - Input numerik offset taktil: `h-[40px] px-14 rounded-sm bg-surface-2 border border-subtle text-ink font-mono text-sm w-[120px] focus:border-accent`.
     - Kelompok tombol fine-tune nudge tactile:
       - Tombol `-100ms`: `bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-xs rounded-sm px-10 h-[32px]`.
       - Tombol `-10ms`: `bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-xs rounded-sm px-10 h-[32px]`.
       - Tombol `+10ms`: `bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-xs rounded-sm px-10 h-[32px]`.
       - Tombol `+100ms`: `bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-mono text-xs rounded-sm px-10 h-[32px]`.
       - Tombol `Reset`: `bg-surface-2 hover:bg-surface-3 border border-subtle text-ink-secondary hover:text-ink font-mono text-xs rounded-sm px-12 h-[32px]`.
     - Komponen Alpine.js menangani interaksi klik nudge, memperbarui nilai input, dan memanggil `fetch('/songs/' + songId + '/backing-track/offset', { method: 'PATCH', ... })` dengan debounce halus / on change.
  5. Rancang area lirik utama dengan slot huruf drag-and-drop dan drawer unplaced chords per baris.
  6. Tulis pengujian Pest di `tests/Feature/SimpleEditorTest.php` mencakup verifikasi render tombol nudge offset dan AJAX update.
- **File Dibuat / Dimodifikasi**:
  - *Modify*: `app/Http/Controllers/SongSectionController.php`, `app/Http/Controllers/LyricLineController.php`, `app/Http/Controllers/ChordPlacementController.php`, `routes/web.php`
  - *Create*: `resources/views/songs/editor-simple.blade.php`
  - *Create*: `tests/Feature/SimpleEditorTest.php`
- **Aturan & Skills yang Berlaku**:
  - `CHORDCUE_RULES.md` §2 (linear controller), §3 (PHPDoc Bahasa Indonesia), §6 (atomik koordinat & lockForUpdate), §8 (Alpine UI state, AJAX CSRF, teks Bahasa Indonesia), `design-rules.md` (Stage & Tape Deck, modal dialog tokens, `font-mono` untuk metrik offset), skill `frontend-design`.
- **Adaptasi TTS**:
  - Editor Simple tidak memutar audio TTS. Menampilkan daftar nama chord dari tabel master `chords`.
- **UI Changes Sesuai Design Rule**:
  - Bilah Backing Track mengikuti estetika hardware tape recorder (indikator waktu offset taktil `font-mono`).
  - Chip chord menggunakan `bg-surface-2 border border-subtle text-accent font-mono text-xs px-8 py-4 rounded-sm`.
- **Kriteria Penerimaan (Checklist Verifikasi)**:
  - [ ] Bilah Backing Track menampilkan input numerik offset dalam `font-mono` dengan format desimal 3 angka (mis. `0.000` atau `2.350`).
  - [ ] Tombol fine-tune nudge (`-100ms`, `-10ms`, `+10ms`, `+100ms`) menambah/mengurangi offset secara tepat sesuai nilai milidetik.
  - [ ] Tombol `Reset` mengembalikan nilai offset ke nilai awal yang tersimpan.
  - [ ] Perubahan offset mengirim request `PATCH /songs/{song}/backing-track/offset` dan tersimpan ke database.
  - [ ] Drag-and-drop chord ke huruf berhasil menyimpan `position` dan mempertahankan `start_beat` lama jika sudah ada.
  - [ ] Aksi "Lepas" berhasil mengubah `position` menjadi null dan chord berpindah ke daftar belum ditempatkan pada baris itu.
  - [ ] Seluruh pengujian Pest di Batch 2 lulus 100%.

---

### BATCH 3: Beat Editor Page
**Tujuan**: Membangun halaman Editor Beat (`/songs/{song}/editor/beat`) untuk mengatur koordinat ketukan global (`start_beat`) berbasis grid birama berlapis per baris lirik sesuai algoritma §7.2, lengkap dengan bilah kontrol Backing Track offset taktil.

- **Scope**:
  - Route `GET /songs/{song}/editor/beat` dan view `resources/views/songs/editor-beat.blade.php`.
  - Bilah Backing Track terintegrasi (identik dengan Batch 2):
    - Input numerik offset dalam `font-mono` (step 0.001s).
    - Tombol fine-tune nudge: `-100ms`, `-10ms`, `+10ms`, `+100ms`, dan `Reset`.
    - Mengatur bantalan fisik audio backing track tanpa mengubah posisi beat pada grid birama.
  - Algoritma render grid per baris lirik (§7.2): kalkulasi `barAwal`, `barAkhirTerpakai`, serta bar kosong di akhir baris.
  - Pengaturan drag-and-drop chord ke sel beat grid (1-indexed global beat).
  - Penegakan keunikan global `start_beat` per lagu dengan `409 Conflict` dan opsi timpa `force: true`.
  - Drawer unplaced chords per baris lirik (chord yang memiliki `position` namun `start_beat === null`).
  - Aksi "Lepas" beat (`release`) yang mengosongkan `start_beat` tanpa menghapus `position`.
  - Pengujian Pest untuk logika grid render, manipulasi `start_beat`, dan offset bar interaksi.
- **Out of Scope**:
  - Pemutaran sesi Player interaktif (sudah di Batch 1).
  - Pengeditan teks lirik (dilakukan di Simple Editor).
- **Langkah Kerja Rinci**:
  1. Buat helper/service method untuk kalkulasi struktur grid birama per baris lirik sesuai spesifikasi §7.2:
     - Hitung `B = time_signature_numerator`.
     - Hitung `barAwal` dan `barAkhirTerpakai` berdasarkan placement baris sebelumnya dan baris berjalan.
     - Tambahkan 1 bar kosong di akhir setiap baris.
  2. Tambahkan aksi pada `ChordPlacementController`:
     - Menangani update `start_beat` (bilangan bulat ≥ 1) dengan lock transaksi pada tabel lagu.
     - Pengecekan tabrakan beat global (lintas baris dalam lagu yang sama).
     - Menangani request pelepasan beat `release` dengan `coordinate: 'start_beat'`.
  3. Daftarkan route `GET /songs/{song}/editor/beat` di `routes/web.php`.
  4. Rancang view `resources/views/songs/editor-beat.blade.php` dengan Alpine.js:
     - Bilah Backing Track di bagian atas editor: input numerik offset taktil `font-mono` dan tombol fine-tune nudge (`-100ms`, `-10ms`, `+10ms`, `+100ms`, `Reset`).
     - Header ringkasan metrik lagu (BPM, Time Signature) dalam tampilan rack hardware studio.
     - Bagian lirik berlapis: di atas setiap baris lirik, render grid bar dan beat kotak tactile (`font-mono`).
     - Indikator bar kosong di ujung kanan baris.
     - Unplaced chords baris: chip chord yang siap ditarik ke dalam sel grid.
     - Dialog konfirmasi tabrakan beat jika beat target telah digunakan oleh chord lain di lagu tersebut.
  5. Tulis pengujian Pest di `tests/Feature/BeatEditorTest.php` memverifikasi rendering grid dan offset controls.
- **File Dibuat / Dimodifikasi**:
  - *Modify*: `app/Http/Controllers/ChordPlacementController.php`, `routes/web.php`
  - *Create*: `resources/views/songs/editor-beat.blade.php`
  - *Create*: `tests/Feature/BeatEditorTest.php`
- **Aturan & Skills yang Berlaku**:
  - `CHORDCUE_RULES.md` §2 (linear controller), §3 (PHPDoc Bahasa Indonesia), §6 (atomik transaksi & keunikan beat global), §8 (Alpine UI, AJAX CSRF, teks Bahasa Indonesia), `design-rules.md` (Stage & Tape Deck grid, `font-mono` untuk nomor bar, beat, dan offset), skill `frontend-design`.
- **Adaptasi TTS**:
  - Editor Beat berfokus murni pada koordinat timing musikal; audio TTS tidak diputar di sini.
- **UI Changes Sesuai Design Rule**:
  - Bilah Backing Track dengan tombol nudge taktil `font-mono`.
  - Sel beat pada grid dirender sebagai tombol/slot taktil: `w-32 h-32 bg-surface-2 border border-subtle text-ink font-mono text-xs rounded-sm hover:border-accent`.
  - Beat pertama tiap birama (downbeat) diberi penanda visual garis batas aksen (`border-l-accent`).
  - Bar kosong diberi latar sedikit lebih redup dengan label subtil "Bar Kosong".
- **Kriteria Penerimaan (Checklist Verifikasi)**:
  - [ ] Bilah Backing Track dengan input offset numerik dan tombol fine-tune nudge hadir serta berfungsi menyimpan nilai via `PATCH /songs/{song}/backing-track/offset`.
  - [ ] Mengubah offset backing track tidak mengubah atau menggeser penomoran beat pada grid birama.
  - [ ] Grid birama dirender berlapis per baris lirik secara tepat mengikuti algoritma §7.2.
  - [ ] Menempatkan chord pada sel beat menyimpan nilai `start_beat` (1-indexed) dan mempertahankan `position` pada baris.
  - [ ] Jika beat target sudah dipakai di baris lain pada lagu yang sama, server mengembalikan `409 Conflict` dan modal konfirmasi timpa muncul di layar.
  - [ ] Konfirmasi timpa berhasil menghapus placement lama dan menaruh placement baru secara atomik.
  - [ ] Aksi "Lepas" beat mengosongkan `start_beat` dan mengembalikan chord ke daftar unplaced baris itu tanpa menghilangkan posisinya di lirik.
  - [ ] Seluruh pengujian Pest di Batch 3 lulus 100%.

---

## 6. Analisis Kesenjangan (Gap Analysis) & Konflik Aturan (Rule Conflicts)

### 6.1 Analisis Kesenjangan Teknis
1. **Model Deletion Chain**:
   - `Song::deleteWithFiles()` sudah ada, namun `Chord::deleteWithFiles()` dan `ChordDiagram::deleteWithFiles()` belum ada. Menghapus chord saat ini berpotensi meninggalkan file MP3 atau gambar diagram orphan di storage disk. Hal ini akan dituntaskan pada Batch 0.
2. **Missing Configuration**:
   - `config/chordcue.php` dan `config/pronunciation.php` diwajibkan oleh spesifikasi §3.3 dan §7.6 namun belum dibuat. Dibuat pada Batch 0.
3. **Endpoint Offset Backing Track Terdedikasi**:
   - Diperlukan endpoint mandiri `PATCH /songs/{song}/backing-track/offset` pada `SongAudioController` dengan validasi rentang `-10.000` s/d `+10.000` detik agar kedua editor dapat melakukan live nudge tanpa harus mengirim ulang payload multipart audio backing track.
4. **Koordinat Independen vs Perilaku Rusak di Project B**:
   - Di Project B, memindahkan chord di satu editor sering mereset koordinat di editor lain. Di Project A, independensi koordinat dijaga ketat di level controller dan database transaction.

### 6.2 Daftar Konflik Aturan (Project B vs Project A)
| Kasus di Project B | Pelanggaran Terhadap Aturan Project A | Penyesuaian & Solusi Wajib di Project A |
|---|---|---|
| Menggunakan rute API stateless (`/api/...`) tanpa session Breeze | Melanggar `CHORDCUE_RULES.md` §8 (AJAX wajib session berbasis web dengan CSRF token). | Seluruh rute editor dan timeline menggunakan rute web (`routes/web.php`) dengan middleware `web` dan `auth`. |
| Menjalankan job TTS sinkron di request HTTP saat transpose | Menghambat latensi HTTP dan memicu timeout jika TTS lambat. | Transpose di Player hanya membaca timeline. Chord baru disimpan via `firstOrCreate` dan Job `GenerateChordAudio` diantrikan di background queue worker. |
| Simbol transpose `#` dikirim mentah ke TTS (dibaca "hash") | Menghasilkan audio ucapan yang salah bagi musisi. | Menggunakan `PronunciationGenerator` untuk mengonversi `#` menjadi `"sharp"`, `m` menjadi `"minor"`, `/` menjadi `"over"`. |
| Menghapus placement lawan saat drag-and-drop | Melanggar prinsip independensi koordinat §5.4. | Update koordinat hanya menyentuh kolom milik editor aktif (`position` di Simple, `start_beat` di Beat). |
| Warna-warni bebas, inline CSS `:style="..."`, dan Google Font | Melanggar `design-rules.md` (Stage & Tape Deck dark theme only, no inline CSS, whitelist 4 radius, system fonts). | Gunakan token Tailwind murni, class utility, `font-sans` untuk UI, dan `font-mono` untuk metrik musik (BPM, beat, offset). |
| Penggunaan fungsi lokalisasi `__('...')` | Melanggar `CHORDCUE_RULES.md` §8 & §10 no. 12. | Seluruh teks antarmuka (copy) ditulis langsung dalam Bahasa Indonesia di file Blade. |

---

## 7. Alur Persetujuan & Batasan Eksekusi
- **STATUS SAAT INI**: Revisi perencanaan selesai. TIDAK ADA KODE APLIKASI YANG DITULIS ATAU DIMODIFIKASI.
- **LANGKAH SELANJUTNYA**: Berhenti di sini dan menunggu persetujuan eksplisit dari Pengguna (USER).
- **ALUR PENGERJAAN SETELAH DISETUJUI**:
  1. Kerjakan **SATU BATCH SAJA** pada satu waktu (diawali dengan Batch 0 setelah disetujui).
  2. Jalankan acceptance checklist dan pengujian Pest untuk batch tersebut.
  3. Ringkas perubahan, sebutkan file yang disentuh, laporkan deviasi jika ada, lalu **STOP** dan minta persetujuan eksplisit sebelum melangkah ke batch berikutnya.
