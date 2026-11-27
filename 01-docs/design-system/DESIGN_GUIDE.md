# Panduan Desain Sistem ChordCue: Stage & Tape Deck

Panduan komprehensif implementasi visual, arsitektur antarmuka, dan sistem token antarmuka ChordCue (Laravel 12 + Tailwind CSS + Alpine.js).

> Dokumen ini adalah gabungan dari `DESIGN_GUIDE.md`, `design-changelog.md`, `ui-refactor-plan.md`, dan aturan sesi `ui-refactor-copy-old.txt`.
> Aturan ringkas yang wajib dibaca agent ada di `.agents/rules/design-rules.md`. Nilai token ada di `resources/css/tokens.css` dan `tokens.json` (folder ini) — keduanya wajib sinkron.

---

## 1. Filosofi & Arah Desain

ChordCue dirancang khusus untuk musisi dan pencipta lagu. Arah desain visual **"Stage & Tape Deck"** memadukan kepraktisan perangkat keras audio profesional (*studio rack-mount hardware*) dengan kehangatan perangkat rekam klasik (*tape deck counter, tactile switches, dan meter analog*).

### Karakteristik Visual Utama
1. **Atmosfer Studio Gelap (*Studio Dark Mode*)**: Dominasi warna biru gelap mendalam (`#0A0F1A` hingga `#182133`) yang nyaman di mata saat sesi latihan malam hari atau pertunjukan panggung dengan pencahayaan redup.
2. **Bebas Klise AI (*No AI Tropes*)**:
   - Tidak ada efek lingkaran/blob blur di latar belakang (*ambient background circles* telah dibersihkan sepenuhnya).
   - Tidak ada gradien neon ungu/pink yang membingungkan fungsi audio.
   - Tidak ada indikator mikro yang tidak terbaca (seperti dot 4px).
3. **Tipografi Presisi untuk Musik**: Angka tempo (BPM), birama (*time signature*), nama chord, dan durasi ditampilkan dengan font monospaced (`font-mono`) agar ritmis dan presisi layaknya display metronom digital.
4. **Top Master Controls (Player)**: Bar kontrol latihan, HUD metronom, dan pengaturan sesi diletakkan di bagian atas (sticky), sehingga area lirik & chord di bawahnya bersih saat dibaca.
5. **Navigasi Mantap & Mengambang**: Kontrol paginasi melayang di bawah layar (*fixed bottom footer*) dengan efek *backdrop blur*.

### Hindari Tampilan Generik "AI"
- Grid kartu seragam di semua halaman.
- Gradien ungu-biru, glow, bayangan lembut seragam.
- Hero generik rata tengah, ikon dalam lingkaran, ikon di setiap judul, emoji di UI.
- Warna aksen di semua elemen interaktif tanpa hierarki; chip/badge berlebihan ("Christmas Tree Syndrome").
- Spasi seragam tanpa ritme; semua teks rata tengah.
- Teks promosi generik. Copy harus singkat dan natural.

### Arah yang Diinginkan
- Hierarki mengikuti apa yang sedang dilakukan pemain: chord dan lirik jadi pusat, kontrol utama besar dan mudah diraih saat tangan sibuk bermain.
- Tata letak bervariasi sesuai fungsi halaman.
- Karakter tipografi dari bobot, ukuran, dan spasi dengan font sistem.
- Aksen dipakai hemat: aksi utama ditonjolkan, aksi sekunder tenang, aksi destruktif kontekstual.
- **Ergonomi sentuh**: hit area minimal 44px pada tombol dan kontrol sentuh mobile.
- **Copywriting alami**: Bahasa Indonesia ramah ("kamu", bukan "Anda"), tanpa teks promosi klise.
- Detail kecil yang berguna: empty state membantu, hover tenang. Terasa matang, bukan norak.

---

## 2. Palet Warna & Semantic Design Tokens

Sistem warna ChordCue menggunakan token semantik berbasis CSS Custom Variables yang didefinisikan pada `resources/css/tokens.css` dan dikonfigurasikan di `tailwind.config.js`.

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
Aplikasi mengandalkan **System Font Stack** bawaan sistem operasi tanpa memuat file font eksternal:
- **UI Utama**: `font-sans` (`ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`).
- **Data Metrik Musik**: `font-mono` (`ui-monospace, "SF Mono", Menlo, Consolas, monospace`) untuk BPM, birama, nama chord, dan penomoran teknis.

### B. Matriks Border Radius
`tailwind.config.js` sengaja memakai whitelist ketat; `rounded-xl` dan `rounded-2xl` **dihapus**:
- `rounded-sm`: **8px** — tombol formulir, tombol tabel, input teks, dropdown, badge chip.
- `rounded-md`: **10px** — batang navbar, dropzone upload diagram, kartu filter.
- `rounded-lg`: **12px** — kartu lagu, kontainer modal, wrapper tabel admin, panel pratinjau pustaka chord.
- `rounded-full`: **9999px** — indikator radio, pill status, avatar bulat, tombol paginasi.

