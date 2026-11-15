<?php

use App\Jobs\GenerateChordAudio;
use App\Services\ChordTransposer;
use App\Services\PronunciationGenerator;

describe('ChordTransposer Service', function () {
    test('mentranspose nada chord dasar dengan benar', function () {
        $transposer = new ChordTransposer();

        expect($transposer->transpose('C', 2))->toBe('D');
        expect($transposer->transpose('Am', 3))->toBe('Cm');
        expect($transposer->transpose('F#m', 1))->toBe('Gm');
        expect($transposer->transpose('G', -2))->toBe('F');
        expect($transposer->transpose('E', 1))->toBe('F');
        expect($transposer->transpose('B', 1))->toBe('C');
    });

    test('mempertahankan skala flat ketika nada asal mengandung flat', function () {
        $transposer = new ChordTransposer();

        // Bb (+2) => C (bukan B#)
        expect($transposer->transpose('Bb', 2))->toBe('C');
        // Eb (+1) => E
        expect($transposer->transpose('Eb', 1))->toBe('E');
        // Ab (-1) => G
        expect($transposer->transpose('Ab', -1))->toBe('G');
        // F (-1) => E
        expect($transposer->transpose('F', -1))->toBe('E');
    });

    test('mentranspose chord dengan ekstensi kompleks dan slash bass', function () {
        $transposer = new ChordTransposer();

        // F#m7 (+1) => Gm7
        expect($transposer->transpose('F#m7', 1))->toBe('Gm7');
        // G/B (-2) => F/A
        expect($transposer->transpose('G/B', -2))->toBe('F/A');
        // Cmaj7 (+2) => Dmaj7
        expect($transposer->transpose('Cmaj7', 2))->toBe('Dmaj7');
        // F#m7/C# (+1) => Gm7/D
        expect($transposer->transpose('F#m7/C#', 1))->toBe('Gm7/D');
    });

    test('mengembalikan chord apa adanya jika semitone 0 atau string tidak dapat diurai', function () {
        $transposer = new ChordTransposer();

        expect($transposer->transpose('Am', 0))->toBe('Am');
        expect($transposer->transpose('N.C.', 3))->toBe('N.C.');
        expect($transposer->transpose('', 2))->toBe('');
    });

    test('mengekstrak nada akar (root note) dengan benar dan mengabaikan bass slash chord', function () {
        $transposer = new ChordTransposer();

        expect($transposer->extractRoot('C'))->toBe('C');
        expect($transposer->extractRoot('F#m7'))->toBe('F#');
        expect($transposer->extractRoot('Bbmaj7'))->toBe('Bb');
        expect($transposer->extractRoot('G/B'))->toBe('G');
        expect($transposer->extractRoot('Unknown'))->toBeNull();
    });
});

describe('PronunciationGenerator Service', function () {
    test('melafalkan nada A menggunakan huruf A agar terbaca alami di UI', function () {
        $generator = new PronunciationGenerator();

        expect($generator->generate('A'))->toBe('A');
        expect($generator->generate('Am'))->toBe('A minor');
        expect($generator->generate('Am7'))->toBe('A minor 7');
        expect($generator->generate('Amaj7'))->toBe('A major 7');
        expect($generator->generate('Ab'))->toBe('A flat');
        expect($generator->generate('A#m'))->toBe('A sharp minor');
        expect($generator->generate('A/C#'))->toBe('A over C sharp');
        expect($generator->generate('G/A'))->toBe('G over A');
    });

    test('tidak mempengaruhi pelafalan nada lain atau kata yang mengandung huruf a', function () {
        $generator = new PronunciationGenerator();

        // Kata 'major', 'flat', 'add', 'augmented' tetap utuh tanpa perubahan
        expect($generator->generate('Cmaj7'))->toBe('C major 7');
        expect($generator->generate('Bb'))->toBe('B flat');
        expect($generator->generate('Cadd9'))->toBe('C add 9');
        expect($generator->generate('Caug'))->toBe('C augmented');
        expect($generator->generate('F#m7'))->toBe('F sharp minor 7');
    });

    test('mendukung berbagai variasi jenis chord, ekstensi, alterasi, dan kombinasi modern', function () {
        $generator = new PronunciationGenerator();

        // Alterations & Complex extensions
        expect($generator->generate('F#m7b5'))->toBe('F sharp minor 7 flat 5');
        expect($generator->generate('C7#9'))->toBe('C 7 sharp 9');
        expect($generator->generate('C7b13'))->toBe('C 7 flat 13');
        expect($generator->generate('Cmaj7#11'))->toBe('C major 7 sharp 11');
        expect($generator->generate('C11'))->toBe('C 11');
        expect($generator->generate('C13'))->toBe('C 13');
        expect($generator->generate('Csus4'))->toBe('C suspended 4');
        expect($generator->generate('Csus2'))->toBe('C suspended 2');
        expect($generator->generate('D+7'))->toBe('D augmented 7');
        expect($generator->generate('Cø7'))->toBe('C half diminished 7');
        expect($generator->generate('E7alt'))->toBe('E 7 altered');
        expect($generator->generate('C6/9'))->toBe('C 6 9');
        expect($generator->generate('C5'))->toBe('C 5');
        expect($generator->generate('Am(maj7)'))->toBe('A minor major 7');
        expect($generator->generate('Ddim'))->toBe('D diminished');
    });

    test('menangani slash chord dengan bass bernada sharp atau flat', function () {
        $generator = new PronunciationGenerator();

        expect($generator->generate('F#m7/C#'))->toBe('F sharp minor 7 over C sharp');
        expect($generator->generate('C/E'))->toBe('C over E');
        expect($generator->generate('G/B'))->toBe('G over B');
    });
});

describe('GenerateChordAudio TTS Text Formatter', function () {
    test('menambahkan koma setelah nada A yang diikuti kata lain untuk edge-tts', function () {
        expect(GenerateChordAudio::formatTextForTts('A'))->toBe('A');
        expect(GenerateChordAudio::formatTextForTts('G over A'))->toBe('G over A');
        expect(GenerateChordAudio::formatTextForTts('A minor'))->toBe('A, minor');
        expect(GenerateChordAudio::formatTextForTts('A major 7'))->toBe('A, major 7');
        expect(GenerateChordAudio::formatTextForTts('A over C sharp'))->toBe('A, over C sharp');
        expect(GenerateChordAudio::formatTextForTts('G over A flat'))->toBe('G over A, flat');
        expect(GenerateChordAudio::formatTextForTts('G over A sharp'))->toBe('G over A, sharp');
        expect(GenerateChordAudio::formatTextForTts('A suss four'))->toBe('A, suss four');
    });

    test('menormalisasi data lama Ae dan tidak mengubah kata non-nada A', function () {
        expect(GenerateChordAudio::formatTextForTts('Ae'))->toBe('A');
        expect(GenerateChordAudio::formatTextForTts('Ae minor'))->toBe('A, minor');
        expect(GenerateChordAudio::formatTextForTts('G over Ae'))->toBe('G over A');
        expect(GenerateChordAudio::formatTextForTts('C major 7'))->toBe('C major 7');
        expect(GenerateChordAudio::formatTextForTts('C add 9'))->toBe('C add 9');
        expect(GenerateChordAudio::formatTextForTts('A, minor'))->toBe('A, minor');
    });
});

