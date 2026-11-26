<?php

use App\Jobs\GenerateChordAudio;
use App\Services\PronunciationGenerator;

describe('PronunciationGenerator — Tabel Output Bawaan (§8)', function () {
    test('menghasilkan pelafalan bahasa Indonesia sesuai tabel ekspektasi', function (string $chordName, string $expected) {
        $generator = new PronunciationGenerator();

        expect($generator->generate($chordName))->toBe($expected);
    })->with([
        'C'        => ['C', 'C'],
        'A'        => ['A', 'A'],
        'Am'       => ['Am', 'A minor'],
        'C#'       => ['C#', 'Cis'],
        'Bb'       => ['Bb', 'Bes'],
        'Eb'       => ['Eb', 'E es'],
        'Ab'       => ['Ab', 'A es'],
        'A#'       => ['A#', 'A is'],
        'E#'       => ['E#', 'E is'],
        'Gb'       => ['Gb', 'Ges'],
        'Abm'      => ['Abm', 'A es minor'],
        'F#m7'     => ['F#m7', 'Fis minor tujuh'],
        'Cmaj7'    => ['Cmaj7', 'C mayor tujuh'],
        'Dsus4'    => ['Dsus4', 'D sus empat'],
        'Cadd9'    => ['Cadd9', 'C add sembilan'],
        'F#m7b5'   => ['F#m7b5', 'Fis minor tujuh mol lima'],
        'C7#9'     => ['C7#9', 'C tujuh kres sembilan'],
        'Cmaj7#11' => ['Cmaj7#11', 'C mayor tujuh kres sebelas'],
        'C#/D'     => ['C#/D', 'Cis on D'],
        'G/Bb'     => ['G/Bb', 'G on Bes'],
        'Bb/D'     => ['Bb/D', 'Bes on D'],
        'C6/9'     => ['C6/9', 'C enam sembilan'],
        'C69'      => ['C69', 'C enam sembilan'],
        'C7-5'     => ['C7-5', 'C tujuh mol lima'],
        'Cm7-5'    => ['Cm7-5', 'C minor tujuh mol lima'],
        'C-7'      => ['C-7', 'C minor tujuh'],
    ]);

    test('mendukung aksidental langka (E#, B#, Cb, Fb) dan simbol unicode', function () {
        $generator = new PronunciationGenerator();

        expect($generator->generate('E#'))->toBe('E is');
        expect($generator->generate('A#'))->toBe('A is');
        expect($generator->generate('B#'))->toBe('Bis');
        expect($generator->generate('Cb'))->toBe('Ces');
        expect($generator->generate('Fb'))->toBe('Fes');
        expect($generator->generate('F♯m7'))->toBe('Fis minor tujuh');
        expect($generator->generate('B♭/D'))->toBe('Bes on D');
        expect($generator->generate('Cø7'))->toBe('C half dim tujuh');
        expect($generator->generate('Chalf-dim'))->toBe('C half dim');
        expect($generator->generate('D+7'))->toBe('D aug tujuh');
        expect($generator->generate('E7alt'))->toBe('E tujuh alt');
        expect($generator->generate('Ddim7'))->toBe('D dim tujuh');
        expect($generator->generate('C7sus4'))->toBe('C tujuh sus empat');
        expect($generator->generate('C7(-5)'))->toBe('C tujuh mol lima');
        expect($generator->generate('Am(maj7)'))->toBe('A minor mayor tujuh');
        expect($generator->generate('C7b13'))->toBe('C tujuh mol tiga belas');
        expect($generator->generate('Comit3'))->toBe('C omit tiga');
    });
});

