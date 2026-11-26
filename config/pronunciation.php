<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pelafalan Nada Alami (Notes)
    |--------------------------------------------------------------------------
    | Nama pengucapan 7 nada alami (A s/d G) untuk nada akar dan bass.
    */
    'notes' => [
        'A' => 'A',
        'B' => 'B',
        'C' => 'C',
        'D' => 'D',
        'E' => 'E',
        'F' => 'F',
        'G' => 'G',
    ],

    /*
    |--------------------------------------------------------------------------
    | Aksidental Nada Akar & Bass (Accidentals)
    |--------------------------------------------------------------------------
    | Untuk nada konsonan (B, C, D, F, G), aksidental digabung ke nama nada:
    |   # / ♯ -> 'is' (C# -> Cis, F# -> Fis, B# -> Bis)
    |   b / ♭ -> 'es' (Bb -> Bes, Db -> Des, Gb -> Ges, Cb -> Ces, Fb -> Fes)
    | Untuk nada vokal di spaced_accidental_notes (A dan E), diberi spasi:
    |   A# -> 'A is', Ab -> 'A es', E# -> 'E is', Eb -> 'E es'
    */
    'accidentals' => [
        '#' => 'is',
        '♯' => 'is',
        'b' => 'es',
        '♭' => 'es',
    ],

    'spaced_accidental_notes' => ['A', 'E'],

    /*
    |--------------------------------------------------------------------------
    | Pengecualian Flat Tradisional (Irregular Flats)
    |--------------------------------------------------------------------------
    | Jika use_irregular_flats bernilai true: Eb -> 'Es', Ab -> 'As'.
    | Jika false (bawaan): mengikuti spaced_accidental_notes (Eb -> 'E es', Ab -> 'A es').
    */
    'use_irregular_flats' => false,

    'irregular_flats' => [
        'E' => 'Es',
        'A' => 'As',
    ],

    /*
    |--------------------------------------------------------------------------
    | Penghubung Slash Bass Chord (Slash)
    |--------------------------------------------------------------------------
    | Kata penghubung untuk '/' pada slash chord (mis. C#/D -> 'Cis on D').
    */
    'slash' => 'on',

    /*
    |--------------------------------------------------------------------------
    | Pemetaan Simbol Kualitas, Ekstensi, dan Alterasi (Symbols)
    |--------------------------------------------------------------------------
    | Dicocokkan secara greedy longest-match di dalam bagian suffix chord.
    | Catatan: '-' pada suffix diartikan 'minor' kecuali bila langsung mengikuti
    | angka (mis. C7-5 / Cm7-5 -> menggunakan aksidental flat pada suffix).
    */
    'symbols' => [
        // Kualitas dasar (mayor & minor)
        'major'    => 'mayor',
        'maj'      => 'mayor',
        'M'        => 'mayor',
        'Δ'        => 'mayor',
        'minor'    => 'minor',
        'min'      => 'minor',
        'm'        => 'minor',
        '-'        => 'minor',

        // Diminished & Half-Diminished
        'dim'      => 'dim',
        'o'        => 'dim',
        '°'        => 'dim',
        'half-dim' => 'half dim',
        'ø'        => 'half dim',

        // Augmented & Altered
        'aug'      => 'aug',
        '+'        => 'aug',
        'alt'      => 'alt',

        // Suspended, Added, Dominant, Omit, No
        'sus'      => 'sus',
        'add'      => 'add',
        'dom'      => 'dom',
        'omit'     => 'omit',
        'no'       => 'no',
    ],

    /*
    |--------------------------------------------------------------------------
    | Aksidental di Dalam Suffix (Suffix Accidentals)
    |--------------------------------------------------------------------------
    | Gaya pelafalan '#' dan 'b' (serta '-' setelah angka) di dalam suffix
    | seperti F#m7b5, C7#9, Cmaj7#11, C7-5.
    | Pilihan suffix_accidental_style: 'indonesian', 'is_es', 'english'.
    */
    'suffix_accidental_style' => 'indonesian',

    'suffix_accidentals' => [
        'indonesian' => [
            '#' => 'kres',
            '♯' => 'kres',
            'b' => 'mol',
            '♭' => 'mol',
        ],
        'is_es' => [
            '#' => 'is',
            '♯' => 'is',
            'b' => 'es',
            '♭' => 'es',
        ],
        'english' => [
            '#' => 'sharp',
            '♯' => 'sharp',
            'b' => 'flat',
            '♭' => 'flat',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pelafalan Angka Ekstensi (Numbers)
    |--------------------------------------------------------------------------
    | Jika numbers_as_words bernilai true, angka diubah menjadi kata bahasa
    | Indonesia (mis. 7 -> 'tujuh', 11 -> 'sebelas', 13 -> 'tiga belas') dengan
    | fallback per digit untuk angka yang tidak terdaftar.
    | Jika false, angka dibiarkan dalam bentuk digit ('7', '11', '13').
    */
    'numbers_as_words' => true,

    'numbers' => [
        '0'  => 'nol',
        '1'  => 'satu',
        '2'  => 'dua',
        '3'  => 'tiga',
        '4'  => 'empat',
        '5'  => 'lima',
        '6'  => 'enam',
        '7'  => 'tujuh',
        '8'  => 'delapan',
        '9'  => 'sembilan',
        '10' => 'sepuluh',
        '11' => 'sebelas',
        '12' => 'dua belas',
        '13' => 'tiga belas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Angka Majemuk yang Dipecah (Split Numbers)
    |--------------------------------------------------------------------------
    | Menangani notasi khusus seperti '6/9' dan '69' agar dipecah menjadi token
    | angka terpisah yang tetap mengikuti aturan numbers_as_words.
    */
    'split_numbers' => [
        '6/9' => ['6', '9'],
        '69'  => ['6', '9'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lapisan Respelling Opsional Khusus TTS (TTS Respelling)
    |--------------------------------------------------------------------------
    | Jika tts_respelling_enabled bernilai true, pemetaan di bawah diterapkan
    | HANYA saat menghasilkan audio TTS (tidak pernah disimpan ke kolom
    | chords.pronunciation). Nonaktif secara bawaan sampai uji dengar.
    */
    'tts_respelling_enabled' => false,

    'tts_respelling' => [],

];
