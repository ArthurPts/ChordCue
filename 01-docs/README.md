# Indeks Dokumentasi ChordCue

Direktori `01-docs/` menyimpan seluruh dokumen spesifikasi, panduan desain, setup, rencana pengembangan, arsip historis, dan naskah skripsi ChordCue.

---

## Struktur Direktori

```
01-docs/
├── README.md          ← Indeks ini
├── spesifikasi/       ← Sumber kebenaran perilaku sistem & skema database
├── design-system/     ← Panduan visual aktif & design tokens
├── panduan/           ← Panduan setup & perintah operasional
├── plans/             ← Dokumen perencanaan AI yang sudah selesai
├── skripsi/           ← Naskah & kerangka Tugas Akhir (BAB 1–5)
└── arsip/             ← Dokumen lama, mockup awal, dan catatan historis
```

---

## Daftar Dokumen per Kategori

### 1. `spesifikasi/` — Spesifikasi Sistem & Skema Database
- [SPESIFIKASI_SISTEM_ChordCue.md](spesifikasi/SPESIFIKASI_SISTEM_ChordCue.md) — Sumber kebenaran perilaku fitur, alur sistem, algoritma (§7), ERD & kamus data (§6), serta format respons API (§9).
- [chordcue-10table-schema.sql](spesifikasi/chordcue-10table-schema.sql) — Referensi DDL SQL untuk 10 tabel utama ChordCue.

### 2. `design-system/` — Panduan Desain & Token Visual
- [DESIGN_GUIDE.md](design-system/DESIGN_GUIDE.md) — Panduan visual aktif **"Stage & Tape Deck"** (gabungan panduan desain, rencana refactor UI, dan changelog desain). Lihat juga aturan ringkas untuk agent di [design-rules.md](../.agents/rules/design-rules.md).
- [tokens.json](design-system/tokens.json) — Definisi *design tokens* JSON (wajib sinkron dengan [tokens.css](../resources/css/tokens.css)).

### 3. `panduan/` — Setup & Perintah Penting
- [SETUP.md](panduan/SETUP.md) — Panduan instalasi awal, konfigurasi `edge-tts`, perintah menjalankan server/worker, perintah audio & pelafalan chord, serta pengujian.

### 4. `plans/` — Rencana Pengembangan (Selesai)
- [PLAN.md](plans/PLAN.md) — Rencana implementasi sistem inti ChordCue.
- [AUTOSCROLL_PLAN.md](plans/AUTOSCROLL_PLAN.md) — Rencana implementasi fitur *auto-scroll* Player.
- [CLEANUP_PLAN.md](plans/CLEANUP_PLAN.md) — Rencana audit & pembersihan kode awal.

### 5. `skripsi/` — Dokumen Tugas Akhir
- [BAB1-3_160423183.md](skripsi/BAB1-3_160423183.md) — Naskah BAB 1–3.
- [BAB4_160423183.md](skripsi/BAB4_160423183.md) — Naskah BAB 4.
- [BAB5_160423183.md](skripsi/BAB5_160423183.md) — Naskah BAB 5.
- [kerangka-bab5.md](skripsi/kerangka-bab5.md) — Kerangka penulisan BAB 5.

### 6. `arsip/` — Arsip Historis & Mockup Awal
- [DESIGN_SYSTEM_OLD.md](arsip/DESIGN_SYSTEM_OLD.md) — Versi lama panduan *design system* sebelum disederhanakan menjadi `DESIGN_GUIDE.md`.
- [PROJECT_B_ANALYSIS.md](arsip/PROJECT_B_ANALYSIS.md) — Catatan analisis proyek pembanding pada tahap awal.
- [walkthrough-awal-pengembangan.md](arsip/walkthrough-awal-pengembangan.md) — Gabungan *walkthrough* awal pembuatan migration, model, auth, dan blank homepage *(sebagian sudah usang)*.
- `mockups/` — Mockup HTML awal eksplorasi tampilan:
  - [dark_blue_palette_music_companion.html](arsip/mockups/dark_blue_palette_music_companion.html)
  - [player-option-a.html](arsip/mockups/player-option-a.html)
  - [player-option-b.html](arsip/mockups/player-option-b.html)
