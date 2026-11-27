# **BAB 5**

# **IMPLEMENTASI SISTEM**

[Isi: jelaskan gambaran umum tahap implementasi sistem ChordCue yang mencakup implementasi struktur basis data MySQL melalui migrasi Laravel, implementasi kode program pada sisi backend dan frontend, serta implementasi antarmuka pengguna berdasarkan peran akses]

1. ## **Implementasi Basis Data**

[Isi: jelaskan implementasi skema basis data MySQL pada aplikasi ChordCue yang terdiri atas 10 tabel domain utama sesuai Entity Relationship Diagram pada Bab 4 serta pengelolaan migrasinya pada Laravel]

1. ### **Tabel Users**

   [Isi: jelaskan implementasi tabel users untuk menyimpan data akun pengguna beserta atribut autentikasi (username, email, password), peran akses (role admin dan user), batas kuota pembuatan lagu (song_limit), serta relasi one-to-many terhadap tabel songs]

##### **Gambar 5.1** Struktur tabel users

2. ### **Tabel Songs**

   [Isi: jelaskan implementasi tabel songs untuk menyimpan metadata lagu dan chord sheet (title, default_key, bpm, time_signature_numerator, time_signature_denominator, backing_track_path, backing_track_offset, visibility) beserta foreign key user_id ke tabel users dan artist_id ke tabel artists]

##### **Gambar 5.2** Struktur tabel songs

3. ### **Tabel Artists**

   [Isi: jelaskan implementasi tabel artists untuk menyimpan daftar master nama penyanyi atau grup musik yang bersifat unik serta relasi one-to-many terhadap tabel songs dengan aturan penghapusan terbatas (restrict)]

##### **Gambar 5.3** Struktur tabel artists

4. ### **Tabel Categories**

   [Isi: jelaskan implementasi tabel categories untuk menyimpan data master kategori pengelompokan lagu yang terdiri atas atribut name dan tipe kategori (genre, mood, dan location) serta relasinya terhadap tabel songs melalui tabel pivot category_song]

##### **Gambar 5.4** Struktur tabel categories

5. ### **Tabel Category\_Song**

   [Isi: jelaskan implementasi tabel pivot category_song yang menjembatani relasi many-to-many antara tabel categories dan tabel songs melalui pasangan foreign key category_id dan song_id dengan constraint unique serta cascade on delete]

##### **Gambar 5.5** Struktur tabel category\_song

6. ### **Tabel Song\_Sections**

   [Isi: jelaskan implementasi tabel song_sections untuk menyimpan pembagian struktur bagian lagu (seperti Intro, Verse, dan Chorus), urutan bagian (sequence), serta relasinya dengan tabel songs dan tabel lyric_lines]

##### **Gambar 5.6** Struktur tabel song\_sections

7. ### **Tabel Lyric\_Lines**

   [Isi: jelaskan implementasi tabel lyric_lines untuk menyimpan urutan baris (line_number) dan teks lirik (content) pada setiap bagian lagu beserta relasi foreign key section_id ke tabel song_sections dan relasi one-to-many ke tabel chord_placements]

##### **Gambar 5.7** Struktur tabel lyric\_lines

8. ### **Tabel Chord\_Placements**

   [Isi: jelaskan implementasi tabel chord_placements untuk mencatat posisi penempatan chord pada indeks karakter lirik (position) di Simple Editor dan nomor ketukan global (start_beat) di Beat Editor sebagai pemicu penjadwalan audio TTS, beserta relasinya ke tabel lyric_lines dan tabel chords]

##### **Gambar 5.8** Struktur tabel chord\_placements

9. ### **Tabel Chords**

   [Isi: jelaskan implementasi tabel chords untuk menyimpan daftar master chord yang mencakup nama notasi chord unik (name), teks pelafalan Bahasa Indonesia untuk konversi TTS (pronunciation), dan path berkas audio MP3 (file_path), beserta relasinya terhadap tabel chord_placements dan tabel chord_diagrams]

