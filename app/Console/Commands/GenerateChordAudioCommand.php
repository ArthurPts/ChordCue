<?php

namespace App\Console\Commands;

use App\Jobs\GenerateChordAudio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateChordAudioCommand extends Command
{
    protected $signature = 'chords:generate-audio
                            {--force : Buat ulang semua audio (termasuk yang sudah ada)}
                            {--sync  : Proses langsung sekarang, tanpa antrean/worker}';

    protected $description = 'Buat audio TTS (edge-tts) untuk chord yang belum punya file audio';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $ids = DB::table('chords')
            ->when(!$force, fn ($q) => $q->whereNull('file_path'))
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('Semua chord sudah punya audio.');
            return self::SUCCESS;
        }

        foreach ($ids as $id) {
            $this->option('sync')
                ? GenerateChordAudio::dispatchSync($id, $force)
                : GenerateChordAudio::dispatch($id, $force);
        }

        $this->info($this->option('sync')
            ? "Selesai memproses {$ids->count()} chord."
            : "{$ids->count()} job masuk antrean. Pastikan worker berjalan (php artisan serve:all).");

        return self::SUCCESS;
    }
}
