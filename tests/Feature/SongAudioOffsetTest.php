<?php

use App\Models\Artist;
use App\Models\Song;
use App\Models\User;

beforeEach(function () {
    $this->artist = Artist::create(['name' => 'Test Artist']);

    $this->owner = User::create([
        'username'   => 'songowner',
        'email'      => 'owner@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 10,
    ]);

    $this->otherUser = User::create([
        'username'   => 'otheruser',
        'email'      => 'other@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 10,
    ]);

    $this->admin = User::create([
        'username'   => 'adminuser',
        'email'      => 'admin@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'admin',
        'song_limit' => 10,
    ]);

    $this->song = Song::create([
        'user_id'                    => $this->owner->id,
        'title'                      => 'Lagu Uji Offset',
        'artist_id'                  => $this->artist->id,
        'bpm'                        => 90,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
        'backing_track_offset'       => 0.000,
    ]);
});

test('tamu (guest) ditolak saat mencoba memperbarui offset backing track', function () {
    $response = $this->patchJson(route('songs.backing-track.offset', $this->song), [
        'offset' => 1.500,
    ]);

    $response->assertUnauthorized();
});

test('pengguna bukan pemilik lagu ditolak dengan status 403', function () {
    $response = $this->actingAs($this->otherUser)->patchJson(route('songs.backing-track.offset', $this->song), [
        'offset' => 1.500,
    ]);

    $response->assertForbidden();
});

test('pemilik lagu berhasil memperbarui nilai offset backing track', function () {
    $response = $this->actingAs($this->owner)->patchJson(route('songs.backing-track.offset', $this->song), [
        'offset' => 2.350,
    ]);

    $response->assertOk()
        ->assertJson([
            'success'              => true,
            'backing_track_offset' => 2.35,
        ]);

    expect((float) $this->song->fresh()->backing_track_offset)->toBe(2.35);
});

test('pemilik lagu berhasil menyimpan offset negatif', function () {
    $response = $this->actingAs($this->owner)->patchJson(route('songs.backing-track.offset', $this->song), [
        'offset' => -0.250,
    ]);

    $response->assertOk()
        ->assertJson([
            'success'              => true,
            'backing_track_offset' => -0.25,
        ]);

    expect((float) $this->song->fresh()->backing_track_offset)->toBe(-0.25);
});

test('administrator dapat memperbarui offset backing track lagu milik siapa saja', function () {
    $response = $this->actingAs($this->admin)->patchJson(route('songs.backing-track.offset', $this->song), [
        'offset' => 4.120,
    ]);

    $response->assertOk()
        ->assertJson([
            'success'              => true,
            'backing_track_offset' => 4.12,
        ]);

    expect((float) $this->song->fresh()->backing_track_offset)->toBe(4.12);
});

test('validasi menolak nilai offset di luar rentang -10.000 hingga +10.000', function () {
    // Nilai di atas 10.000
    $tooHighResponse = $this->actingAs($this->owner)->patchJson(route('songs.backing-track.offset', $this->song), [
        'offset' => 10.001,
    ]);
    $tooHighResponse->assertUnprocessable()
        ->assertJsonValidationErrors(['offset']);

    // Nilai di bawah -10.000
    $tooLowResponse = $this->actingAs($this->owner)->patchJson(route('songs.backing-track.offset', $this->song), [
        'offset' => -10.001,
    ]);
    $tooLowResponse->assertUnprocessable()
        ->assertJsonValidationErrors(['offset']);
});

test('validasi menolak offset dengan presisi lebih dari 3 angka desimal', function () {
    $response = $this->actingAs($this->owner)->patchJson(route('songs.backing-track.offset', $this->song), [
        'offset' => 1.2345,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['offset']);
});

test('validasi menolak offset yang bukan berupa angka numerik', function () {
    $response = $this->actingAs($this->owner)->patchJson(route('songs.backing-track.offset', $this->song), [
        'offset' => 'bukan-angka',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['offset']);
});

