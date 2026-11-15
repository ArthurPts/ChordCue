<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DummyChordPlacementSeeder extends Seeder
{
    /**
     * Mengisi data dummy `chord_placements` dan memperbarui `default_key` lagu target
     * dengan mengeksekusi script SQL pada database/seeders/sql/dummy_chord_placements.sql.
     *
     * Fungsi ini digunakan pada:
     * - DatabaseSeeder::run()
     * - Perintah artisan: php artisan db:seed --class=DummyChordPlacementSeeder
     */
    public function run(): void
    {
        $sqlFilePath = database_path('seeders/sql/dummy_chord_placements.sql');

        if (! File::exists($sqlFilePath)) {
            throw new RuntimeException("File SQL dummy chord placement tidak ditemukan: {$sqlFilePath}");
        }

        $sqlContent = File::get($sqlFilePath);
        $driverName = DB::connection()->getDriverName();

        if ($driverName === 'sqlite') {
            $sqlContent = str_replace(['START TRANSACTION;', 'COMMIT;'], '', $sqlContent);
        }

        DB::unprepared($sqlContent);

        $this->command?->info('OK: Dummy chord_placements untuk Sempurna & Dummy Waltz berhasil dijalankan.');
    }
}

