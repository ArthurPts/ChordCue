# Rencana & Dokumentasi Implementasi Auto-Scroll — Halaman Player ChordCue

> **Status:** **DIIMPLEMENTASIKAN (Opsi C — Interpolasi Linear Per-Segmen Beat → DOM Y)** — seluruh unit test JS (`21/21`), build Vite, Blade `view:cache`, dan Pest suite (`117 passed, 1 skipped`) lulus. Menunggu konfirmasi uji visual langsung di browser & persetujuan akhir Anda.

---

## 1. Temuan Analisis (Fakta Kode & Verifikasi Batasan)

### A. Fakta Terverifikasi dari Codebase

1. **Halaman yang Membutuhkan Auto-Scroll:**
   - **Player (`resources/views/songs/player.blade.php`, route `songs.show`, Alpine `resources/js/alpine/player.js`):** Satu-satunya halaman dengan pemutaran audio/beat real-time (`AudioEngine`), sorotan chord aktif, dan latihan *hands-free*. **Target implementasi.**
   - **Simple Editor (`resources/views/songs/editor-simple.blade.php`) & Beat Editor (`resources/views/songs/editor-beat.blade.php`):** Tidak memiliki mesin playback beat live; di luar cakupan.

2. **Scroll Container & Verifikasi Sticky Tape Deck (Batasan Tambahan #4):**
   - **Scroll container adalah `window` / dokumen utama** (`resources/views/layouts/app.blade.php:13-30`).
   - **Verifikasi posisi tombol `Putar` & `Berhenti`:**
     - Kedua tombol berada di dalam bilah Tape Deck sticky (`resources/views/songs/player.blade.php:146-177`, Baris 1).
     - **Perbaikan yang diterapkan (`player.blade.php:146`):** Sebelumnya Tape Deck memakai `sticky top-8 z-20` (`top: 8px`, `z-20` tidak terdaftar di `tailwind.config.js`), sementara `<x-navbar />` memakai `sticky top-0 z-sticky` setinggi `64px`. Diubah menjadi **`sticky top-[72px] z-sticky`** (`64px` navbar + `8px` celah) agar tombol **Putar** dan **Berhenti** selalu terlihat penuh selama halaman menggulir.
   - Tinggi bawah sticky Tape Deck dihitung dari `parseFloat(getComputedStyle(tapeDeckEl).top) + tapeDeckEl.offsetHeight` sehingga selalu konsisten baik saat klik `Putar` dari `scrollY = 0` maupun dari tengah halaman.

3. **Sumber BPM & Penguncian Selama Pemutaran:**
   - `songs.bpm` (`40..240`) → `player.blade.php:19` (`songBpm`) → `player.js:20` (`sessionBpm`) → `audio-engine.js:467` (`this.bpm`).
   - BPM dikunci selama playback (`:disabled="isPlaying"`). Toggle `Gulir Otomatis` memakai pola penguncian yang sama.

4. **Definisi Beat 1, Akhir Chord Terakhir, & Konsistensi Urutan Data:**
   - **`this.mulaiLagu`** (`audio-engine.js:84,672`): stempel waktu tepat **Beat 1** pada jam hardware `AudioContext.currentTime`, tepat setelah pre-roll (offset backing track + 1 bar count-in) berakhir.
   - **TTS cue chord pertama** berbunyi ~1–1.8 detik *sebelum* Beat 1, sedangkan auto-scroll mulai bergerak tepat pada **`this.mulaiLagu`** (Beat 1).
   - **Waktu selesai pembacaan chord terakhir (`lastChordSpeechEndTime`):** `lastChord.waktuMulai + lastChord.duration` dari `AudioEngine.scheduledChords` terakhir.
   - **Konsistensi urutan section & baris:** `SongController::show()` diselaraskan dengan `SongTimelineController::show()` untuk mengurutkan `sections` menurut `sequence` dan `lines` menurut `line_number`.

5. **Aturan Proyek & Desain yang Dipatuhi:**
   - `.agents/rules/CHORDCUE_RULES.md`: §8 (modul Vanilla JS mandiri tanpa ketergantungan Alpine; teks UI murni Bahasa Indonesia tanpa `__()`), §10 (tanpa inline `style="..."` / `:style`, tanpa package baru, tanpa warna/radius di luar token).
   - `.agents/rules/design-rules.md`: Dark Theme Only, radius whitelist (`rounded-sm`/`md`/`lg`/`full`), tidak mengubah assertion tes Pest yang sudah ada (§1 no. 3).

---

## 2. Desain Final & Rumus Matematika (Opsi C — Terimplementasi)

### A. Ringkasan Keputusan yang Diterapkan
- **Model Kecepatan (Opsi C):** **Interpolasi linear per-segmen Beat → posisi DOM (`#placement-{id}`)** mengikuti BPM, menggabungkan chord pada baris horizontal yang sama (`±8px`) agar tidak berhenti-henti (*stop-and-go*) dan memastikan chord aktif selalu sejajar di area baca walau kepadatan chord, wrapping, atau tinggi section bervariasi. *(Otomatis fallback ke linear global / `12 px/beat` bila lagu 0 chord.)*
- **Posisi saat Klik `Putar` (Q2):** Langsung bergerak ke awal chord sheet (`yStart`) selama pre-roll, lalu **diam** hingga tepat **Beat 1 (`mulaiLagu`)**. Untuk lagu pendek-sedang yang terpotong di bawah viewport saat `scrollY = 0` (`needsInitialAlign`), tetap disejajarkan ke `yStart` agar seluruh chord sheet tampil penuh.
- **Saat Klik `Berhenti` (Q3):** Langsung berhenti menggulir dan **tetap di posisi scroll terakhir**.
- **Toggle & `prefers-reduced-motion` (Q4):** Default **ON**, otomatis default **OFF** bila OS mengaktifkan `prefers-reduced-motion: reduce`, pilihan disimpan di `localStorage` (`chordcue.player.autoScroll`). Tombol berada di dalam **Tape Deck**, diatur **sebelum `Putar`**, dan **dikunci saat sedang diputar (`:disabled="isPlaying"`)**.
- **Titik Mulai (Q5):** Tepat pada **Beat 1 (`mulaiLagu`)**, bukan saat TTS aba-aba pertama berbunyi ~1.5 detik sebelumnya.
- **Satu Kontrol Transport (Batasan #1):** Satu tombol **Putar** menjalankan semuanya bersamaan (audio, sorotan chord, TTS, auto-scroll); satu tombol **Berhenti** di sebelahnya menghentikan semuanya (scroll tetap di tempat). Tidak ada kontrol play/pause/kecepatan scroll terpisah.
- **Titik Akhir Gulir (Batasan #2):** Auto-scroll selesai tepat ketika **chord terakhir selesai dibacakan** (`lastChordSpeechEndTime`), bukan akhir backing track. Pada lagu ber-chord parsial (masih ada lirik tanpa beat di bawah chord terakhir), target akhir dibatasi pada posisi chord beat terakhir agar tidak melonjak melewati chord terakhir.
- **Override Gulir Manual Tanpa Chip Re-sync (Batasan #3):** Tidak ada chip `Lanjutkan Gulir`. Jika pengguna menggulir manual saat pemutaran berlangsung, auto-scroll berhenti untuk sesi putar tersebut; klik **Putar** berikutnya mengaktifkannya kembali secara otomatis (selama toggle `Gulir Otomatis` ON).
- **Sticky Tape Deck (Batasan #4):** `player.blade.php:146` memakai `sticky top-[72px] z-sticky` agar tombol **Putar** & **Berhenti** tetap terlihat penuh di bawah Navbar `64px`.

### B. Sumber Waktu (Timing Source)
- Membaca langsung jam hardware **`AudioContext.currentTime`** melalui 1 getter read-only pada `AudioEngine` (`resources/js/audio/audio-engine.js`):
  ```js
  getClockSnapshot() {
      const lastChord = this.scheduledChords.length > 0
          ? this.scheduledChords[this.scheduledChords.length - 1]
          : null;

      const chordAnchors = [];
      for (const c of this.scheduledChords) {
          chordAnchors.push({
              placementId: c.placementId,
              start_beat: c.start_beat,
          });
      }

      return {
          isPlaying: this.isPlaying,
          currentTime: this.audioContext ? this.audioContext.currentTime : 0,
          beatOneTime: this.mulaiLagu,
          bpm: this.bpm,
          endBeat: this.endBeat,
          songEndTime: this.waktuSelesaiLagu,
          isContinuousMetronome: this.isContinuousMetronome,
          hasChords: this.scheduledChords.length > 0,
          lastChordSpeechEndTime: lastChord ? (lastChord.waktuMulai + lastChord.duration) : null,
          chordAnchors,
      };
  }
  ```
- **Nol drift** terhadap metronom/TTS/chord highlight dan **nol perubahan** pada algoritma penjadwalan audio yang ada.

### C. Rumus Interpolasi Per-Segmen (Opsi C + Fallback 0 Chord)

1. **Pengukuran Tata Letak & Rentang Gulir (`[yStart, yEnd]`):**
   - Diukur sekali saat `start()` dan ulang saat `resize` ter-debounce `150ms` (tidak pernah di dalam `rAF`):
     - `viewportHeight = document.documentElement.clientHeight || window.innerHeight`
     - `maxScrollY = Math.max(0, document.documentElement.scrollHeight - viewportHeight)`
     - `yStart = clamp(sheetTop - stickyBottom - 12, 0, maxScrollY)`
     - `sheetEndY = clamp(sheetBottom - viewportHeight + 24, yStart, maxScrollY)`
     - `readingAnchorY`: untuk lagu ber-chord diselaraskan ke posisi awal chord pertama di `yStart` (`clamp(firstChordDocTop - yStart, stickyBottom + 24, stickyBottom + min(120, max(48, visibleHeight * 0.28)))`) agar transisi baris 1 → baris 2 langsung mulus tanpa perlambatan awal.
     - `yEnd`: untuk lagu ber-chord dibatasi oleh `clamp(lastChordDocTop - readingAnchorY, yStart, sheetEndY)`; untuk lagu 0 chord memakai `sheetEndY`.

2. **Menentukan `endScrollBeat` & Daftar Jangkar Monotonik (`buildBeatScrollAnchors`):**
   - **Lagu ber-chord (`hasChords === true`):**
     - `targetEndTime = lastChordSpeechEndTime` (fallback ke `songEndTime` jika satu-satunya chord ada di Beat 1 sehingga TTS selesai sebelum `beatOneTime`).
     - $$\text{totalScrollBeats} = (\text{targetEndTime} - \text{beatOneTime}) \times \frac{\text{BPM}}{60}, \quad \text{endScrollBeat} = 1 + \text{totalScrollBeats}$$
     - Seluruh placement DOM `#placement-{id}` diurutkan menurut `start_beat`, dihitung `rawY = clamp(docTop - readingAnchorY, yStart, yEnd)`, disaring dengan **suffix-minimum** (melindungi dari outlier beat out-of-order), lalu digabungkan per baris visual (`ROW_MERGE_TOLERANCE_PX = 8px`) menjadi jangkar monotonik `[{ beat: 1, y: yStart }, ..., { beat: endScrollBeat, y: yEnd }]`.
   - **Fallback 1 — Lagu 0 chord + backing track (`!isContinuousMetronome`):** 2 jangkar `[(1, yStart), (1 + endBeat, yEnd)]`.
   - **Fallback 2 — Lagu 0 chord + metronom kontinyu (`isContinuousMetronome`):** 2 jangkar dengan `DEFAULT_PX_PER_BEAT = 12` px/beat hingga `yEnd`.

3. **Posisi Gulir pada Setiap Frame `requestAnimationFrame` (`computeAnchoredScrollY`):**
   $$\Delta t = \max\bigl(0,\; t_{\text{audio}} - t_{\text{beatOneTime}}\bigr), \quad b_{\text{now}} = 1 + \Delta t \times \frac{\text{BPM}}{60}$$
   Untuk segmen aktif $b_i \le b_{\text{now}} \le b_{i+1}$:
   $$Y_{\text{target}} = y_i + \frac{b_{\text{now}} - b_i}{b_{i+1} - b_i} \times (y_{i+1} - y_i)$$
   - Selama pre-roll ($t_{\text{audio}} < t_{\text{beatOneTime}}$): $b_{\text{now}} = 1 \Rightarrow Y_{\text{target}} = y_{\text{Start}}$.
   - Tepat saat chord terakhir selesai dibacakan ($b_{\text{now}} \ge \text{endScrollBeat}$): $Y_{\text{target}} = y_{\text{End}}$, loop `rAF` berhenti bersih.

### D. Perilaku Siklus Hidup & Interaksi Pengguna

| Kejadian | Perilaku |
|---|---|
| **Halaman dimuat** | Baca `localStorage.getItem('chordcue.player.autoScroll')`. Jika belum ada: `true` secara default, atau `false` jika `prefers-reduced-motion: reduce`. |
| **Ubah toggle `Gulir Otomatis`** | Hanya dapat diubah **sebelum `Putar`** (`:disabled="isPlaying"`). Menyimpan nilai baru ke `localStorage`. |
| **Klik `Putar`** | Jika `autoScrollEnabled` aktif: ukur tata letak & jangkar DOM, pindah rapi ke `yStart` saat pre-roll (termasuk untuk lagu sedang via `needsInitialAlign`), diam hingga Beat 1 (`mulaiLagu`), lalu jalankan `rAF` hingga `yEnd`. |
| **Pengguna menggulir manual saat `Putar`** | Terdeteksi via `wheel`, `touchstart`, `keydown` (panah/PageUp/PageDown/Space/Home/End), dan selisih `window.scrollY` > `12px` dari `lastProgrammaticY` aktual pasca-`scrollTo` (drag scrollbar). Auto-scroll langsung berhenti untuk sesi itu tanpa chip tambahan. |
| **Klik `Berhenti`** | Menghentikan audio & `rAF` auto-scroll seketika; **posisi scroll tetap di tempat terakhir**. Toggle aktif kembali. |
| **Chord terakhir selesai dibacakan / `onSongEnded`** | Posisi mencapai `yEnd`, loop `rAF` berhenti bersih. |
| **Ubah BPM / Transpose** | Terkunci saat `isPlaying`; pada klik `Putar` berikutnya `bpm`, `chordAnchors`, dan `lastChordSpeechEndTime` baru otomatis dibaca ulang. |

### E. UI Toggle & Performa Anti-Jank
- Tombol `Gulir Otomatis` berada di Baris 2 Tape Deck (grup `Jalur Audio & Gulir`, memakai `<x-icon name="chevron-down" />` dan token warna/radius resmi ChordCue, `:disabled="isPlaying"`, `:aria-pressed`, Bahasa Indonesia murni, tanpa chip `Lanjutkan Gulir`).
- Pengukuran DOM hanya dilakukan saat `start()` dan debounced `resize` (`150ms`), tidak pernah di dalam `rAF`. `window.scrollTo({ top: targetY, behavior: 'instant' })` dilewati bila `Math.abs(targetY - currentScrollY) < 0.5`, lalu `lastProgrammaticY` dibaca kembali dari `window.scrollY` aktual agar clamp browser / DPI tidak pernah memicu stop palsu.

---

## 3. Daftar File yang Dibuat / Dimodifikasi

| Aksi | File | Perubahan |
|---|---|---|
| **CREATE** | `resources/js/player/auto-scroll.js` | Modul Vanilla JS mandiri Opsi C (`resolveInitialAutoScroll`, `readStoredAutoScroll`, `writeStoredAutoScroll`, `buildBeatScrollAnchors`, `computeAutoScrollMetrics`, `computeAnchoredScrollY`, `AutoScrollController`). |
| **CREATE** | `tests/js/auto-scroll.test.mjs` | 11 unit test Node (`node --test`) untuk Opsi C: pre-roll, penggabungan baris, outlier out-of-order, chord parsial, proporsionalitas BPM, kasus tepi 1 chord di Beat 1, lagu sedang `needsInitialAlign`, konten pendek, fallback 0 chord, `prefers-reduced-motion` + `localStorage`, dan ketahanan clamp `window.scrollTo`. |
| **MODIFY** | `resources/js/audio/audio-engine.js` | Tambah 1 method read-only `getClockSnapshot()` (termasuk `chordAnchors` dan `lastChordSpeechEndTime`). Tidak mengubah logika audio/scheduling. |
| **MODIFY** | `resources/js/alpine/player.js` | Inisialisasi `AutoScrollController`, state `autoScrollEnabled`, method `toggleAutoScroll()` (`localStorage`, terkunci saat `isPlaying`), `destroy()`, serta hook `start()`/`stop()` pada lifecycle playback. |
| **MODIFY** | `resources/views/songs/player.blade.php` | Ubah `sticky top-8 z-20` → `sticky top-[72px] z-sticky` + `x-ref="tapeDeck"` (baris 146), tambah `x-ref="chordSheet"` (baris 420), dan tambah tombol toggle `Gulir Otomatis` (`:disabled="isPlaying"`) di grup `Jalur Audio & Gulir`. |
| **MODIFY** | `app/Http/Controllers/SongController.php` | Selaraskan eager loading di `SongController::show()` agar `sections` urut `sequence` dan `lines` urut `line_number` seperti `SongTimelineController::show()`. |

---

## 4. Status Langkah Implementasi

1. **[SELESAI]** Tambah `getClockSnapshot()` (termasuk `chordAnchors`) di `resources/js/audio/audio-engine.js`.
2. **[SELESAI]** Buat `resources/js/player/auto-scroll.js` (Opsi C + 5 perbaikan ketahanan data lagu) dan 11 unit test di `tests/js/auto-scroll.test.mjs`.
3. **[SELESAI]** Integrasikan ke `resources/js/alpine/player.js`, perbaiki sticky Tape Deck + tombol `Gulir Otomatis` di `resources/views/songs/player.blade.php`, dan selaraskan urutan query `sections`/`lines` di `app/Http/Controllers/SongController.php`.
4. **[SELESAI]** Verifikasi penuh: `node --test` (`21/21` PASS), `npm.cmd run build` PASS, `php artisan view:cache` PASS, `php vendor/bin/pest` (`117 passed, 1 skipped, 0 failed`).

---

## 5. Daftar Periksa Penerimaan Manual (Acceptance Checklist)

- [ ] **Sticky Tape Deck selalu terlihat:** Gulir halaman Player panjang ke bawah → bilah Tape Deck menempel rapi di bawah Navbar `64px` dan tombol **Putar** serta **Berhenti** tetap terlihat penuh. *(Kode terverifikasi; menunggu cek visual browser Anda.)*
- [ ] **Satu tombol transport:** Klik **Putar** → audio, sorotan chord, TTS, dan auto-scroll berjalan bersamaan; klik **Berhenti** → semuanya berhenti dan **posisi scroll tetap di tempat terakhir**. Tidak ada kontrol scroll terpisah.
- [ ] **Posisi pre-roll & mulai tepat di Beat 1:** Gulir dulu ke tengah/bawah halaman lalu klik **Putar** → halaman kembali ke awal chord sheet selama pre-roll (offset + count-in), diam tenang (tidak bergerak saat TTS aba-aba pertama berbunyi), lalu mulai menggulir tepat di **Beat 1**.
- [ ] **Kecepatan mengikuti BPM & selaras per baris hingga chord terakhir:** Putar lagu panjang (`#1 Sempurna`, `#18 Dummy Waltz`) pada 60 BPM lalu 120 BPM → gulir bergerak mulus selaras dengan baris chord aktif (2× lebih cepat pada 120 BPM) dan selesai tepat saat **chord terakhir selesai dibacakan** (tidak menunggu outro backing track).
- [ ] **Toggle terkunci saat bermain & tersimpan:** Sebelum `Putar`, ubah `Gulir Otomatis` OFF → muat ulang halaman → status tetap OFF (`localStorage`); nyalakan ON → klik `Putar` → tombol terkunci saat bermain; klik `Berhenti` → tombol aktif kembali.
- [ ] **Override gulir manual tanpa chip re-sync:** Saat pemutaran berlangsung dengan `Gulir Otomatis` ON, gulir manual (wheel / touch / panah / scrollbar) → auto-scroll berhenti untuk sesi itu tanpa chip `Lanjutkan Gulir`; klik `Berhenti` lalu `Putar` lagi → aktif kembali normal.
- [ ] **Reduced motion:** Dengan `localStorage` kosong dan `prefers-reduced-motion: reduce` aktif → `Gulir Otomatis` default OFF (tetap bisa dinyalakan manual sebelum `Putar`). *(Terverifikasi di unit test.)*
- [ ] **Lintas jenis data lagu (pendek, sedang, parsial, 0 chord, tanpa lirik):** `#24` pendek tetap tenang, `#25` 4 baris disejajarkan ke `yStart`, `#2`/`#9` 0-chord menggulir mulus sesuai BPM, lagu chord parsial berhenti tepat di chord terakhir. *(Terverifikasi di unit test & simulasi 6 lagu DB; menunggu konfirmasi browser Anda.)*

---

## 6. Risiko & Catatan Teknis

1. **Kasus tepi lagu dengan 1 chord di Beat 1 saja:** Karena TTS chord pada Beat 1 dijadwalkan selesai ~1 detik *sebelum* Beat 1 (`lastChordSpeechEndTime < beatOneTime`), rumus menggunakan pengaman otomatis jatuh ke `songEndTime` (akhir birama 1) agar penyebut selalu `> 0`.
2. **Ketahanan data tidak seragam:** Filter *suffix-minimum* melindungi auto-scroll dari placement yang beat-nya tidak urut terhadap posisi baris, dan pembatasan `yEnd` pada chord beat terakhir mencegah lonjakan melewati lirik tanpa beat.
3. **Pemindahan pre-roll (`behavior: 'instant'`):** Pemindahan ke `yStart` saat `Putar` memakai `behavior: 'instant'` dan membaca kembali `window.scrollY` aktual agar tidak pernah memicu deteksi gulir manual maupun tersisa animasi saat Beat 1 dimulai.
