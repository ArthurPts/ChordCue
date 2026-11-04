<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\UserController;
use App\Models\Artist;
use App\Models\Category;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $artists = Artist::orderBy('name')->get();
    $genres = Category::where('type', 'genre')->orderBy('name')->get();
    $moods = Category::where('type', 'mood')->orderBy('name')->get();
    $locations = Category::where('type', 'location')->orderBy('name')->get();

    $query = Song::with(['artist', 'categories'])
        ->where('visibility', 'public');

    if ($request->filled('q')) {
        $search = $request->input('q');
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhereHas('artist', function ($artistQ) use ($search) {
                    $artistQ->where('name', 'like', "%{$search}%");
                });
        });
    }

    if ($request->filled('artist_id')) {
        $query->where('artist_id', $request->input('artist_id'));
    }

    if ($request->filled('genre')) {
        $query->whereHas('categories', function ($catQ) use ($request) {
            $catQ->where('categories.id', $request->input('genre'));
        });
    }

    if ($request->filled('mood')) {
        $query->whereHas('categories', function ($catQ) use ($request) {
            $catQ->where('categories.id', $request->input('mood'));
        });
    }

    if ($request->filled('location')) {
        $query->whereHas('categories', function ($catQ) use ($request) {
            $catQ->where('categories.id', $request->input('location'));
        });
    }

    $songs = $query->latest('id')->paginate(6)->withQueryString();

    return view('welcome', compact('songs', 'artists', 'genres', 'moods', 'locations'));
})->name('home');

Route::get('/dashboard', function () {
    return redirect()->route('home');
})->middleware(['auth'])->name('dashboard');

// Pustaka Chord (Dapat diakses Guest & Auth)
Route::get('/chords', function () {
    return view('chords.index');
})->name('chords.index');

Route::middleware('auth')->group(function () {
    // Profil Pengguna
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Lagu Saya (My Songs) - Hanya menampilkan lagu milik user yang login (berlaku sama untuk admin)
    Route::get('/my/songs', function (Request $request) {
        $hasAnySongs = Song::where('user_id', auth()->id())->exists();
        $visibility = $request->input('visibility', 'public');

        $query = Song::with(['artist', 'categories'])
            ->where('user_id', auth()->id());

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('artist', function ($artistQ) use ($search) {
                        $artistQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($visibility && in_array($visibility, ['public', 'private'])) {
            $query->where('visibility', $visibility);
        }

        $songs = $query->latest('id')->paginate(6)->withQueryString();

        return view('my-songs', compact('songs', 'visibility', 'hasAnySongs'));
    })->name('my.songs');

    // Song Actions
    Route::get('/songs/create', [SongController::class, 'create'])->name('songs.create');
    Route::get('/songs/{song}/edit', [SongController::class, 'edit'])->name('songs.edit');
    Route::delete('/songs/{song}', [SongController::class, 'destroy'])->name('songs.destroy');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Kelola User (Admin Only)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Kelola Lagu (Admin Only) - Tabel manajemen seluruh lagu publik dan privat
        Route::get('/songs', function (Request $request) {
            if (auth()->user()->role !== 'admin') {
                abort(403, 'Akses khusus administrator.');
            }

            $totalSongs = Song::count();
            $publicSongsCount = Song::where('visibility', 'public')->count();
            $privateSongsCount = Song::where('visibility', 'private')->count();

            $visibility = $request->input('visibility', 'all');

            $query = Song::with(['artist', 'user']);

            if ($request->filled('q')) {
                $search = $request->input('q');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhereHas('artist', function ($artistQ) use ($search) {
                            $artistQ->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('owner')) {
                $owner = $request->input('owner');
                $query->whereHas('user', function ($userQ) use ($owner) {
                    $userQ->where('username', 'like', "%{$owner}%");
                });
            }

            if ($visibility && in_array($visibility, ['public', 'private'])) {
                $query->where('visibility', $visibility);
            }

            $songs = $query->latest('id')->paginate(7)->withQueryString();

            return view('admin.songs.index', compact('songs', 'totalSongs', 'publicSongsCount', 'privateSongsCount', 'visibility'));
        })->name('songs.index');
    });
});

require __DIR__.'/auth.php';
