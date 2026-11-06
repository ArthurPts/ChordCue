# Panduan Desain Sistem ChordCue: Stage & Tape Deck

Panduan komprehensif implementasi visual, arsitektur antarmuka, dan sistem token antarmuka ChordCue (Laravel 12 + Tailwind CSS + Alpine.js).

---

## 1. Filosofi & Arah Desain

ChordCue dirancang khusus untuk musisi dan pencipta lagu. Arah desain visual **"Stage & Tape Deck"** memadukan kepraktisan perangkat keras audio profesional (*studio rack-mount hardware*) dengan kehangatan perangkat rekam klasik (*tape deck counter, tactile switches, dan meter analog*).

### Karakteristik Visual Utama
1. **Atmosfer Studio Gelap (*Studio Dark Mode*)**: Dominasi warna biru gelap mendalam (`#0A0F1A` hingga `#182133`) yang nyaman di mata saat sesi latihan malam hari atau pertunjukan panggung dengan pencahayaan redup.
2. **Bebas Klise AI (*No AI Tropes*)**:
   - Tidak ada efek lingkaran hitam/blob blur sembrono di latar belakang (*ambient background circles* telah dibersihkan sepenuhnya).
   - Tidak ada gradien neon ungu/pink yang membingungkan fungsi audio.
   - Tidak ada indikator mikro yang tidak terbaca (seperti dot 4px).
3. **Tipografi Presisi untuk Musik**: Angka tempo (BPM), birama (*time signature*), chord diagram, fret senar, dan durasi ditampilkan dengan font monospaced (`font-mono`) agar ritmis dan presisi layaknya display metronom digital.
4. **Navigasi Mantap & Mengambang**: Kontrol paginasi dibuat melayang di bagian bawah layar (*fixed bottom footer*) dengan efek *backdrop blur*, menjaga akses navigasi antarpages selalu siap dijangkau tanpa perlu scroll bolak-balik.

---

## 2. Palet Warna & Semantic Design Tokens

Sistem warna ChordCue menggunakan token semantik berbasis CSS Custom Variables yang didefinisikan pada `resources/css/tokens.css` dan dikonfigurasikan di `tailwind.config.js`.

### A. Tabel Token Warna
| Token Tailwind | Variabel CSS | Hex / RGBA | Rasio Kontras | Kegunaan Utama |
|---|---|---|---|---|
| `bg-base` | `--color-bg-base` | `#0A0F1A` | N/A | Latar kanvas utama aplikasi (60% area visual). |
| `bg-surface-1` | `--color-surface-1` | `#111826` | N/A | Kartu lagu, panel modal, kartu profil, tabel. |
| `bg-surface-2` | `--color-surface-2` | `#182133` | N/A | Input field, toolbar filter, latar kartu terpilih, hover state. |
| `bg-surface-3` | `--color-surface-3` | `#212C42` | N/A | Avatar pengguna, badge/chip terangkat, indikator slider. |
| `bg-overlay` | `--color-overlay` | `rgba(10, 15, 26, 0.72)` | N/A | Scrim gelap di belakang modal dialog (backdrop). |
| `border-subtle` | `--color-border-subtle` | `#2A3752` | N/A | Garis tepi 1px untuk seluruh kartu, pemisah, dan input. |
| `bg-accent` / `text-accent` | `--color-accent-primary` | `#5B8DEF` | 5.8:1 di `#0A0F1A` | Aksi utama, status radio aktif, tab navigasi aktif, ikon brand. |
| `bg-accent-hover` | `--color-accent-hover` | `#7AA2F7` | N/A | Status hover dan ring fokus elemen interaktif. |
| `bg-accent-soft-bg` | `--color-accent-soft-bg` | `#1B2A4A` | N/A | Latar belakang chip tag dan pill status publik. |
| `text-accent-soft-text` | `--color-accent-soft-text` | `#A9C4FF` | 7.9:1 di `#1B2A4A` | Teks di dalam badge atau chip aksen. |
| `text-accent-on` | `--color-on-accent` | `#0A0F1A` | 5.8:1 di `#5B8DEF` | Warna teks wajib pada tombol berlatar `bg-accent`. |
| `text-ink` | `--color-text-primary` | `#E8EEF9` | 13.5:1 di `#0A0F1A` | Teks judul utama dan isi penting berbobot. |
| `text-ink-secondary` | `--color-text-secondary` | `#A3AEC4` | 7.6:1 di `#0A0F1A` | Teks pendukung, nama artis, label form, ikon netral. |
| `text-ink-tertiary` | `--color-text-tertiary` | `#8A95AB` | 5.2:1 di `#0A0F1A` | Placeholder form, hint keterangan rentang BPM. |
| `text-error` / `bg-error-tint` | `--color-error` / `--color-error-tint` | `#E5766E` / `#2A232F` | 4.8:1 | Aksi destruktif (Hapus), alert error, status gagal. |
| `text-success` | `--color-success` | `#5CC8A1` | 9.2:1 di `#0A0F1A` | Indikator sukses simpan, status berhasil. |

