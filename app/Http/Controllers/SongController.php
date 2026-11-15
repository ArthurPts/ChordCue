<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SongController extends Controller
{
    /**
     * Menampilkan halaman Player latihan untuk sebuah lagu.
     * Mengikuti otorisasi SongPolicy: lagu publik terbuka untuk siapa saja (termasuk Guest),
     * sedangkan lagu privat hanya dapat diakses oleh pemilik atau administrator.
     *
     * Fungsi ini digunakan pada:
     * - Route: GET /songs/{song} (songs.show)
     * - resources/views/welcome.blade.php (klik kartu lagu)
     * - resources/views/my-songs.blade.php (klik tombol Putar)
     */
    public function show(Song $song): View
    {
        // 1. Otorisasi & Validasi Input
        Gate::authorize('view', $song);

        // 2. Query Database
        $song->loadMissing(['artist', 'categories', 'sections.lines.placements.chord']);

        // 3. Response / Render View
        return view('songs.player', [
            'song' => $song,
        ]);
    }
    /**
     * Tampilkan form pembuatan lagu baru.
     */
    public function create()
    {
        return redirect()->route('my.songs');
    }

    /**
     * Simpan lagu baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'artist' => 'required|string|max:255',
            'bpm' => 'required|integer|min:60|max:140',
            'time_signature' => 'required|string|in:4/4,3/4,2/4,6/8,12/8',
            'visibility' => 'required|in:public,private',
        ], [
            'title.required' => 'Judul lagu wajib diisi.',
            'artist.required' => 'Nama artist wajib diisi.',
            'bpm.required' => 'BPM wajib diisi.',
            'bpm.integer' => 'BPM harus berupa angka.',
            'bpm.min' => 'BPM minimal 60.',
            'bpm.max' => 'BPM maksimal 140.',
            'time_signature.required' => 'Time signature wajib dipilih.',
            'visibility.required' => 'Visibilitas lagu wajib dipilih.',
        ]);

        // Kuota lagu: pengguna biasa dibatasi oleh song_limit, admin tanpa batas
        if (auth()->user()->role !== 'admin') {
            $currentSongCount = Song::where('user_id', auth()->id())->count();
            if ($currentSongCount >= auth()->user()->song_limit) {
                return back()->with('error', 'Batas kuota lagu (' . auth()->user()->song_limit . ' lagu) telah tercapai. Hapus lagu lama untuk membuat lagu baru.');
            }
        }

        // Cari atau buat artis baru
        $artist = Artist::firstOrCreate([
            'name' => trim($validated['artist']),
        ]);

        // Pecah time signature (misal 4/4) menjadi numerator dan denominator
        [$numerator, $denominator] = explode('/', $validated['time_signature']);

        $song = Song::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'artist_id' => $artist->id,
            'bpm' => (int) $validated['bpm'],
            'time_signature_numerator' => (int) $numerator,
            'time_signature_denominator' => (int) $denominator,
            'visibility' => $validated['visibility'],
        ]);

        return redirect()->route('my.songs')->with('status', 'Lagu "' . $song->title . '" berhasil dibuat.');
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
