<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Menjalankan web server + queue worker (proses TTS) sekaligus.
 *   php artisan serve:all
 *   php artisan serve:all --host=0.0.0.0 --port=8080
 *   php artisan serve:all --no-worker
 * Hentikan dengan Ctrl+C (keduanya ikut berhenti).
 */
class ServeAllCommand extends Command
{
    protected $signature = 'serve:all
                            {--host=127.0.0.1 : Host web server}
                            {--port=8000 : Port web server}
                            {--no-worker : Jalankan web server saja}';

    protected $description = 'Jalankan php artisan serve beserta queue worker (TTS) sekaligus';

    /** @var array<string, Process> */
    private array $processes = [];

    public function handle(): int
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $childEnv = $this->buildCleanChildEnv();

        $this->processes['server'] = new Process(
            [$php, $artisan, 'serve', '--host=' . $this->option('host'), '--port=' . $this->option('port')],
            base_path(),
            $childEnv
        );

        if (!$this->option('no-worker')) {
            // queue:listen memuat ulang kode & .env tiap job, nyaman untuk development
            $workerTries = max(1, (int) config('tts.tries', 3));
            $workerTimeout = max(10, (int) config('tts.job_timeout', 90));
            $this->processes['worker'] = new Process(
                [$php, $artisan, 'queue:listen', '--tries=' . $workerTries, '--timeout=' . $workerTimeout, '--sleep=1'],
                base_path(),
                $childEnv
            );
        }

        register_shutdown_function(fn () => $this->stopAll());

        foreach ($this->processes as $name => $process) {
            $process->setTimeout(null);
            $process->start(function (string $type, string $buffer) use ($name) {
                foreach (preg_split('/\R/', rtrim($buffer)) as $line) {
                    if ($line !== '') {
                        $this->line("[$name] $line");
                    }
                }
            });
        }

        $this->info('Server + worker berjalan. Tekan Ctrl+C untuk berhenti.');

        // Berhenti kalau salah satu proses mati
        while (collect($this->processes)->every(fn (Process $p) => $p->isRunning())) {
            usleep(200000);
        }

        $exit = 0;
        foreach ($this->processes as $name => $process) {
            if (!$process->isRunning() && $process->getExitCode() !== 0) {
                $this->error("Proses [$name] berhenti dengan kode {$process->getExitCode()}.");
                $exit = 1;
            }
        }
        $this->stopAll();

        return $exit;
    }

    /**
     * Menghapus variabel yang berasal dari file .env pada environment proses anak
     * agar artisan serve & queue:listen selalu membaca ulang .env terbaru,
     * bukan mewarisi nilai .env lama saat serve:all pertama kali dijalankan.
     *
     * Fungsi ini digunakan pada:
     * - ServeAllCommand::handle
     *
     * @return array<string, false>
     */
    private function buildCleanChildEnv(): array
    {
        $envFile = base_path('.env');
        if (!is_file($envFile)) {
            return [];
        }

        $unsetMap = [];
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return [];
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (preg_match('/^(?:export\s+)?([A-Z0-9_]+)\s*=/i', $trimmed, $matches) === 1) {
                $unsetMap[$matches[1]] = false;
            }
        }

        return $unsetMap;
    }

    private function stopAll(): void
    {
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $process->stop(3);
            }
        }
    }
}
