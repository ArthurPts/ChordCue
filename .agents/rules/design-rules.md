# Aturan Desain Visual ChordCue (Stage & Tape Deck)

Dokumen ini adalah aturan wajib bagi AI Agent saat mendesain, memodifikasi, atau memvalidasi antarmuka pengguna (UI) di repositori ChordCue.
Dokumentasi lengkap dan log keputusan historis tersedia di `01-docs/design-system/DESIGN_GUIDE.md`.

---

## 1. Prinsip & Karakter Visual

1. **Arah Desain**: "Stage & Tape Deck" — Menggabungkan fungsionalitas workstation audio profesional dengan kehangatan perangkat fisik studio (rack-mount pro audio, tape deck counter, VU meter, dan tombol tactile).
2. **Bebas Klise AI (*No AI Tropes*)**:
   - Dilarang menggunakan efek ambient glow/blob hitam di latar belakang, gradien neon sembrono, dan bayangan lembut seragam.
   - Hindari hero generik rata tengah, ikon berlebihan di setiap judul, dan emoji di UI. Copywriting singkat, fungsional, dan natural untuk musisi.
   - Aksen biru (`#5B8DEF`) dipakai hemat dan terarah, bukan di semua elemen tanpa hierarki.
3. **Integritas Uji Otomatis**: Dilarang memodifikasi file di `tests/` atau menghapus string teks/assertion pengujian (misal: tombol Putar, Edit, Hapus, pesan status, teks placeholder empty state).
4. **Tanpa Inline CSS**: Dilarang keras menggunakan attribute `style="..."`. Seluruh tampilan wajib menggunakan utility class Tailwind CSS yang telah dikonfigurasi.

---

## 2. Palet Warna & Token Tema (MUTLAK)

ChordCue beroperasi **eksklusif dalam tema gelap (Dark Theme Only)**. Dilarang membuat mode terang atau menggunakan warna hex arbitrer di dalam Blade.

| Token Tailwind | Nilai CSS Var | Peruntukan Utama |
|---|---|---|
| `bg-base` | `#0A0F1A` | Latar belakang halaman utama (60% layar) |
| `bg-surface-1` | `#111826` | Kartu konten, kontainer tabel, modal dialog, panel default |
| `bg-surface-2` | `#182133` | Field input, toolbar filter, latar dropzone, status kartu terpilih |
| `bg-surface-3` | `#212C42` | Avatar inisial, tooltip, chip tag terangkat, knob kontrol |
| `border-subtle` | `#2A3752` | Garis tepi 1px untuk semua kontainer, pembatas tabel, dan divider |
| `bg-accent` / `text-accent` | `#5B8DEF` | Aksi primer, tab aktif, ikon brand, status radio terpilih |
| `text-accent-on` | `#0A0F1A` | Warna teks wajib di atas `bg-accent` (kontras rasio AA) |
| `bg-accent-soft-bg` | `#1B2A4A` | Latar badge/chip aksen, status pill terpilih |
| `text-accent-soft-text` | `#A9C4FF` | Teks di atas badge `accent-soft-bg` |
| `text-ink` | `#E8EEF9` | Teks judul utama, isi teks berbobot |
| `text-ink-secondary` | `#A3AEC4` | Label formulir, artis, teks pendukung, ikon netral |
| `text-ink-tertiary` | `#8A95AB` | Placeholder input, hint tempo, teks caption halus |
| `text-error` / `bg-error-tint` | `#E5766E` / `#2A232F` | Tombol dan peringatan destruktif / error |
| `text-success` | `#5CC8A1` | Indikator sukses / verifikasi |

---

## 3. Tipografi & Skala Radius (MUTLAK)

1. **Font**: Menggunakan *System Font Stack* bawaan OS (tanpa file font eksternal).
   - Teks UI: `font-sans` (`ui-sans-serif, system-ui, -apple-system, sans-serif`).
   - Metrik Audio (BPM, Time Signature, Chord, Fret, Durasi): Wajib menggunakan `font-mono`.
2. **Border Radius Whitelist**:
   *Peringatan Kritis*: Tailwind config ChordCue menghapus skala bawaan. `rounded-xl` dan `rounded-2xl` **TIDAK TERDAFTAR** dan akan menghasilkan sudut siku tajam (0px).
   - `rounded-sm`: **8px** — Tombol aksi, input field, select dropdown, tombol tabel, chip tag.
   - `rounded-md`: **10px** — Navbar wrapper, toggle segmentasi filter, dropzone upload.
   - `rounded-lg`: **12px** — Kartu lagu, kontainer dialog modal, wrapper tabel admin.
   - `rounded-full`: **9999px** — Radio button, status pill badge, tombol paginasi bundar, avatar bulat.

---

## 4. Standar Komponen UI

### A. Tombol Aksi (Buttons)
- **Tinggi Standar**: `h-[40px]` untuk tombol modal/formulir, `h-[32px]` untuk tombol aksi baris tabel admin.
- **Tombol Utama (Primer)**: `bg-accent hover:bg-accent-hover text-accent-on font-bold rounded-sm shadow-sm px-20` (atau `px-24` di modal).
- **Tombol Sekunder (Batal/Edit)**: `bg-surface-2 hover:bg-surface-3 border border-subtle text-ink font-semibold rounded-sm px-20`.
- **Tombol Destruktif (Hapus)**: `bg-error-tint/70 hover:bg-error-tint border border-error/30 text-error font-semibold rounded-sm px-18`.

### B. Formulir & Field Input
- **Input & Select**: `h-[40px] px-14 rounded-sm bg-surface-2 border border-subtle text-ink placeholder:text-ink-tertiary focus:border-accent focus:ring-1 focus:ring-accent text-xs sm:text-sm`.
- **Input Pencarian**: Tambahkan `pl-[38px]` untuk menampung ikon search di sebelah kiri tanpa menabrak teks.
- **BPM Input**: Terapkan batas `min="60" max="140"` pada atribut input dan validasi controller.

### C. Radio Button & Pilihan Visibilitas
- Gunakan lingkaran radio standar `w-[18px] h-[18px] rounded-full border` dengan dot tengah `w-[8px] h-[8px] rounded-full` saat aktif.
- Dilarang membuat indikator radio dengan ukuran mikro (`w-4` adalah 4px di Tailwind custom config).

### D. Paginasi Melayang (Fixed Bottom Pagination)
- Komponen `<x-pagination>` melayang di bawah layar: `fixed bottom-0 inset-x-0 z-sticky backdrop-blur-md bg-base/90 border-t border-subtle/50 py-12 flex justify-center`.
- Seluruh halaman dengan paginasi (`welcome`, `my-songs`, `chords`, `admin/songs`, `admin/users`) **wajib memiliki `pb-[88px]`** pada kontainer utama agar kartu terbawah tidak tertutup bilah paginasi.

### E. Pustaka Chord & Panel Pratinjau
- Halaman katalog chord menampilkan **25 chord per halaman** (grid 5 kolom pada desktop).
- Sisi kanan (kartu pratinjau chord detail) wajib dibuat sticky di desktop (`lg:sticky lg:top-[88px] self-start`) agar tetap diam saat daftar chord di sisi kiri di-scroll.
- Menghapus outline ganda di dalam kartu chord agar diagram duduk bersih.

### F. Navbar
- Pertahankan struktur dan styling asli navbar ChordCue. Jangan merombak susunan atau menghapus elemen navigasi.
