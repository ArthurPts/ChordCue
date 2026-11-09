<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Controller untuk pengelolaan audio lagu, termasuk offset backing track.
 *
 * Mengikuti pedoman gaya koding CHORDCUE_RULES.md §2 dengan alur linear.
 */
class SongAudioController extends Controller
{
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
                'between:-10.000,10.000',
                'regex:/^-?\d+(\.\d{1,3})?$/',
            ],
        ], [
            'offset.required' => 'Nilai offset backing track wajib diisi.',
            'offset.numeric'  => 'Nilai offset harus berupa bilangan angka.',
            'offset.between'  => 'Nilai offset harus berada dalam rentang -10.000 hingga +10.000 detik.',
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

