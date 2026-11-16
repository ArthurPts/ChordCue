<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Controller untuk pengelolaan audio lagu, termasuk upload, hapus, dan offset backing track.
 *
 * Mengikuti pedoman gaya koding CHORDCUE_RULES.md §2 dengan alur linear.
 */
class SongAudioController extends Controller
{
    /**
     * Mengunggah atau mengganti file audio backing track lagu.
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /songs/{song}/backing-track (songs.backing-track.store)
     * - resources/views/songs/editor-simple.blade.php
     */
    public function store(Request $request, Song $song): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        Gate::authorize('update', $song);

        $maxMb = (int) config('chordcue.backing_track_max_mb', 15);
        $maxKb = $maxMb * 1024;

        $validatedInput = $request->validate([
            'backing_track' => [
                'required',
                'file',
                'mimes:mp3',
                'max:' . $maxKb,
            ],
            'offset' => [
                'nullable',
                'numeric',
                'min:0',
                // 'between:-10.000,10.000', // Batas lama ±10s dinonaktifkan
                'regex:/^\d+(\.\d{1,3})?$/',
            ],
        ], [
            'backing_track.required' => 'Berkas audio backing track wajib dipilih.',
            'backing_track.file'     => 'Unggahan harus berupa berkas yang valid.',
            'backing_track.mimes'    => 'Format backing track harus MP3.',
            'backing_track.max'      => 'Ukuran backing track maksimal ' . $maxMb . ' MB.',
            'offset.numeric'         => 'Nilai offset harus berupa bilangan angka.',
            'offset.min'             => 'Nilai offset minimal 0 detik.',
            'offset.regex'           => 'Format presisi offset maksimal 3 angka di belakang koma.',
        ]);

        // 2. Query Database
        if ($song->backing_track_path && Storage::disk('public')->exists($song->backing_track_path)) {
            Storage::disk('public')->delete($song->backing_track_path);
        }

        $storedPath = $request->file('backing_track')->store('backing-tracks', 'public');
        $newOffset = isset($validatedInput['offset'])
            ? round((float) $validatedInput['offset'], 3)
            : ($song->backing_track_offset !== null ? (float) $song->backing_track_offset : 0.000);

        $song->update([
            'backing_track_path'   => $storedPath,
            'backing_track_offset' => $newOffset,
        ]);

        // 3. Response / Render View
        return response()->json([
            'success'              => true,
            'message'              => 'Backing track berhasil diunggah.',
            'backing_track_path'   => $storedPath,
            'backing_track_url'    => Storage::disk('public')->url($storedPath),
            'backing_track_offset' => (float) $song->backing_track_offset,
        ]);
    }

    /**
     * Menghapus file audio backing track fisik dan mereset offset lagu.
     *
     * Fungsi ini digunakan pada:
     * - Route: DELETE /songs/{song}/backing-track (songs.backing-track.destroy)
     * - resources/views/songs/editor-simple.blade.php
     */
    public function destroy(Song $song): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        Gate::authorize('update', $song);

        // 2. Query Database
        if ($song->backing_track_path && Storage::disk('public')->exists($song->backing_track_path)) {
            Storage::disk('public')->delete($song->backing_track_path);
        }

        $song->update([
            'backing_track_path'   => null,
            'backing_track_offset' => null,
        ]);

        // 3. Response / Render View
        return response()->json([
            'success'              => true,
            'message'              => 'Backing track berhasil dihapus.',
            'backing_track_path'   => null,
            'backing_track_url'    => null,
            'backing_track_offset' => 0.0,
        ]);
    }
    /**
     * Memperbarui nilai offset backing track lagu (dalam detik, presisi milidetik).
     *
     * Fungsi ini digunakan pada:
     * - Route: PATCH /songs/{song}/backing-track/offset
     * - Bilah Backing Track pada Editor Simple & Editor Beat
     */
    public function updateOffset(Request $request, Song $song): JsonResponse
    {
        // 1. Otorisasi & Validasi Input
        Gate::authorize('update', $song);

        $validatedInput = $request->validate([
            'offset' => [
                'required',
                'numeric',
                'min:0',
                // 'between:-10.000,10.000', // Batas lama ±10s dinonaktifkan
                'regex:/^\d+(\.\d{1,3})?$/',
            ],
        ], [
            'offset.required' => 'Nilai offset backing track wajib diisi.',
            'offset.numeric'  => 'Nilai offset harus berupa bilangan angka.',
            'offset.min'      => 'Nilai offset minimal 0 detik (offset negatif tidak diperbolehkan).',
            // 'offset.between'  => 'Nilai offset harus berada dalam rentang -10.000 hingga +10.000 detik.',
            'offset.regex'    => 'Format presisi offset maksimal 3 angka di belakang koma (milidetik).',
        ]);

        // 2. Query Database
        $newOffset = round((float) $validatedInput['offset'], 3);
        $song->update([
            'backing_track_offset' => $newOffset,
        ]);

        // 3. Response / Render View
        return response()->json([
            'success'              => true,
            'message'              => 'Offset backing track berhasil diperbarui.',
            'backing_track_offset' => (float) $song->backing_track_offset,
        ]);
    }
}