##### **Gambar 5.9** Struktur tabel chords

10. ### **Tabel Chord\_Diagrams**

    [Isi: jelaskan implementasi tabel chord_diagrams untuk menyimpan lokasi berkas gambar diagram posisi jari pada fretboard gitar (file_path) serta relasi foreign key chord_id yang merujuk ke tabel chords]

##### **Gambar 5.10** Struktur tabel chord\_diagrams

2. ## **Implementasi Kode Program**

[Isi: jelaskan arsitektur implementasi kode program ChordCue yang menggunakan kerangka kerja Laravel pada sisi server (controller, service, job antrean, model, dan policy) serta modul JavaScript berbasis Alpine.js dan Web Audio API pada sisi klien]

1. ### **Fitur Autentikasi, Hak Akses, dan Profil Pengguna**

   [Isi: jelaskan implementasi mekanisme registrasi akun baru, autentikasi sesi login pengguna, pembatasan rute khusus administrator melalui middleware EnsureUserIsAdmin, kebijakan otorisasi kepemilikan dan visibilitas lagu pada SongPolicy, serta pengelolaan profil dan penghapusan akun pengguna]

###### **Listing 5.1** Fungsi store() pada RegisteredUserController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/Auth/RegisteredUserController.php::store -->

###### **Listing 5.2** Fungsi store() pada AuthenticatedSessionController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/Auth/AuthenticatedSessionController.php::store -->

###### **Listing 5.3** Fungsi handle() pada middleware EnsureUserIsAdmin

\[Tabel kode\]
<!-- sumber: app/Http/Middleware/EnsureUserIsAdmin.php::handle -->

###### **Listing 5.4** Fungsi view(), update(), dan delete() pada SongPolicy

\[Tabel kode\]
<!-- sumber: app/Policies/SongPolicy.php::view -->

###### **Listing 5.5** Fungsi update() dan destroy() pada ProfileController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/ProfileController.php::update -->

2. ### **Fitur Katalog dan Pencarian Lagu serta Daftar Lagu Pengguna (My Songs)**

   [Isi: jelaskan implementasi query katalog lagu publik pada halaman utama yang mendukung pencarian kata kunci judul atau artis serta pemfilteran berdasarkan artis, genre, mood, dan lokasi, serta implementasi halaman Lagu Saya (My Songs) yang memfilter daftar lagu milik pengguna aktif berdasarkan visibilitas publik atau privat]

###### **Listing 5.6** Route handler katalog publik dan pemfilteran lagu pada routes/web.php

\[Tabel kode\]
<!-- sumber: routes/web.php::home -->

###### **Listing 5.7** Route handler daftar lagu pribadi (My Songs) pada routes/web.php

\[Tabel kode\]
<!-- sumber: routes/web.php::my.songs -->

3. ### **Fitur Pembuatan dan Pengelolaan Chord Sheet (Metadata, Section, dan Baris Lirik)**

   [Isi: jelaskan implementasi pembuatan lagu baru dengan pemeriksaan batas kuota song_limit, pemuatan data pada halaman Simple Editor dan Beat Editor, pembaruan metadata lagu beserta sinkronisasi kategori dan unggahan backing track, penghapusan lagu beserta berkas fisiknya, serta operasi CRUD asinkron untuk bagian lagu (section) dan baris lirik disertai penyesuaian posisi chord ketika teks lirik berubah]

###### **Listing 5.8** Fungsi store() pada SongController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/SongController.php::store -->

###### **Listing 5.9** Fungsi editorSimple() dan editorBeat() pada SongController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/SongController.php::editorSimple -->

###### **Listing 5.10** Fungsi update() pada SongController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/SongController.php::update -->

###### **Listing 5.11** Fungsi destroy() pada SongController dan deleteWithFiles() pada model Song

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/SongController.php::destroy -->

###### **Listing 5.12** Fungsi store(), update(), dan destroy() pada SongSectionController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/SongSectionController.php::store -->

