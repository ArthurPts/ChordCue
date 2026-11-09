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
    | Jeda aman antara akhir suara ucapan chord dan ketukan pergantian chord.
    | Dapat dikonfigurasi melalui variabel lingkungan .env.
    */
    'cue_margin_ms' => (int) env('CHORDCUE_CUE_MARGIN_MS', 50),

];
