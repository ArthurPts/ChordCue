<?php

use App\Models\Artist;
use App\Models\Category;
use App\Models\Song;
use App\Models\User;

test('test all filters across homepage, my-songs, admin users, admin songs', function () {
    $admin = User::factory()->create(['role' => 'admin', 'username' => 'superboss']);
    $user = User::factory()->create(['role' => 'user', 'username' => 'ordinaryuser']);
    $artist = Artist::firstOrCreate(['name' => 'Kahitna']);
    $genre = Category::firstOrCreate(['name' => 'Jazz', 'type' => 'genre']);
    $mood = Category::firstOrCreate(['name' => 'Santai', 'type' => 'mood']);
    $loc = Category::firstOrCreate(['name' => 'Bandung', 'type' => 'location']);

    $song = Song::create([
        'user_id' => $user->id,
        'title' => 'Cantik',
        'artist_id' => $artist->id,
        'bpm' => 95,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);
    $song->categories()->sync([$genre->id, $mood->id, $loc->id]);

    // 1. Homepage filters
    $resHomeQ = $this->get('/?q=Cantik');
    $resHomeQ->assertStatus(200)->assertSee('Cantik');

    $resHomeArtist = $this->get('/?artist_id=' . $artist->id);
    $resHomeArtist->assertStatus(200)->assertSee('Cantik');

    $resHomeGenre = $this->get('/?genre=' . $genre->id);
    $resHomeGenre->assertStatus(200)->assertSee('Cantik');

    $resHomeMood = $this->get('/?mood=' . $mood->id);
    $resHomeMood->assertStatus(200)->assertSee('Cantik');

    $resHomeLoc = $this->get('/?location=' . $loc->id);
    $resHomeLoc->assertStatus(200)->assertSee('Cantik');

    // 2. My Songs filters
    $resMyQ = $this->actingAs($user)->get('/my/songs?q=Cantik');
    $resMyQ->assertStatus(200)->assertSee('Cantik');

    $resMyPub = $this->actingAs($user)->get('/my/songs?visibility=public');
    $resMyPub->assertStatus(200)->assertSee('Cantik');

    // 3. Admin Users filters
    $resUsersQ = $this->actingAs($admin)->get('/admin/users?q=ordinaryuser');
    $resUsersQ->assertStatus(200);
    expect($resUsersQ->viewData('users')->pluck('username'))->toContain('ordinaryuser');

    $resUsersRole = $this->actingAs($admin)->get('/admin/users?role=member');
    $resUsersRole->assertStatus(200);
    expect($resUsersRole->viewData('users')->pluck('username'))->toContain('ordinaryuser');

    // 4. Admin Songs filters
    $resSongsQ = $this->actingAs($admin)->get('/admin/songs?q=Cantik');
    $resSongsQ->assertStatus(200)->assertSee('Cantik');

    $resSongsOwner = $this->actingAs($admin)->get('/admin/songs?owner=ordinaryuser');
    $resSongsOwner->assertStatus(200)->assertSee('Cantik');

    $resSongsVis = $this->actingAs($admin)->get('/admin/songs?visibility=public');
    $resSongsVis->assertStatus(200)->assertSee('Cantik');
});