describe('PronunciationGenerator — Pengaturan Konfigurasi', function () {
    test('mengikuti switch spaced_accidental_notes dan use_irregular_flats', function () {
        $spacedDefault = new PronunciationGenerator();
        $traditional = new PronunciationGenerator(['use_irregular_flats' => true]);
        $mergedRegular = new PronunciationGenerator([
            'use_irregular_flats'     => false,
            'spaced_accidental_notes' => [],
        ]);

        // Bawaan: A dan E memakai spasi untuk is dan es
        expect($spacedDefault->generate('Eb'))->toBe('E es');
        expect($spacedDefault->generate('Ab'))->toBe('A es');
        expect($spacedDefault->generate('E#'))->toBe('E is');
        expect($spacedDefault->generate('A#'))->toBe('A is');
        expect($spacedDefault->generate('Abm'))->toBe('A es minor');
        expect($spacedDefault->generate('G/Eb'))->toBe('G on E es');
        expect($spacedDefault->generate('F/Ab'))->toBe('F on A es');

        // Mode irregular_flats aktif: Eb -> Es, Ab -> As
        expect($traditional->generate('Eb'))->toBe('Es');
        expect($traditional->generate('Ab'))->toBe('As');
        expect($traditional->generate('Abm'))->toBe('As minor');
        expect($traditional->generate('G/Eb'))->toBe('G on Es');
        expect($traditional->generate('F/Ab'))->toBe('F on As');

        // Tanpa spasi & tanpa irregular flats: Ees, Aes, Eis, Ais
        expect($mergedRegular->generate('Eb'))->toBe('Ees');
        expect($mergedRegular->generate('Ab'))->toBe('Aes');
        expect($mergedRegular->generate('E#'))->toBe('Eis');
        expect($mergedRegular->generate('A#'))->toBe('Ais');
    });

    test('mengikuti setiap gaya suffix_accidental_style (indonesian, is_es, english)', function () {
        $indonesian = new PronunciationGenerator(['suffix_accidental_style' => 'indonesian']);
        $isEs = new PronunciationGenerator(['suffix_accidental_style' => 'is_es']);
        $english = new PronunciationGenerator(['suffix_accidental_style' => 'english']);

        expect($indonesian->generate('F#m7b5'))->toBe('Fis minor tujuh mol lima');
        expect($indonesian->generate('C7#9'))->toBe('C tujuh kres sembilan');
        expect($indonesian->generate('C7-5'))->toBe('C tujuh mol lima');

        expect($isEs->generate('F#m7b5'))->toBe('Fis minor tujuh es lima');
        expect($isEs->generate('C7#9'))->toBe('C tujuh is sembilan');
        expect($isEs->generate('C7-5'))->toBe('C tujuh es lima');

        expect($english->generate('F#m7b5'))->toBe('Fis minor tujuh flat lima');
        expect($english->generate('C7#9'))->toBe('C tujuh sharp sembilan');
        expect($english->generate('C7-5'))->toBe('C tujuh flat lima');
    });

    test('mengikuti switch numbers_as_words (on vs off) dan fallback per digit', function () {
        $asWords = new PronunciationGenerator(['numbers_as_words' => true]);
        $asDigits = new PronunciationGenerator(['numbers_as_words' => false]);

        expect($asWords->generate('Cmaj11'))->toBe('C mayor sebelas');
        expect($asWords->generate('Cmaj13'))->toBe('C mayor tiga belas');
        expect($asWords->generate('C6/9'))->toBe('C enam sembilan');
        expect($asWords->generate('C25'))->toBe('C dua lima');

        expect($asDigits->generate('F#m7'))->toBe('Fis minor 7');
        expect($asDigits->generate('Cmaj11'))->toBe('C mayor 11');
        expect($asDigits->generate('Cmaj13'))->toBe('C mayor 13');
        expect($asDigits->generate('C6/9'))->toBe('C 6 9');
        expect($asDigits->generate('C69'))->toBe('C 6 9');
        expect($asDigits->generate('F#m7b5'))->toBe('Fis minor 7 mol 5');
    });

    test('mengikuti switch tts_respelling_enabled tanpa memengaruhi hasil generate()', function () {
        $disabled = new PronunciationGenerator([
            'tts_respelling_enabled' => false,
            'tts_respelling'         => ['Cis' => 'Chis', 'Fis' => 'Phis'],
        ]);

        $enabled = new PronunciationGenerator([
            'tts_respelling_enabled' => true,
            'tts_respelling'         => ['Cis' => 'Chis', 'Fis' => 'Phis'],
        ]);

        // generate() selalu mengembalikan teks UI bersih tanpa respelling
        expect($enabled->generate('C#m'))->toBe('Cis minor');
        expect($enabled->generate('F#m7'))->toBe('Fis minor tujuh');

        // applyTtsRespelling() hanya aktif saat flag true
        expect($disabled->applyTtsRespelling('Cis minor'))->toBe('Cis minor');
        expect($enabled->applyTtsRespelling('Cis minor'))->toBe('Chis minor');
        expect($enabled->applyTtsRespelling('Cis on D'))->toBe('Chis on D');
        expect($enabled->applyTtsRespelling('Fis minor tujuh'))->toBe('Phis minor tujuh');

        // GenerateChordAudio::formatTextForTts mendelegasikan ke applyTtsRespelling
        expect(GenerateChordAudio::formatTextForTts('Cis minor', $disabled))->toBe('Cis minor');
        expect(GenerateChordAudio::formatTextForTts('Cis minor', $enabled))->toBe('Chis minor');
    });
});
