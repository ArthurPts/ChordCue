<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Taruh file ini di database/seeders/ChordcueSeeder.php
 * Taruh file lirik gabungan di database/seeders/lyrics/dummy-lirik.txt
 *
 * Format file (satu file, semua lagu):
 *   [Lirik "Judul Lagu"]
 *   [Verse 1]
 *   baris lirik
 *   [Chorus]
 *   baris lirik
 *   [Lirik "Judul Lagu Berikutnya"]
 *   ...
 *
 * - Blok lirik dipasangkan dengan daftar $songs BERDASARKAN URUTAN (blok ke-1 = lagu ke-1, dst).
 *   Kalau judul di header berbeda dengan judul di daftar, muncul peringatan (tetap diproses).
 * - Baris kosong diabaikan, CRLF/BOM/kurung tutup yang hilang (mis. "[Verse 1") ditangani.
 * - Section berulang tulis lengkap tiap kali muncul.
 *
 * Jalankan: php artisan db:seed --class=ChordcueSeeder
 */
class ChordcueSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ---- Admin (pemilik semua lagu) ----
        $adminId = DB::table('users')->where('username', 'admin')->value('id');
        if (!$adminId) {
            $adminId = DB::table('users')->insertGetId([
                'username'   => 'admin',
                'email'      => 'admin@chordcue.test',
                'password'   => Hash::make('password'),
                'role'       => 'admin',
                'song_limit' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ---- Daftar lagu ----
        // [slug, judul, artis, [kategori nama => tipe]]
        $songs = [
            ['sempurna', 'Sempurna', 'Andra and the Backbone', ['Pop Rock' => 'genre', 'Romantis' => 'mood', 'Indonesia' => 'location']],
            ['sesi-potret', 'Sesi Potret', 'Ari Lesmana', ['Pop' => 'genre', 'Sendu' => 'mood', 'Indonesia' => 'location']],
            ['kota-ini-tak-sama-tanpamu', 'Kota Ini Tak Sama Tanpamu', 'Nadhif Basalamah', ['Pop' => 'genre', 'Sendu' => 'mood', 'Indonesia' => 'location']],
            ['cantik', 'Cantik', 'Kahitna', ['Pop' => 'genre', 'Romantis' => 'mood', 'Indonesia' => 'location']],
            ['serana', 'Serana', 'For Revenge', ['Pop Rock' => 'genre', 'Sendu' => 'mood', 'Indonesia' => 'location']],
            ['cincin', 'Cincin', 'Hindia', ['Indie Pop' => 'genre', 'Romantis' => 'mood', 'Indonesia' => 'location']],
            ['celengan-rindu', 'Celengan Rindu', 'Fiersa Besari', ['Folk' => 'genre', 'Sendu' => 'mood', 'Indonesia' => 'location']],
            ['akad', 'Akad', 'Payung Teduh', ['Folk' => 'genre', 'Romantis' => 'mood', 'Indonesia' => 'location']],
            ['doremi', 'Doremi', 'Budi Doremi', ['Pop' => 'genre', 'Romantis' => 'mood', 'Indonesia' => 'location']],
            ['everything-u-are', 'Everything U Are', 'Hindia', ['Indie Pop' => 'genre', 'Romantis' => 'mood', 'Indonesia' => 'location']],
            ['i-still-love-you', 'I Still Love You', 'The Overtunes', ['Pop' => 'genre', 'Romantis' => 'mood', 'Indonesia' => 'location']],
            ['one-thing', 'One Thing', 'One Direction', ['Pop' => 'genre', 'Ceria' => 'mood', 'Barat' => 'location']],
            ['what-makes-you-beautiful', 'What Makes You Beautiful', 'One Direction', ['Pop' => 'genre', 'Ceria' => 'mood', 'Barat' => 'location']],
            ['perfect', 'Perfect', 'Ed Sheeran', ['Pop' => 'genre', 'Romantis' => 'mood', 'Barat' => 'location']],
            ['24k-magic', '24K Magic', 'Bruno Mars', ['Funk Pop' => 'genre', 'Ceria' => 'mood', 'Barat' => 'location']],
        ];

        $file = database_path('seeders/lyrics/dummy-lirik.txt');
        $blocks = is_file($file) ? $this->parseCombined(file_get_contents($file)) : [];
        if (!$blocks) {
            $this->command?->warn('dummy-lirik.txt tidak ditemukan / kosong, lagu dibuat tanpa lirik.');
        }

        foreach ($songs as $idx => [$slug, $title, $artistName, $cats]) {
            // artist
            $artistId = DB::table('artists')->where('name', $artistName)->value('id')
                ?? DB::table('artists')->insertGetId(['name' => $artistName]);

            // skip kalau lagu sudah ada
            $exists = DB::table('songs')->where('title', $title)->where('artist_id', $artistId)->exists();
            if ($exists) {
                $this->command?->warn("Skip (sudah ada): $title");
                continue;
            }

            $songId = DB::table('songs')->insertGetId([
                'user_id'    => $adminId,
                'title'      => $title,
                'artist_id'  => $artistId,
                'visibility' => 'public',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // categories
            foreach ($cats as $catName => $catType) {
                $catId = DB::table('categories')->where('name', $catName)->value('id')
                    ?? DB::table('categories')->insertGetId(['name' => $catName, 'type' => $catType]);
                DB::table('category_song')->insertOrIgnore(['category_id' => $catId, 'song_id' => $songId]);
            }

            // lyrics
            $block = $blocks[$idx] ?? null;
            if (!$block) {
                $this->command?->warn("Blok lirik ke-" . ($idx + 1) . " tidak ada: $title");
                continue;
            }
            if ($this->norm($block['title']) !== $this->norm($title)) {
                $this->command?->warn("Header lirik \"{$block['title']}\" dipasangkan dengan \"$title\" (cek urutan!)");
            }
            $this->seedLyrics($songId, $block['sections']);
            $this->command?->info("OK: $title");
        }
    }

    private function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($s));
    }

    /** Pecah file gabungan menjadi blok per lagu. */
    private function parseCombined(string $text): array
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        $blocks = [];
        $b = null;   // index blok aktif
        $s = null;   // index section aktif

        foreach (preg_split('/\R/u', $text) as $raw) {
            $line = trim($raw);
            if ($line === '') {
                continue;
            }
            if ($line[0] === '[') {
                $label = trim(rtrim(substr($line, 1), ']'));
                if (stripos($label, 'Lirik') === 0) {
                    $title = trim(preg_replace('/^Lirik\s*/iu', '', $label), " \t\"'\u{201C}\u{201D}");
                    $blocks[] = ['title' => $title, 'sections' => []];
                    $b = count($blocks) - 1;
                    $s = null;
                } elseif ($b !== null) {
                    $blocks[$b]['sections'][] = ['name' => $label, 'lines' => []];
                    $s = count($blocks[$b]['sections']) - 1;
                }
                continue;
            }
            if ($b === null) {
                continue;
            }
            if ($s === null) {
                $blocks[$b]['sections'][] = ['name' => 'Verse', 'lines' => []];
                $s = 0;
            }
            $blocks[$b]['sections'][$s]['lines'][] = $line;
        }
        return $blocks;
    }

    private function seedLyrics(int $songId, array $sections): void
    {
        foreach ($sections as $i => $section) {
            $sectionId = DB::table('song_sections')->insertGetId([
                'song_id'  => $songId,
                'name'     => $section['name'],
                'sequence' => $i + 1,
            ]);
            $rows = [];
            foreach ($section['lines'] as $n => $content) {
                $rows[] = [
                    'song_section_id' => $sectionId,
                    'line_number'     => $n + 1,
                    'content'         => $content,
                ];
            }
            if ($rows) {
                DB::table('lyric_lines')->insert($rows);
            }
        }
    }
}
