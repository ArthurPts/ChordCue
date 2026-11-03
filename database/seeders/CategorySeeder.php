<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Pop', 'genre'],
            ['Pop Rock', 'genre'],
            ['Indie Pop', 'genre'],
            ['Folk', 'genre'],
            ['Funk Pop', 'genre'],
            ['Rock', 'genre'],
            ['Jazz', 'genre'],
            ['R&B', 'genre'],
            ['Reggae', 'genre'],
            ['Dangdut', 'genre'],
            ['Pop Punk', 'genre'],
            ['Ballad', 'genre'],
            ['Acoustic', 'genre'],
            ['Country', 'genre'],
            ['Romantis', 'mood'],
            ['Sendu', 'mood'],
            ['Ceria', 'mood'],
            ['Semangat', 'mood'],
            ['Santai', 'mood'],
            ['Galau', 'mood'],
            ['Religi', 'mood'],
            ['Indonesia', 'location'],
            ['Barat', 'location'],
            ['Korea', 'location'],
            ['Jepang', 'location'],
            ['Latin', 'location'],
        ];

        foreach ($categories as [$name, $type]) {
            DB::table('categories')->insertOrIgnore(['name' => $name, 'type' => $type]);
        }
    }
}
