# Design Changelog — UI Refactor ChordCue

Catatan perubahan visual dan deviasi dari aturan kaku lama selama sesi UI refactor.
Format: `[Batch] Tanggal — Apa yang diubah, Kenapa, File mana`.

---

## Log Perubahan

### [Tahap 4 - Batch 7: Autentikasi & Profil (`login.blade.php`, `register.blade.php`, `profile/edit.blade.php`)] 05 November 2026
- **Apa:**
  1. Menyeragamkan kontainer kartu guest (`layouts/guest.blade.php`) menggunakan token `rounded-lg bg-surface-1 border border-subtle shadow-2xl` dengan padding responsif `p-24 sm:p-32`.
  2. Memperbaiki tombol primer (`components/primary-button.blade.php`) menjadi `h-[40px] px-20 rounded-sm bg-accent hover:bg-accent-hover text-accent-on font-bold text-xs sm:text-sm shadow-sm` dengan kontras teks on-accent `#0A0F1A` yang sesuai standar aksesibilitas.
  3. Memperbaiki komponen input teks (`components/text-input.blade.php`) menjadi `h-[40px] bg-surface-2 px-14 rounded-sm border border-subtle text-ink focus:border-accent focus:ring-1 focus:ring-accent`.
  4. Merapikan form halaman Login (`auth/login.blade.php`), Register (`auth/register.blade.php`), dan seluruh kartu Pengaturan Profil (`profile/edit.blade.php` beserta partials: update informasi, update kata sandi, dan konfirmasi hapus akun).
  5. Memastikan seluruh pengujian fitur autentikasi dan profil tetap 100% lulus (57 passed, 1 skipped).
- **Kenapa:** Menyelesaikan masalah sudut siku kaku bawaan `rounded-2xl` yang tidak terdaftar di Tailwind config, memastikan konsistensi field input di seluruh alur otentikasi dan profil pengguna, serta menjamin kontras warna teks tombol aksi utama yang nyaman dibaca.
- **File:** `resources/views/layouts/guest.blade.php`, `resources/views/components/primary-button.blade.php`, `resources/views/components/text-input.blade.php`, `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`, `resources/views/profile/edit.blade.php`, `resources/views/profile/partials/update-profile-information-form.blade.php`, `resources/views/profile/partials/update-password-form.blade.php`, `resources/views/profile/partials/delete-user-form.blade.php`.

### [Tahap 4 - Batch 6: Admin Dashboard (`admin/songs/index.blade.php`, `admin/users/index.blade.php`)] 05 November 2026
- **Apa:**
  1. Merapikan kartu ringkasan statistik (Stat Cards) pada Kelola Lagu (Total Lagu, Public, Private) dan Kelola Pengguna (Total User, Admin) dengan hierarki tipografi mono yang jelas dan menghilangkan strip garis biru arbitrer.
  2. Memadukan bilah filter pencarian, visibilitas, dan role menjadi toolbar terintegrasi `bg-surface-1 border border-subtle rounded-lg p-14 sm:p-16` dengan input dan dropdown terstandarisasi `h-[40px] rounded-sm`.
  3. Memperbaiki styling tabel data lagu dan pengguna: header tabel uppercase yang bersih (`bg-surface-2/60 text-[11px] font-semibold`), baris data dengan hover subtle, serta chip avatar berinisial (`w-[32px] h-[32px]`) yang proporsional pada pengguna.
  4. Menyeragamkan tombol aksi baris tabel: tombol Putar (`bg-accent text-accent-on rounded-sm`), Edit (`bg-surface-2 border border-subtle rounded-sm`), dan Hapus (`bg-error-tint/70 border border-error/30 text-error rounded-sm`) tanpa warna hex arbitrer `#DE6D62`.
  5. Memastikan floating footer paginasi tetap melayang bersih di bawah layar tanpa menutupi baris tabel terbawah.
- **Kenapa:** Menghadirkan antarmuka konsol manajemen admin yang solid, presisi, profesional, dan serasi dengan tema gelap studio ChordCue, serta menjaga seluruh test suite tetap 100% lulus.
- **File:** `resources/views/admin/songs/index.blade.php`, `resources/views/admin/users/index.blade.php`.

