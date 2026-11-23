<?php

namespace App\Jobs;

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
        // "F#m" -> "fsharpm", "Bb" -> "bb", "G/B" -> "goverb" (cegah tabrakan dengan "Gb")
        return Str::of($chordName)
            ->replace('#', 'sharp')
            ->replace('/', 'over')
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

        $relative = trim(config('tts.directory'), '/') . '/' . self::fileName($chord->name) . '.mp3';
        $disk->makeDirectory(dirname($relative));
        $absolute = $disk->path($relative);

        $rawText = trim($chord->pronunciation) !== '' ? $chord->pronunciation : $chord->name;
        $text = self::formatTextForTts($rawText);

        $command = array_merge(config('tts.command'), [
            '--voice', config('tts.voice'),
            '--rate=' . config('tts.rate'),
            '--text', $text,
            '--write-media', $absolute,
        ]);

        $process = new Process($command);
        $process->setEnv($this->processEnv());
        $process->setTimeout(config('tts.timeout'));
        $process->run();

        if (!$process->isSuccessful() || !is_file($absolute) || filesize($absolute) === 0) {
            throw new \RuntimeException(
                "edge-tts gagal untuk chord {$chord->name}: " . trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        DB::table('chords')->where('id', $chord->id)->update(['file_path' => $relative]);
    }

    /**
     * Menyesuaikan teks pelafalan untuk edge-tts agar huruf nada 'A' tetap
     * dilafalkan sebagai /eɪ/ (bukan artikel /ə/), kata 'major'/'maj' menjadi
     * 'majj' (/mædʒ/), dan kata 'sus' menjadi 'suss'.
     *
     * Fungsi ini digunakan pada:
     * - GenerateChordAudio::handle
     * - Pengujian unit MusicServicesTest
     */
    public static function formatTextForTts(string $rawText): string
    {
        $trimmedText = trim($rawText);
        if ($trimmedText === '') {
            return '';
        }

        // Kembalikan teks lama yang memakai 'Ae' sebagai token nada ke 'A'
        $normalizedText = preg_replace('/\bAe\b/', 'A', $trimmedText) ?? $trimmedText;

        // Ubah token 'major' atau 'maj' menjadi 'majj' agar edge-tts melafalkan /mædʒ/
        $normalizedText = preg_replace('/\bmaj(?:or)?\b/i', 'majj', $normalizedText) ?? $normalizedText;

        // Ubah token 'sus' menjadi 'suss' agar edge-tts tidak mengejanya 'S U S'
        $normalizedText = preg_replace('/\bsus\b/i', 'suss', $normalizedText) ?? $normalizedText;

        // Sisipkan koma setelah huruf nada tunggal 'A' bila diikuti kata lain
        // (mis. "A minor" -> "A, minor", "G over A flat" -> "G over A, flat")
        // agar mesin TTS en-US tidak menganggapnya artikel "a" (/ə/).
        return preg_replace('/\bA(?=\s+\S)/', 'A,', $normalizedText) ?? $normalizedText;
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
