<?php

use App\Models\Artist;
use App\Models\Song;
use App\Models\User;

test('user with no songs sees empty placeholder on my songs page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/my/songs');

    $response->assertStatus(200);
    $response->assertSee('Anda Belum Memiliki Lagu Pribadi.');
    $response->assertSee('Ayo buat lagu pertamamu.');
    $response->assertSee('Buat Lagu');
    $response->assertDontSee('Judul Lagu Contoh');
});

test('user only sees their own songs on my songs page', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $artist = Artist::firstOrCreate(['name' => 'Artist Test']);

    $songA = Song::create([
        'user_id' => $userA->id,
        'title' => 'Lagu Milik User A',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $songB = Song::create([
        'user_id' => $userB->id,
        'title' => 'Lagu Milik User B',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $response = $this->actingAs($userA)->get('/my/songs');

    $response->assertStatus(200);
    $response->assertSee('Lagu Milik User A');
    $response->assertDontSee('Lagu Milik User B');
});

test('admin on my songs page only sees their own songs', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);
    $artist = Artist::firstOrCreate(['name' => 'Artist Test']);

    $adminSong = Song::create([
        'user_id' => $admin->id,
        'title' => 'Lagu Khusus Admin Sendiri',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $userSong = Song::create([
        'user_id' => $user->id,
        'title' => 'Lagu Milik User Lain',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $response = $this->actingAs($admin)->get('/my/songs');

    $response->assertStatus(200);
    $response->assertSee('Lagu Khusus Admin Sendiri');
    $response->assertDontSee('Lagu Milik User Lain');
});

test('regular user cannot access admin kelola lagu', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->get('/admin/songs');

    $response->assertStatus(403);
});

test('admin can access kelola lagu and view both public and private songs from any user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);
    $artist = Artist::firstOrCreate(['name' => 'Artist Test']);

    $publicSong = Song::create([
        'user_id' => $user->id,
        'title' => 'Lagu Publik User',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $privateSong = Song::create([
        'user_id' => $user->id,
        'title' => 'Lagu Privat User',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'private',
    ]);

    $response = $this->actingAs($admin)->get('/admin/songs');

    $response->assertStatus(200);
    $response->assertSee('Kelola Lagu');
    $response->assertSee('Lagu Publik User');
    $response->assertSee('Lagu Privat User');
});

test('user can delete their own song', function () {
    $user = User::factory()->create();
    $artist = Artist::firstOrCreate(['name' => 'Artist Test']);

    $song = Song::create([
        'user_id' => $user->id,
        'title' => 'Lagu Yang Akan Dihapus',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $response = $this->actingAs($user)->delete("/songs/{$song->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('songs', ['id' => $song->id]);
});

test('user cannot delete someone elses song', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $artist = Artist::firstOrCreate(['name' => 'Artist Test']);

    $songB = Song::create([
        'user_id' => $userB->id,
        'title' => 'Lagu User B Aman',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $response = $this->actingAs($userA)->delete("/songs/{$songB->id}");

    $response->assertStatus(403);
    $this->assertDatabaseHas('songs', ['id' => $songB->id]);
});

test('admin can delete any song', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);
    $artist = Artist::firstOrCreate(['name' => 'Artist Test']);

    $song = Song::create([
        'user_id' => $user->id,
        'title' => 'Lagu User Dihapus Admin',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $response = $this->actingAs($admin)->delete("/songs/{$song->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('songs', ['id' => $song->id]);
});

test('user can create a new song through modal submission', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu Baru Ciptaan User',
        'artist' => 'Artis Baru',
        'bpm' => 128,
        'time_signature' => '4/4',
        'visibility' => 'public',
    ]);

    $response->assertRedirect('/my/songs');
    $this->assertDatabaseHas('songs', [
        'user_id' => $user->id,
        'title' => 'Lagu Baru Ciptaan User',
        'bpm' => 128,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);
    $this->assertDatabaseHas('artists', [
        'name' => 'Artis Baru',
    ]);
});

test('song creation fails if required fields are missing while artist and default key remain optional', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->post('/songs', [
        'title' => '',
        'artist' => '',
        'default_key' => '',
    ]);

    $response->assertSessionHasErrors(['title', 'bpm', 'time_signature', 'visibility']);
    $response->assertSessionDoesntHaveErrors(['artist', 'default_key']);
});

test('song creation validates bpm boundary values (40 - 240)', function () {
    $user = User::factory()->create(['role' => 'user']);

    // BPM 39 gagal (di bawah batas minimum 40)
    $responseLow = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu BPM 39',
        'artist' => 'Artis',
        'bpm' => 39,
        'time_signature' => '4/4',
        'visibility' => 'public',
    ]);
    $responseLow->assertSessionHasErrors(['bpm']);

    // BPM 241 gagal (di atas batas maksimum 240)
    $responseHigh = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu BPM 241',
        'artist' => 'Artis',
        'bpm' => 241,
        'time_signature' => '4/4',
        'visibility' => 'public',
    ]);
    $responseHigh->assertSessionHasErrors(['bpm']);

    // BPM 40 berhasil (batas minimum)
    $responseMin = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu BPM 40',
        'artist' => 'Artis',
        'bpm' => 40,
        'time_signature' => '4/4',
        'visibility' => 'public',
    ]);
    $responseMin->assertSessionHasNoErrors();
    $this->assertDatabaseHas('songs', ['title' => 'Lagu BPM 40', 'bpm' => 40]);

    // BPM 240 berhasil (batas maksimum)
    $responseMax = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu BPM 240',
        'artist' => 'Artis',
        'bpm' => 240,
        'time_signature' => '4/4',
        'visibility' => 'public',
    ]);
    $responseMax->assertSessionHasNoErrors();
    $this->assertDatabaseHas('songs', ['title' => 'Lagu BPM 240', 'bpm' => 240]);
});