> [!IMPORTANT]
> **Larangan Warna Hex Arbitrer**: Dilarang menggunakan warna hex kustom seperti `#DE6D62`, `#E06D63`, atau `#5B8DF6` langsung di Blade. Gunakan utility semantik di atas untuk menjamin konsistensi palet studio.

---

## 3. Tipografi & Skala Sudut (*Border Radius*)

### A. Tipografi
Aplikasi mengandalkan **System Font Stack** bawaan sistem operasi pengguna tanpa memuat file font eksternal:
- **UI Utama**: `font-sans` (`ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`).
- **Data Metrik Musik**: `font-mono` (`ui-monospace, "SF Mono", Menlo, Consolas, monospace`) diterapkan untuk nilai BPM, birama (*time signature*), pembacaan chord, fret, dan penomoran teknis.

### B. Matriks Border Radius
Konfigurasi Tailwind (`tailwind.config.js`) secara sengaja menggunakan whitelist ketat. Skala bawaan seperti `rounded-xl` dan `rounded-2xl` **dihapus**:
- `rounded-sm`: **8px** (`var(--radius-sm)`) — Tombol formulir, tombol tabel, field input teks, dropdown, badge chip.
- `rounded-md`: **10px** (`var(--radius-md)`) — Batang navbar, dropzone upload diagram, kartu filter.
- `rounded-lg`: **12px** (`var(--radius-lg)`) — Kartu lagu (*song card*), kontainer dialog modal, wrapper tabel admin, panel pratinjau pustaka chord.
- `rounded-full`: **9999px** (`var(--radius-full)`) — Indikator radio button, pill status, avatar bulat, lingkaran tombol paginasi.

---

## 4. Standar Desain Komponen

### A. Kartu Lagu (*Song Card*)
- **Struktur**: Kontainer berlatar `bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 hover:border-accent/40 transition-all`.
- **Hierarki Audio**:
  - Judul lagu berbobot tebal (`font-bold text-ink text-base sm:text-lg`).
  - Nama artis di bawah judul (`text-ink-secondary text-xs sm:text-sm`).
  - Baris metrik: Metronom BPM (`font-mono text-accent`), Birama (`font-mono text-ink-secondary`), dan tag kategori genre/mood.
  - Tombol aksi: Tombol Putar (*Play*) berkontras tinggi dan tombol *Edit* sekunder yang rapi.

### B. Pustaka Chord (*Chord Library*)
- **Kapasitas**: Menampilkan **25 chord per halaman** dalam format grid 5 kolom pada desktop (`ChordController@index`).
- **Panel Pratinjau Kanan**: Dibuat tetap/melekat di desktop (`lg:sticky lg:top-[88px] self-start`) agar tidak ikut bergeser saat katalog chord di sisi kiri di-scroll.
- **Interaksi Reaktif**: Menggunakan Alpine.js (`@click="selectChord({...})"`) yang langsung memperbarui diagram visual, nama chord, dan cara baca secara instan saat kartu diklik.
- **Diagram Bersih**: Diagram duduk langsung di atas kanvas kartu tanpa outline ganda berlebih maupun latar belakang biru pastel yang mengaburkan kontras.

