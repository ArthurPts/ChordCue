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
    test('melafalkan nada A menggunakan huruf A dan aturan Bahasa Indonesia', function () {
        $generator = new PronunciationGenerator();

        expect($generator->generate('A'))->toBe('A');
        expect($generator->generate('Am'))->toBe('A minor');
        expect($generator->generate('Am7'))->toBe('A minor tujuh');
        expect($generator->generate('Amaj7'))->toBe('A mayor tujuh');
        expect($generator->generate('Ab'))->toBe('A es');
        expect($generator->generate('A#m'))->toBe('A is minor');
        expect($generator->generate('A/C#'))->toBe('A on Cis');
        expect($generator->generate('G/A'))->toBe('G on A');
    });

    test('tidak mempengaruhi pelafalan nada lain atau kata yang mengandung huruf a', function () {
        $generator = new PronunciationGenerator();

        // Kata kualitas dan angka mengikuti aturan Bahasa Indonesia
        expect($generator->generate('Cmaj7'))->toBe('C mayor tujuh');
        expect($generator->generate('Bb'))->toBe('Bes');
        expect($generator->generate('Cadd9'))->toBe('C add sembilan');
        expect($generator->generate('Caug'))->toBe('C aug');
        expect($generator->generate('F#m7'))->toBe('Fis minor tujuh');
    });

    test('mendukung berbagai variasi jenis chord, ekstensi, alterasi, dan kombinasi modern', function () {
        $generator = new PronunciationGenerator();

        // Alterations & Complex extensions
        expect($generator->generate('F#m7b5'))->toBe('Fis minor tujuh mol lima');
        expect($generator->generate('C7#9'))->toBe('C tujuh kres sembilan');
        expect($generator->generate('C7b13'))->toBe('C tujuh mol tiga belas');
        expect($generator->generate('Cmaj7#11'))->toBe('C mayor tujuh kres sebelas');
        expect($generator->generate('Cmaj9'))->toBe('C mayor sembilan');
        expect($generator->generate('CM'))->toBe('C mayor');
        expect($generator->generate('C11'))->toBe('C sebelas');
        expect($generator->generate('C13'))->toBe('C tiga belas');
        expect($generator->generate('Csus4'))->toBe('C sus empat');
        expect($generator->generate('Csus2'))->toBe('C sus dua');
        expect($generator->generate('C7sus4'))->toBe('C tujuh sus empat');
        expect($generator->generate('D+7'))->toBe('D aug tujuh');
        expect($generator->generate('Cø7'))->toBe('C half dim tujuh');
        expect($generator->generate('E7alt'))->toBe('E tujuh alt');
        expect($generator->generate('C6/9'))->toBe('C enam sembilan');
        expect($generator->generate('C5'))->toBe('C lima');
        expect($generator->generate('Am(maj7)'))->toBe('A minor mayor tujuh');
        expect($generator->generate('Ddim'))->toBe('D dim');
    });

    test('menangani slash chord dengan bass bernada sharp atau flat', function () {
        $generator = new PronunciationGenerator();

        expect($generator->generate('F#m7/C#'))->toBe('Fis minor tujuh on Cis');
        expect($generator->generate('C/E'))->toBe('C on E');
        expect($generator->generate('G/B'))->toBe('G on B');
    });
});

describe('GenerateChordAudio TTS Text Formatter', function () {
    test('menerapkan respelling bawaan (aug -> augg) tanpa mengubah token lain', function () {
        expect(GenerateChordAudio::formatTextForTts('A'))->toBe('A');
        expect(GenerateChordAudio::formatTextForTts('A minor'))->toBe('A minor');
        expect(GenerateChordAudio::formatTextForTts('Cis minor'))->toBe('Cis minor');
        expect(GenerateChordAudio::formatTextForTts('C mayor tujuh'))->toBe('C mayor tujuh');
        expect(GenerateChordAudio::formatTextForTts('D sus empat'))->toBe('D sus empat');
        expect(GenerateChordAudio::formatTextForTts('G on Bes'))->toBe('G on Bes');
        expect(GenerateChordAudio::formatTextForTts('C aug'))->toBe('C augg');
        expect(GenerateChordAudio::formatTextForTts('C aug tujuh'))->toBe('C augg tujuh');
        expect(GenerateChordAudio::formatTextForTts('C Aug sembilan'))->toBe('C augg sembilan');
        expect(GenerateChordAudio::formatTextForTts('C augmented'))->toBe('C augmented');
    });

    test('menerapkan peta tts_respelling ketika tts_respelling_enabled bernilai true', function () {
        $generator = new PronunciationGenerator([
            'tts_respelling_enabled' => true,
            'tts_respelling'         => [
                'Cis' => 'Chis',
                'sus' => 'suss',
            ],
        ]);

        expect(GenerateChordAudio::formatTextForTts('Cis on D', $generator))->toBe('Chis on D');
        expect(GenerateChordAudio::formatTextForTts('D sus empat', $generator))->toBe('D suss empat');
        expect(GenerateChordAudio::formatTextForTts('G on Bes', $generator))->toBe('G on Bes');
    });

    test('membentuk nama file audio yang unik untuk slash chord tanpa bentrok dengan chord flat', function () {
        expect(GenerateChordAudio::fileName('F#m'))->toBe('fkresm');
        expect(GenerateChordAudio::fileName('F♯m'))->toBe('fkresm');
        expect(GenerateChordAudio::fileName('Bb'))->toBe('bb');
        expect(GenerateChordAudio::fileName('G/B'))->toBe('gonb');
        expect(GenerateChordAudio::fileName('Gb'))->toBe('gb');
        expect(GenerateChordAudio::fileName('D/F#'))->toBe('donfkres');
        expect(GenerateChordAudio::fileName('A/C#'))->toBe('aonckres');
        expect(GenerateChordAudio::fileName('Bb/D'))->toBe('bbond');
    });
});

