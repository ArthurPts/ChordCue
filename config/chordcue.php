<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas Ukuran File Backing Track (MB)
    |--------------------------------------------------------------------------
    | Batas ukuran maksimal file audio backing track yang diizinkan untuk diunggah.
    | Dapat dikonfigurasi melalui variabel lingkungan .env.
    */
    'backing_track_max_mb' => (int) env('CHORDCUE_BACKING_TRACK_MAX_MB', 15),

    /*
    |--------------------------------------------------------------------------
    | Batas Ukuran File Gambar Diagram Chord (MB)
    |--------------------------------------------------------------------------
    | Batas ukuran maksimal satu file gambar diagram chord (PNG/JPG/JPEG).
    | Dapat dikonfigurasi melalui variabel lingkungan .env.
    */
    'chord_diagram_max_mb' => (int) env('CHORDCUE_CHORD_DIAGRAM_MAX_MB', 2),

    /*
    |--------------------------------------------------------------------------
    | Jeda Aba-aba Cue Chord (Milidetik)
    |--------------------------------------------------------------------------
    | Waktu reaksi sebelum ketukan pergantian chord agar pengguna sempat
    | membaca dan memindahkan jari sebelum beat tiba (default 1000 ms / 1 dtk).
    */
    'cue_margin_ms' => (int) env('CHORDCUE_CUE_MARGIN_MS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Ambang Batas Hening Akhir Ucapan TTS (dBFS)
    |--------------------------------------------------------------------------
    | Ambang batas tingkat suara RMS (dBFS) untuk mendeteksi kapan ucapan chord
    | pada berkas MP3 TTS benar-benar selesai sebelum hening penutup (padding).
    */
    'speech_silence_threshold_db' => (float) env('CHORDCUE_SPEECH_SILENCE_DB', -40.0),

    /*
    |--------------------------------------------------------------------------
    | Koreksi Halus Akhir Cue Chord (Milidetik)
    |--------------------------------------------------------------------------
    | Konstanta trim (ms) untuk koreksi halus posisi akhir ucapan chord
    | (misalnya kompensasi encoder delay MP3). Nilai positif memangkas akhir
    | ucapan sehingga cue dimulai sedikit lebih lambat; nilai negatif memajukan cue.
    */
    'chord_cue_trim_ms' => (int) env('CHORDCUE_CHORD_CUE_TRIM_MS', 0),

];
