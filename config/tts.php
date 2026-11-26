<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Status Text-to-Speech (TTS)
    |--------------------------------------------------------------------------
    | Dapat dinonaktifkan dengan TTS_ENABLED=false (mis. saat pengujian tanpa internet).
    */
    'enabled' => env('TTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Perintah Eksekusi edge-tts
    |--------------------------------------------------------------------------
    | Perintah CLI edge-tts dipisah spasi menjadi array argumen yang aman
    | dijalankan di berbagai sistem operasi (Windows, macOS, Linux).
    | Contoh nilai .env:
    |   TTS_COMMAND=edge-tts
    |   TTS_COMMAND="py -m edge_tts"          (Windows, Python launcher)
    |   TTS_COMMAND="python -m edge_tts"      (executable python di PATH)
    |   TTS_COMMAND="python3 -m edge_tts"     (Linux/macOS)
    */
    'command' => array_values(array_filter(preg_split('/\s+/', trim(env('TTS_COMMAND', 'edge-tts'))))),

    /*
    |--------------------------------------------------------------------------
    | Suara TTS (Voice)
    |--------------------------------------------------------------------------
    | Voice default yang dipakai untuk pelafalan audio chord oleh edge-tts.
    | Contoh opsi suara:
    |   - id-ID-ArdiNeural  (Pria, Bahasa Indonesia - bawaan)
    |   - id-ID-GadisNeural (Wanita, Bahasa Indonesia)
    |   - en-US-AriaNeural  (Wanita, Bahasa Inggris)
    |   - en-US-GuyNeural   (Pria, Bahasa Inggris)
    */
    'voice' => env('TTS_VOICE', 'id-ID-ArdiNeural'),

    /*
    |--------------------------------------------------------------------------
    | Kecepatan Bicara (Speech Rate)
    |--------------------------------------------------------------------------
    | Format parameter kecepatan bicara edge-tts: "+0%", "-10%", "+20%".
    */
    'rate' => env('TTS_RATE', '+0%'),

    /*
    |--------------------------------------------------------------------------
    | Batas Percobaan & Waktu Proses (Tries & Timeout)
    |--------------------------------------------------------------------------
    | 'tries'      : Jumlah maksimal percobaan (retry) server saat generate audio
    |                chord melalui edge-tts gagal.
    | 'timeout'    : Batas waktu maksimal (detik) eksekusi satu proses edge-tts.
    | 'job_timeout': Batas waktu maksimal (detik) eksekusi satu job GenerateChordAudio.
    */
    'tries' => (int) env('TTS_TRIES', 3),
    'timeout' => (int) env('TTS_TIMEOUT', 60),
    'job_timeout' => (int) env('TTS_JOB_TIMEOUT', 90),

    /*
    |--------------------------------------------------------------------------
    | Disk & Lokasi Penyimpanan File Audio TTS
    |--------------------------------------------------------------------------
    | 'disk': Menentukan nama disk penyimpanan Laravel (didefinisikan pada
    | config/filesystems.php) tempat file MP3 hasil generate disimpan dan
    | disajikan ke browser.
    | Nilai bawaan adalah 'public' (storage/app/public/), yang dapat diakses publik
    | lewat URL Storage::url('chords/audio/...').
    |
    | 'directory': Subdirektori penyimpanan file audio di dalam disk tersebut.
    */
    'disk' => env('TTS_DISK', 'public'),
    'directory' => env('TTS_DIRECTORY', 'chords/audio'),

];
