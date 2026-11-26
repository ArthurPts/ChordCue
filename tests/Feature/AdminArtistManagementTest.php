<?php

use App\Models\Artist;
use App\Models\Song;
use App\Models\User;

test('tamu (guest) diarahkan ke login saat mengakses endpoint kelola artis', function () {
    $artist = Artist::create(['name' => 'Artis Tamu']);

    $this->get('/admin/artists')->assertRedirect('/login');
    $this->post('/admin/artists', ['name' => 'Artis Baru'])->assertRedirect('/login');
    $this->put("/admin/artists/{$artist->id}", ['name' => 'Artis Ubah'])->assertRedirect('/login');
    $this->delete("/admin/artists/{$artist->id}")->assertRedirect('/login');
});

test('pengguna non-admin ditolak (403) pada seluruh endpoint kelola artis', function () {
    $user = User::factory()->create(['role' => 'user']);
    $artist = Artist::create(['name' => 'Artis Member']);

    $this->actingAs($user)->get('/admin/artists')->assertForbidden();
    $this->actingAs($user)->post('/admin/artists', ['name' => 'Artis Baru'])->assertForbidden();
    $this->actingAs($user)->put("/admin/artists/{$artist->id}", ['name' => 'Artis Ubah'])->assertForbidden();
    $this->actingAs($user)->delete("/admin/artists/{$artist->id}")->assertForbidden();

    $this->assertDatabaseHas('artists', ['id' => $artist->id, 'name' => 'Artis Member']);
});

test('admin dapat melihat daftar artis, menambah, mengubah, dan menghapus artis yang belum dipakai', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Create
    $this->actingAs($admin)->post('/admin/artists', ['name' => '  Efek Rumah Kaca  '])
        ->assertRedirect(route('admin.artists.index'))
        ->assertSessionHas('status');

    $artist = Artist::where('name', 'Efek Rumah Kaca')->first();
    expect($artist)->not->toBeNull();

    // Index menampilkan artis dan statistik
    $this->actingAs($admin)->get('/admin/artists')
        ->assertOk()
        ->assertSee('Kelola Artis')
        ->assertSee('Total Artis')
        ->assertSee('Efek Rumah Kaca')
        ->assertSee('Belum Dipakai');

    // Update
    $this->actingAs($admin)->put("/admin/artists/{$artist->id}", ['name' => 'ERK Band'])
        ->assertRedirect(route('admin.artists.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('artists', ['id' => $artist->id, 'name' => 'ERK Band']);

    // Delete artis yang tidak dipakai lagu
    $this->actingAs($admin)->delete("/admin/artists/{$artist->id}")
        ->assertRedirect(route('admin.artists.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('artists', ['id' => $artist->id]);
});

test('artis yang masih digunakan pada lagu tidak dapat dihapus dan pembaruan namanya tercermin di lagu', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $artist = Artist::create(['name' => 'Band Lama']);

    $song = Song::create([
        'user_id'                    => $admin->id,
        'title'                      => 'Lagu Relasi Artis',
        'artist_id'                  => $artist->id,
        'bpm'                        => 100,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
    ]);

    // Coba hapus artis yang masih terpakai => ditolak dengan flash error
    $this->actingAs($admin)->delete("/admin/artists/{$artist->id}")
        ->assertRedirect(route('admin.artists.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('artists', ['id' => $artist->id]);
    $this->assertDatabaseHas('songs', ['id' => $song->id, 'artist_id' => $artist->id]);

    // Ubah nama artis => langsung tercermin di halaman katalog & player
    $this->actingAs($admin)->put("/admin/artists/{$artist->id}", ['name' => 'Band Baru Terbarui'])
        ->assertRedirect(route('admin.artists.index'))
        ->assertSessionHas('status');

    $this->get('/')->assertOk()->assertSee('Band Baru Terbarui');
    $this->get(route('songs.show', $song))->assertOk()->assertSee('Band Baru Terbarui');
});

test('validasi menolak nama artis kosong, terlalu panjang, atau duplikat', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Artist::create(['name' => 'Artis Unik']);

    $this->actingAs($admin)->post('/admin/artists', ['name' => '   '])
        ->assertSessionHasErrors(['name']);

    $this->actingAs($admin)->post('/admin/artists', ['name' => str_repeat('a', 121)])
        ->assertSessionHasErrors(['name']);

    $this->actingAs($admin)->post('/admin/artists', ['name' => 'Artis Unik'])
        ->assertSessionHasErrors(['name']);
});

test('pencarian, paginasi, dan empty state pada halaman kelola artis bekerja dengan benar', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Empty state saat belum ada artis sama sekali
    $this->actingAs($admin)->get('/admin/artists')
        ->assertOk()
        ->assertSee('Belum ada Artis');

    // Buat 12 artis untuk uji paginasi (10 per halaman) & pencarian
    for ($i = 1; $i <= 12; $i++) {
        Artist::create(['name' => sprintf('Artis Uji %02d', $i)]);
    }

    $page1 = $this->actingAs($admin)->get('/admin/artists');
    $page1->assertOk();
    expect($page1->viewData('artists')->count())->toBe(10);

    $page2 = $this->actingAs($admin)->get('/admin/artists?page=2');
    $page2->assertOk();
    expect($page2->viewData('artists')->count())->toBe(2);

    // Pencarian cocok
    $this->actingAs($admin)->get('/admin/artists?q=Uji%2011')
        ->assertOk()
        ->assertSee('Artis Uji 11')
        ->assertDontSee('Artis Uji 01');

    // Pencarian kosong
    $this->actingAs($admin)->get('/admin/artists?q=TidakAdaArtisIni')
        ->assertOk()
        ->assertSee('Artis tidak ditemukan');
});
