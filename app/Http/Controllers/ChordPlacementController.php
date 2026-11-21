<?php

namespace App\Http\Controllers;

use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Controller AJAX untuk mengelola penempatan chord (placement) pada baris lirik.
 * Menjamin keunikan atomik position/start_beat dan independensi antar editor (§5.4 & §7.3).
 */
class ChordPlacementController extends Controller
{
    /**
     * Menempatkan chord baru pada baris lirik dengan position atau start_beat.
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /lines/{line}/placements (lines.placements.store)
     * - resources/views/songs/editor-simple.blade.php
     * - resources/views/songs/editor-beat.blade.php
     */
    public function store(Request $request, LyricLine $line): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        $line->loadMissing('section.song');
        $song = $line->section->song;
        Gate::authorize('update', $song);

        $validatedInput = $request->validate([
            'chord_id'   => ['required', 'integer', 'exists:chords,id'],
            'position'   => ['nullable', 'integer', 'min:0'],
            'start_beat' => ['nullable', 'integer', 'min:1'],
            'force'      => ['sometimes', 'boolean'],
        ], [
            'chord_id.required'  => 'Chord wajib dipilih.',
            'chord_id.exists'    => 'Chord yang dipilih tidak valid.',
            'position.integer'   => 'Posisi huruf harus berupa angka bulat.',
            'position.min'       => 'Posisi huruf minimal 0.',
            'start_beat.integer' => 'Beat harus berupa angka bulat.',
            'start_beat.min'     => 'Beat minimal 1.',
        ]);

        $position = array_key_exists('position', $validatedInput) && $validatedInput['position'] !== null
            ? (int) $validatedInput['position']
            : null;
        $startBeat = array_key_exists('start_beat', $validatedInput) && $validatedInput['start_beat'] !== null
            ? (int) $validatedInput['start_beat']
            : null;
        $force = (bool) ($validatedInput['force'] ?? false);

        if ($position === null && $startBeat === null) {
            return response()->json([
                'message' => 'Minimal satu koordinat (position atau start_beat) wajib diisi.',
                'errors'  => ['position' => ['Minimal satu koordinat wajib diisi.']],
            ], 422);
        }

        $lineLength = mb_strlen($line->content, 'UTF-8');
        if ($position !== null && $position >= $lineLength) {
            return response()->json([
                'message' => 'Posisi huruf melebihi panjang teks baris lirik.',
                'errors'  => ['position' => ['Posisi huruf di luar panjang teks baris.']],
            ], 422);
        }

        // 2. Query Database
        $result = DB::transaction(function () use ($song, $line, $validatedInput, $position, $startBeat, $force) {
            Song::where('id', $song->id)->lockForUpdate()->first();

            $conflict = null;

            if ($position !== null) {
                $conflict = ChordPlacement::where('lyric_line_id', $line->id)
                    ->where('position', $position)
                    ->with('chord')
                    ->first();
            }

            if ($conflict === null && $startBeat !== null) {
                $conflict = ChordPlacement::where('start_beat', $startBeat)
                    ->whereHas('lyricLine.section', function ($sectionQuery) use ($song) {
                        $sectionQuery->where('song_id', $song->id);
                    })
                    ->with('chord')
                    ->first();
            }

            if ($conflict !== null && !$force) {
                return [
                    'status'   => 409,
                    'conflict' => $conflict,
                ];
            }

            if ($conflict !== null && $force) {
                $conflict->delete();
            }

            $placement = ChordPlacement::create([
                'lyric_line_id' => $line->id,
                'chord_id'      => (int) $validatedInput['chord_id'],
                'position'      => $position,
                'start_beat'    => $startBeat,
            ]);
            $placement->load('chord');

            return [
                'status'    => 201,
                'placement' => $placement,
            ];
        });

        // 3. Response / Render View
        if ($result['status'] === 409) {
            $conflict = $result['conflict'];

            return response()->json([
                'message'  => 'Koordinat target sudah digunakan oleh chord lain.',
                'conflict' => [
                    'id'         => $conflict->id,
                    'chord_id'   => $conflict->chord_id,
                    'chord_name' => $conflict->chord ? $conflict->chord->name : '',
                    'position'   => $conflict->position,
                    'start_beat' => $conflict->start_beat,
                ],
            ], 409);
        }

        $placement = $result['placement'];

