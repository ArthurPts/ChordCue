<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pelafalan Nada Akar & Bass (Notes)
    |--------------------------------------------------------------------------
    | Seluruh nada akar & bass (A s/d G) menggunakan huruf alfabetnya masing-masing
    | agar terbaca alami di UI; penyesuaian khusus TTS (mis. jeda koma setelah 'A'
    | agar tidak dibaca sebagai artikel /ə/) ditangani di GenerateChordAudio.
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
    | Pemetaan Simbol Kualitas, Ekstensi, Alterasi, dan Aksidental (Symbols)
    |--------------------------------------------------------------------------
    | Mendukung berbagai variasi jenis chord, ekstensi, alterasi, dan kombinasi
    | musik modern secara modular melalui pencocokan terpanjang (greedy matching).
    */
    'symbols' => [
        // Aksidental nada & alterasi
        '#'    => 'sharp',
        'b'    => 'flat',
        '♯'    => 'sharp',
        '♭'    => 'flat',

        // Kualitas dasar & variasi penulisan triad / ketujuh
        'major'=> 'major',
        'maj'  => 'major',
        'M'    => 'major',
        'Δ'    => 'major',
        'minor'=> 'minor',
        'min'  => 'minor',
        'm'    => 'minor',
        '-'    => 'minor',

        // Diminished & Half-Diminished
        'dim'      => 'diminished',
        'o'        => 'diminished',
        '°'        => 'diminished',
        'ø'        => 'half diminished',
        'half-dim' => 'half diminished',

        // Augmented & Altered
        'aug'  => 'augmented',
        '+'    => 'augmented',
        'alt'  => 'altered',

        // Suspended, Added, Dominant
        'sus'  => 'suspended',
        'add'  => 'add',
        'dom'  => 'dominant',
        'omit' => 'omit',
        'no'   => 'no',

        // Bass / Slash Chord
        '/'    => 'over',

        // Compound Extensions & Alterations (Pencocokan terpanjang)
        'maj7'     => 'major 7',
        'maj9'     => 'major 9',
        'maj11'    => 'major 11',
        'maj13'    => 'major 13',
        'min7'     => 'minor 7',
        'min9'     => 'minor 9',
        'min11'    => 'minor 11',
        'min13'    => 'minor 13',
        'm7b5'     => 'minor 7 flat 5',
        'm7-5'     => 'minor 7 flat 5',
        'm7'       => 'minor 7',
        'm9'       => 'minor 9',
        'm11'      => 'minor 11',
        'm13'      => 'minor 13',
        'm6'       => 'minor 6',
        'dim7'     => 'diminished 7',
        'aug7'     => 'augmented 7',
        '+7'       => 'augmented 7',
        'ø7'       => 'half diminished 7',
        '°7'       => 'diminished 7',
        'sus4'     => 'suspended 4',
        'sus2'     => 'suspended 2',
        '7sus4'    => '7 suspended 4',
        '7sus2'    => '7 suspended 2',
        '9sus4'    => '9 suspended 4',
        'add9'     => 'add 9',
        'add11'    => 'add 11',
        'add2'     => 'add 2',
        'add4'     => 'add 4',
        '6/9'      => '6 9',
        '69'       => '6 9',
    ],

];
