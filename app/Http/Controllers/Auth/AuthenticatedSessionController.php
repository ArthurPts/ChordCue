<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Menampilkan halaman login untuk pengunjung.
     *
     * Fungsi ini digunakan pada:
     * - Route: GET /login (auth.php)
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Memproses autentikasi login pengguna menggunakan email dan password.
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /login (auth.php)
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        $request->authenticate();

        $request->session()->regenerate();

        // 2. Response / Render View
        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Menghapus sesi autentikasi pengguna (logout).
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /logout (auth.php)
     */
    public function destroy(Request $request): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // 2. Response / Render View
        return redirect('/');
    }
}
