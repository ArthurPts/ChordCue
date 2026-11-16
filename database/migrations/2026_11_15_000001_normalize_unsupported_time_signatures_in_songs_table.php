<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menormalisasi time signature lama ke salah satu opsi valid (4/4, 3/4, 2/4, 6/8).
     */
    public function up(): void
    {
        $supported = ['4/4', '3/4', '2/4', '6/8'];

        $songs = DB::table('songs')
            ->select(['id', 'time_signature_numerator', 'time_signature_denominator'])
            ->get();

        foreach ($songs as $song) {
            $current = $song->time_signature_numerator . '/' . $song->time_signature_denominator;

            if (in_array($current, $supported, true)) {
                continue;
            }

            if ($current === '12/8') {
                DB::table('songs')
                    ->where('id', $song->id)
                    ->update([
                        'time_signature_numerator'   => 6,
                        'time_signature_denominator' => 8,
                    ]);

                continue;
            }

            DB::table('songs')
                ->where('id', $song->id)
                ->update([
                    'time_signature_numerator'   => 4,
                    'time_signature_denominator' => 4,
                ]);
        }
    }

    /**
     * Normalisasi data bersifat satu arah; biarkan kosong.
     */
    public function down(): void
    {
        // Tidak ada pembalikan otomatis karena nilai lama tidak lagi didukung.
    }
};