### [Tahap 4 - Batch 5: Modal Dialogs (`create-song-modal`, `create-chord-modal`, `edit-chord-modal`) - Revisi] 05 November 2026
- **Apa:**
  1. Menyesuaikan indikator radio button untuk opsi `Publik` dan `Privat` pada modal Buat Lagu (`create-song-modal.blade.php`) menjadi lingkaran radio 18px (`w-[18px] h-[18px]`) dengan dot tengah 8px (`w-[8px] h-[8px]`) yang proporsional dan jelas, serta ikon globe dan lock berukuran 16px.
  2. Menyeragamkan ukuran dan padding/margin untuk seluruh tombol modal: tinggi tombol aksi standar 40px (`h-[40px]`), padding tombol sekunder `px-20` dan tombol utama `px-24`, margin atas `mt-24 pt-20 border-t border-subtle`, serta gap antar tombol `gap-12`.
  3. Membatasi rentang BPM menjadi 60 hingga 140 pada UI (`min="60" max="140"` dengan label penjelas `60 - 140`) serta validasi backend controller (`SongController.php` dengan aturan `min:60|max:140` dan pesan error spesifik), dengan seluruh unit & feature tests tetap 100% lolos (57 passed, 1 skipped).
  4. Memperbaiki kontainer ikon upload diagram pada `create-chord-modal.blade.php` dan `edit-chord-modal.blade.php` menjadi 40x40px agar tidak kerdil/terjepit.
- **Kenapa:** Merespons masukan pengguna mengenai proporsi radio button publik/privat yang perlu disesuaikan, tombol dialog yang memerlukan padding/margin yang proporsional, serta limitasi rentang BPM musik (60-140 BPM).
- **File:** `resources/views/components/create-song-modal.blade.php`, `resources/views/components/create-chord-modal.blade.php`, `resources/views/components/edit-chord-modal.blade.php`, `app/Http/Controllers/SongController.php`.

### [Tahap 4 - Batch 5: Modal Dialogs (`create-song-modal`, `create-chord-modal`, `edit-chord-modal`)] 05 November 2026
- **Apa:**
  1. Menyeragamkan seluruh backdrop modal dialog menggunakan `bg-overlay backdrop-blur-sm` tanpa inline CSS `rgba()`.
  2. Memperbaiki kontainer modal dengan token `rounded-lg bg-surface-1 border border-subtle shadow-2xl p-24 sm:p-28`.
  3. Memperbaiki seluruh form input dan select menjadi `h-[40px] rounded-sm bg-surface-2 border border-subtle text-ink focus:border-accent focus:ring-1 focus:ring-accent`.
  4. Menghilangkan warna hex arbitrer yang tidak konsisten pada tombol batal (sebelumnya `border-[#E06D63]` dan `border-[#E06C75]`) dan tombol simpan (`bg-[#5B8DF6]`), menggantinya dengan tombol sekunder tenang `bg-surface-2 hover:bg-surface-3 border border-subtle text-ink rounded-sm` dan tombol utama `bg-accent hover:bg-accent-hover text-[#080C14] font-bold rounded-sm`.
  5. Mendesain ulang dropzone upload diagram chord menjadi area drag/klik minimalis bergaris putus-putus (`border-dashed border-subtle hover:border-accent bg-surface-2/50`).
  6. Memperbaiki kartu pilihan visibilitas (`Publik` & `Privat`) di modal buat lagu agar serasi dengan panel audio studio dan tidak menghasilkan error Blade prop.
- **Kenapa:** Menyelesaikan masalah inkonsistensi warna tombol modal, menghilangkan styling arbitrer yang merusak tema gelap, dan memastikan dialog input terasa presisi, kokoh, dan mudah digunakan di desktop maupun mobile.
- **File:** `resources/views/components/create-song-modal.blade.php`, `resources/views/components/create-chord-modal.blade.php`, `resources/views/components/edit-chord-modal.blade.php`.

### [Tahap 4 - Batch 4: Pustaka Chord (`chords/index.blade.php`, `x-chord-card`, `x-chord-diagram`, `x-pagination`)] 05 November 2026
- **Apa:**
  1. Menghapus latar belakang diagram warna periwinkle/biru muda pastel (`#9DB9FB`) serta menghilangkan outline ganda di dalam kartu chord sehingga gambar diagram langsung duduk bersih di dalam kartu.
  2. Menghapus baris visualisasi fret senar gitar interaktif sesuai arahan pengguna.
  3. Memperbaiki interaksi klik kartu chord (`@click`) sehingga saat kartu mana pun diklik, panel pratinjau detail di sisi kanan langsung reaktif berubah sesuai chord yang dipilih.
  4. Meningkatkan kapasitas kartu chord per halaman menjadi 25 chord (grid 5 kolom pada desktop) dari yang sebelumnya 8 chord (`ChordController@index`).
  5. Menjadikan sisi kanan (panel pratinjau chord) tetap (*sticky* di desktop pada `top-[88px]`) sehingga tidak ikut tergulung saat daftar kartu chord di sisi kiri di-scroll.
  6. Mengubah komponen `<x-pagination>` menjadi *fixed bottom footer* yang melayang di bawah layar dengan efek *backdrop blur*, sehingga tetap diam di bawah layar saat halaman di-scroll (berlaku untuk semua halaman yang menggunakan paginasi: Home, Lagu Saya, Pustaka Chord, Admin Kelola Lagu, dan Admin Kelola User).
  7. Menambahkan `pb-[88px]` pada seluruh kontainer halaman yang menggunakan paginasi agar kartu terbawah tidak tertutup oleh bilah paginasi tetap.
