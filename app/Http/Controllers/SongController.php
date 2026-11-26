<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Category;
use App\Models\Chord;
use App\Models\Song;
use App\Services\BeatGridBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
        $song->loadMissing([
            'artist',
            'categories',
            'sections' => function ($sectionQuery) {
                $sectionQuery->orderBy('sequence');
                $sectionQuery->with([
                    'lines' => function ($lineQuery) {
                        $lineQuery->orderBy('line_number');
                        $lineQuery->with('placements.chord');
                    },
                ]);
            },
        ]);

        // 3. Response / Render View
        return view('songs.player', [
            'song' => $song,
        ]);
    }

    /**
     * Simpan lagu baru ke database.
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /songs (songs.store)
     * - resources/views/components/songs/create-song-modal.blade.php
     */
    public function store(Request $request)
    {
        // 1. Otorisasi & Validasi Input
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'artist' => ['nullable', 'string', 'max:255'],
            'default_key' => ['nullable', 'string', 'max:45'],
            'bpm' => 'required|integer|min:40|max:240',
            'time_signature' => 'required|string|in:4/4,3/4,2/4,6/8',
            'visibility' => 'required|in:public,private',
        ], [
            'title.required' => 'Judul lagu wajib diisi.',
            'default_key.max' => 'Nada dasar maksimal 45 karakter.',
            'bpm.required' => 'BPM wajib diisi.',
            'bpm.integer' => 'BPM harus berupa angka.',
            'bpm.min' => 'BPM minimal 40.',
            'bpm.max' => 'BPM maksimal 240.',
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

        // 2. Query Database
        $trimmedArtist = isset($validated['artist']) ? trim($validated['artist']) : '';
        $artistId = null;
        if ($trimmedArtist !== '') {
            $artist = Artist::firstOrCreate([
                'name' => $trimmedArtist,
            ]);
            $artistId = $artist->id;
        }

        // Pecah time signature (misal 4/4) menjadi numerator dan denominator
        [$numerator, $denominator] = explode('/', $validated['time_signature']);
        $trimmedKey = isset($validated['default_key']) ? trim($validated['default_key']) : '';

        $song = Song::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'artist_id' => $artistId,
            'default_key' => $trimmedKey !== '' ? $trimmedKey : null,
            'bpm' => (int) $validated['bpm'],
            'time_signature_numerator' => (int) $numerator,
            'time_signature_denominator' => (int) $denominator,
            'visibility' => $validated['visibility'],
        ]);

        // 3. Response / Render View
        return redirect()->route('my.songs')->with('status', 'Lagu "' . $song->title . '" berhasil dibuat.');
    }

    /**
     * Menampilkan halaman Simple Editor untuk mengelola metadata, section,
     * baris lirik, penempatan chord pada huruf, dan backing track lagu.
     *
     * Fungsi ini digunakan pada:
     * - Route: GET /songs/{song}/editor/simple (songs.editor.simple)
     * - Route: GET /songs/{song}/edit (songs.edit)
     * - resources/views/components/song-card.blade.php
     * - resources/views/admin/songs/index.blade.php
     * - resources/views/songs/player.blade.php
     */
    public function editorSimple(Song $song): View
    {
        // 1. Otorisasi & Validasi Input
        Gate::authorize('update', $song);

        // 2. Query Database
        $viewData = $this->prepareEditorViewData($song, 'position');

        // 3. Response / Render View
        return view('songs.editor-simple', $viewData);
    }

    /**
     * Menampilkan halaman Beat Editor untuk mengatur ketukan global (start_beat)
     * per baris lirik berbasis grid birama berlapis (§7.2) beserta kontrol metadata dan audio.
     *
     * Fungsi ini digunakan pada:
     * - Route: GET /songs/{song}/editor/beat (songs.editor.beat)
     * - resources/views/songs/editor-simple.blade.php (tombol switch Beat Editor)
     */
    public function editorBeat(Song $song, BeatGridBuilder $beatGridBuilder): View
    {
        // 1. Otorisasi & Validasi Input
        Gate::authorize('update', $song);

        // 2. Query Database
        $viewData = $this->prepareEditorViewData($song, 'start_beat');
        $beatsPerBar = max(1, (int) ($song->time_signature_numerator ?? 4));
        $viewData['beatGrid'] = $beatGridBuilder->buildForSections($viewData['editorData']['sections'], $beatsPerBar);

        // 3. Response / Render View
        return view('songs.editor-beat', $viewData);
    }

    /**
     * Menyusun payload data bersama untuk halaman Simple Editor dan Beat Editor.
     *
     * Fungsi ini digunakan pada:
     * - SongController::editorSimple()
     * - SongController::editorBeat()
     *
     * @return array<string, mixed>
     */
    private function prepareEditorViewData(Song $song, string $orderColumn = 'position'): array
    {
        $allowedOrder = in_array($orderColumn, ['position', 'start_beat'], true) ? $orderColumn : 'position';

        $song->load([
            'artist',
            'categories',
            'sections' => function ($sectionQuery) use ($allowedOrder) {
                $sectionQuery->orderBy('sequence');
                $sectionQuery->with(['lines' => function ($lineQuery) use ($allowedOrder) {
                    $lineQuery->orderBy('line_number');
                    $lineQuery->with(['placements' => function ($placementQuery) use ($allowedOrder) {
                        $placementQuery->orderByRaw("CASE WHEN {$allowedOrder} IS NULL THEN 1 ELSE 0 END")
                            ->orderBy($allowedOrder)
                            ->orderBy('id')
                            ->with('chord');
                    }]);
                }]);
            },
        ]);

        $chords = Chord::orderBy('name')->get(['id', 'name']);
        $artists = Artist::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('type')->orderBy('name')->get(['id', 'name', 'type']);

        $sectionsData = [];
        foreach ($song->sections as $section) {
            $linesData = [];
            foreach ($section->lines as $line) {
                $placementsData = [];
                foreach ($line->placements as $placement) {
                    $placementsData[] = [
                        'id'            => $placement->id,
                        'lyric_line_id' => $placement->lyric_line_id,
                        'chord_id'      => $placement->chord_id,
                        'chord_name'    => $placement->chord ? $placement->chord->name : '',
                        'position'      => $placement->position,
                        'start_beat'    => $placement->start_beat,
                    ];
                }

                $linesData[] = [
                    'id'              => $line->id,
                    'song_section_id' => $line->song_section_id,
                    'line_number'     => $line->line_number,
                    'content'         => $line->content,
                    'placements'      => $placementsData,
                ];
            }

            $sectionsData[] = [
                'id'       => $section->id,
                'song_id'  => $section->song_id,
                'name'     => $section->name,
                'sequence' => $section->sequence,
                'lines'    => $linesData,
            ];
        }

        $backingTrackUrl = null;
        if ($song->backing_track_path && Storage::disk('public')->exists($song->backing_track_path)) {
            $backingTrackUrl = Storage::disk('public')->url($song->backing_track_path);
        }

        $chordsData = [];
        foreach ($chords as $chord) {
            $chordsData[] = [
                'id'   => $chord->id,
                'name' => $chord->name,
            ];
        }

        $categoriesData = [];
        foreach ($categories as $category) {
            $categoriesData[] = [
                'id'   => $category->id,
                'name' => $category->name,
                'type' => $category->type,
            ];
        }

        $songCategoryIds = [];
        foreach ($song->categories as $category) {
            $songCategoryIds[] = (int) $category->id;
        }

        $editorData = [
            'song' => [
                'id'                   => $song->id,
                'title'                => $song->title,
                'artist'               => $song->artist ? $song->artist->name : '',
                'default_key'          => $song->default_key ?? '',
                'bpm'                  => (int) $song->bpm,
                'time_signature'       => ($song->time_signature_numerator ?? 4) . '/' . ($song->time_signature_denominator ?? 4),
                'visibility'           => $song->visibility,
                'category_ids'         => $songCategoryIds,
                'backing_track_path'   => $song->backing_track_path,
                'backing_track_url'    => $backingTrackUrl,
                'backing_track_offset' => $song->backing_track_offset !== null ? (float) $song->backing_track_offset : 0.0,
            ],
            'sections'   => $sectionsData,
            'chords'     => $chordsData,
            'categories' => $categoriesData,
        ];

        return [
            'song'       => $song,
            'chords'     => $chords,
            'artists'    => $artists,
            'categories' => $categories,
            'editorData' => $editorData,
        ];
    }

    /**
     * Memperbarui metadata lagu (judul, artis, nada dasar, BPM, time signature, visibilitas)
     * serta opsional berkas backing track MP3 dalam satu aksi simpan.
     *
     * Fungsi ini digunakan pada:
     * - Route: PATCH /songs/{song} (songs.update)
     * - resources/views/songs/editor-simple.blade.php
     */
    public function update(Request $request, Song $song): JsonResponse|RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        Gate::authorize('update', $song);

        $maxMb = (int) config('chordcue.backing_track_max_mb', 15);
        $maxKb = $maxMb * 1024;

        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:120'],
            'artist'          => ['nullable', 'string', 'max:255'],
            'default_key'     => ['nullable', 'string', 'max:45'],
            'bpm'             => ['required', 'integer', 'min:40', 'max:240'],
            'time_signature'  => ['required', 'string', 'in:4/4,3/4,2/4,6/8'],
            'visibility'      => ['required', 'in:public,private'],
            'backing_track'   => ['nullable', 'file', 'mimes:mp3', 'max:' . $maxKb],
            'sync_categories' => ['nullable', 'boolean'],
            'category_ids'    => ['nullable', 'array'],
            'category_ids.*'  => ['integer', 'exists:categories,id'],
        ], [
            'title.required'          => 'Judul lagu wajib diisi.',
            'title.max'               => 'Judul lagu maksimal 120 karakter.',
            'default_key.max'         => 'Nada dasar maksimal 45 karakter.',
            'bpm.required'            => 'BPM wajib diisi.',
            'bpm.integer'             => 'BPM harus berupa angka.',
            'bpm.min'                 => 'BPM minimal 40.',
            'bpm.max'                 => 'BPM maksimal 240.',
            'time_signature.required' => 'Time signature wajib dipilih.',
            'time_signature.in'       => 'Time signature hanya mendukung 4/4, 3/4, 2/4, atau 6/8.',
            'visibility.required'     => 'Visibilitas lagu wajib dipilih.',
            'visibility.in'           => 'Visibilitas lagu harus berupa public atau private.',
            'backing_track.file'      => 'Unggahan harus berupa berkas yang valid.',
            'backing_track.mimes'     => 'Format backing track harus MP3.',
            'backing_track.max'       => 'Ukuran backing track maksimal ' . $maxMb . ' MB.',
            'category_ids.array'      => 'Format kategori tidak valid.',
            'category_ids.*.exists'   => 'Kategori yang dipilih tidak ditemukan.',
        ]);

        // 2. Query Database
        $trimmedArtist = isset($validated['artist']) ? trim($validated['artist']) : '';
        $artist = null;
        if ($trimmedArtist !== '') {
            $artist = Artist::firstOrCreate([
                'name' => $trimmedArtist,
            ]);
        }

        [$numerator, $denominator] = explode('/', $validated['time_signature']);
        $trimmedKey = isset($validated['default_key']) ? trim($validated['default_key']) : '';

        $updateData = [
            'title'                      => trim($validated['title']),
            'artist_id'                  => $artist ? $artist->id : null,
            'default_key'                => $trimmedKey !== '' ? $trimmedKey : null,
            'bpm'                        => (int) $validated['bpm'],
            'time_signature_numerator'   => (int) $numerator,
            'time_signature_denominator' => (int) $denominator,
            'visibility'                 => $validated['visibility'],
        ];

        if ($request->hasFile('backing_track')) {
            if ($song->backing_track_path && Storage::disk('public')->exists($song->backing_track_path)) {
                Storage::disk('public')->delete($song->backing_track_path);
            }

            $storedPath = $request->file('backing_track')->store('backing-tracks', 'public');
            $updateData['backing_track_path'] = $storedPath;

            if ($song->backing_track_offset === null) {
                $updateData['backing_track_offset'] = 0.000;
            }
        }

        $song->update($updateData);

        if ($request->boolean('sync_categories') || array_key_exists('category_ids', $validated)) {
            $syncIds = [];
            foreach ($validated['category_ids'] ?? [] as $catId) {
                $syncIds[] = (int) $catId;
            }
            $song->categories()->sync($syncIds);
        }

        $backingTrackUrl = null;
        if ($song->backing_track_path && Storage::disk('public')->exists($song->backing_track_path)) {
            $backingTrackUrl = Storage::disk('public')->url($song->backing_track_path);
        }

        $updatedCategoryIds = [];
        foreach ($song->categories()->pluck('categories.id') as $catId) {
            $updatedCategoryIds[] = (int) $catId;
        }

        // 3. Response / Render View
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Perubahan lagu berhasil disimpan.',
                'song'    => [
                    'id'                   => $song->id,
                    'title'                => $song->title,
                    'artist'               => $artist ? $artist->name : '',
                    'default_key'          => $song->default_key ?? '',
                    'bpm'                  => (int) $song->bpm,
                    'time_signature'       => $song->time_signature_numerator . '/' . $song->time_signature_denominator,
                    'visibility'           => $song->visibility,
                    'category_ids'         => $updatedCategoryIds,
                    'backing_track_path'   => $song->backing_track_path,
                    'backing_track_url'    => $backingTrackUrl,
                    'backing_track_offset' => $song->backing_track_offset !== null ? (float) $song->backing_track_offset : 0.0,
                ],
            ]);
        }

        return redirect()->route('songs.editor.simple', $song)->with('status', 'Perubahan lagu berhasil disimpan.');
    }

    /**
     * Hapus lagu beserta file backing track fisiknya.
     *
     * Fungsi ini digunakan pada:
     * - Route: DELETE /songs/{song} (songs.destroy)
     * - resources/views/components/songs/song-card.blade.php
     * - resources/views/admin/songs/index.blade.php
     */
    public function destroy(Song $song): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        Gate::authorize('delete', $song);

        // 2. Query Database
        $song->deleteWithFiles();

        // 3. Response / Render View
        return back()->with('status', 'Lagu berhasil dihapus.');
    }
}