---

## 4. Standar Desain Komponen

### A. Kartu Lagu (*Song Card*)
- **Struktur**: `bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 hover:border-accent/40 transition-all`.
- **Hierarki**: judul tebal (`font-bold text-ink text-base sm:text-lg`), nama artis (`text-ink-secondary text-xs sm:text-sm`), baris metrik BPM (`font-mono text-accent`) + birama (`font-mono text-ink-secondary`) + tag kategori, tombol Putar berkontras tinggi dan tombol Edit sekunder.

### B. Pustaka Chord (*Chord Library*)
- **Kapasitas**: **25 chord per halaman**, grid 5 kolom di desktop (`ChordController@index`).
- **Panel Pratinjau Kanan**: sticky di desktop (`lg:sticky lg:top-[88px] self-start`).
- **Interaksi Reaktif**: Alpine.js (`@click="selectChord({...})"`) memperbarui gambar diagram, nama chord, dan cara baca secara instan.
- **Diagram**: gambar diagram (unggahan admin, fallback `No_Image_Available.jpg`) duduk langsung di kartu tanpa outline ganda maupun latar pastel. Visualisasi fret SVG sudah dihapus.

### C. Paginasi Mengambang
- `<x-pagination>` memakai `fixed bottom-0 inset-x-0 z-sticky backdrop-blur-md bg-base/90 border-t border-subtle/50 py-12 flex justify-center`.
- Halaman berpaginasi (`welcome`, `my-songs`, `chords`, `admin/songs`, `admin/users`) memakai `pb-[88px]` agar konten terbawah tidak tertutup.

### D. Modal Dialog (*Create Song, Create Chord, Edit Chord*)
- **Backdrop**: `bg-overlay backdrop-blur-sm fixed inset-0`.
- **Kontainer**: `bg-surface-1 border border-subtle rounded-lg shadow-2xl p-24 sm:p-28 max-w-[520px]`.
- **Radio Publik/Privat**: lingkaran 18px, titik dalam 8px saat aktif, ikon 16px, dan teks penjelas hak akses.
- **Rentang BPM**: **40 – 240 BPM** di UI (`min="40" max="240"`) dan validasi backend (`min:40|max:240`).
- **Birama**: hanya `4/4`, `3/4`, `2/4`, `6/8`.
- **Cara Baca Chord**: hanya huruf, angka, dan spasi (tanpa simbol) — berlaku di modal tambah & edit.
- **Tombol Aksi**: tinggi **40px**; Batal `px-20 bg-surface-2 border border-subtle text-ink rounded-sm`, utama `px-24 bg-accent text-accent-on font-bold rounded-sm`.

### E. Konsol Admin (*Kelola Lagu, Pengguna, Kategori, Artis*)
- **Stat Cards**: angka monospaced yang tegas, label uppercase ringkas, tanpa garis ornamen.
- **Toolbar Filter**: `bg-surface-1 border border-subtle rounded-lg p-14 sm:p-16`.
- **Tabel Data**: header uppercase ringkas (`bg-surface-2/60 text-[11px] font-semibold text-ink-secondary`), baris hover halus, avatar inisial 32px, tombol aksi baris 32px.

### F. Autentikasi & Pengaturan Profil
- **Form Tamu**: kartu terpusat `rounded-lg bg-surface-1 border border-subtle shadow-2xl p-24 sm:p-32 max-w-[460px]`, input 40px.
- **Kartu Profil**: panel modular untuk informasi profil, ubah sandi, dan hapus akun.

---

## 5. Checklist Verifikasi Perubahan UI

Sebelum menyatakan perubahan tampilan selesai:
1. `npm run build` sukses tanpa error CSS/JS.
2. `php artisan test` tetap lulus 100% (dilarang mengubah/menghapus tes hanya agar lulus).
3. Periksa tampilan desktop (**1280px**) dan mobile (**375px**).
4. Periksa kontras teks (WCAG AA ≥ 4.5:1), focus ring keyboard, dan tap target ≥ 44px.
5. Catat setiap perubahan token/utility di bagian **Riwayat Keputusan Desain** di bawah.

---

## 6. Riwayat Keputusan Desain (Changelog)

Format: `[Konteks] Tanggal — Apa yang diubah, Kenapa, File mana`. Entri terbaru di atas.

### [Sinkronisasi Form Chord] 26 November 2026
- **Apa:** Modal Edit Chord kini memakai aturan cara baca yang sama dengan modal Tambah Chord (label "Tanpa simbol", `pattern="[A-Za-z0-9 ]+"`, filter `@input`); batas ukuran diagram dibaca dari `config('chordcue.chord_diagram_max_mb')`; data `fret_data` yang tidak dirender dihapus.
- **File:** `resources/views/components/chords/edit-chord-modal.blade.php`, `resources/views/chords/index.blade.php`, `app/Http/Controllers/ChordController.php`, `app/Models/Chord.php`.

