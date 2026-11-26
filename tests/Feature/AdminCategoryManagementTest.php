<?php

use App\Models\Artist;
use App\Models\Category;
use App\Models\Song;
use App\Models\User;

test('tamu (guest) diarahkan ke login saat mengakses endpoint kelola kategori', function () {
    $category = Category::create(['name' => 'Pop Uji', 'type' => 'genre']);

    $this->get('/admin/categories')->assertRedirect('/login');
    $this->post('/admin/categories', ['name' => 'Jazz Uji', 'type' => 'genre'])->assertRedirect('/login');
    $this->put("/admin/categories/{$category->id}", ['name' => 'Pop Baru', 'type' => 'genre'])->assertRedirect('/login');
    $this->delete("/admin/categories/{$category->id}")->assertRedirect('/login');
});

test('pengguna non-admin ditolak (403) pada seluruh endpoint kelola kategori', function () {
    $user = User::factory()->create(['role' => 'user']);
    $category = Category::create(['name' => 'Rock Uji', 'type' => 'genre']);

    $this->actingAs($user)->get('/admin/categories')->assertForbidden();
    $this->actingAs($user)->post('/admin/categories', ['name' => 'Jazz Uji', 'type' => 'genre'])->assertForbidden();
    $this->actingAs($user)->put("/admin/categories/{$category->id}", ['name' => 'Rock Baru', 'type' => 'genre'])->assertForbidden();
    $this->actingAs($user)->delete("/admin/categories/{$category->id}")->assertForbidden();

    $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Rock Uji']);
});

test('halaman kelola kategori mengganti data dan label sesuai tipe aktif tanpa mencampur tipe lain', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Category::create(['name' => 'Synthpop', 'type' => 'genre']);
    Category::create(['name' => 'Nostalgia', 'type' => 'mood']);
    Category::create(['name' => 'Nusantara', 'type' => 'location']);

    // Default (Genre)
    $resGenre = $this->actingAs($admin)->get('/admin/categories');
    $resGenre->assertOk()
        ->assertSee('Kelola Kategori')
        ->assertSee('Tambah Genre')
        ->assertSee('Nama Genre')
        ->assertSee('Cari nama genre...')
        ->assertSee('Synthpop')
        ->assertDontSee('Nostalgia')
        ->assertDontSee('Nusantara');

    // Mood
    $resMood = $this->actingAs($admin)->get('/admin/categories?type=mood');
    $resMood->assertOk()
        ->assertSee('Tambah Mood')
        ->assertSee('Nama Mood')
        ->assertSee('Cari nama mood...')
        ->assertSee('Nostalgia')
        ->assertDontSee('Synthpop')
        ->assertDontSee('Nusantara');

    // Lokasi
    $resLocation = $this->actingAs($admin)->get('/admin/categories?type=location');
    $resLocation->assertOk()
        ->assertSee('Tambah Lokasi')
        ->assertSee('Nama Lokasi')
        ->assertSee('Cari nama lokasi...')
        ->assertSee('Nusantara')
        ->assertDontSee('Synthpop')
        ->assertDontSee('Nostalgia');

    // Tipe tidak valid fallback aman ke genre
    $resInvalid = $this->actingAs($admin)->get('/admin/categories?type=tidak-ada');
    $resInvalid->assertOk()
        ->assertSee('Tambah Genre')
        ->assertSee('Synthpop')
        ->assertDontSee('Nostalgia');
});

