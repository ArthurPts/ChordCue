<?php

namespace App\Services;

/**
 * Layanan algoritma pembentuk teks pengucapan fonetik chord (PronunciationGenerator).
 * Mengonversi notasi simbolik chord menjadi kata-kata bahasa Inggris ramah TTS
 * sesuai aturan spesifikasi sistem §7.6 dan config/pronunciation.php.
 *
 * Seluruh nada akar & bass (A s/d G) disimpan menggunakan huruf alfabetnya agar
 * mudah dibaca pada UI, sedangkan penyesuaian pelafalan 'A' untuk mesin TTS
 * ditangani di job GenerateChordAudio.
 *
 * Fungsi ini digunakan pada:
 * - SongTimelineController::show (GET /songs/{song}/timeline?transpose=N)
 * - Pembuatan chord otomatis saat transpose
 */
class PronunciationGenerator
{
    /**
     * @param array<string, mixed>|null $config Aturan kustom (opsional, untuk testing)
     */
    public function __construct(private ?array $config = null)
    {
    }

    /**
     * Menghasilkan teks pengucapan fonetik dari nama chord.
     *
     * Contoh:
     * - generate('A')        => 'A'
     * - generate('Am')       => 'A minor'
     * - generate('A/C#')     => 'A over C sharp'
     * - generate('G/A')      => 'G over A'
     * - generate('F#m7b5')   => 'F sharp minor 7 flat 5'
     * - generate('Cmaj7#11') => 'C majj 7 sharp 11'
     * - generate('C7#9b13')  => 'C 7 sharp 9 flat 13'
     *
     * @param string $chordName Nama chord (mis. 'A', 'F#m7', 'G/B')
     * @return string Kalimat pengucapan fonetik
     */
    public function generate(string $chordName): string
    {
        $trimmed = trim($chordName);
        if ($trimmed === '') {
            return '';
        }

        $notesMap = $this->getNotesMap();
        $symbolsMap = $this->getSymbolsMap();

        // 1. Cek pemisahan slash bass chord (contoh: 'G/B', 'F#m7/C#', 'G/A')
        // Catatan: '6/9' bukan slash bass chord karena angka setelah '/', bukan nada [A-G]
        $mainChord = $trimmed;
        $bassPart = null;

        if (preg_match('/^(.*?)\/([A-G][#b♯♭]?)$/u', $trimmed, $slashMatches)) {
            $mainChord = $slashMatches[1];
            $bassPart = $slashMatches[2];
        }

        $tokens = [];

        // 2. Parse nada akar (root note) dan aksidental pada main chord
        if (preg_match('/^([A-G])([#b♯♭]?)(.*)$/u', $mainChord, $rootMatches)) {
            $rootLetter = $rootMatches[1];
            $rootAccidental = $rootMatches[2];
            $suffix = $rootMatches[3];

            // Pelafalan nada akar sesuai huruf alfabetnya
            $tokens[] = $notesMap[$rootLetter] ?? $rootLetter;

            // Aksidental nada akar
            if ($rootAccidental !== '') {
                $tokens[] = $symbolsMap[$rootAccidental] ?? $rootAccidental;
            }

            // 3. Parse bagian suffix (kualitas, ekstensi, alterasi)
            if (trim($suffix) !== '') {
                $suffixTokens = $this->tokenizeSuffix($suffix, $symbolsMap);
                foreach ($suffixTokens as $token) {
                    $tokens[] = $token;
                }
            }
        } else {
            // Jika format tidak diawali A-G standar, tokenisasi langsung menggunakan kamus simbol
            $tokens = $this->tokenizeSuffix($mainChord, $symbolsMap);
        }

        // 4. Parse bagian bass note jika ada
        if ($bassPart !== null) {
            $tokens[] = $symbolsMap['/'] ?? 'over';

            if (preg_match('/^([A-G])([#b♯♭]?)$/u', $bassPart, $bassMatches)) {
                $bassLetter = $bassMatches[1];
                $bassAccidental = $bassMatches[2];

                $tokens[] = $notesMap[$bassLetter] ?? $bassLetter;

                if ($bassAccidental !== '') {
                    $tokens[] = $symbolsMap[$bassAccidental] ?? $bassAccidental;
                }
            } else {
                $tokens[] = $bassPart;
            }
        }

        return trim(preg_replace('/\s+/', ' ', implode(' ', $tokens)) ?? '');
    }