###### **Listing 5.13** Fungsi store(), update(), dan destroy() pada LyricLineController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/LyricLineController.php::update -->

4. ### **Fitur Penempatan Chord dan Pembentukan Grid Birama**

   [Isi: jelaskan implementasi algoritma pembentukan struktur grid birama dan ketukan per baris lirik berdasarkan pembilang time signature pada layanan BeatGridBuilder, serta implementasi penempatan chord pada indeks huruf lirik maupun sel ketukan global termasuk penanganan konflik penimpaan (HTTP 409 Conflict) dan pelepasan ketukan (release beat)]

###### **Listing 5.14** Fungsi buildForSections() pada BeatGridBuilder

\[Tabel kode\]
<!-- sumber: app/Services/BeatGridBuilder.php::buildForSections -->

###### **Listing 5.15** Fungsi store() pada ChordPlacementController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/ChordPlacementController.php::store -->

###### **Listing 5.16** Fungsi update() pada ChordPlacementController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/ChordPlacementController.php::update -->

###### **Listing 5.17** Fungsi release() dan destroy() pada ChordPlacementController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/ChordPlacementController.php::release -->

5. ### **Fitur Pembentukan Pelafalan dan Konversi Audio Text-to-Speech (TTS)**

   [Isi: jelaskan implementasi konversi simbol notasi chord menjadi teks pelafalan Bahasa Indonesia dengan metode greedy longest-match pada layanan PronunciationGenerator, lapisan pra-pemrosesan respelling fonetik khusus TTS, sanitasi penamaan berkas MP3, serta eksekusi background job GenerateChordAudio yang memanggil CLI edge-tts melalui antrean Laravel Queue]

###### **Listing 5.18** Fungsi generate() pada PronunciationGenerator

\[Tabel kode\]
<!-- sumber: app/Services/PronunciationGenerator.php::generate -->

###### **Listing 5.19** Fungsi buildNoteToken() dan tokenizeSuffix() pada PronunciationGenerator

\[Tabel kode\]
<!-- sumber: app/Services/PronunciationGenerator.php::tokenizeSuffix -->

###### **Listing 5.20** Fungsi applyTtsRespelling() pada PronunciationGenerator

\[Tabel kode\]
<!-- sumber: app/Services/PronunciationGenerator.php::applyTtsRespelling -->

###### **Listing 5.21** Fungsi fileName() dan handle() pada job GenerateChordAudio

\[Tabel kode\]
<!-- sumber: app/Jobs/GenerateChordAudio.php::handle -->

6. ### **Fitur Pengunggahan dan Pengelolaan Audio Referensi (Backing Track)**

   [Isi: jelaskan implementasi pengunggahan berkas audio MP3 backing track ke penyimpanan publik, pengaturan nilai offset sinkronisasi ketukan pertama dalam satuan detik, serta penghapusan berkas backing track dari lagu]

###### **Listing 5.22** Fungsi store() pada SongAudioController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/SongAudioController.php::store -->

###### **Listing 5.23** Fungsi updateOffset() dan destroy() pada SongAudioController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/SongAudioController.php::updateOffset -->

7. ### **Fitur Sesi Latihan, Player Audio, dan Transpose Chord**

   [Isi: jelaskan implementasi algoritma pergeseran nada kromatik 12 semitone pada layanan ChordTransposer, penyediaan endpoint JSON timeline lagu yang turut menangani pembuatan chord hasil transpose secara on-demand pada SongTimelineController, serta implementasi modul Web Audio API pada AudioEngine untuk pemangkasan keheningan akhir audio dengan RMS, sinkronisasi count-in dan offset backing track, serta penjadwalan aba-aba suara chord (anticipatory cue scheduling)]

###### **Listing 5.24** Fungsi show() pada SongController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/SongController.php::show -->

###### **Listing 5.25** Fungsi transpose(), parseChord(), dan shiftNote() pada ChordTransposer

\[Tabel kode\]
<!-- sumber: app/Services/ChordTransposer.php::transpose -->