        return response()->json([
            'success'   => true,
            'message'   => 'Chord berhasil ditempatkan.',
            'placement' => [
                'id'            => $placement->id,
                'lyric_line_id' => $placement->lyric_line_id,
                'chord_id'      => $placement->chord_id,
                'chord_name'    => $placement->chord ? $placement->chord->name : '',
                'position'      => $placement->position,
                'start_beat'    => $placement->start_beat,
            ],
        ], 201);
    }

    /**
     * Memindahkan satu koordinat placement (position atau start_beat) secara independen.
     * Mengubah position tidak akan menyentuh start_beat, demikian sebaliknya.
     *
     * Fungsi ini digunakan pada:
     * - Route: PATCH /placements/{placement} (placements.update)
     * - resources/views/songs/editor-simple.blade.php
     * - resources/views/songs/editor-beat.blade.php
     */
    public function update(Request $request, ChordPlacement $placement): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        $placement->loadMissing('lyricLine.section.song');
        $line = $placement->lyricLine;
        $song = $line->section->song;
        Gate::authorize('update', $song);

        $validatedInput = $request->validate([
            'position'   => ['sometimes', 'required', 'integer', 'min:0'],
            'start_beat' => ['sometimes', 'required', 'integer', 'min:1'],
            'force'      => ['sometimes', 'boolean'],
        ], [
            'position.integer'   => 'Posisi huruf harus berupa angka bulat.',
            'position.min'       => 'Posisi huruf minimal 0.',
            'start_beat.integer' => 'Beat harus berupa angka bulat.',
            'start_beat.min'     => 'Beat minimal 1.',
        ]);

        $hasPosition = array_key_exists('position', $validatedInput);
        $hasStartBeat = array_key_exists('start_beat', $validatedInput);

        if (!$hasPosition && !$hasStartBeat) {
            return response()->json([
                'message' => 'Koordinat position atau start_beat wajib dikirim.',
                'errors'  => ['position' => ['Koordinat target wajib diisi.']],
            ], 422);
        }

        if ($hasPosition) {
            $newPosition = (int) $validatedInput['position'];
            $lineLength = mb_strlen($line->content, 'UTF-8');
            if ($newPosition >= $lineLength) {
                return response()->json([
                    'message' => 'Posisi huruf melebihi panjang teks baris lirik.',
                    'errors'  => ['position' => ['Posisi huruf di luar panjang teks baris.']],
                ], 422);
            }
        }

        $force = (bool) ($validatedInput['force'] ?? false);

        // 2. Query Database
        $result = DB::transaction(function () use ($song, $line, $placement, $validatedInput, $hasPosition, $hasStartBeat, $force) {
            Song::where('id', $song->id)->lockForUpdate()->first();

            $conflict = null;

            if ($hasPosition) {
                $newPosition = (int) $validatedInput['position'];
                $conflict = ChordPlacement::where('lyric_line_id', $line->id)
                    ->where('position', $newPosition)
                    ->where('id', '!=', $placement->id)
                    ->with('chord')
                    ->first();
            }

            if ($conflict === null && $hasStartBeat) {
                $newStartBeat = (int) $validatedInput['start_beat'];
                $conflict = ChordPlacement::where('start_beat', $newStartBeat)
                    ->where('id', '!=', $placement->id)
                    ->whereHas('lyricLine.section', function ($sectionQuery) use ($song) {
                        $sectionQuery->where('song_id', $song->id);
                    })
                    ->with('chord')
                    ->first();
            }

            if ($conflict !== null && !$force) {
                return [
                    'status'   => 409,
                    'conflict' => $conflict,
                ];
            }

            if ($conflict !== null && $force) {
                $conflict->delete();
            }

            if ($hasPosition) {
                $placement->position = (int) $validatedInput['position'];
            }
            if ($hasStartBeat) {
                $placement->start_beat = (int) $validatedInput['start_beat'];
            }
            $placement->save();
            $placement->load('chord');

            return [
                'status'    => 200,
                'placement' => $placement,
            ];
        });

        // 3. Response / Render View
        if ($result['status'] === 409) {
            $conflict = $result['conflict'];

            return response()->json([
                'message'  => 'Koordinat target sudah digunakan oleh chord lain.',
                'conflict' => [
                    'id'         => $conflict->id,
                    'chord_id'   => $conflict->chord_id,
                    'chord_name' => $conflict->chord ? $conflict->chord->name : '',
                    'position'   => $conflict->position,
                    'start_beat' => $conflict->start_beat,
                ],
            ], 409);
        }

        $updated = $result['placement'];

        return response()->json([
            'success'   => true,
            'message'   => 'Posisi chord berhasil diperbarui.',
            'placement' => [
                'id'            => $updated->id,
                'lyric_line_id' => $updated->lyric_line_id,
                'chord_id'      => $updated->chord_id,
                'chord_name'    => $updated->chord ? $updated->chord->name : '',
                'position'      => $updated->position,
                'start_beat'    => $updated->start_beat,
            ],
        ]);
    }

    /**
     * Melepas satu koordinat (position atau start_beat) tanpa mengubah koordinat pasangannya.
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /placements/{placement}/release (placements.release)
     * - resources/views/songs/editor-beat.blade.php
     */
    public function release(Request $request, ChordPlacement $placement): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        $placement->loadMissing('lyricLine.section.song');
        Gate::authorize('update', $placement->lyricLine->section->song);

        $validatedInput = $request->validate([
            'coordinate' => ['required', 'in:position,start_beat'],
        ], [
            'coordinate.required' => 'Koordinat yang dilepas wajib ditentukan.',
            'coordinate.in'       => 'Koordinat hanya boleh position atau start_beat.',
        ]);

        // 2. Query Database
        $coordinate = $validatedInput['coordinate'];
        $placement->{$coordinate} = null;
        $placement->save();
        $placement->load('chord');

        // 3. Response / Render View
        return response()->json([
            'success'   => true,
            'message'   => 'Koordinat placement berhasil dilepas.',
            'placement' => [
                'id'            => $placement->id,
                'lyric_line_id' => $placement->lyric_line_id,
                'chord_id'      => $placement->chord_id,
                'chord_name'    => $placement->chord ? $placement->chord->name : '',
                'position'      => $placement->position,
                'start_beat'    => $placement->start_beat,
            ],
        ]);
    }

    /**
     * Menghapus record placement seutuhnya dari lagu.
     *
     * Fungsi ini digunakan pada:
     * - Route: DELETE /placements/{placement} (placements.destroy)
     * - resources/views/songs/editor-simple.blade.php
     * - resources/views/songs/editor-beat.blade.php
     */
    public function destroy(ChordPlacement $placement): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        $placement->loadMissing('lyricLine.section.song');
        Gate::authorize('update', $placement->lyricLine->section->song);

        // 2. Query Database
        $placement->delete();

        // 3. Response / Render View
        return response()->json([
            'success' => true,
            'message' => 'Chord berhasil dihapus dari baris lirik.',
        ]);
    }
}
