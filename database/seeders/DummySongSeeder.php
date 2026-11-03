<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Lagu dummy fiktif (BUKAN lagu asli) dengan lirik placeholder buatan sendiri.
 * Berguna untuk menguji variasi BPM, key, time signature, visibility, dan pemilik lagu.
 * Chord belum dipasang (chord_placements kosong).
 */
class DummySongSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // [judul, artis, owner, key, bpm, num, den, visibility, [kategori]]
        $songs = [
            ['Contoh Lagu Pagi', 'Band Contoh', 'budi', 'G', 96, 4, 4, 'public', ['Pop', 'Ceria', 'Indonesia']],
            ['Contoh Lagu Hujan', 'Solois Contoh', 'sari', 'Am', 68, 4, 4, 'public', ['Ballad', 'Sendu', 'Indonesia']],
            ['Dummy Waltz', 'Duo Dummy', 'andi', 'C', 90, 3, 4, 'public', ['Folk', 'Santai', 'Barat']],
            ['Dummy Rock Anthem', 'Band Contoh', 'dewi', 'E', 132, 4, 4, 'public', ['Rock', 'Semangat', 'Barat']],
            ['Dummy Reggae Sore', 'Solois Contoh', 'rudi', 'D', 78, 4, 4, 'private', ['Reggae', 'Santai', 'Indonesia']],
            ['Dummy Jazz Malam', 'Duo Dummy', 'maya', 'F', 104, 4, 4, 'public', ['Jazz', 'Romantis', 'Barat']],
            ['Contoh Lagu Galau', 'Band Contoh', 'fajar', 'Em', 72, 6, 8, 'private', ['Pop Rock', 'Galau', 'Indonesia']],
            ['Dummy Acoustic Draft', 'Solois Contoh', 'lina', 'D', 84, 4, 4, 'private', ['Acoustic', 'Santai', 'Barat']],
        ];

        // Struktur section + jumlah baris placeholder
        $structure = [
            ['Intro', 2], ['Verse 1', 4], ['Chorus', 4], ['Verse 2', 4], ['Chorus', 4], ['Bridge', 3], ['Outro', 2],
        ];

        foreach ($songs as [$title, $artistName, $owner, $key, $bpm, $num, $den, $visibility, $cats]) {
            $userId = DB::table('users')->where('username', $owner)->value('id');
            $artistId = DB::table('artists')->where('name', $artistName)->value('id');
            if (!$userId || !$artistId) {
                $this->command?->warn("Lewati $title (jalankan UserSeeder & ArtistSeeder dulu)");
                continue;
            }
            if (DB::table('songs')->where('title', $title)->where('artist_id', $artistId)->exists()) {
                continue;
            }

            $songId = DB::table('songs')->insertGetId([
                'user_id'                    => $userId,
                'title'                      => $title,
                'artist_id'                  => $artistId,
                'default_key'                => $key,
                'bpm'                        => $bpm,
                'time_signature_numerator'   => $num,
                'time_signature_denominator' => $den,
                'visibility'                 => $visibility,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ]);

            foreach ($cats as $catName) {
                $catId = DB::table('categories')->where('name', $catName)->value('id');
                if ($catId) {
                    DB::table('category_song')->insertOrIgnore(['category_id' => $catId, 'song_id' => $songId]);
                }
            }

            foreach ($structure as $i => [$sectionName, $lineCount]) {
                $sectionId = DB::table('song_sections')->insertGetId([
                    'song_id'  => $songId,
                    'name'     => $sectionName,
                    'sequence' => $i + 1,
                ]);
                $lines = [];
                for ($n = 1; $n <= $lineCount; $n++) {
                    $lines[] = [
                        'song_section_id' => $sectionId,
                        'line_number'     => $n,
                        'content'         => "Contoh baris lirik $n ($sectionName)",
                    ];
                }
                DB::table('lyric_lines')->insert($lines);
            }
            $this->command?->info("OK (dummy): $title");
        }
    }
}
