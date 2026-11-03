<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        $artists = [
            'Andra and the Backbone',
            'Ari Lesmana',
            'Nadhif Basalamah',
            'Kahitna',
            'For Revenge',
            'Hindia',
            'Fiersa Besari',
            'Payung Teduh',
            'Budi Doremi',
            'The Overtunes',
            'One Direction',
            'Ed Sheeran',
            'Bruno Mars',
            'Tulus',
            'Sheila on 7',
            'Raisa',
            'Noah',
            'Dewa 19',
            'Coldplay',
            'Taylor Swift',
            'John Mayer',
            'Jason Mraz',
            'Band Contoh',
            'Solois Contoh',
            'Duo Dummy',
        ];

        foreach ($artists as $name) {
            DB::table('artists')->insertOrIgnore(['name' => $name]);
        }
    }
}
