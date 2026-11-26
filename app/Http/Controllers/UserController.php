<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Tampilkan daftar manajemen pengguna untuk administrator.
     *
     * Fungsi ini digunakan pada:
     * - Route: GET /admin/users (admin.users.index)
     */
    public function index(Request $request)
    {
        $totalUsers = User::count();
        $adminUsers = User::where('role', 'admin')->count();

        $query = User::withCount('songs');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = $request->input('role');
            if ($role === 'member') {
                $role = 'user';
            }
            if (in_array($role, ['admin', 'user'])) {
                $query->where('role', $role);
            }
        }

        $users = $query->latest('id')->paginate(7)->withQueryString();

        return view('admin.users.index', compact('users', 'totalUsers', 'adminUsers'));
    }

    /**
     * Hapus pengguna beserta seluruh lagunya.
     *
     * Fungsi ini digunakan pada:
     * - Route: DELETE /admin/users/{user} (admin.users.destroy)
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->deleteWithSongs();

        return back()->with('status', 'Pengguna dan seluruh lagunya berhasil dihapus.');
    }
}
