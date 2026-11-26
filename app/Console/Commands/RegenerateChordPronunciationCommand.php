<?php

namespace App\Console\Commands;

use App\Services\PronunciationGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Perintah Artisan untuk menghitung ulang kolom `chords.pronunciation`
 * menggunakan PronunciationGenerator (mode pratinjau --dry-run secara bawaan).
 */
class RegenerateChordPronunciationCommand extends Command
{
    protected $signature = 'chords:regenerate-pronunciation
                            {--dry-run : Tampilkan pratinjau perubahan tanpa menulis ke database (bawaan)}
                            {--apply   : Terapkan pembaruan pronunciation ke tabel chords}';

    protected $description = 'Hitung ulang teks pelafalan (pronunciation) seluruh chord dari config/pronunciation.php';

    /**
     * Menjalankan pratinjau atau pembaruan kolom `chords.pronunciation` saja
     * tanpa mengubah `chords.name` maupun `chords.file_path`.
     *
     * Fungsi ini digunakan pada:
     * - Perintah artisan: php artisan chords:regenerate-pronunciation [--dry-run|--apply]
     */
    public function handle(PronunciationGenerator $pronunciationGenerator): int
    {
        $isApply = (bool) $this->option('apply') && ! (bool) $this->option('dry-run');

        $chords = DB::table('chords')
            ->orderBy('id')
            ->get(['id', 'name', 'pronunciation', 'file_path']);

        if ($chords->isEmpty()) {
            $this->info('Tabel chords kosong.');
            return self::SUCCESS;
        }

        $tableRows = [];
        $changedCount = 0;
        $flaggedRows = [];

        foreach ($chords as $chord) {
            $oldPronunciation = (string) ($chord->pronunciation ?? '');
            $newPronunciation = $pronunciationGenerator->generate((string) $chord->name);
            $hasAudio = $chord->file_path !== null && trim((string) $chord->file_path) !== '';

            $isChanged = $oldPronunciation !== $newPronunciation;
            if ($isChanged) {
                $changedCount++;
            }

            $flagReason = $this->detectSuspiciousOutput($newPronunciation);
            if ($flagReason !== null) {
                $flaggedRows[] = "{$chord->name} => '{$newPronunciation}' ({$flagReason})";
            }

            if ($isApply && $isChanged) {
                DB::table('chords')
                    ->where('id', $chord->id)
                    ->update(['pronunciation' => $newPronunciation]);
            }

            $tableRows[] = [
                $chord->id,
                $chord->name,
                $oldPronunciation,
                $newPronunciation,
                $hasAudio ? 'yes' : 'no',
                $flagReason ?? ($isChanged ? ($isApply ? 'updated' : 'will update') : 'unchanged'),
            ];
        }

        $this->table(
            ['ID', 'Name', 'Old Pronunciation', 'New Pronunciation', 'file_path', 'Status'],
            $tableRows
        );

        $modeLabel = $isApply ? 'APPLY' : 'DRY-RUN';
        $this->info("[{$modeLabel}] Total: {$chords->count()} chord | Berubah: {$changedCount} | Mencurigakan: " . count($flaggedRows));

        if (count($flaggedRows) > 0) {
            foreach ($flaggedRows as $warning) {
                $this->warn("PERINGATAN: {$warning}");
            }
        }

        if (! $isApply) {
            $this->line('Tidak ada data yang diubah. Gunakan --apply untuk menerapkan perubahan.');
        }

        return self::SUCCESS;
    }

    /**
     * Memeriksa apakah hasil pelafalan kosong atau masih menyisakan simbol notasi mentah.
     *
     * Fungsi ini digunakan pada:
     * - RegenerateChordPronunciationCommand::handle
     */
    private function detectSuspiciousOutput(string $pronunciation): ?string
    {
        if (trim($pronunciation) === '') {
            return 'hasil kosong';
        }

        if (preg_match('/[#♯♭\/°øΔ+]/u', $pronunciation) === 1) {
            return 'masih mengandung simbol mentah';
        }

        return null;
    }
}