    /**
     * Memecah suffix chord menjadi token pengucapan secara greedy matching.
     *
     * @param string $suffix
     * @param array<string, string> $symbolsMap
     * @return array<int, string>
     */
    private function tokenizeSuffix(string $suffix, array $symbolsMap): array
    {
        // Bersihkan karakter pengelompokan seperti kurung buka/tutup
        $cleanSuffix = str_replace(['(', ')', '[', ']'], ' ', $suffix);
        $cleanSuffix = trim($cleanSuffix);

        $tokens = [];
        $cursor = 0;
        $length = strlen($cleanSuffix);

        while ($cursor < $length) {
            $char = $cleanSuffix[$cursor];

            // Lewati spasi
            if ($char === ' ') {
                $cursor++;
                continue;
            }

            // 1. Coba greedy match terpanjang dari kamus simbol
            $matched = false;
            foreach ($symbolsMap as $symbol => $spokenWord) {
                $symbolLength = strlen($symbol);
                if (substr($cleanSuffix, $cursor, $symbolLength) === $symbol) {
                    $tokens[] = $spokenWord;
                    $cursor += $symbolLength;
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                continue;
            }

            // 2. Jika berupa angka (ekstensi seperti 7, 9, 11, 13, 2, 4, 5, 6)
            if (ctype_digit($char)) {
                $numStr = '';
                while ($cursor < $length && ctype_digit($cleanSuffix[$cursor])) {
                    $numStr .= $cleanSuffix[$cursor];
                    $cursor++;
                }
                $tokens[] = $numStr;
                continue;
            }

            // 3. Karakter yang tidak ada dalam aturan khusus diambil sebagai token sendiri
            $tokens[] = $char;
            $cursor++;
        }

        return $tokens;
    }

    /**
     * Mengambil pemetaan pelafalan nada akar.
     *
     * @return array<string, string>
     */
    private function getNotesMap(): array
    {
        $rawConfig = $this->loadRawConfig();

        if (isset($rawConfig['notes']) && is_array($rawConfig['notes'])) {
            return $rawConfig['notes'];
        }

        return [
            'A' => 'A',
            'B' => 'B',
            'C' => 'C',
            'D' => 'D',
            'E' => 'E',
            'F' => 'F',
            'G' => 'G',
        ];
    }

    /**
     * Mengambil pemetaan simbol kamus diurutkan dari yang terpanjang lebih dulu.
     *
     * @return array<string, string>
     */
    private function getSymbolsMap(): array
    {
        $rawConfig = $this->loadRawConfig();

        $symbols = [];
        if (isset($rawConfig['symbols']) && is_array($rawConfig['symbols'])) {
            $symbols = $rawConfig['symbols'];
        } elseif (!isset($rawConfig['notes']) && is_array($rawConfig)) {
            // Kompatibilitas jika file konfigurasi berupa flat array
            $symbols = $rawConfig;
        }

        // Urutkan kunci aturan berdasarkan panjang string terpanjang lebih dulu (greedy match)
        uksort($symbols, function (string $a, string $b): int {
            return strlen($b) <=> strlen($a);
        });

        return $symbols;
    }

    /**
     * Memuat konfigurasi mentah dari dependency, config helper, atau file config/pronunciation.php.
     *
     * @return array<string, mixed>
     */
    private function loadRawConfig(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        if (function_exists('config')) {
            try {
                $appConfig = config('pronunciation');
                if (is_array($appConfig)) {
                    return $appConfig;
                }
            } catch (\Throwable) {
                // Abaikan jika kontainer config belum diboot
            }
        }

        $configFile = __DIR__ . '/../../config/pronunciation.php';
        if (file_exists($configFile)) {
            /** @var array<string, mixed> $fileRules */
            $fileRules = require $configFile;
            return $fileRules;
        }

        return [];
    }
}
