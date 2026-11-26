<?php

namespace App\Services;

/**
 * Layanan algoritma pembentuk teks pengucapan chord (PronunciationGenerator).
 * Mengonversi notasi simbolik chord menjadi teks pelafalan Bahasa Indonesia
 * berdasarkan seluruh aturan di config/pronunciation.php (tanpa kata hard-coded).
 *
 * Fungsi ini digunakan pada:
 * - SongTimelineController::show (GET /songs/{song}/timeline?transpose=N)
 * - GenerateChordAudio::formatTextForTts (lapisan opsional tts_respelling)
 * - Pembuatan chord otomatis saat transpose
 */
class PronunciationGenerator
{
    /**
     * @param array<string, mixed>|null $config Override konfigurasi kustom (opsional, untuk testing)
     */
    public function __construct(private ?array $config = null)
    {
    }

    /**
     * Menghasilkan teks pengucapan chord sesuai aturan config/pronunciation.php.
     *
     * Contoh (konfigurasi bawaan):
     * - generate('C#')       => 'Cis'
     * - generate('Bb')       => 'Bes'
     * - generate('A#')       => 'A is'
     * - generate('Ab')       => 'A es'
     * - generate('E#')       => 'E is'
     * - generate('Eb')       => 'E es'
     * - generate('F#m7')     => 'Fis minor tujuh'
     * - generate('Cmaj7')    => 'C mayor tujuh'
     * - generate('F#m7b5')   => 'Fis minor tujuh mol lima'
     * - generate('Cmaj7#11') => 'C mayor tujuh kres sebelas'
     * - generate('C#/D')     => 'Cis on D'
     * - generate('G/Bb')     => 'G on Bes'
     *
     * Fungsi ini digunakan pada:
     * - SongTimelineController::show (GET /songs/{song}/timeline?transpose=N)
     * - Pengujian unit PronunciationGeneratorTest & MusicServicesTest
     *
     * @param string $chordName Nama chord (mis. 'A', 'F#m7', 'G/Bb')
     * @return string Kalimat pengucapan chord
     */
    public function generate(string $chordName): string
    {
        $trimmed = trim($chordName);
        if ($trimmed === '') {
            return '';
        }

        $resolvedConfig = $this->loadConfig();

        // 1. Cek pemisahan slash bass chord (contoh: 'C#/D', 'G/Bb', 'F#m7/C#')
        // Catatan: '6/9' bukan slash bass chord karena setelah '/' berupa angka, bukan nada [A-G]
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

            $tokens[] = $this->buildNoteToken($rootLetter, $rootAccidental, $resolvedConfig);

            // 3. Parse bagian suffix (kualitas, ekstensi, alterasi, angka)
            if (trim($suffix) !== '') {
                $suffixTokens = $this->tokenizeSuffix($suffix, $resolvedConfig);
                foreach ($suffixTokens as $token) {
                    $tokens[] = $token;
                }
            }
        } else {
            // Jika format tidak diawali nada A-G standar, tokenisasi langsung sebagai suffix
            $tokens = $this->tokenizeSuffix($mainChord, $resolvedConfig);
        }

        // 4. Parse bagian bass note jika ada slash chord
        if ($bassPart !== null) {
            $slashWord = isset($resolvedConfig['slash']) && is_string($resolvedConfig['slash'])
                ? trim($resolvedConfig['slash'])
                : '';

            if ($slashWord !== '') {
                $tokens[] = $slashWord;
            }

            if (preg_match('/^([A-G])([#b♯♭]?)$/u', $bassPart, $bassMatches)) {
                $bassLetter = $bassMatches[1];
                $bassAccidental = $bassMatches[2];

                $tokens[] = $this->buildNoteToken($bassLetter, $bassAccidental, $resolvedConfig);
            } else {
                $tokens[] = $bassPart;
            }
        }