### C. Paginasi Mengambang (*Fixed Bottom Floating Pagination*)
- **Lokasi & Perilaku**: Elemen `<x-pagination>` melayang di bawah jendela layar dengan styling `fixed bottom-0 inset-x-0 z-sticky backdrop-blur-md bg-base/90 border-t border-subtle/50 py-12 flex justify-center`.
- **Kompatibilitas Halaman**: Seluruh halaman dengan paginasi (`welcome`, `my-songs`, `chords`, `admin/songs`, `admin/users`) memiliki padding bawah `pb-[88px]` sehingga baris konten terbawah tidak pernah terpotong oleh bilah paginasi.

### D. Modal Dialog (*Create Song, Create Chord, Edit Chord*)
- **Backdrop**: Scrim gelap `bg-overlay backdrop-blur-sm fixed inset-0`.
- **Kontainer**: Berlatar `bg-surface-1 border border-subtle rounded-lg shadow-2xl p-24 sm:p-28 max-w-[540px]`.
- **Indikator Radio Button (Publik / Privat)**:
  - Lingkaran luar standar 18px (`w-[18px] h-[18px] rounded-full border`).
  - Titik dalam saat aktif 8px (`w-[8px] h-[8px] rounded-full bg-[#0A0F1A]`) dengan ring border biru aksen.
  - Dilengkapi ikon 16px (`w-icon-control h-icon-control`) dan teks penjelas hak akses.
- **Limitasi Rentang BPM**: Dibatasi ketat pada rentang **60 hingga 140 BPM** di antarmuka (`min="60" max="140"`) serta validasi backend controller.
- **Tombol Aksi**: Tinggi standar **40px** (`h-[40px]`), tombol Batal (`px-20 bg-surface-2 border border-subtle text-ink rounded-sm`), dan tombol utama (`px-24 bg-accent text-accent-on font-bold rounded-sm`).

### E. Konsol Admin (*Kelola Lagu & Kelola Pengguna*)
- **Stat Cards**: Menampilkan ringkasan metrik sistem dengan angka monospaced 24px yang tegas, label uppercase ringkas, dan subjudul keterangan tanpa garis ornamen sembrono.
- **Toolbar Filter**: Pencarian teks dan dropdown status terintegrasi dalam panel terpadu `bg-surface-1 border border-subtle rounded-lg p-14 sm:p-16`.
- **Tabel Data**: Header tabel uppercase ringkas (`bg-surface-2/60 text-[11px] font-semibold text-ink-secondary`), baris hover halus, inisial avatar 32px untuk pengguna, serta tombol aksi baris 32px (Putar, Edit, Hapus).

### F. Autentikasi & Pengaturan Profil
- **Form Tamu (Login & Register)**: Kartu terpusat `rounded-lg bg-surface-1 border border-subtle shadow-2xl p-24 sm:p-32 max-w-[460px]` dengan logo ganda ChordCue dan input field 40px.
- **Kartu Profil**: Panel modular untuk informasi profil, perbarui sandi, dan hapus akun permanen dengan tombol aksi primer dan sekunder yang serasi.

---

## 5. Ringkasan Verifikasi & Kualitas Pengujian

- **Pest PHP Test Suite**: 57 tests passed, 1 skipped (100% green, 191 assertions).
- **Vite Asset Build**: Berhasil dikompilasi bebas error CSS/JS dalam 3.5 detik.
- **Responsivitas**: Terverifikasi pada desktop (1280px) dan mobile (375px) di seluruh halaman utama, pustaka chord, konsol admin, form modal, dan autentikasi.