### [Alignment: BPM, Time Signature, & Offset] 15 November 2026
- **Apa:**
  1. **BPM:** rentang standar **40 – 240** tanpa pengecualian (membatalkan limitasi sementara 60–140) di modal, validasi `SongController`, dan test boundary.
  2. **Birama:** opsi `12/8` dihapus; hanya `4/4`, `3/4`, `2/4`, `6/8`.
  3. **Offset (`backing_track_offset`):** minimum 0 detik, tanpa batas atas validasi (batas keras kolom `DECIMAL(6,3)` = 999.999s).
- **Kenapa:** Penyelarasan aturan global (UI, backend, database, tes).
- **File:** `create-song-modal.blade.php`, `SongController.php`, `SongAudioController.php`, `SongManagementTest.php`, `SongAudioOffsetTest.php`.

### [UI Refactor Batch 7: Autentikasi & Profil] 05 November 2026
- **Apa:** Kartu guest `rounded-lg bg-surface-1 border border-subtle shadow-2xl p-24 sm:p-32`; tombol primer `h-[40px] px-20 rounded-sm bg-accent text-accent-on font-bold`; input `h-[40px] bg-surface-2 px-14 rounded-sm border border-subtle`; form Login, Register, dan Profil dirapikan.
- **Kenapa:** Menghapus `rounded-2xl` yang tidak terdaftar, konsistensi input, kontras tombol aksi.
- **File:** `layouts/guest.blade.php`, `components/primary-button.blade.php`, `components/text-input.blade.php`, `auth/login.blade.php`, `auth/register.blade.php`, `profile/edit.blade.php` + partials.

### [UI Refactor Batch 6: Admin Dashboard] 05 November 2026
- **Apa:** Stat card dengan tipografi mono tanpa strip arbitrer; toolbar filter terintegrasi; tabel dengan header uppercase dan avatar inisial 32px; tombol baris Putar/Edit/Hapus berbasis token (tanpa `#DE6D62`).
- **File:** `admin/songs/index.blade.php`, `admin/users/index.blade.php`.

### [UI Refactor Batch 5: Modal Dialog] 05 November 2026
- **Apa:** Backdrop `bg-overlay backdrop-blur-sm`; kontainer berbasis token; input/select `h-[40px]`; tombol tanpa hex arbitrer (`border-[#E06D63]`, `bg-[#5B8DF6]`); dropzone diagram bergaris putus-putus; radio Publik/Privat 18px/8px; ikon upload 40×40px.
- **Catatan:** Revisi saat itu sempat membatasi BPM 60–140 — **sudah dibatalkan** pada 15 November 2026 (lihat di atas).
- **File:** `create-song-modal.blade.php`, `create-chord-modal.blade.php`, `edit-chord-modal.blade.php`.

### [UI Refactor Batch 4: Pustaka Chord] 05 November 2026
- **Apa:** Latar diagram periwinkle `#9DB9FB` dan outline ganda dihapus; visualisasi fret interaktif dihapus; klik kartu memperbarui panel kanan secara reaktif; 25 chord per halaman; panel kanan sticky; `<x-pagination>` menjadi fixed bottom footer + `pb-[88px]` di semua halaman berpaginasi.
- **File:** `chords/index.blade.php`, `chord-card.blade.php`, `chord-diagram.blade.php`, `pagination.blade.php`, `ChordController.php`, `welcome.blade.php`, `my-songs.blade.php`, `admin/songs/index.blade.php`, `admin/users/index.blade.php`.

### [UI Refactor Batch 3: Lagu Saya] 05 November 2026
- **Apa:** Header dan tombol "+ Buat Lagu" tidak terpotong di mobile; tab Publik/Privat menjadi toggle kompak; radius memakai token terdaftar.
- **File:** `my-songs.blade.php`, `song-card.blade.php`.

### [UI Refactor: Konten Utama & Kartu Lagu] 05 November 2026
- **Apa:** Navbar, responsive-nav-link, dan pagination dikembalikan ke versi asli; margin/padding halaman Home dan kartu lagu dirapikan.
- **File:** `welcome.blade.php`, `song-card.blade.php`.

### [UI Refactor Batch 0: Fondasi] 05 November 2026
- **Apa:** Elemen "ambient background circles" dihapus dari layout; scrollbar tema gelap; `:focus-visible` konsisten; skill `frontend-design` dipasang di `.agents/skills/`; `DESIGN_SYSTEM.md` lama dipindah ke arsip (`01-docs/arsip/DESIGN_SYSTEM_OLD.md`); mockup Opsi B (Top Master Controls) dibuat sebagai acuan arah desain player (`01-docs/arsip/mockups/player-option-b.html`).
- **File:** `layouts/app.blade.php`, `layouts/guest.blade.php`, `resources/css/app.css`.