test('song creation validates allowed time signatures (4/4, 3/4, 2/4, 6/8) and rejects removed 12/8', function () {
    $user = User::factory()->create(['role' => 'user']);

    // 12/8 ditolak
    $response128 = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu 12/8',
        'artist' => 'Artis',
        'bpm' => 120,
        'time_signature' => '12/8',
        'visibility' => 'public',
    ]);
    $response128->assertSessionHasErrors(['time_signature']);

    // Nilai valid diterima
    foreach (['4/4', '3/4', '2/4', '6/8'] as $timeSignature) {
        $response = $this->actingAs($user)->post('/songs', [
            'title' => "Lagu Birama {$timeSignature}",
            'artist' => 'Artis',
            'bpm' => 120,
            'time_signature' => $timeSignature,
            'visibility' => 'public',
        ]);
        $response->assertSessionHasNoErrors();
        [$num, $den] = explode('/', $timeSignature);
        $this->assertDatabaseHas('songs', [
            'title' => "Lagu Birama {$timeSignature}",
            'time_signature_numerator' => (int) $num,
            'time_signature_denominator' => (int) $den,
        ]);
    }
});

test('user cannot exceed song quota limit', function () {
    $user = User::factory()->create(['role' => 'user', 'song_limit' => 1]);
    $artist = Artist::firstOrCreate(['name' => 'Artis Kuota']);

    // Buat 1 lagu sehingga kuota (1) penuh
    Song::create([
        'user_id' => $user->id,
        'title' => 'Lagu Pertama',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    // Coba buat lagu kedua
    $response = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu Kedua Melebihi Kuota',
        'artist' => 'Artis Kuota',
        'bpm' => 120,
        'time_signature' => '4/4',
        'visibility' => 'public',
    ]);

    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('songs', [
        'title' => 'Lagu Kedua Melebihi Kuota',
    ]);
});