###### **Listing 5.26** Fungsi show() pada SongTimelineController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/SongTimelineController.php::show -->

###### **Listing 5.27** Fungsi measureSpeechEnd() pada modul AudioEngine

\[Tabel kode\]
<!-- sumber: resources/js/audio/audio-engine.js::measureSpeechEnd -->

###### **Listing 5.28** Fungsi computeAnchors() dan computeChordScheduleTime() pada modul AudioEngine

\[Tabel kode\]
<!-- sumber: resources/js/audio/audio-engine.js::computeChordScheduleTime -->

###### **Listing 5.29** Fungsi preloadTimelineAssets() dan play() pada kelas AudioEngine

\[Tabel kode\]
<!-- sumber: resources/js/audio/audio-engine.js::play -->

8. ### **Fitur Pustaka Chord dan Kelola Chord (Admin)**

   [Isi: jelaskan implementasi penampilan katalog Pustaka Chord beserta pratinjau diagram jari, penambahan chord kustom baru oleh administrator disertai pengunggahan gambar diagram dan pengiriman job TTS, pembaruan data chord yang memicu regenerasi audio apabila nama atau cara baca berubah, serta penghapusan chord dengan validasi keterpakaiandi lagu dan pembersihan berkas fisik]

###### **Listing 5.30** Fungsi index() dan store() pada ChordController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/ChordController.php::store -->

###### **Listing 5.31** Fungsi update() pada ChordController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/ChordController.php::update -->

###### **Listing 5.32** Fungsi destroy() pada ChordController dan deleteWithFiles() pada model Chord

\[Tabel kode\]
<!-- sumber: app/Models/Chord.php::deleteWithFiles -->

9. ### **Fitur Admin Kelola User**

   [Isi: jelaskan implementasi halaman manajemen pengguna bagi administrator yang menyediakan pencarian akun, penyaringan berdasarkan peran (Admin atau Member), serta penghapusan akun pengguna beserta seluruh lagu dan berkas audio miliknya secara berantai]

###### **Listing 5.33** Fungsi index() dan destroy() pada UserController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/UserController.php::index -->

###### **Listing 5.34** Fungsi deleteWithSongs() pada model User

\[Tabel kode\]
<!-- sumber: app/Models/User.php::deleteWithSongs -->

10. ### **Fitur Admin Kelola Lagu**

    [Isi: jelaskan implementasi daftar manajemen seluruh lagu publik dan privat bagi administrator yang menyajikan statistik ringkas serta pemfilteran berdasarkan judul, artis, visibilitas, dan username pemilik lagu]

###### **Listing 5.35** Route handler manajemen seluruh lagu (admin.songs.index) pada routes/web.php

\[Tabel kode\]
<!-- sumber: routes/web.php::admin.songs.index -->

11. ### **Fitur Admin Kelola Kategori dan Artist**

    [Isi: jelaskan implementasi operasi CRUD terpadu untuk ketiga tipe kategori (Genre, Mood, dan Lokasi) pada CategoryController termasuk pelepasan relasi pivot saat kategori dihapus, serta operasi CRUD data master artis pada ArtistController yang dilengkapi pembatasan penghapusan (restrict) apabila artis masih digunakan pada lagu]

###### **Listing 5.36** Fungsi index(), store(), update(), dan destroy() pada CategoryController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/CategoryController.php::index -->

###### **Listing 5.37** Fungsi index(), store(), update(), dan destroy() pada ArtistController

\[Tabel kode\]
<!-- sumber: app/Http/Controllers/ArtistController.php::index -->

3. ## **Implementasi Tampilan Sistem** 

[Isi: jelaskan implementasi antarmuka pengguna sistem ChordCue yang dibangun menggunakan Blade Template, Tailwind CSS, dan Alpine.js dengan membedakan halaman yang dapat diakses oleh pengguna umum/terdaftar dan halaman manajemen khusus administrator]

