<?php

namespace Database\Seeders;

use App\Jobs\GenerateChordAudio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi tabel `chords` (nama + cara baca untuk audio/TTS).
 * Setelah itu, tiap chord yang belum punya audio dikirim ke antrean TTS (edge-tts).
 * Pastikan worker berjalan: php artisan serve:all   (atau php artisan queue:work)
 */
class ChordSeeder extends Seeder
{
    public function run(): void
    {
        // [nama, cara baca]
        $chords = [
            ['C', 'C'],
            ['D', 'D'],
            ['E', 'E'],
            ['F', 'F'],
            ['G', 'G'],
            ['A', 'A'],
            ['B', 'B'],
            ['Cm', 'C minor'],
            ['Dm', 'D minor'],
            ['Em', 'E minor'],
            ['Fm', 'F minor'],
            ['Gm', 'G minor'],
            ['Am', 'A minor'],
            ['Bm', 'B minor'],
            ['F#m', 'F sharp minor'],
            ['C7', 'C seven'],
            ['D7', 'D seven'],
            ['E7', 'E seven'],
            ['G7', 'G seven'],
            ['A7', 'A seven'],
            ['B7', 'B seven'],
            ['Cmaj7', 'C majj seven'],
            ['Fmaj7', 'F majj seven'],
            ['Gmaj7', 'G majj seven'],
            ['Am7', 'A minor seven'],
            ['Dm7', 'D minor seven'],
            ['Em7', 'E minor seven'],
            ['Bm7', 'B minor seven'],
            ['Dsus4', 'D suss four'],
            ['Asus4', 'A suss four'],
            ['Bb', 'B flat'],
            ['Eb', 'E flat'],
            ['Ab', 'A flat'],
            ['C#m', 'C sharp minor'],
            ['G#m', 'G sharp minor'],
            ['Bdim', 'B diminished'],
            ['Cadd9', 'C add nine'],
            ['Gadd9', 'G add nine'],
            ['Dadd9', 'D add nine'],
            ['F#7', 'F sharp seven'],
            ['Gsus4', 'G suss four'],
            ['Csus4', 'C suss four'],

            // Slash Chords (12 varian)
            ['C/G', 'C over G'],
            ['C/E', 'C over E'],
            ['D/F#', 'D over F sharp'],
            ['G/B', 'G over B'],
            ['G/F', 'G over F'],
            ['Am/G', 'A minor over G'],
            ['Am/E', 'A minor over E'],
            ['F/A', 'F over A'],
            ['Dm/F', 'D minor over F'],
            ['Em/B', 'E minor over B'],
            ['A/C#', 'A over C sharp'],
            ['Bb/D', 'B flat over D'],
        ];

        foreach ($chords as [$name, $pronunciation]) {
            DB::table('chords')->updateOrInsert(
                ['name' => $name],
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
