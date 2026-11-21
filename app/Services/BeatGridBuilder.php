<?php

namespace App\Services;

/**
 * Layanan kalkulasi grid birama berlapis per baris lirik untuk Editor Beat (§7.2).
 * Seluruh penomoran beat tetap bersifat global (mulai dari 1) dan tidak bergantung pada offset audio.
 */
class BeatGridBuilder
{
    /**
     * Menghitung nomor bar global (1-indexed) yang memuat suatu nomor beat global.
     *
     * Fungsi ini digunakan pada:
     * - BeatGridBuilder::buildForSections()
     */
    public function barOfBeat(int $beat, int $beatsPerBar): int
    {
        $safeBeats = max(1, $beatsPerBar);

        return (int) ceil($beat / $safeBeats);
    }

    /**
     * Menyusun struktur bar dan sel beat untuk seluruh baris lirik lagu sesuai spesifikasi §7.2.
     *
     * Fungsi ini digunakan pada:
     * - SongController::editorBeat() (GET /songs/{song}/editor/beat)
     * - tests/Feature/BeatEditorTest.php
     *
     * @param  array<int, array<string, mixed>>  $sectionsData
     * @return array<int, array<string, mixed>> Map [lineId => struktur grid baris]
     */
    public function buildForSections(array $sectionsData, int $beatsPerBar): array
    {
        $safeBeats = max(1, $beatsPerBar);
        $highestBarUsed = 0;
        $lineGrids = [];

        foreach ($sectionsData as $section) {
            foreach ($section['lines'] as $line) {
                $placementsByBeat = [];

                foreach ($line['placements'] as $placement) {
                    if ($placement['start_beat'] !== null) {
                        $beatNumber = (int) $placement['start_beat'];
                        $placementsByBeat[$beatNumber] = $placement;
                    }
                }

                if (count($placementsByBeat) > 0) {
                    $beatKeys = array_keys($placementsByBeat);
                    $minBeat = min($beatKeys);
                    $maxBeat = max($beatKeys);
                    $barAwal = $this->barOfBeat($minBeat, $safeBeats);
                    $barAkhirTerpakai = $this->barOfBeat($maxBeat, $safeBeats);
                } else {
                    $barAwal = $highestBarUsed + 1;
                    $barAkhirTerpakai = $barAwal - 1;
                }

                $bars = [];
                $lastBarToRender = $barAkhirTerpakai + 1;

                for ($globalBar = $barAwal; $globalBar <= $lastBarToRender; $globalBar++) {
                    $localBar = $globalBar - $barAwal + 1;
                    $beats = [];

                    for ($beatInBar = 1; $beatInBar <= $safeBeats; $beatInBar++) {
                        $globalBeat = ($globalBar - 1) * $safeBeats + $beatInBar;
                        $beats[] = [
                            'beat_global' => $globalBeat,
                            'beat_in_bar' => $beatInBar,
                            'is_downbeat' => $beatInBar === 1,
                            'placement'   => $placementsByBeat[$globalBeat] ?? null,
                        ];
                    }

                    $bars[] = [
                        'bar_global'        => $globalBar,
                        'bar_lokal'         => $localBar,
                        'is_empty_trailing' => $globalBar === $lastBarToRender,
                        'beats'             => $beats,
                    ];
                }

                if ($barAkhirTerpakai > $highestBarUsed) {
                    $highestBarUsed = $barAkhirTerpakai;
                }

                $lineGrids[(int) $line['id']] = [
                    'bar_awal'           => $barAwal,
                    'bar_akhir_terpakai' => $barAkhirTerpakai,
                    'bars'               => $bars,
                ];
            }
        }

        return $lineGrids;
    }
}
