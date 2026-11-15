<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Menjalankan seluruh seeder awal aplikasi ChordCue secara berurutan.
     *
     * Fungsi ini digunakan pada:
     * - Perintah artisan: php artisan db:seed / php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ArtistSeeder::class,
            CategorySeeder::class,
            ChordSeeder::class,
            ChordcueSeeder::class,             // 15 lagu asli (lirik dari dummy-lirik.txt)
            DummySongSeeder::class,            // 8 lagu dummy fiktif (lirik placeholder)
            DummyChordPlacementSeeder::class,  // Dummy chord_placements untuk Sempurna & Dummy Waltz
        ]);
    }
}
