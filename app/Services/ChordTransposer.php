<?php

namespace App\Services;

/**
 * Layanan algoritma musik untuk menggeser nada dasar chord (transpose)
 * dan mengekstrak nada akar chord.
 *
 * Mengacu pada spesifikasi sistem §7.5 dan §5.11.
 *
 * Fungsi ini digunakan pada:
 * - SongTimelineController::show (GET /songs/{song}/timeline?transpose=N)
 */
class ChordTransposer
{
    /**
     * Skala kromatik dengan notasi sharp (#).
     *
     * @var array<int, string>
     */
    private array $sharpScale = [
        0  => 'C',
        1  => 'C#',
        2  => 'D',
        3  => 'D#',
        4  => 'E',
        5  => 'F',
        6  => 'F#',
        7  => 'G',
        8  => 'G#',
        9  => 'A',
        10 => 'A#',
        11 => 'B',
    ];

    /**
     * Skala kromatik dengan notasi flat (b).
     *
     * @var array<int, string>
     */
    private array $flatScale = [
        0  => 'C',
        1  => 'Db',
        2  => 'D',
        3  => 'Eb',
        4  => 'E',
        5  => 'F',
        6  => 'Gb',
        7  => 'G',
        8  => 'Ab',
        9  => 'A',
        10 => 'Bb',
        11 => 'B',
    ];

    /**
     * Pemetaan nama nada ke indeks semitone (0-11).
     *
     * @var array<string, int>
     */
    private array $noteIndices = [
        'C'  => 0,  'B#' => 0,
        'C#' => 1,  'Db' => 1,
        'D'  => 2,
        'D#' => 3,  'Eb' => 3,
        'E'  => 4,  'Fb' => 4,
        'F'  => 5,  'E#' => 5,
        'F#' => 6,  'Gb' => 6,
        'G'  => 7,
        'G#' => 8,  'Ab' => 8,
        'A'  => 9,
        'A#' => 10, 'Bb' => 10,
        'B'  => 11, 'Cb' => 11,
    ];

    /**
     * Menggeser nada chord sejumlah semitone (-11 sampai +11).
     *
     * Contoh:
     * - transpose('C', 2)    => 'D'
     * - transpose('Am', 3)   => 'Cm'
     * - transpose('Bb', 2)   => 'C'
     * - transpose('F#m7', 1) => 'Gm7'
     * - transpose('G/B', -2) => 'F/A'
     *
     * @param string $chordName Nama chord (mis. 'Am', 'F#m7/C#')
     * @param int $semitones Jumlah langkah semitone (-11 sampai +11)
     * @return string Nama chord hasil transpose
     */
    public function transpose(string $chordName, int $semitones): string
    {
        $trimmedName = trim($chordName);
        if ($trimmedName === '' || $semitones === 0) {
            return $trimmedName;
        }

        $parsed = $this->parseChord($trimmedName);
        if ($parsed === null) {
            // Jika nama tidak dapat diurai, kembalikan apa adanya (§7.5 no. 5)
            return $trimmedName;
        }

        $transposedRoot = $this->shiftNote($parsed['root'], $semitones);

        $result = $transposedRoot . $parsed['suffix'];

        if ($parsed['bass'] !== null) {
            $transposedBass = $this->shiftNote($parsed['bass'], $semitones);
            $result .= '/' . $transposedBass;
        }

        return $result;
    }

    /**
     * Mengekstrak nada akar (root note) dari nama chord.
     * Nada bass pada slash chord diabaikan (§5.11).
     *
     * Contoh:
     * - extractRoot('F#m7') => 'F#'
     * - extractRoot('G/B')  => 'G'
     * - extractRoot('Bb')   => 'Bb'
     *
     * Fungsi ini digunakan pada:
     * - Pengujian unit MusicServicesTest (disiapkan untuk fitur filter nada dasar)
     *
     * @param string $chordName
     * @return string|null Nada akar (A-G dengan aksidental opsional) atau null jika tidak dapat diurai
     */
    public function extractRoot(string $chordName): ?string
    {
        $parsed = $this->parseChord(trim($chordName));
        if ($parsed === null) {
            return null;
        }

        return $parsed['root'];
    }

    /**
     * Mengurai string nama chord menjadi komponen struktur musik.
     *
     * @param string $chordName
     * @return array{root: string, suffix: string, bass: string|null}|null
     */
    public function parseChord(string $chordName): ?array
    {
        if ($chordName === '') {
            return null;
        }

        // Cek pemisah bass slash chord '/'
        $parts = explode('/', $chordName, 2);
        $mainChord = $parts[0];
        $bassPart = isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : null;

        // Cocokkan nada akar: Huruf A-G diikuti opsional # atau b
        if (!preg_match('/^([A-G][#b]?)(.*)$/', $mainChord, $matches)) {
            return null;
        }

        $root = $matches[1];
        $suffix = $matches[2];

        // Validasi bass jika ada
        $bass = null;
        if ($bassPart !== null) {
            if (!preg_match('/^([A-G][#b]?)$/', $bassPart, $bassMatches)) {
                return null;
            }
            $bass = $bassMatches[1];
        }

        return [
            'root'   => $root,
            'suffix' => $suffix,
            'bass'   => $bass,
        ];
    }

    /**
     * Menggeser satu nada spesifik sejumlah semitone.
     *
     * @param string $note Nama nada tunggal (mis. 'C', 'F#', 'Bb')
     * @param int $semitones Jumlah semitone
     * @return string
     */
    private function shiftNote(string $note, int $semitones): string
    {
        if (!isset($this->noteIndices[$note])) {
            return $note;
        }

        $currentIndex = $this->noteIndices[$note];

        // Normalisasi pergeseran semitone dalam modular 12
        $newIndex = ($currentIndex + ($semitones % 12) + 12) % 12;

        // Memakai flat scale jika nada asal memakai 'b', selain itu sharp scale (§7.5 no. 3)
        $useFlat = str_contains($note, 'b');
        $scale = $useFlat ? $this->flatScale : $this->sharpScale;

        return $scale[$newIndex];
    }
}

