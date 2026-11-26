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
            'C', 'D', 'E', 'F', 'G', 'A', 'B',
            'Cm', 'Dm', 'Em', 'Fm', 'Gm', 'Am', 'Bm',
            'F#m',
            'C7', 'D7', 'E7', 'G7', 'A7', 'B7',
            'Cmaj7', 'Fmaj7', 'Gmaj7',
            'Am7', 'Dm7', 'Em7', 'Bm7',
            'Dsus4', 'Asus4',
            'Bb', 'Eb', 'Ab',
            'C#m', 'G#m',
            'Bdim',
            'Cadd9', 'Gadd9', 'Dadd9',
            'F#7',
            'Gsus4', 'Csus4',

            // Slash Chords (12 varian)
            'C/G', 'C/E', 'D/F#', 'G/B', 'G/F', 'Am/G',
            'Am/E', 'F/A', 'Dm/F', 'Em/B', 'A/C#', 'Bb/D',
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