1. ### **Halaman Login & Register**

   [Isi: jelaskan implementasi tampilan halaman Login dan halaman Register untuk autentikasi pengguna, termasuk elemen formulir masukan (username, email, password, konfirmasi password), tautan perpindahan antarhalaman, serta tampilan pesan validasi kesalahan masukan]

##### **Gambar 5.11** Tampilan halaman Login

##### **Gambar 5.12** Tampilan halaman Register

2. ### **Halaman Home / Katalog**

   [Isi: jelaskan implementasi tampilan halaman utama Katalog Lagu publik yang memuat bilah navigasi, bilah pencarian judul atau artis, empat dropdown filter (Artis, Genre, Mood, dan Lokasi), grid kartu lagu beserta paginasi, serta tampilan kondisi kosong ketika hasil pencarian tidak ditemukan]

##### **Gambar 5.13** Tampilan halaman Home / Katalog lagu publik

##### **Gambar 5.14** Tampilan halaman Home / Katalog saat melakukan pencarian dan pemfilteran lagu

3. ### **Halaman My Songs**

   [Isi: jelaskan implementasi tampilan halaman Lagu Saya (My Songs) bagi pengguna yang telah login, meliputi tampilan kondisi kosong saat pengguna belum memiliki lagu, daftar kartu lagu pribadi dengan tombol pengalih filter visibilitas (Publik dan Privat), bilah pencarian, serta tombol aksi pada kartu lagu untuk memutar, mengedit, atau menghapus lagu]

##### **Gambar 5.15** Tampilan halaman My Songs dengan daftar lagu milik pengguna dan filter visibilitas

##### **Gambar 5.16** Tampilan halaman My Songs pada kondisi pengguna belum memiliki lagu pribadi

4. ### **Halaman Create New Song**

   [Isi: jelaskan implementasi tampilan jendela modal Buat Lagu Baru yang muncul saat pengguna menekan tombol Buat Lagu pada halaman My Songs, yang berisi kolom masukan judul lagu, pilihan artis, nada dasar (default key), tempo (BPM), time signature, dan pilihan visibilitas lagu]

##### **Gambar 5.17** Tampilan modal form Buat Lagu Baru (Create New Song)

5. ### **Halaman Simple dan Beat Editor**

   [Isi: jelaskan implementasi tampilan halaman editor penyusunan chord sheet yang memiliki panel atas terpadu (form metadata lagu, pengunggahan dan pemutar pratinjau MP3 backing track, pemilih kategori Genre/Mood/Wilayah, serta kontrol penggeser offset), area kerja Simple Editor untuk pengelolaan section, baris lirik, dan penempatan chord di atas karakter huruf, area kerja Beat Editor untuk penempatan chord pada sel ketukan di dalam grid birama, panel samping daftar chord, serta modal konfirmasi saat terjadi konflik penimpaan posisi huruf atau ketukan beat]

##### **Gambar 5.18** Tampilan halaman Simple Editor beserta panel metadata, kategori, dan backing track

##### **Gambar 5.19** Tampilan form tambah/ubah section dan baris lirik pada Simple Editor

##### **Gambar 5.20** Tampilan modal konfirmasi timpa posisi chord pada Simple Editor

##### **Gambar 5.21** Tampilan halaman Beat Editor dengan grid birama dan sel ketukan per baris lirik

##### **Gambar 5.22** Tampilan modal konfirmasi timpa ketukan beat pada Beat Editor

6. ### **Halaman Player**

   [Isi: jelaskan implementasi tampilan halaman Player sesi latihan yang menampilkan header metadata lagu, bilah kontrol sticky bergaya tape deck (tombol Putar dan Berhenti, layar status LCD beserta indikator birama/ketukan dan lampu LED, pengatur tempo BPM, pengatur transpose −11 hingga +11 semitone, tombol mute independen untuk metronom, suara aba-aba chord, dan backing track, kontrol offset, serta tombol gulir otomatis), dan area lembar lirik beserta sorotan (highlight) chord aktif saat pemutaran berlangsung]

