# Panduan Setup & Perintah Penting ChordCue

> Menggantikan `catatan.txt` lama. Perintah job lama `GenerateChordAudioJob` sudah tidak ada; gunakan perintah artisan di bawah.

## Stack
Laravel 12 · MySQL 8 · Pest · Breeze (Blade + Alpine.js) · Tailwind CSS · Vite · `edge-tts` (Python) untuk audio pelafalan chord.

## Instalasi Awal
```bash
copy .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

npm install
npm run build        # atau: npm run dev
```

## Instalasi edge-tts (Python)
```bash
pip install edge-tts
# atau
py -m pip install edge-tts
# jika error / versi lama
py -m pip install --upgrade edge-tts
```
Atur perintah di `.env` bila `edge-tts` tidak ada di PATH (lihat `config/tts.php`):
```env
TTS_COMMAND="py -m edge_tts"
TTS_VOICE=id-ID-ArdiNeural
TTS_ENABLED=true
```

## Menjalankan Aplikasi
```bash
php artisan serve:all              # web server + queue worker (TTS) sekaligus
php artisan serve:all --no-worker  # web server saja
php artisan queue:work --verbose   # worker terpisah (alternatif)
```

## Perintah Audio & Pelafalan Chord
```bash
php artisan chords:generate-audio            # buat audio untuk chord yang belum punya file
php artisan chords:generate-audio --force    # buat ulang semua audio
php artisan chords:generate-audio --sync     # proses langsung tanpa antrean

php artisan chords:regenerate-pronunciation          # pratinjau (dry-run) pelafalan dari config/pronunciation.php
php artisan chords:regenerate-pronunciation --apply  # terapkan ke tabel chords
```

## Pengujian
```bash
php artisan test
node --test tests/js/auto-scroll.test.mjs tests/js/beat-grid.test.mjs tests/js/timing.test.mjs
```
