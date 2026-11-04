<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Menampilkan halaman formulir profil pengguna.
     *
     * Fungsi ini digunakan pada:
     * - Route: GET /profile (web.php)
     */
    public function edit(Request $request): View
    {
        // 1. Otorisasi & Ambil Data
        $user = $request->user();

        // 2. Response / Render View
        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Memperbarui informasi profil pengguna (username dan email).
     *
     * Fungsi ini digunakan pada:
     * - Route: PATCH /profile (web.php)
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        $user = $request->user();
        $validatedData = $request->validated();

        // 2. Query Database
        $user->fill($validatedData);
        $user->save();

        // 3. Response / Render View
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Menghapus akun pengguna beserta seluruh lagu miliknya.
     *
     * Fungsi ini digunakan pada:
     * - Route: DELETE /profile (web.php)
     */
    public function destroy(Request $request): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // 2. Query Database & Bersihkan Relasi
        Auth::logout();

        $user->deleteWithSongs();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 3. Response / Render View
        return Redirect::to('/');
    }
}