##### **Gambar 5.23** Tampilan halaman Player sebelum pemutaran dimulai

##### **Gambar 5.24** Tampilan halaman Player saat sesi latihan berjalan dengan sorotan chord aktif dan perubahan transpose

7. ### **Halaman Pustaka Chord**

   [Isi: jelaskan implementasi tampilan halaman Pustaka Chord yang dapat diakses oleh seluruh pengguna, terdiri atas bilah pencarian nama chord, grid 25 kartu chord per halaman di sisi kiri, serta panel pratinjau sticky di sisi kanan yang menampilkan nama chord terpilih, teks cara baca Bahasa Indonesia, dan gambar diagram posisi jari pada fretboard gitar]

##### **Gambar 5.25** Tampilan daftar kartu chord dan panel pratinjau diagram jari pada halaman Pustaka Chord

8. ### **Halaman Pengaturan Profil**

   [Isi: jelaskan implementasi tampilan halaman Pengaturan Profil bagi pengguna terdaftar yang memuat tiga bagian formulir, yaitu pembaruan informasi profil (username dan email), pembaruan kata sandi, serta penghapusan akun pengguna beserta modal konfirmasi kata sandi]

##### **Gambar 5.26** Tampilan halaman Pengaturan Profil pengguna

##### **Gambar 5.27** Tampilan modal konfirmasi penghapusan akun pada halaman Pengaturan Profil

9. ### **Halaman Admin Kelola User**

   [Isi: jelaskan implementasi tampilan halaman Kelola Pengguna khusus administrator yang memuat dua kartu statistik (Total User dan Admin), bilah pencarian username atau email, dropdown filter peran (Semua Role, Admin, Member), serta tabel daftar pengguna dengan informasi jumlah lagu dan tombol aksi Hapus beserta konfirmasinya]

##### **Gambar 5.28** Tampilan daftar pengguna dan filter peran pada halaman Admin Kelola User

##### **Gambar 5.29** Tampilan dialog konfirmasi penghapusan pengguna pada halaman Admin Kelola User

10. ### **Halaman Admin Kelola Lagu**

    [Isi: jelaskan implementasi tampilan halaman Kelola Lagu khusus administrator yang menampilkan tiga kartu statistik (Total Lagu, Public, dan Private), bilah pencarian judul atau artis, filter visibilitas, kolom pencarian username pemilik lagu, serta tabel daftar seluruh lagu dengan tombol aksi Putar, Edit, dan Hapus]

##### **Gambar 5.30** Tampilan daftar seluruh lagu dan kontrol filter pada halaman Admin Kelola Lagu

11. ### **Halaman Admin Kelola Kategori**

    [Isi: jelaskan implementasi tampilan halaman Kelola Kategori khusus administrator yang mengelola tiga tipe kategori dalam satu halaman melalui kartu tab pemilih (Genre, Mood, dan Lokasi), dilengkapi bilah pencarian, tabel data kategori beserta jumlah keterpakaian di lagu, jendela modal Tambah Kategori, jendela modal Edit Kategori, serta jendela modal konfirmasi Hapus Kategori]

##### **Gambar 5.31** Tampilan daftar kategori dan tab pemilih tipe (Genre, Mood, Lokasi) pada halaman Admin Kelola Kategori

##### **Gambar 5.32** Tampilan modal form Tambah Kategori pada halaman Admin Kelola Kategori

##### **Gambar 5.33** Tampilan modal form Edit Kategori pada halaman Admin Kelola Kategori

##### **Gambar 5.34** Tampilan modal konfirmasi Hapus Kategori pada halaman Admin Kelola Kategori

12. ### **Halaman Admin Kelola Artist**

    [Isi: jelaskan implementasi tampilan halaman Kelola Artis khusus administrator yang menyajikan dua kartu statistik (Total Artis dan Artis Terpakai), bilah pencarian nama artis, tabel daftar artis dengan indikator lencana status pemakaian ("Dipakai di Lagu" dengan tombol Hapus nonaktif dan "Belum Dipakai" dengan tombol Hapus aktif), serta jendela modal Tambah Artis, Edit Artis, dan konfirmasi Hapus Artis]