test('admin dapat melakukan CRUD lengkap untuk tipe genre, mood, dan lokasi', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach ([
        ['type' => 'genre', 'name' => 'Bossa Nova', 'updated' => 'Bossa Jazz'],
        ['type' => 'mood', 'name' => 'Melankolis', 'updated' => 'Syahdu'],
        ['type' => 'location', 'name' => 'Skandinavia', 'updated' => 'Nordik'],
    ] as $case) {
        // Create
        $createRes = $this->actingAs($admin)->post('/admin/categories', [
            'name' => '  ' . $case['name'] . '  ',
            'type' => $case['type'],
        ]);
        $createRes->assertRedirect(route('admin.categories.index', ['type' => $case['type']]))
            ->assertSessionHas('status');

        $category = Category::where('name', $case['name'])->first();
        expect($category)->not->toBeNull();
        expect($category->type)->toBe($case['type']);

        // Update
        $updateRes = $this->actingAs($admin)->put("/admin/categories/{$category->id}", [
            'name' => $case['updated'],
            'type' => $case['type'],
        ]);
        $updateRes->assertRedirect(route('admin.categories.index', ['type' => $case['type']]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('categories', [
            'id'   => $category->id,
            'name' => $case['updated'],
            'type' => $case['type'],
        ]);

        // Delete
        $deleteRes = $this->actingAs($admin)->delete("/admin/categories/{$category->id}");
        $deleteRes->assertRedirect(route('admin.categories.index', ['type' => $case['type']]))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
});

test('validasi menolak kategori kosong, duplikat lintas tipe, dan tipe tidak valid', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Category::create(['name' => 'Akustik Unik', 'type' => 'genre']);

    // Kosong
    $this->actingAs($admin)->post('/admin/categories', [
        'name' => '   ',
        'type' => 'genre',
    ])->assertSessionHasErrors(['name']);

    // Duplikat lintas tipe (karena categories.name unique di schema)
    $this->actingAs($admin)->post('/admin/categories', [
        'name' => 'Akustik Unik',
        'type' => 'mood',
    ])->assertSessionHasErrors(['name']);

    // Tipe tidak valid
    $this->actingAs($admin)->post('/admin/categories', [
        'name' => 'Kategori Baru',
        'type' => 'invalid_type',
    ])->assertSessionHasErrors(['type']);
});

test('menghapus kategori melepasnya dari lagu terkait tanpa menghapus lagu dan tetap menjaga filter katalog', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $artist = Artist::create(['name' => 'Artis Tes Kategori']);
    $category = Category::create(['name' => 'Folk Uji', 'type' => 'genre']);

    $song = Song::create([
        'user_id'                    => $admin->id,
        'title'                      => 'Lagu Ber-Kategori',
        'artist_id'                  => $artist->id,
        'bpm'                        => 90,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
    ]);
    $song->categories()->attach($category->id);

    // Filter homepage mengenali kategori sebelum dihapus
    $this->get('/?genre=' . $category->id)->assertOk()->assertSee('Lagu Ber-Kategori');

    // Hapus kategori
    $this->actingAs($admin)->delete("/admin/categories/{$category->id}")
        ->assertRedirect(route('admin.categories.index', ['type' => 'genre']))
        ->assertSessionHas('status');

    // Kategori hilang, pivot hilang, lagu tetap utuh
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    $this->assertDatabaseMissing('category_song', ['category_id' => $category->id, 'song_id' => $song->id]);
    $this->assertDatabaseHas('songs', ['id' => $song->id, 'title' => 'Lagu Ber-Kategori']);
});

test('pencarian, paginasi, dan empty state pada halaman kelola kategori bekerja dengan benar', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Empty state saat belum ada data pada tipe location
    $this->actingAs($admin)->get('/admin/categories?type=location')
        ->assertOk()
        ->assertSee('Belum ada Lokasi');

    // Buat 12 genre untuk uji paginasi (10 per halaman) & pencarian
    for ($i = 1; $i <= 12; $i++) {
        Category::create(['name' => sprintf('Genre Uji %02d', $i), 'type' => 'genre']);
    }

    $page1 = $this->actingAs($admin)->get('/admin/categories?type=genre');
    $page1->assertOk();
    expect($page1->viewData('categories')->count())->toBe(10);

    $page2 = $this->actingAs($admin)->get('/admin/categories?type=genre&page=2');
    $page2->assertOk();
    expect($page2->viewData('categories')->count())->toBe(2);

    // Pencarian yang cocok
    $searchMatch = $this->actingAs($admin)->get('/admin/categories?type=genre&q=Uji%2012');
    $searchMatch->assertOk()->assertSee('Genre Uji 12')->assertDontSee('Genre Uji 01');

    // Pencarian kosong
    $searchEmpty = $this->actingAs($admin)->get('/admin/categories?type=genre&q=TidakAdaSamaSekali');
    $searchEmpty->assertOk()->assertSee('Genre tidak ditemukan');
});

test('navigasi admin menampilkan tautan kelola kategori dan kelola artis hanya untuk admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->create(['role' => 'user']);

    $this->get('/')->assertOk()->assertDontSee('Kelola Kategori')->assertDontSee('Kelola Artis');

    $this->actingAs($member)->get('/')->assertOk()
        ->assertDontSee('Kelola Kategori')
        ->assertDontSee('Kelola Artis');

    $this->actingAs($admin)->get('/admin/categories')->assertOk()
        ->assertSee('Kelola Kategori')
        ->assertSee('Kelola Artis');
});
