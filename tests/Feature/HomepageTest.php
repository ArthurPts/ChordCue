<?php

use App\Models\Artist;
use App\Models\Category;
use App\Models\Song;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->artist = Artist::firstOrCreate(['name' => 'Sheila on 7']);
    $this->genre = Category::firstOrCreate(['name' => 'Pop', 'type' => 'genre']);

    $this->song = Song::create([
        'user_id' => $this->user->id,
        'title' => 'Dan',
        'artist_id' => $this->artist->id,
        'bpm' => 110,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);
    $this->song->categories()->sync([$this->genre->id]);
});

test('homepage can be rendered with song search and filters', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Temukan Lagu');
    $response->assertSee('Cari judul lagu atau artis...');
    $response->assertSee('Dan');
    $response->assertSee('Sheila on 7');
});

test('homepage filter by artist works', function () {
    $response = $this->get('/?q=Sheila');

    $response->assertStatus(200);
    $response->assertSee('Sheila on 7');
    $response->assertSee('Dan');
});