##### **Gambar 5.35** Tampilan daftar artis dan kartu statistik pada halaman Admin Kelola Artist

##### **Gambar 5.36** Tampilan modal form Tambah Artis pada halaman Admin Kelola Artist

##### **Gambar 5.37** Tampilan modal form Edit Artis pada halaman Admin Kelola Artist

##### **Gambar 5.38** Tampilan modal konfirmasi Hapus Artis pada halaman Admin Kelola Artist

13. ### **Halaman Add & Edit Chord (Mode Admin pada Pustaka Chord)**

    [Isi: jelaskan implementasi tampilan fitur manajemen chord khusus administrator pada halaman Pustaka Chord, yaitu kemunculan tombol Tambah Chord di header dan tombol Edit di panel pratinjau, jendela modal Buat Chord Baru untuk mengisi nama chord, cara baca tanpa simbol, dan unggah diagram jari, serta jendela modal Edit Chord untuk memperbarui data atau menghapus chord]

##### **Gambar 5.39** Tampilan modal form Buat Chord Baru (Add Custom Chord) pada mode Admin

##### **Gambar 5.40** Tampilan modal form Edit dan Hapus Chord pada mode Admin

---

### **Catatan untuk Penulis**

1. Pada Subbab 5.1, ejaan dua tabel pada kerangka lama telah disesuaikan dengan skema migrasi di codebase, yaitu `Tabel Artist` diperbaiki menjadi `Tabel Artists` (`artists`) dan `Tabel Song_Section` diperbaiki menjadi `Tabel Song_Sections` (`song_sections`), dengan urutan kesepuluh tabel domain disamakan persis dengan urutan entitas 4.1.1 hingga 4.1.10 pada Bab 4.
2. Selain 10 tabel domain utama pada Subbab 5.1, di folder `database/migrations/` terdapat 7 tabel infrastruktur bawaan Laravel (`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, dan `failed_jobs`) yang tidak dibuatkan subbab terpisah karena Bab 4 Subbab 4.2 telah menyatakan bahwa tabel bawaan Laravel tidak dimasukkan sebagai entitas pada ERD.
3. Terdapat perbedaan kecil antara penamaan di Bab 4 dan implementasi antarmuka di codebase yang perlu dijelaskan saat menulis narasi Subbab 5.3, yaitu fitur *Create New Song* (Subbab 5.3.4) dan *Add/Edit Chord* (Subbab 5.3.13) diimplementasikan sebagai komponen modal dialog di dalam halaman *My Songs* dan *Pustaka Chord* (bukan halaman dengan URL tersendiri), serta pada halaman *Admin Kelola User* (Subbab 5.3.9) administrator hanya dapat mencari, memfilter peran, dan menghapus pengguna (tidak terdapat fitur ubah peran atau ubah `song_limit` di `UserController`).
4. Pada Subbab 5.3 telah ditambahkan satu subbab halaman yang benar-benar ada di codebase (`routes/web.php` dan `resources/views/profile/edit.blade.php`) namun belum tercantum di Bab 4 Subbab 4.5, yaitu **Halaman Pengaturan Profil** (Subbab 5.3.8), dengan urutan seluruh halaman dikelompokkan mulai dari halaman pengguna umum/member (5.3.1–5.3.8) diikuti halaman khusus administrator (5.3.9–5.3.13).
5. Secara keseluruhan, kerangka Bab 5 ini memuat **40 placeholder gambar** (`Gambar 5.1` s/d `Gambar 5.40`, terdiri atas 10 gambar struktur tabel basis data dan 30 gambar tampilan antarmuka sistem) serta **37 placeholder listing kode program** (`Listing 5.1` s/d `Listing 5.37` yang tersebar pada 11 subbab fitur).
