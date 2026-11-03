<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Semua akun dummy memakai password: "password"
 * 'tamu' sengaja song_limit = 1 untuk menguji pembatasan jumlah lagu.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password');

        // [username, email, role, song_limit]
        $users = [
            ['admin', 'admin@chordcue.test', 'admin', 100],
            ['budi', 'budi@chordcue.test', 'user', 5],
            ['sari', 'sari@chordcue.test', 'user', 5],
            ['andi', 'andi@chordcue.test', 'user', 10],
            ['rina', 'rina@chordcue.test', 'user', 5],
            ['dewi', 'dewi@chordcue.test', 'user', 5],
            ['rudi', 'rudi@chordcue.test', 'user', 5],
            ['maya', 'maya@chordcue.test', 'user', 8],
            ['fajar', 'fajar@chordcue.test', 'user', 5],
            ['lina', 'lina@chordcue.test', 'user', 5],
            ['tamu', 'tamu@chordcue.test', 'user', 1],
        ];

        foreach ($users as [$username, $email, $role, $limit]) {
            DB::table('users')->updateOrInsert(
                ['username' => $username],
                [
                    'email'      => $email,
                    'password'   => $password,
                    'role'       => $role,
                    'song_limit' => $limit,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