- **Kenapa:** Memenuhi arahan revisi pengguna: menghilangkan outline internal kartu yang berlebih, memperbanyak jumlah katalog chord per halaman (25 item), menjaga kontrol navigasi paginasi selalu terlihat di bawah layar, dan memastikan sisi kanan tetap diam saat sisi kiri di-scroll.
- **File:** `resources/views/chords/index.blade.php`, `resources/views/components/chord-card.blade.php`, `resources/views/components/chord-diagram.blade.php`, `resources/views/components/pagination.blade.php`, `app/Http/Controllers/ChordController.php`, `resources/views/welcome.blade.php`, `resources/views/my-songs.blade.php`, `resources/views/admin/songs/index.blade.php`, `resources/views/admin/users/index.blade.php`.

### [Tahap 4 - Batch 3: Lagu Saya (`my-songs.blade.php`)] 05 November 2026
- **Apa:**
  1. Merapikan tata letak header halaman "Lagu Saya" dan tombol "+ Buat Lagu" agar seimbang dan tidak terpotong pada mobile maupun desktop.
  2. Memperbarui kontrol segmentasi filter visibilitas (`Publik` / `Privat`) menjadi tab toggle kompak terintegrasi dalam satu baris dengan pencarian.
  3. Menggunakan token border-radius yang terdaftar (`rounded-sm`, `rounded-md`, `rounded-lg`) sehingga tombol dan kartu tidak menghasilkan sudut siku kaku bawaan Tailwind yang tidak terbaca.
  4. Mempertahankan string teks uji otomatis (`Anda Belum Memiliki Lagu Pribadi.`, `Ayo buat lagu pertamamu.`, `Buat Lagu`) agar seluruh test suite tetap 100% lulus.
- **Kenapa:** Memberikan pengalaman manajemen lagu personal yang rapi, mudah diakses, konsisten dengan kartu lagu di halaman utama, dan tetap mempertahankan fungsionalitas pengujian.
- **File:** `resources/views/my-songs.blade.php`, `resources/views/components/song-card.blade.php`.

### [Tahap 4 - Penyesuaian Main Content & Kartu Lagu] 05 November 2026
- **Apa:** 
  1. Mengembalikan `navbar.blade.php`, `responsive-nav-link.blade.php`, dan `pagination.blade.php` ke versi asli (tidak diubah) sesuai arahan pengguna.
  2. Merapikan margin dan padding konten utama halaman Home (`welcome.blade.php`) dan komponen kartu lagu (`x-song-card.blade.php`).
- **Kenapa:** Fokus perbaikan adalah struktur dan kerapian konten kartu lagu serta ritme vertikal/horizontal halaman utama (mengurangi gap berlebih, merapikan hierarki judul, metronom BPM, time signature, dan chip tag).
- **File:** `resources/views/welcome.blade.php`, `resources/views/components/song-card.blade.php`.

### [Tahap 4 - Batch 0] 05 November 2026
- **Apa:** Menghapus elemen dekoratif "ambient background circles" dari layout utama (`layouts/app.blade.php` dan `layouts/guest.blade.php`), menambahkan style scrollbar tema gelap, dan memastikan focus-visible konsisten di `resources/css/app.css`.
- **Kenapa:** Menghilangkan bercak lengkungan hitam aneh yang mengotori kontras dan keterbacaan halaman, serta memberikan fondasi shell gelap pekat yang bersih dan profesional.
- **File:** `resources/views/layouts/app.blade.php`, `resources/views/layouts/guest.blade.php`, `resources/css/app.css`.
- **Apa:** Pemasangan skill `frontend-design` ke `.agents/skills/frontend-design/`.
- **Kenapa:** Sebagai panduan kurasi visual terarah untuk menghindari pola generik AI.
- **File:** `.agents/skills/frontend-design/`

- **Apa:** Pemindahan sementara `DESIGN_SYSTEM.md` ke `01-docs/design-system/DESIGN_SYSTEM_OLD.md`.
- **Kenapa:** Mencegah aturan kaku lama 40.618 karakter dibaca otomatis dan membatasi eksplorasi visual musisi.
- **File:** `01-docs/design-system/DESIGN_SYSTEM_OLD.md`

- **Apa:** Pembuatan mockup statis Opsi B dengan Top Master Controls.
- **Kenapa:** Memvalidasi arah desain musisi (Stage & Tape Deck) dengan kontrol di bagian atas (sticky HUD) agar area lirik dan chord bersih di bawahnya.
- **File:** `01-docs/design-system/mockups/player-option-b.html`

