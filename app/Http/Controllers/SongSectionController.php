<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\SongSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Controller AJAX untuk mengelola section lagu pada Simple Editor.
 * Mengikuti pedoman gaya koding CHORDCUE_RULES.md §2 dengan alur linear.
 */
class SongSectionController extends Controller
{
    /**
     * Menambahkan section baru ke dalam lagu.
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /songs/{song}/sections (songs.sections.store)
     * - resources/views/songs/editor-simple.blade.php
     */
    public function store(Request $request, Song $song): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        Gate::authorize('update', $song);

        $validatedInput = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'sequence' => ['nullable', 'integer', 'min:1'],
        ], [
            'name.required'    => 'Nama section wajib diisi.',
            'name.max'         => 'Nama section maksimal 120 karakter.',
            'sequence.integer' => 'Urutan section harus berupa angka bulat.',
            'sequence.min'     => 'Urutan section minimal 1.',
        ]);

        // 2. Query Database
        $section = DB::transaction(function () use ($song, $validatedInput) {
            Song::where('id', $song->id)->lockForUpdate()->first();

            $maxSequence = (int) SongSection::where('song_id', $song->id)->max('sequence');
            $sequence = isset($validatedInput['sequence'])
                ? (int) $validatedInput['sequence']
                : $maxSequence + 1;

            return SongSection::create([
                'song_id'  => $song->id,
                'name'     => trim($validatedInput['name']),
                'sequence' => $sequence,
            ]);
        });

        // 3. Response / Render View
        return response()->json([
            'success' => true,
            'message' => 'Section berhasil ditambahkan.',
            'section' => [
                'id'       => $section->id,
                'song_id'  => $section->song_id,
                'name'     => $section->name,
                'sequence' => $section->sequence,
                'lines'    => [],
            ],
        ], 201);
    }

    /**
     * Memperbarui nama atau urutan section lagu.
     *
     * Fungsi ini digunakan pada:
     * - Route: PATCH /sections/{section} (sections.update)
     * - resources/views/songs/editor-simple.blade.php
     */
    public function update(Request $request, SongSection $section): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        $section->loadMissing('song');
        Gate::authorize('update', $section->song);

        $validatedInput = $request->validate([
            'name'     => ['sometimes', 'required', 'string', 'max:120'],
            'sequence' => ['sometimes', 'required', 'integer', 'min:1'],
        ], [
            'name.required'    => 'Nama section wajib diisi.',
            'name.max'         => 'Nama section maksimal 120 karakter.',
            'sequence.integer' => 'Urutan section harus berupa angka bulat.',
            'sequence.min'     => 'Urutan section minimal 1.',
        ]);

        // 2. Query Database
        $updateData = [];
        if (array_key_exists('name', $validatedInput)) {
            $updateData['name'] = trim($validatedInput['name']);
        }
        if (array_key_exists('sequence', $validatedInput)) {
            $updateData['sequence'] = (int) $validatedInput['sequence'];
        }

        if ($updateData !== []) {
            $section->update($updateData);
        }

        // 3. Response / Render View
        return response()->json([
            'success' => true,
            'message' => 'Section berhasil diperbarui.',
            'section' => [
                'id'       => $section->id,
                'song_id'  => $section->song_id,
                'name'     => $section->name,
                'sequence' => $section->sequence,
            ],
        ]);
    }

    /**
     * Menghapus section beserta seluruh baris lirik dan placement di dalamnya.
     *
     * Fungsi ini digunakan pada:
     * - Route: DELETE /sections/{section} (sections.destroy)
     * - resources/views/songs/editor-simple.blade.php
     */
    public function destroy(SongSection $section): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        $section->loadMissing('song');
        Gate::authorize('update', $section->song);

        // 2. Query Database
        $songId = $section->song_id;

        DB::transaction(function () use ($section, $songId) {
            Song::where('id', $songId)->lockForUpdate()->first();
            $section->delete();

            $remaining = SongSection::where('song_id', $songId)
                ->orderBy('sequence')
                ->orderBy('id')
                ->get();

            $order = 1;
            foreach ($remaining as $item) {
                if ((int) $item->sequence !== $order) {
                    $item->update(['sequence' => $order]);
                }
                $order++;
            }
        });

        // 3. Response / Render View
        return response()->json([
            'success' => true,
            'message' => 'Section berhasil dihapus.',
        ]);
    }
}
