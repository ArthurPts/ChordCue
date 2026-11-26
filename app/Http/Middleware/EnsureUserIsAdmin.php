<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Memastikan pengguna yang terotentikasi memiliki peran administrator.
     *
     * Fungsi ini digunakan pada:
     * - bootstrap/app.php (alias middleware 'admin')
     * - routes/web.php (rute admin/* dan CRUD Pustaka Chord)
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'admin') {
            abort(403, 'Akses khusus administrator.');
        }

        return $next($request);
    }
}
