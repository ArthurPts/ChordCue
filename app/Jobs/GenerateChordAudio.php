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

    public static function fileName(string $chordName): string
    {
        // "F#m" -> "fsharpm", "Bb" -> "bb" (lowercase supaya aman di Windows/macOS)
        return Str::of($chordName)->replace('#', 'sharp')->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')->toString();
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

        $text = trim($chord->pronunciation) !== '' ? $chord->pronunciation : $chord->name;

        $command = array_merge(config('tts.command'), [
            '--voice', config('tts.voice'),
            '--rate=' . config('tts.rate'),
            '--text', $text,
            '--write-media', $absolute,
        ]);

        $process = new Process($command);
        $process->setTimeout(config('tts.timeout'));
        $process->run();

        if (!$process->isSuccessful() || !is_file($absolute) || filesize($absolute) === 0) {
            throw new \RuntimeException(
                "edge-tts gagal untuk chord {$chord->name}: " . trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        DB::table('chords')->where('id', $chord->id)->update(['file_path' => $relative]);
    }
}
