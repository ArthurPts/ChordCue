<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Menampilkan form registrasi pengguna baru.
     *
     * Fungsi ini digunakan pada:
     * - Route: GET /register (auth.php)
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Memproses pendaftaran pengguna baru dengan validasi username dan email.
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /register (auth.php)
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        $validatedInput = $request->validate([
            'username' => ['required', 'string', 'max:120', 'alpha_dash', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 2. Query Database
        $user = User::create([
            'username' => $validatedInput['username'],
            'email' => $validatedInput['email'],
            'password' => Hash::make($validatedInput['password']),
            'role' => 'user',
            'song_limit' => 5,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // 3. Response / Render View
        return redirect()->intended(route('home', absolute: false));
    }
}
