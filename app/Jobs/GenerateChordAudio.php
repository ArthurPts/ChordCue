<?php

namespace App\Jobs;

use App\Services\PronunciationGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Membuat file audio (mp3) untuk satu chord memakai edge-tts,
 * lalu menyimpan path-nya ke kolom chords.file_path.
 */
class GenerateChordAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;

    public function __construct(public int $chordId, public bool $force = false)
    {
        $this->tries = max(1, (int) config('tts.tries', 3));
        $this->timeout = max(10, (int) config('tts.job_timeout', 90));
    }

    public function backoff(): array
    {
        return [5, 20];
    }

    /**
     * Membentuk nama file MP3 yang aman untuk sistem berkas dari nama chord.
     *
     * Fungsi ini digunakan pada:
     * - GenerateChordAudio::handle
     * - Pengujian unit MusicServicesTest
     */
    public static function fileName(string $chordName): string
    {
        // "F#m" -> "fkresm", "Bb" -> "bb", "D/F#" -> "donfkres", "G/B" -> "gonb" (cegah bentrok "Gb")
        return Str::of($chordName)
            ->replace(['#', '♯'], 'kres')
            ->replace('/', 'on')
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }

    public function handle(): void
    {
        $chord = DB::table('chords')->where('id', $this->chordId)->first();
        if (!$chord) {
            return;
        }

        $disk = Storage::disk(config('tts.disk'));

        if (!$this->force && $chord->file_path && $disk->exists($chord->file_path)) {
            return; // sudah ada
        }

        $oldFilePath = $chord->file_path;
        $relative = trim(config('tts.directory'), '/') . '/' . self::fileName($chord->name) . '.mp3';
        $disk->makeDirectory(dirname($relative));
        $absolute = $disk->path($relative);

        if (!$this->force && is_file($absolute) && filesize($absolute) > 0) {
            if ($chord->file_path !== $relative) {
                DB::table('chords')->where('id', $chord->id)->update(['file_path' => $relative]);
            }
            return;
        }

        $jobTimeout = max(10, (int) config('tts.job_timeout', 90));
        if (function_exists('set_time_limit')) {
            @set_time_limit($jobTimeout);
        }

        $rawText = trim($chord->pronunciation) !== '' ? $chord->pronunciation : $chord->name;
        $text = self::formatTextForTts($rawText);

        $command = array_merge(config('tts.command'), [
            '--voice', config('tts.voice'),
            '--rate=' . config('tts.rate'),
            '--text', $text,
            '--write-media', $absolute,
        ]);

        $processTimeout = max(5, (int) config('tts.timeout', 60));
        $maxAttempts = config('queue.default') === 'sync'
            ? max(1, (int) config('tts.tries', 3))
            : 1;

        $lastErrorOutput = '';
        $succeeded = false;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $process = new Process($command);
            $process->setEnv($this->processEnv());
            $process->setTimeout($processTimeout);
            $process->run();

            if ($process->isSuccessful() && is_file($absolute) && filesize($absolute) > 0) {
                $succeeded = true;
                break;
            }

            $lastErrorOutput = trim($process->getErrorOutput() ?: $process->getOutput());
        }

        if (!$succeeded) {
            throw new \RuntimeException(
                "edge-tts gagal untuk chord {$chord->name}: " . $lastErrorOutput
            );
        }

        DB::table('chords')->where('id', $chord->id)->update(['file_path' => $relative]);

        if ($oldFilePath && $oldFilePath !== $relative && $disk->exists($oldFilePath)) {
            $disk->delete($oldFilePath);
        }
    }

    /**
     * Menyesuaikan teks pelafalan untuk edge-tts menggunakan lapisan opsional
     * tts_respelling di config/pronunciation.php (nonaktif secara bawaan).
     *
     * Fungsi ini digunakan pada:
     * - GenerateChordAudio::handle
     * - Pengujian unit PronunciationGeneratorTest & MusicServicesTest
     */
    public static function formatTextForTts(string $rawText, ?PronunciationGenerator $generator = null): string
    {
        $resolvedGenerator = $generator ?? new PronunciationGenerator();

        return $resolvedGenerator->applyTtsRespelling($rawText);
    }

    /**
     * Environment variables untuk subprocess.
     * Di Windows, asyncio/SSL butuh SystemRoot agar tidak error.
     */
    private function processEnv(): array
    {
        $env = getenv();

        if (PHP_OS_FAMILY === 'Windows') {
            $env = array_merge($env, [
                'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
                'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\\Windows',
                'WINDIR'     => getenv('WINDIR') ?: 'C:\\Windows',
            ]);
        }

        return $env;
    }
}