        return trim(preg_replace('/\s+/u', ' ', implode(' ', $tokens)) ?? '');
    }

    /**
     * Menerapkan lapisan respelling fonetik opsional khusus untuk audio TTS.
     * Tidak dipanggil oleh generate() sehingga kolom chords.pronunciation tetap bersih.
     *
     * Fungsi ini digunakan pada:
     * - GenerateChordAudio::formatTextForTts
     * - Pengujian unit PronunciationGeneratorTest
     *
     * @param string $pronunciationText Teks pengucapan yang tersimpan di database
     * @return string Teks yang siap dikirim ke edge-tts
     */
    public function applyTtsRespelling(string $pronunciationText): string
    {
        $trimmedText = trim($pronunciationText);
        if ($trimmedText === '') {
            return '';
        }

        $resolvedConfig = $this->loadConfig();
        $isEnabled = (bool) ($resolvedConfig['tts_respelling_enabled'] ?? false);
        $respellingMap = $resolvedConfig['tts_respelling'] ?? [];

        if (!$isEnabled || !is_array($respellingMap) || count($respellingMap) === 0) {
            return $trimmedText;
        }

        // Urutkan kata kunci dari yang terpanjang lebih dulu agar tidak saling menimpa
        uksort($respellingMap, function (string $a, string $b): int {
            return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
        });

        $resultText = $trimmedText;
        foreach ($respellingMap as $sourceWord => $replacementWord) {
            if (!is_string($sourceWord) || !is_string($replacementWord) || $sourceWord === '') {
                continue;
            }

            $pattern = '/(?<!\p{L})' . preg_quote($sourceWord, '/') . '(?!\p{L})/iu';
            $replaced = preg_replace($pattern, $replacementWord, $resultText);
            if ($replaced !== null) {
                $resultText = $replaced;
            }
        }

        return trim(preg_replace('/\s+/u', ' ', $resultText) ?? '');
    }

    /**
     * Membentuk satu token nama nada (root atau bass) beserta aksidentalnya.
     * Contoh: ('C', '#') -> 'Cis', ('E', 'b') -> 'Es' (atau 'Ees' jika irregular flats nonaktif).
     *
     * Fungsi ini digunakan pada:
     * - PronunciationGenerator::generate
     *
     * @param string $noteLetter Huruf nada alami (A-G)
     * @param string $accidental Simbol aksidental ('', '#', '♯', 'b', '♭')
     * @param array<string, mixed> $resolvedConfig Konfigurasi terpadu
     * @return string Token nama nada tunggal
     */
    private function buildNoteToken(string $noteLetter, string $accidental, array $resolvedConfig): string
    {
        $notesMap = isset($resolvedConfig['notes']) && is_array($resolvedConfig['notes'])
            ? $resolvedConfig['notes']
            : [];
        $accidentalsMap = isset($resolvedConfig['accidentals']) && is_array($resolvedConfig['accidentals'])
            ? $resolvedConfig['accidentals']
            : [];
        $spacedNotes = isset($resolvedConfig['spaced_accidental_notes']) && is_array($resolvedConfig['spaced_accidental_notes'])
            ? $resolvedConfig['spaced_accidental_notes']
            : [];
        $useIrregularFlats = (bool) ($resolvedConfig['use_irregular_flats'] ?? false);
        $irregularFlats = isset($resolvedConfig['irregular_flats']) && is_array($resolvedConfig['irregular_flats'])
            ? $resolvedConfig['irregular_flats']
            : [];

        $spokenNote = $notesMap[$noteLetter] ?? $noteLetter;

        if ($accidental === '') {
            return $spokenNote;
        }

        $isFlatAccidental = ($accidental === 'b' || $accidental === '♭');
        if ($isFlatAccidental && $useIrregularFlats && isset($irregularFlats[$noteLetter])) {
            return (string) $irregularFlats[$noteLetter];
        }

        $accidentalSuffix = $accidentalsMap[$accidental] ?? $accidental;

        if (in_array($noteLetter, $spacedNotes, true)) {
            return $spokenNote . ' ' . $accidentalSuffix;
        }

        return $spokenNote . $accidentalSuffix;
    }

    /**
     * Memecah suffix chord menjadi token pengucapan menggunakan greedy longest-match,
     * aturan aksidental suffix, aturan minus setelah angka, dan konversi angka.
     *
     * Fungsi ini digunakan pada:
     * - PronunciationGenerator::generate
     *
     * @param string $suffix Bagian setelah nada akar dan aksidental akar
     * @param array<string, mixed> $resolvedConfig Konfigurasi terpadu
     * @return array<int, string>
     */
    private function tokenizeSuffix(string $suffix, array $resolvedConfig): array
    {
        // Bersihkan tanda kurung pengelompokan menjadi spasi
        $cleanSuffix = trim(str_replace(['(', ')', '[', ']'], ' ', $suffix));

        $splitNumbersMap = $this->getSortedSplitNumbersMap($resolvedConfig);
        $multiCharSymbols = $this->getSortedSymbolsByLength($resolvedConfig, true);
        $singleCharSymbols = $this->getSortedSymbolsByLength($resolvedConfig, false);
        $suffixAccidentals = $this->getActiveSuffixAccidentals($resolvedConfig);

        $tokens = [];
        $cursor = 0;
        $length = mb_strlen($cleanSuffix, 'UTF-8');
        $previousTokenWasDigit = false;

        while ($cursor < $length) {
            $char = mb_substr($cleanSuffix, $cursor, 1, 'UTF-8');

            // Lewati spasi tanpa mengubah status previousTokenWasDigit (untuk kasus seperti C7(-5))
            if ($char === ' ') {
                $cursor++;
                continue;
            }

            // 1. Cek angka majemuk khusus seperti '6/9' dan '69'
            $matchedSplitNumber = false;
            foreach ($splitNumbersMap as $pattern => $numberParts) {
                $patternStr = (string) $pattern;
                $patternLength = mb_strlen($patternStr, 'UTF-8');
                if (mb_substr($cleanSuffix, $cursor, $patternLength, 'UTF-8') === $patternStr) {
                    foreach ($numberParts as $numberPart) {
                        $numberTokens = $this->formatNumberTokens((string) $numberPart, $resolvedConfig);
                        foreach ($numberTokens as $numberToken) {
                            $tokens[] = $numberToken;
                        }
                    }
                    $cursor += $patternLength;
                    $previousTokenWasDigit = true;
                    $matchedSplitNumber = true;
                    break;
                }
            }

            if ($matchedSplitNumber) {
                continue;
            }

            // 2. Greedy longest-match untuk simbol multi-karakter (mis. half-dim, major, minor, omit, maj, min, sus)
            $matchedMultiSymbol = false;
            foreach ($multiCharSymbols as $symbol => $spokenWord) {
                $symbolLength = mb_strlen($symbol, 'UTF-8');
                if (mb_substr($cleanSuffix, $cursor, $symbolLength, 'UTF-8') === $symbol) {
                    $tokens[] = $spokenWord;
                    $cursor += $symbolLength;
                    $previousTokenWasDigit = false;
                    $matchedMultiSymbol = true;
                    break;
                }
            }

            if ($matchedMultiSymbol) {
                continue;
            }

            // 3. Aturan khusus tanda minus '-': langsung setelah angka berarti flat (mol), selain itu minor
            if ($char === '-' && $previousTokenWasDigit) {
                $flatWord = $suffixAccidentals['b'] ?? $suffixAccidentals['♭'] ?? '-';
                $tokens[] = $flatWord;
                $cursor++;
                $previousTokenWasDigit = false;
                continue;
            }

            // 4. Aksidental di dalam suffix ('#', '♯', 'b', '♭') memakai peta suffix_accidentals
            if (isset($suffixAccidentals[$char])) {
                $tokens[] = $suffixAccidentals[$char];
                $cursor++;
                $previousTokenWasDigit = false;
                continue;
            }

            // 5. Simbol satu karakter (mis. 'M', 'Δ', 'm', '-', 'o', '°', 'ø', '+')
            if (isset($singleCharSymbols[$char])) {
                $tokens[] = $singleCharSymbols[$char];
                $cursor++;
                $previousTokenWasDigit = false;
                continue;
            }

            // 6. Rangkaian digit angka (ekstensi seperti 2, 4, 5, 6, 7, 9, 11, 13)
            if (ctype_digit($char)) {
                $digitRun = '';
                while ($cursor < $length) {
                    $nextChar = mb_substr($cleanSuffix, $cursor, 1, 'UTF-8');
                    if (!ctype_digit($nextChar)) {
                        break;
                    }
                    $digitRun .= $nextChar;
                    $cursor++;
                }

                $numberTokens = $this->formatNumberTokens($digitRun, $resolvedConfig);
                foreach ($numberTokens as $numberToken) {
                    $tokens[] = $numberToken;
                }
                $previousTokenWasDigit = true;
                continue;
            }

            // 7. Karakter tidak dikenal dipertahankan sebagai token sendiri
            $tokens[] = $char;
            $cursor++;
            $previousTokenWasDigit = false;
        }

        return $tokens;
    }

    /**
     * Mengonversi rangkaian digit menjadi kata bilangan Indonesia atau digit mentah
     * sesuai flag numbers_as_words dan tabel numbers di konfigurasi.
     *
     * Fungsi ini digunakan pada:
     * - PronunciationGenerator::tokenizeSuffix
     *
     * @param string $digitRun Rangkaian karakter angka (mis. '7', '11', '13', '25')
     * @param array<string, mixed> $resolvedConfig Konfigurasi terpadu
     * @return array<int, string>
     */
    private function formatNumberTokens(string $digitRun, array $resolvedConfig): array
    {
        $numbersAsWords = (bool) ($resolvedConfig['numbers_as_words'] ?? true);
        if (!$numbersAsWords) {
            return [$digitRun];
        }

        $numbersMap = isset($resolvedConfig['numbers']) && is_array($resolvedConfig['numbers'])
            ? $resolvedConfig['numbers']
            : [];

        // Prioritaskan kecocokan angka penuh (mis. '11' -> 'sebelas', '13' -> 'tiga belas')
        if (isset($numbersMap[$digitRun])) {
            return [(string) $numbersMap[$digitRun]];
        }

        // Fallback per digit untuk angka multi-digit yang belum ada di tabel
        $digitTokens = [];
        $digitLength = strlen($digitRun);
        for ($index = 0; $index < $digitLength; $index++) {
            $singleDigit = $digitRun[$index];
            $digitTokens[] = isset($numbersMap[$singleDigit])
                ? (string) $numbersMap[$singleDigit]
                : $singleDigit;
        }

        return $digitTokens;
    }

    /**
     * Mengambil pemetaan split_numbers terurut dari kunci terpanjang lebih dulu.
     *
     * Fungsi ini digunakan pada:
     * - PronunciationGenerator::tokenizeSuffix
     *
     * @param array<string, mixed> $resolvedConfig
     * @return array<int|string, array<int, string>>
     */
    private function getSortedSplitNumbersMap(array $resolvedConfig): array
    {
        $rawMap = isset($resolvedConfig['split_numbers']) && is_array($resolvedConfig['split_numbers'])
            ? $resolvedConfig['split_numbers']
            : [];

        $filteredMap = [];
        foreach ($rawMap as $key => $parts) {
            $keyStr = (string) $key;
            if ($keyStr !== '' && is_array($parts)) {
                $filteredMap[$keyStr] = $parts;
            }
        }

        uksort($filteredMap, function (int|string $a, int|string $b): int {
            return mb_strlen((string) $b, 'UTF-8') <=> mb_strlen((string) $a, 'UTF-8');
        });

        return $filteredMap;
    }

    /**
     * Mengambil pemetaan simbol (multi-karakter atau satu-karakter) terurut dari terpanjang.
     *
     * Fungsi ini digunakan pada:
     * - PronunciationGenerator::tokenizeSuffix
     *
     * @param array<string, mixed> $resolvedConfig
     * @param bool $multiCharOnly True untuk panjang >= 2, false untuk panjang === 1
     * @return array<string, string>
     */
    private function getSortedSymbolsByLength(array $resolvedConfig, bool $multiCharOnly): array
    {
        $symbols = isset($resolvedConfig['symbols']) && is_array($resolvedConfig['symbols'])
            ? $resolvedConfig['symbols']
            : [];

        $filtered = [];
        foreach ($symbols as $symbol => $spokenWord) {
            $symbolKey = (string) $symbol;
            $symbolLen = mb_strlen($symbolKey, 'UTF-8');

            if ($multiCharOnly && $symbolLen >= 2) {
                $filtered[$symbolKey] = (string) $spokenWord;
            } elseif (!$multiCharOnly && $symbolLen === 1) {
                $filtered[$symbolKey] = (string) $spokenWord;
            }
        }

        uksort($filtered, function (string $a, string $b): int {
            return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
        });

        return $filtered;
    }

    /**
     * Mengambil peta aksidental suffix sesuai nilai suffix_accidental_style yang aktif.
     *
     * Fungsi ini digunakan pada:
     * - PronunciationGenerator::tokenizeSuffix
     *
     * @param array<string, mixed> $resolvedConfig
     * @return array<string, string>
     */
    private function getActiveSuffixAccidentals(array $resolvedConfig): array
    {
        $stylesMap = isset($resolvedConfig['suffix_accidentals']) && is_array($resolvedConfig['suffix_accidentals'])
            ? $resolvedConfig['suffix_accidentals']
            : [];
        $selectedStyle = isset($resolvedConfig['suffix_accidental_style']) && is_string($resolvedConfig['suffix_accidental_style'])
            ? $resolvedConfig['suffix_accidental_style']
            : 'indonesian';

        if (isset($stylesMap[$selectedStyle]) && is_array($stylesMap[$selectedStyle])) {
            return $stylesMap[$selectedStyle];
        }

        return [];
    }

    /**
     * Memuat konfigurasi dasar dari config helper atau file config/pronunciation.php,
     * lalu menimpanya dengan override konstruktor jika diberikan (untuk testing).
     *
     * Fungsi ini digunakan pada:
     * - PronunciationGenerator::generate
     * - PronunciationGenerator::applyTtsRespelling
     *
     * @return array<string, mixed>
     */
    private function loadConfig(): array
    {
        $baseConfig = [];

        if (function_exists('config')) {
            try {
                $appConfig = config('pronunciation');
                if (is_array($appConfig)) {
                    $baseConfig = $appConfig;
                }
            } catch (\Throwable) {
                // Abaikan jika kontainer config Laravel belum diboot
            }
        }

        if ($baseConfig === []) {
            $configFile = __DIR__ . '/../../config/pronunciation.php';
            if (file_exists($configFile)) {
                /** @var array<string, mixed> $fileRules */
                $fileRules = require $configFile;
                $baseConfig = $fileRules;
            }
        }

        if ($this->config !== null) {
            return array_merge($baseConfig, $this->config);
        }

        return $baseConfig;
    }
}
