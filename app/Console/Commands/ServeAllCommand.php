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

        $this->processes['server'] = new Process(
            [$php, $artisan, 'serve', '--host=' . $this->option('host'), '--port=' . $this->option('port')],
            base_path()
        );

        if (!$this->option('no-worker')) {
            // queue:listen memuat ulang kode tiap job, nyaman untuk development
            $this->processes['worker'] = new Process(
                [$php, $artisan, 'queue:listen', '--tries=3', '--timeout=120', '--sleep=1'],
                base_path()
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

    private function stopAll(): void
    {
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $process->stop(3);
            }
        }
    }
}
