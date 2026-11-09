<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateChordAudio;
use App\Models\Chord;
use App\Models\Song;
use App\Services\ChordTransposer;
use App\Services\PronunciationGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Controller untuk menyediakan data timeline lagu bagi Player dan Editor.
 * Mengikuti spesifikasi sistem §9.2 dan §9.3.
 */
class SongTimelineController extends Controller
{
    public function __construct(
        private ChordTransposer $transposer,
        private PronunciationGenerator $pronunciationGenerator
    ) {
    }

    /**
     * Mengambil struktur timeline lagu beserta section, baris, dan placement lengkap.
     * Mendukung pergeseran nada (transpose) read-only (-11 s/d +11 semitone).
     *
     * Fungsi ini digunakan pada:
     * - Route: GET /songs/{song}/timeline?transpose=N
     * - resources/views/songs/player.blade.php
     * - resources/js/audio/audio-engine.js
     */
    public function show(Request $request, Song $song): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        Gate::authorize('view', $song);

        $validatedInput = $request->validate([
            'transpose' => ['nullable', 'integer', 'between:-11,11'],
        ], [
            'transpose.integer' => 'Nilai transpose harus berupa angka bulat.',
            'transpose.between' => 'Nilai transpose harus berada di antara -11 sampai +11 semitone.',
        ]);

        $transposeSteps = isset($validatedInput['transpose']) ? (int) $validatedInput['transpose'] : 0;

        // 2. Query Database
        $song->loadMissing(['artist', 'sections' => function ($sectionQuery) {
            $sectionQuery->orderBy('sequence');
            $sectionQuery->with(['lines' => function ($lineQuery) {
                $lineQuery->orderBy('line_number');
                $lineQuery->with(['placements' => function ($placementQuery) {
                    // Hanya placement lengkap (position dan start_beat terisi) sesuai §5.6
                    $placementQuery->whereNotNull('position')
                        ->whereNotNull('start_beat')
                        ->orderBy('start_beat')
                        ->orderBy('position')
                        ->with('chord');
                }]);
            }]);
        }]);

        // Pergeseran nada dasar lagu jika formatnya dapat diurai (§5.7)
        $transposedKey = $song->default_key;
        if ($transposedKey && $transposeSteps !== 0) {
            $transposedKey = $this->transposer->transpose($transposedKey, $transposeSteps);
        }

        // Susun data backing track
        $backingTrackData = null;
        if ($song->backing_track_path && Storage::disk('public')->exists($song->backing_track_path)) {
            $backingTrackData = [
                'url'    => Storage::disk('public')->url($song->backing_track_path),
                'offset' => (float) $song->backing_track_offset,
            ];
        }

        // Cache chord hasil transpose agar tidak melakukan query/create berulang pada placement yang sama
        $chordCache = [];
        $distinctChordIds = [];
        $sectionsData = [];

        foreach ($song->sections as $section) {
            $linesData = [];

            foreach ($section->lines as $line) {
                $placementsData = [];

                foreach ($line->placements as $placement) {
                    $originalChord = $placement->chord;
                    $targetChordName = $originalChord ? $originalChord->name : '';

                    if ($transposeSteps !== 0 && $targetChordName !== '') {
                        $targetChordName = $this->transposer->transpose($targetChordName, $transposeSteps);
                    }

                    if (!isset($chordCache[$targetChordName])) {
                        if ($targetChordName === ($originalChord ? $originalChord->name : '')) {
                            $chordModel = $originalChord;
                        } else {
                            // Cari atau buat chord baru di database (§7.5 no. 6)
                            $chordModel = Chord::where('name', $targetChordName)->first();

                            if ($chordModel === null) {
                                $pronunciation = $this->pronunciationGenerator->generate($targetChordName);
                                $chordModel = Chord::create([
                                    'name'          => $targetChordName,
                                    'pronunciation' => $pronunciation,
                                    'file_path'     => null,
                                ]);

                                // Antrikan job TTS hanya jika chord baru saja dibuat (§9 aturan keandalan)
                                GenerateChordAudio::dispatch($chordModel->id);
                            }
                        }

                        $chordCache[$targetChordName] = $chordModel;
                    }

                    $resolvedChord = $chordCache[$targetChordName];
                    if ($resolvedChord) {
                        $distinctChordIds[$resolvedChord->id] = $resolvedChord;
                    }

                    $placementsData[] = [
                        'id'         => $placement->id,
                        'chord'      => [
                            'name'      => $resolvedChord ? $resolvedChord->name : $targetChordName,
                            'audio_url' => $resolvedChord ? $resolvedChord->audio_url : null,
                        ],
                        'position'   => $placement->position,
                        'start_beat' => $placement->start_beat,
                    ];
                }

                $linesData[] = [
                    'id'         => $line->id,
                    'content'    => $line->content,
                    'placements' => $placementsData,
                ];
            }

            $sectionsData[] = [
                'name'  => $section->name,
                'lines' => $linesData,
            ];
        }

        // Periksa kesiapan audio untuk seluruh chord yang digunakan pada lagu
        $pendingChordsCount = 0;
        foreach ($distinctChordIds as $chordItem) {
            if ($chordItem->audio_url === null) {
                $pendingChordsCount++;
            }
        }

        $audioReady = ($pendingChordsCount === 0);

        // 3. Response / Render View (Mengikuti Skema §9.3 dan Ketetapan Offset)
        return response()->json([
            'song' => [
                'id'                   => $song->id,
                'title'                => $song->title,
                'artist'               => $song->artist ? $song->artist->name : 'Unknown Artist',
                'key'                  => $transposedKey,
                'bpm'                  => $song->bpm,
                'time_signature'       => [
                    'numerator'   => $song->time_signature_numerator,
                    'denominator' => $song->time_signature_denominator,
                ],
                'backing_track'        => $backingTrackData,
                'backing_track_offset' => $song->backing_track_offset !== null ? (float) $song->backing_track_offset : null,
            ],
            'transpose'      => $transposeSteps,
            'audio_ready'    => $audioReady,
            'pending_chords' => $pendingChordsCount,
            'sections'       => $sectionsData,
        ]);
    }
}

