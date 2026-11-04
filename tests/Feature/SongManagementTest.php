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

