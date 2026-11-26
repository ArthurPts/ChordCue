<?php

namespace Database\Seeders;

use App\Jobs\GenerateChordAudio;
use App\Services\PronunciationGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi tabel `chords` (nama + cara baca otomatis dari PronunciationGenerator).
 * Setelah itu, tiap chord yang belum punya audio dikirim ke antrean TTS (edge-tts).
 * Pastikan worker berjalan: php artisan serve:all   (atau php artisan queue:work)
 */
class ChordSeeder extends Seeder
{
    /**
     * Mengisi daftar chord master beserta teks pelafalan dari config/pronunciation.php
     * lalu mengantrekan pembuatan audio TTS untuk chord yang belum memiliki file audio.
     *
     * Fungsi ini digunakan pada:
     * - DatabaseSeeder::run()
     * - Perintah artisan: php artisan db:seed --class=ChordSeeder
     */
    public function run(): void
    {
        $chordNames = [
            'A',
            'Am',
            'Bb',
            'Bdim',
            'Bm7b5',
            'Cadd9',
            'Caug',
            'Cdim7',
            'Csus2',
            'Csus4',
            'C7',
            'Cmaj7',
            'Cm7',
            'Cmaj9',
            'C7#5',
            'C7b9',
            'D7sus4',
            'E/G#',
            'Fmaj7',
            'G13',
        ];

        $pronunciationGenerator = new PronunciationGenerator();

        foreach ($chordNames as $chordName) {
            $pronunciation = $pronunciationGenerator->generate($chordName);

            DB::table('chords')->updateOrInsert(
                ['name' => $chordName],
                ['pronunciation' => $pronunciation, 'file_path' => null]
            );
        }

        // Antrekan pembuatan audio untuk chord yang belum punya file
        if (config('tts.enabled')) {
            $pending = DB::table('chords')->whereNull('file_path')->pluck('id');
            foreach ($pending as $id) {
                GenerateChordAudio::dispatch($id);
            }
            $this->command?->info("{$pending->count()} job audio TTS masuk antrean.");
        }
    }
}
