<?php

namespace App\Http\Controllers;

use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Controller AJAX untuk mengelola baris lirik lagu pada Simple Editor.
 * Menangani aturan pemendekan teks lirik §5.4 secara atomik.
 */
class LyricLineController extends Controller
{
    /**
     * Menambahkan baris lirik baru ke dalam section lagu.
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /sections/{section}/lines (sections.lines.store)
     * - resources/views/songs/editor-simple.blade.php
     */
    public function store(Request $request, SongSection $section): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        $section->loadMissing('song');
        Gate::authorize('update', $section->song);

        $validatedInput = $request->validate([
            'content'     => ['required', 'string', 'max:2000'],
            'line_number' => ['nullable', 'integer', 'min:1'],
        ], [
            'content.required'    => 'Isi teks baris lirik wajib diisi.',
            'content.max'         => 'Panjang baris lirik maksimal 2000 karakter.',
            'line_number.integer' => 'Nomor baris harus berupa angka bulat.',
            'line_number.min'     => 'Nomor baris minimal 1.',
        ]);

        // 2. Query Database
        $line = DB::transaction(function () use ($section, $validatedInput) {
            Song::where('id', $section->song_id)->lockForUpdate()->first();

            $maxLineNumber = (int) LyricLine::where('song_section_id', $section->id)->max('line_number');
            $lineNumber = isset($validatedInput['line_number'])
                ? (int) $validatedInput['line_number']
                : $maxLineNumber + 1;

            return LyricLine::create([
                'song_section_id' => $section->id,
                'line_number'     => $lineNumber,
                'content'         => $validatedInput['content'],
            ]);
        });

        // 3. Response / Render View
        return response()->json([
            'success' => true,
            'message' => 'Baris lirik berhasil ditambahkan.',
            'line'    => [
                'id'              => $line->id,
                'song_section_id' => $line->song_section_id,
                'line_number'     => $line->line_number,
                'content'         => $line->content,
                'placements'      => [],
            ],
        ], 201);
    }

    /**
     * Memperbarui isi teks atau urutan baris lirik.
     * Jika teks dipendekkan sehingga position placement melebihi panjang baris baru,
     * position diset null (atau placement dihapus jika start_beat juga null).
     *
     * Fungsi ini digunakan pada:
     * - Route: PATCH /lines/{line} (lines.update)
     * - resources/views/songs/editor-simple.blade.php
     */
    public function update(Request $request, LyricLine $line): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        $line->loadMissing('section.song');
        Gate::authorize('update', $line->section->song);

        $validatedInput = $request->validate([
            'content'     => ['sometimes', 'required', 'string', 'max:2000'],
            'line_number' => ['sometimes', 'required', 'integer', 'min:1'],
        ], [
            'content.required'    => 'Isi teks baris lirik wajib diisi.',
            'content.max'         => 'Panjang baris lirik maksimal 2000 karakter.',
            'line_number.integer' => 'Nomor baris harus berupa angka bulat.',
            'line_number.min'     => 'Nomor baris minimal 1.',
        ]);

        // 2. Query Database
        $updatedLine = DB::transaction(function () use ($line, $validatedInput) {
            Song::where('id', $line->section->song_id)->lockForUpdate()->first();

            $updateData = [];
            if (array_key_exists('content', $validatedInput)) {
                $updateData['content'] = $validatedInput['content'];
            }
            if (array_key_exists('line_number', $validatedInput)) {
                $updateData['line_number'] = (int) $validatedInput['line_number'];
            }

            if ($updateData !== []) {
                $line->update($updateData);
            }

            if (array_key_exists('content', $validatedInput)) {
                $newLength = mb_strlen($line->content, 'UTF-8');
                $placements = $line->placements()->get();

                foreach ($placements as $placement) {
                    if ($placement->position !== null && (int) $placement->position >= $newLength) {
                        if ($placement->start_beat === null) {
                            $placement->delete();
                        } else {
                            $placement->update(['position' => null]);
                        }
                    }
                }
            }

            return $line->fresh(['placements' => function ($query) {
                $query->orderByRaw('CASE WHEN position IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('position')
                    ->orderBy('id')
                    ->with('chord');
            }]);
        });

        $placementsData = [];
        foreach ($updatedLine->placements as $placement) {
            $placementsData[] = [
                'id'            => $placement->id,
                'lyric_line_id' => $placement->lyric_line_id,
                'chord_id'      => $placement->chord_id,
                'chord_name'    => $placement->chord ? $placement->chord->name : '',
                'position'      => $placement->position,
                'start_beat'    => $placement->start_beat,
            ];
        }

        // 3. Response / Render View
        return response()->json([
            'success' => true,
            'message' => 'Baris lirik berhasil diperbarui.',
            'line'    => [
                'id'              => $updatedLine->id,
                'song_section_id' => $updatedLine->song_section_id,
                'line_number'     => $updatedLine->line_number,
                'content'         => $updatedLine->content,
                'placements'      => $placementsData,
            ],
        ]);
    }

    /**
     * Menghapus baris lirik beserta seluruh placement chord di baris tersebut.
     *
     * Fungsi ini digunakan pada:
     * - Route: DELETE /lines/{line} (lines.destroy)
     * - resources/views/songs/editor-simple.blade.php
     */
    public function destroy(LyricLine $line): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        $line->loadMissing('section.song');
        Gate::authorize('update', $line->section->song);

        // 2. Query Database
        $sectionId = $line->song_section_id;
        $songId = $line->section->song_id;

        DB::transaction(function () use ($line, $sectionId, $songId) {
            Song::where('id', $songId)->lockForUpdate()->first();
            $line->delete();

            $remaining = LyricLine::where('song_section_id', $sectionId)
                ->orderBy('line_number')
                ->orderBy('id')
                ->get();

            $order = 1;
            foreach ($remaining as $item) {
                if ((int) $item->line_number !== $order) {
                    $item->update(['line_number' => $order]);
                }
                $order++;
            }
        });

        // 3. Response / Render View
        return response()->json([
            'success' => true,
            'message' => 'Baris lirik berhasil dihapus.',
        ]);
    }
}
