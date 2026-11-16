<?php

use App\Models\Artist;
use App\Models\Category;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->artist = Artist::create(['name' => 'Artis Uji Editor']);

    $this->owner = User::create([
        'username'   => 'editorowner',
        'email'      => 'editorowner@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 10,
    ]);

    $this->otherUser = User::create([
        'username'   => 'editorother',
        'email'      => 'editorother@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 10,
    ]);

    $this->admin = User::create([
        'username'   => 'editoradmin',
        'email'      => 'editoradmin@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'admin',
        'song_limit' => 50,
    ]);

    $this->chordC = Chord::create([
        'name'          => 'C',
        'pronunciation' => 'C',
        'file_path'     => 'chords/audio/c.mp3',
    ]);

    $this->chordG = Chord::create([
        'name'          => 'G',
        'pronunciation' => 'G',
        'file_path'     => 'chords/audio/g.mp3',
    ]);

    $this->chordAm = Chord::create([
        'name'          => 'Am',
        'pronunciation' => 'Ae minor',
        'file_path'     => 'chords/audio/am.mp3',
    ]);

    $this->song = Song::create([
        'user_id'                    => $this->owner->id,
        'title'                      => 'Lagu Simple Editor',
        'artist_id'                  => $this->artist->id,
        'default_key'                => 'C',
        'bpm'                        => 100,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
        'backing_track_offset'       => 2.350,
    ]);

    $this->section = SongSection::create([
        'song_id'  => $this->song->id,
        'name'     => 'Verse 1',
        'sequence' => 1,
    ]);

    $this->line = LyricLine::create([
        'song_section_id' => $this->section->id,
        'line_number'     => 1,
        'content'         => 'Ku berjalan di jalan sunyi',
    ]);
});

test('otorisasi melindungi akses halaman simple editor untuk tamu, non-pemilik, pemilik, dan admin', function () {
    $this->get(route('songs.editor.simple', $this->song))
        ->assertRedirect(route('login'));

    $this->actingAs($this->otherUser)
        ->get(route('songs.editor.simple', $this->song))
        ->assertForbidden();

    $this->actingAs($this->owner)
        ->get(route('songs.editor.simple', $this->song))
        ->assertOk()
        ->assertSee('Editor Simple')
        ->assertSee('Pustaka Chord');

    $this->actingAs($this->admin)
        ->get(route('songs.edit', $this->song))
        ->assertOk()
        ->assertSee('Editor Simple');
});

test('tautan editor di lagu saya, player, dan admin kelola lagu mengarah ke songs.editor.simple', function () {
    $expectedUrl = route('songs.editor.simple', $this->song);

    $this->actingAs($this->owner)
        ->get(route('my.songs'))
        ->assertOk()
        ->assertSee($expectedUrl);

    $this->actingAs($this->owner)
        ->get(route('songs.show', $this->song))
        ->assertOk()
        ->assertSee($expectedUrl);

    $this->actingAs($this->admin)
        ->get(route('admin.songs.index'))
        ->assertOk()
        ->assertSee($expectedUrl);
});

test('halaman simple editor merender tombol nudge inline, dropdown birama valid, panel chord belum digunakan, dan patuh design rules', function () {
    $response = $this->actingAs($this->owner)->get(route('songs.editor.simple', $this->song));
    $response->assertOk();

    // Navbar asli, header editor, dan tombol nudge inline sebaris
    $response->assertSee('Pustaka Chord');
    $response->assertSee('Chord yang belum digunakan');
    $response->assertSee('-1s');
    $response->assertSee('-100ms');
    $response->assertSee('-10ms');
    $response->assertSee('+10ms');
    $response->assertSee('+100ms');
    $response->assertSee('+1s');
    $response->assertSee('Reset');
    $response->assertSee('step="0.001"', false);
    $response->assertSee('min="0"', false);

    // Dropdown time signature hanya 4 opsi & tanpa 12/8
    $response->assertSee('<option value="4/4">4/4</option>', false);
    $response->assertSee('<option value="3/4">3/4</option>', false);
    $response->assertSee('<option value="2/4">2/4</option>', false);
    $response->assertSee('<option value="6/8">6/8</option>', false);
    $response->assertDontSee('<option value="12/8">', false);

    // Tidak ada tombol "Lepas" pada placement di Simple Editor (hanya Hapus)
    $blade = file_get_contents(resource_path('views/songs/editor-simple.blade.php'));
    $js = file_get_contents(resource_path('js/simple-editor.js'));

    expect($blade)->not->toContain('Lepas');
    expect($blade)->not->toContain('Unggah Audio');
    expect($blade)->not->toContain(' controls ');
    expect($blade)->toContain('togglePreviewAudio()');
    expect($blade)->toContain('backingAudioEl');
    expect($blade)->toContain('Atur Kategori');
    expect($blade)->toContain('toggleCategory(');
    expect($blade)->toContain('accept=".mp3,audio/mpeg"');
    expect($blade)->toContain('x-show="unusedPlacements.length > 0"');
    expect($blade)->not->toContain('style=');
    expect($blade)->not->toContain('rounded-xl');
    expect($blade)->not->toContain('rounded-2xl');
    expect($blade)->not->toContain('__(');
    expect($blade)->toContain('font-mono');
    expect($js)->toContain('Math.max(0');
});

