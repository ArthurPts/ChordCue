<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ArtistSeeder::class,
            CategorySeeder::class,
            ChordSeeder::class,
            ChordcueSeeder::class,   // 15 lagu asli (lirik dari dummy-lirik.txt)
            DummySongSeeder::class,  // 8 lagu dummy fiktif (lirik placeholder)
        ]);
    }
}
