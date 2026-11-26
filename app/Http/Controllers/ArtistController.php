<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArtistController extends Controller
{
    /**
     * Memastikan pengguna yang mengakses adalah administrator.
     *
     * Fungsi ini digunakan pada:
     * - ArtistController::index()
     * - ArtistController::store()
     * - ArtistController::update()
     * - ArtistController::destroy()
     */
    private function ensureAdmin(): void
    {
        if (! auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Akses khusus administrator.');
        }
    }

    /**
     * Menampilkan daftar manajemen artis beserta statistik dan pencarian.
     *
     * Fungsi ini digunakan pada:
     * - Route: GET /admin/artists (admin.artists.index)
     * - resources/views/components/navbar.blade.php
     */
    public function index(Request $request): View
    {
        // 1. Otorisasi & Validasi Input
        $this->ensureAdmin();

        $searchKeyword = trim((string) $request->input('q', ''));

        // 2. Query Database
        $totalArtists = Artist::count();
        $usedArtistsCount = Artist::has('songs')->count();

        $artistsQuery = Artist::withCount('songs');

        if ($searchKeyword !== '') {
            $artistsQuery->where('name', 'like', '%' . $searchKeyword . '%');
        }

        $artists = $artistsQuery
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // 3. Response / Render View
        return view('admin.artists.index', [
            'artists'          => $artists,
            'totalArtists'     => $totalArtists,
            'usedArtistsCount' => $usedArtistsCount,
            'searchKeyword'    => $searchKeyword,
        ]);
    }

    /**
     * Menyimpan artis baru ke tabel master artis.
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /admin/artists (admin.artists.store)
     * - resources/views/admin/artists/index.blade.php
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        $this->ensureAdmin();

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
        ]);

        $validatedInput = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('artists', 'name')],
        ], [
            'name.required' => 'Nama artis wajib diisi.',
            'name.max'      => 'Nama artis maksimal 120 karakter.',
            'name.unique'   => 'Artis dengan nama tersebut sudah terdaftar.',
        ]);

        // 2. Query Database
        $artist = Artist::create([
            'name' => $validatedInput['name'],
        ]);

        // 3. Response / Render View
        return redirect()
            ->route('admin.artists.index')
            ->with('status', "Artis \"{$artist->name}\" berhasil ditambahkan.");
    }

    /**
     * Memperbarui nama artis yang sudah ada.
     *
     * Fungsi ini digunakan pada:
     * - Route: PUT /admin/artists/{artist} (admin.artists.update)
     * - resources/views/admin/artists/index.blade.php
     */
    public function update(Request $request, Artist $artist): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        $this->ensureAdmin();

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
        ]);

        $validatedInput = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('artists', 'name')->ignore($artist->id)],
        ], [
            'name.required' => 'Nama artis wajib diisi.',
            'name.max'      => 'Nama artis maksimal 120 karakter.',
            'name.unique'   => 'Artis dengan nama tersebut sudah terdaftar.',
        ]);

        // 2. Query Database
        $artist->update([
            'name' => $validatedInput['name'],
        ]);

        // 3. Response / Render View
        return redirect()
            ->route('admin.artists.index')
            ->with('status', "Artis \"{$artist->name}\" berhasil diperbarui.");
    }

    /**
     * Menghapus artis jika tidak lagi digunakan oleh lagu mana pun (§5.11 & §6.2 RESTRICT).
     *
     * Fungsi ini digunakan pada:
     * - Route: DELETE /admin/artists/{artist} (admin.artists.destroy)
     * - resources/views/admin/artists/index.blade.php
     */
    public function destroy(Artist $artist): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        $this->ensureAdmin();

        // 2. Query Database
        $songsCount = $artist->songs()->count();
        if ($songsCount > 0) {
            return redirect()
                ->route('admin.artists.index')
                ->with('error', "Artis \"{$artist->name}\" tidak dapat dihapus karena masih digunakan pada {$songsCount} lagu.");
        }

        $artistName = $artist->name;
        $artist->delete();

        // 3. Response / Render View
        return redirect()
            ->route('admin.artists.index')
            ->with('status', "Artis \"{$artistName}\" berhasil dihapus.");
    }
}