test('pemilik lagu dapat memperbarui metadata lagu sekaligus backing track MP3 melalui PATCH /songs/{song}', function () {
    Storage::fake('public');

    // Non-pemilik ditolak
    $this->actingAs($this->otherUser)
        ->patchJson(route('songs.update', $this->song), [
            'title'          => 'Dibajak',
            'artist'         => 'Artis Lain',
            'default_key'    => 'G',
            'bpm'            => 120,
            'time_signature' => '4/4',
            'visibility'     => 'private',
        ])
        ->assertForbidden();

    // Validasi BPM di luar 40-240, birama 12/8, dan audio non-MP3 ditolak
    $wavFile = UploadedFile::fake()->create('ditolak.wav', 500, 'audio/wav');
    $this->actingAs($this->owner)
        ->patchJson(route('songs.update', $this->song), [
            'title'          => 'Judul Valid',
            'artist'         => 'Artis Baru',
            'default_key'    => 'Am',
            'bpm'            => 39,
            'time_signature' => '12/8',
            'visibility'     => 'public',
            'backing_track'  => $wavFile,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bpm', 'time_signature', 'backing_track']);

    // Update metadata + upload MP3 dalam satu panggilan Simpan (PATCH) berhasil
    $mp3File = UploadedFile::fake()->create('combined-save.mp3', 500, 'audio/mpeg');
    $response = $this->actingAs($this->owner)
        ->patchJson(route('songs.update', $this->song), [
            'title'          => 'Lagu Setelah Diedit',
            'artist'         => 'Artis Baru Editor',
            'default_key'    => 'F#m',
            'bpm'            => 128,
            'time_signature' => '6/8',
            'visibility'     => 'private',
            'backing_track'  => $mp3File,
        ]);

    $response->assertOk()->assertJson([
        'success' => true,
        'song'    => [
            'title'          => 'Lagu Setelah Diedit',
            'artist'         => 'Artis Baru Editor',
            'default_key'    => 'F#m',
            'bpm'            => 128,
            'time_signature' => '6/8',
            'visibility'     => 'private',
        ],
    ]);

    $fresh = $this->song->fresh();
    expect($fresh->title)->toBe('Lagu Setelah Diedit');
    expect($fresh->default_key)->toBe('F#m');
    expect($fresh->bpm)->toBe(128);
    expect($fresh->time_signature_numerator)->toBe(6);
    expect($fresh->time_signature_denominator)->toBe(8);
    expect($fresh->visibility)->toBe('private');
    expect($fresh->backing_track_path)->not->toBeNull();
    Storage::disk('public')->assertExists($fresh->backing_track_path);
});

test('pemilik lagu dapat mengunggah MP3, mengganti MP3, dan menghapus file backing track beserta validasi MP3 saja', function () {
    Storage::fake('public');

    // File non-audio & WAV non-MP3 ditolak
    $invalidFile = UploadedFile::fake()->create('dokumen.txt', 50, 'text/plain');
    $this->actingAs($this->owner)
        ->postJson(route('songs.backing-track.store', $this->song), [
            'backing_track' => $invalidFile,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['backing_track']);

    $wavFile = UploadedFile::fake()->create('bukan-mp3.wav', 600, 'audio/wav');
    $this->actingAs($this->owner)
        ->postJson(route('songs.backing-track.store', $this->song), [
            'backing_track' => $wavFile,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['backing_track']);

    // Upload MP3 valid berhasil
    $mp3File = UploadedFile::fake()->create('backing-1.mp3', 500, 'audio/mpeg');
    $uploadResponse = $this->actingAs($this->owner)
        ->postJson(route('songs.backing-track.store', $this->song), [
            'backing_track' => $mp3File,
            'offset'        => 4.250,
        ]);

    $uploadResponse->assertOk()->assertJson([
        'success'              => true,
        'backing_track_offset' => 4.25,
    ]);

    $firstPath = $this->song->fresh()->backing_track_path;
    expect($firstPath)->not->toBeNull();
    Storage::disk('public')->assertExists($firstPath);

    // Ganti dengan MP3 baru melalui PATCH gabungan menghapus file lama
    $secondMp3File = UploadedFile::fake()->create('backing-2.mp3', 600, 'audio/mpeg');
    $this->actingAs($this->owner)
        ->patchJson(route('songs.update', $this->song), [
            'title'          => $this->song->title,
            'artist'         => $this->song->artist->name,
            'default_key'    => $this->song->default_key,
            'bpm'            => $this->song->bpm,
            'time_signature' => '4/4',
            'visibility'     => 'public',
            'backing_track'  => $secondMp3File,
        ])
        ->assertOk();

    $secondPath = $this->song->fresh()->backing_track_path;
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);

    // Hapus backing track membersihkan file & offset
    $this->actingAs($this->owner)
        ->deleteJson(route('songs.backing-track.destroy', $this->song))
        ->assertOk()
        ->assertJson([
            'success'            => true,
            'backing_track_path' => null,
        ]);

    Storage::disk('public')->assertMissing($secondPath);
    expect($this->song->fresh()->backing_track_path)->toBeNull();
    expect($this->song->fresh()->backing_track_offset)->toBeNull();
});

test('crud section dan baris lirik berjalan serta pemendekan lirik menerapkan aturan null atau hapus placement', function () {
    // Tambah section
    $secRes = $this->actingAs($this->owner)
        ->postJson(route('songs.sections.store', $this->song), [
            'name' => 'Chorus',
        ]);
    $secRes->assertCreated();
    $newSectionId = $secRes->json('section.id');

    // Ubah nama section
    $this->actingAs($this->owner)
        ->patchJson(route('sections.update', $newSectionId), [
            'name' => 'Reff',
        ])
        ->assertOk();
    expect(SongSection::find($newSectionId)->name)->toBe('Reff');

    // Tambah baris lirik
    $lineRes = $this->actingAs($this->owner)
        ->postJson(route('sections.lines.store', $newSectionId), [
            'content' => 'Aku cinta musik',
        ]);
    $lineRes->assertCreated();
    $newLineId = $lineRes->json('line.id');

    // Buat 3 placement pada baris baru:
    // 1) di dalam panjang baru (pos 1, beat null) -> tetap bertahan
    $pKeep = ChordPlacement::create([
        'lyric_line_id' => $newLineId,
        'chord_id'      => $this->chordC->id,
        'position'      => 1,
        'start_beat'    => null,
    ]);
    // 2) di luar panjang baru DENGAN start_beat (pos 10, beat 5) -> position menjadi null, tetap bertahan untuk Beat Editor
    $pKeepBeat = ChordPlacement::create([
        'lyric_line_id' => $newLineId,
        'chord_id'      => $this->chordG->id,
        'position'      => 10,
        'start_beat'    => 5,
    ]);
    // 3) di luar panjang baru TANPA start_beat (pos 12, beat null) -> dihapus total!
    $pDelete = ChordPlacement::create([
        'lyric_line_id' => $newLineId,
        'chord_id'      => $this->chordAm->id,
        'position'      => 12,
        'start_beat'    => null,
    ]);

    // Pendekkan lirik menjadi panjang 4 ("Aku!")
    $this->actingAs($this->owner)
        ->patchJson(route('lines.update', $newLineId), [
            'content' => 'Aku!',
        ])
        ->assertOk();

    expect($pKeep->fresh()->position)->toBe(1);
    expect($pKeepBeat->fresh()->position)->toBeNull();
    expect($pKeepBeat->fresh()->start_beat)->toBe(5);
    expect(ChordPlacement::find($pDelete->id))->toBeNull();

    // Hapus baris & section
    $this->actingAs($this->owner)->deleteJson(route('lines.update', $newLineId))->assertOk();
    expect(LyricLine::find($newLineId))->toBeNull();

    $this->actingAs($this->owner)->deleteJson(route('sections.destroy', $newSectionId))->assertOk();
    expect(SongSection::find($newSectionId))->toBeNull();
});

test('penempatan, pemindahan independen, deteksi tabrakan 409, timpa paksa, lepas, dan hapus chord placement bekerja atomik', function () {
    // Buat placement awal (position 0, start_beat null)
    $createRes = $this->actingAs($this->owner)
        ->postJson(route('lines.placements.store', $this->line), [
            'chord_id' => $this->chordC->id,
            'position' => 0,
        ]);
    $createRes->assertCreated();
    $placementAId = $createRes->json('placement.id');

    // Simulasikan placement A sudah memiliki start_beat = 1 dari Beat Editor
    ChordPlacement::where('id', $placementAId)->update(['start_beat' => 1]);

    // Memindahkan position TIDAK BOLEH menghapus start_beat (§5.4)
    $this->actingAs($this->owner)
        ->patchJson(route('placements.update', $placementAId), [
            'position' => 3,
        ])
        ->assertOk()
        ->assertJson([
            'placement' => [
                'position'   => 3,
                'start_beat' => 1,
            ],
        ]);

    // Taruh chord G di position 3 tanpa force -> 409 Conflict
    $conflictRes = $this->actingAs($this->owner)
        ->postJson(route('lines.placements.store', $this->line), [
            'chord_id' => $this->chordG->id,
            'position' => 3,
        ]);
    $conflictRes->assertStatus(409)
        ->assertJsonPath('conflict.id', $placementAId);

    // Dengan force: true -> placement lama terhapus, placement baru tersimpan di position 3
    $overwriteRes = $this->actingAs($this->owner)
        ->postJson(route('lines.placements.store', $this->line), [
            'chord_id' => $this->chordG->id,
            'position' => 3,
            'force'    => true,
        ]);
    $overwriteRes->assertCreated();
    $placementBId = $overwriteRes->json('placement.id');

    expect(ChordPlacement::find($placementAId))->toBeNull();
    expect(ChordPlacement::find($placementBId)->position)->toBe(3);

    // Endpoint release mengosongkan position tanpa menyentuh start_beat
    ChordPlacement::where('id', $placementBId)->update(['start_beat' => 9]);
    $this->actingAs($this->owner)
        ->postJson(route('placements.release', $placementBId), [
            'coordinate' => 'position',
        ])
        ->assertOk()
        ->assertJson([
            'placement' => [
                'position'   => null,
                'start_beat' => 9,
            ],
        ]);

    // Hapus placement seutuhnya
    $this->actingAs($this->owner)
        ->deleteJson(route('placements.destroy', $placementBId))
        ->assertOk();
    expect(ChordPlacement::find($placementBId))->toBeNull();
});

test('migrasi normalisasi time signature mengubah lagu 12/8 menjadi 6/8 dan mempertahankan birama valid', function () {
    $song128 = Song::create([
        'user_id'                    => $this->owner->id,
        'title'                      => 'Lagu Birama 12/8',
        'artist_id'                  => $this->artist->id,
        'bpm'                        => 85,
        'time_signature_numerator'   => 12,
        'time_signature_denominator' => 8,
        'visibility'                 => 'public',
    ]);

    $songOther = Song::create([
        'user_id'                    => $this->owner->id,
        'title'                      => 'Lagu Birama 5/4',
        'artist_id'                  => $this->artist->id,
        'bpm'                        => 95,
        'time_signature_numerator'   => 5,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
    ]);

    $migration = require database_path('migrations/2026_11_15_000001_normalize_unsupported_time_signatures_in_songs_table.php');
    $migration->up();

    expect($song128->fresh()->time_signature_numerator)->toBe(6);
    expect($song128->fresh()->time_signature_denominator)->toBe(8);

    expect($songOther->fresh()->time_signature_numerator)->toBe(4);
    expect($songOther->fresh()->time_signature_denominator)->toBe(4);

    // Lagu 4/4 awal tidak berubah
    expect($this->song->fresh()->time_signature_numerator)->toBe(4);
    expect($this->song->fresh()->time_signature_denominator)->toBe(4);
});

test('chord yang baru ditempatkan di simple editor (punya position, start_beat null) langsung tampil di timeline player', function () {
    $chordUnbeated = Chord::firstOrCreate(
        ['name' => 'F#m7'],
        ['pronunciation' => 'F sharp minor seven', 'file_path' => null]
    );

    ChordPlacement::create([
        'lyric_line_id' => $this->line->id,
        'chord_id'      => $chordUnbeated->id,
        'position'      => 2,
        'start_beat'    => null,
    ]);

    $response = $this->actingAs($this->owner)
        ->getJson(route('songs.timeline', $this->song));

    $response->assertOk();
    $data = $response->json();

    expect($data['audio_ready'])->toBeTrue();
    expect($data['pending_chords'])->toBe(0);
    $placements = $data['sections'][0]['lines'][0]['placements'];
    expect($placements)->toHaveCount(1);
    expect($placements[0]['chord']['name'])->toBe('F#m7');
    expect($placements[0]['position'])->toBe(2);
    expect($placements[0]['start_beat'])->toBeNull();
});

test('pemilik lagu dapat melihat, menetapkan, mengubah, dan mengosongkan kategori lagu di simple editor', function () {
    $catGenre = Category::create(['name' => 'Indie Folk', 'type' => 'genre']);
    $catMood = Category::create(['name' => 'Sendu', 'type' => 'mood']);
    $catLoc = Category::create(['name' => 'Indonesia', 'type' => 'location']);

    $this->song->categories()->sync([$catGenre->id]);

    // Halaman simple editor merender seluruh grup kategori beserta opsi yang ada
    $page = $this->actingAs($this->owner)
        ->get(route('songs.editor.simple', $this->song));
    $page->assertOk();
    $page->assertSee('Genre');
    $page->assertSee('Mood');
    $page->assertSee('Wilayah');
    $page->assertSee('Indie Folk');
    $page->assertSee('Sendu');
    $page->assertSee('Indonesia');

    // Validasi menolak ID kategori yang tidak ada
    $this->actingAs($this->owner)
        ->patchJson(route('songs.update', $this->song), [
            'title'           => $this->song->title,
            'artist'          => $this->artist->name,
            'default_key'     => $this->song->default_key,
            'bpm'             => $this->song->bpm,
            'time_signature'  => '4/4',
            'visibility'      => 'public',
            'sync_categories' => true,
            'category_ids'    => [999999],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_ids.0']);

    // Sinkronisasi kategori baru lewat tombol Simpan gabungan
    $updateRes = $this->actingAs($this->owner)
        ->patchJson(route('songs.update', $this->song), [
            'title'           => $this->song->title,
            'artist'          => $this->artist->name,
            'default_key'     => $this->song->default_key,
            'bpm'             => $this->song->bpm,
            'time_signature'  => '4/4',
            'visibility'      => 'public',
            'sync_categories' => true,
            'category_ids'    => [$catMood->id, $catLoc->id],
        ]);

    $updateRes->assertOk();
    $returnedIds = $updateRes->json('song.category_ids');
    sort($returnedIds);
    expect($returnedIds)->toBe([$catMood->id, $catLoc->id]);

    $dbIds = $this->song->fresh()->categories()->pluck('categories.id')->all();
    sort($dbIds);
    expect($dbIds)->toBe([$catMood->id, $catLoc->id]);

    // Mengosongkan semua kategori lagu berhasil
    $clearRes = $this->actingAs($this->owner)
        ->patchJson(route('songs.update', $this->song), [
            'title'           => $this->song->title,
            'artist'          => $this->artist->name,
            'default_key'     => $this->song->default_key,
            'bpm'             => $this->song->bpm,
            'time_signature'  => '4/4',
            'visibility'      => 'public',
            'sync_categories' => true,
            'category_ids'    => [],
        ]);

    $clearRes->assertOk();
    expect($clearRes->json('song.category_ids'))->toBe([]);
    expect($this->song->fresh()->categories()->count())->toBe(0);
});
