<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\Http\Request;

class SongController extends Controller
{
    /**
     * Tampilkan form pembuatan lagu baru.
     */
    public function create()
    {
        return view('chords.index');
    }

    /**
     * Tampilkan form edit lagu.
     */
    public function edit(Song $song)
    {
        // Hanya pemilik lagu atau administrator yang berhak mengedit
        if (auth()->user()->role !== 'admin' && $song->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengedit lagu ini.');
        }

        return view('chords.index', compact('song'));
    }

    /**
     * Hapus lagu beserta file backing track fisiknya.
     */
    public function destroy(Song $song)
    {
        // Hanya pemilik lagu atau administrator yang berhak menghapus
        if (auth()->user()->role !== 'admin' && $song->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus lagu ini.');
        }

        $song->deleteWithFiles();

        return back()->with('status', 'Lagu berhasil dihapus.');
    }
}