test('admin can create songs without quota limit', function () {
    $admin = User::factory()->create(['role' => 'admin', 'song_limit' => 1]);
    $artist = Artist::firstOrCreate(['name' => 'Artis Admin']);

    Song::create([
        'user_id' => $admin->id,
        'title' => 'Lagu Pertama Admin',
        'artist_id' => $artist->id,
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $response = $this->actingAs($admin)->post('/songs', [
        'title' => 'Lagu Kedua Admin Tetap Bisa',
        'artist' => 'Artis Admin',
        'bpm' => 130,
        'time_signature' => '4/4',
        'visibility' => 'private',
    ]);

    $response->assertRedirect('/my/songs');
    $this->assertDatabaseHas('songs', [
        'title' => 'Lagu Kedua Admin Tetap Bisa',
    ]);
});

test('create song modal renders default key textbox beside optional artist on the same two-column row', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->get('/my/songs');

    $response->assertStatus(200);
    $response->assertSee('id="modal_song_artist"', false);
    $response->assertSee('id="modal_song_default_key"', false);
    $response->assertSee('name="default_key"', false);
    $response->assertSee('Nada Dasar');

    $modalContent = file_get_contents(resource_path('views/components/songs/create-song-modal.blade.php'));
    $artistPos = strpos($modalContent, 'id="modal_song_artist"');
    $keyPos = strpos($modalContent, 'id="modal_song_default_key"');
    $bpmPos = strpos($modalContent, 'id="modal_song_bpm"');
    expect($artistPos)->not->toBeFalse();
    expect($keyPos)->not->toBeFalse();
    expect($bpmPos)->not->toBeFalse();
    expect($artistPos)->toBeLessThan($keyPos);
    expect($keyPos)->toBeLessThan($bpmPos);

    // Default Key adalah textbox (<input type="text">), bukan dropdown (<select>), dan Artist/Default Key tidak required
    expect($modalContent)->toContain('id="modal_song_default_key"');
    expect($modalContent)->not->toContain('<select' . "\n" . '                                id="modal_song_default_key"');
    expect($modalContent)->toMatch('/<input[^>]*id="modal_song_default_key"[^>]*type="text"/s');
    expect($modalContent)->not->toMatch('/<input[^>]*id="modal_song_artist"[^>]*\brequired\b/s');
    expect($modalContent)->not->toMatch('/<input[^>]*id="modal_song_default_key"[^>]*\brequired\b/s');
});

test('user can create a new song with free-text default key and it initializes player, editor, and transpose', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu Nada Dasar F#',
        'artist' => 'Artis Nada',
        'default_key' => 'F#',
        'bpm' => 112,
        'time_signature' => '4/4',
        'visibility' => 'public',
    ]);

    $response->assertRedirect('/my/songs');
    $response->assertSessionHasNoErrors();

    $song = Song::where('title', 'Lagu Nada Dasar F#')->first();
    expect($song)->not->toBeNull();
    expect($song->default_key)->toBe('F#');

    // Player initializes with selected default_key
    $playerResponse = $this->actingAs($user)->get(route('songs.show', $song));
    $playerResponse->assertStatus(200);
    $playerResponse->assertSee('F#');
    $playerResponse->assertSee('Artis Nada');

    // Editor initializes with selected default_key
    $editorResponse = $this->actingAs($user)->get(route('songs.editor.simple', $song));
    $editorResponse->assertStatus(200);
    $editorResponse->assertSee('F#');

    // Timeline & transposition use default_key cleanly
    $timelineBase = $this->actingAs($user)->getJson(route('songs.timeline', $song));
    $timelineBase->assertOk()
        ->assertJsonPath('song.key', 'F#')
        ->assertJsonPath('song.artist', 'Artis Nada');

    $timelineTransposed = $this->actingAs($user)->getJson(route('songs.timeline', ['song' => $song, 'transpose' => 2]));
    $timelineTransposed->assertOk()->assertJsonPath('song.key', 'G#');
});

test('user can create songs without artist, without default key, or without both and empty/whitespace values persist as NULL', function () {
    $user = User::factory()->create(['role' => 'user']);

    // 1. Tanpa Artist (dengan Default Key)
    $noArtist = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu Tanpa Artist',
        'artist' => '   ',
        'default_key' => 'Bb',
        'bpm' => 100,
        'time_signature' => '4/4',
        'visibility' => 'public',
    ]);
    $noArtist->assertSessionHasNoErrors();
    $songNoArtist = Song::where('title', 'Lagu Tanpa Artist')->first();
    expect($songNoArtist)->not->toBeNull();
    expect($songNoArtist->artist_id)->toBeNull();
    expect($songNoArtist->default_key)->toBe('Bb');

    // 2. Tanpa Default Key (dengan Artist)
    $noKey = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu Tanpa Key',
        'artist' => 'Solois Bebas',
        'default_key' => '   ',
        'bpm' => 96,
        'time_signature' => '3/4',
        'visibility' => 'public',
    ]);
    $noKey->assertSessionHasNoErrors();
    $songNoKey = Song::where('title', 'Lagu Tanpa Key')->first();
    expect($songNoKey)->not->toBeNull();
    expect($songNoKey->artist->name)->toBe('Solois Bebas');
    expect($songNoKey->default_key)->toBeNull();

    // 3. Tanpa Artist maupun Default Key
    $neither = $this->actingAs($user)->post('/songs', [
        'title' => 'Lagu Tanpa Artist Dan Key',
        'artist' => '',
        'default_key' => '',
        'bpm' => 110,
        'time_signature' => '6/8',
        'visibility' => 'public',
    ]);
    $neither->assertSessionHasNoErrors();
    $songNeither = Song::where('title', 'Lagu Tanpa Artist Dan Key')->first();
    expect($songNeither)->not->toBeNull();
    expect($songNeither->artist_id)->toBeNull();
    expect($songNeither->default_key)->toBeNull();

    // Pastikan tidak ada tanda "-" yang tersimpan di DB
    expect(Artist::where('name', '-')->exists())->toBeFalse();
    expect(Artist::where('name', '')->exists())->toBeFalse();
});

