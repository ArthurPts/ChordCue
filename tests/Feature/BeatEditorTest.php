<?php

use App\Models\Artist;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use App\Services\BeatGridBuilder;

beforeEach(function () {
    $this->artist = Artist::create(['name' => 'Artis Uji Beat']);

    $this->owner = User::create([
        'username'   => 'beatowner',
        'email'      => 'beatowner@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 10,
    ]);

    $this->otherUser = User::create([
        'username'   => 'beatother',
        'email'      => 'beatother@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 10,
    ]);

    $this->admin = User::create([
        'username'   => 'beatadmin',
        'email'      => 'beatadmin@example.com',
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
        'pronunciation' => 'A minor',
        'file_path'     => 'chords/audio/am.mp3',
    ]);

    $this->song = Song::create([
        'user_id'                    => $this->owner->id,
        'title'                      => 'Lagu Uji Beat Editor',
        'artist_id'                  => $this->artist->id,
        'default_key'                => 'C',
        'bpm'                        => 100,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
        'backing_track_offset'       => 2.500,
    ]);

    $this->section = SongSection::create([
        'song_id'  => $this->song->id,
        'name'     => 'Verse 1',
        'sequence' => 1,
    ]);

    $this->line1 = LyricLine::create([
        'song_section_id' => $this->section->id,
        'line_number'     => 1,
        'content'         => 'Baris pertama lagu uji',
    ]);

    $this->line2 = LyricLine::create([
        'song_section_id' => $this->section->id,
        'line_number'     => 2,
        'content'         => 'Baris kedua lagu uji',
    ]);
});

test('otorisasi melindungi akses halaman beat editor', function () {
    $this->get(route('songs.editor.beat', $this->song))
        ->assertRedirect(route('login'));

    $this->actingAs($this->otherUser)
        ->get(route('songs.editor.beat', $this->song))
        ->assertForbidden();

    $this->actingAs($this->owner)
        ->get(route('songs.editor.beat', $this->song))
        ->assertOk()
        ->assertSee('Editor Beat');

    $this->actingAs($this->admin)
        ->get(route('songs.editor.beat', $this->song))
        ->assertOk();
});

test('halaman beat editor merender switcher mode, kontrol offset, pustaka chord, dan komponen grid sesuai design rules', function () {
    $response = $this->actingAs($this->owner)
        ->get(route('songs.editor.beat', $this->song));

    $response->assertOk();
    $response->assertSee(route('songs.editor.simple', $this->song));
    $response->assertSee(route('songs.show', $this->song));
    $response->assertSee('Simple Editor');
    $response->assertSee('Beat Editor');
    $response->assertSee('-1s');
    $response->assertSee('-100ms');
    $response->assertSee('-10ms');
    $response->assertSee('+10ms');
    $response->assertSee('+100ms');
    $response->assertSee('+1s');
    $response->assertSee('Reset');
    $response->assertSee('Pustaka Chord');
    $response->assertSee('Chord yang belum digunakan');
    $response->assertSee('Bar Kosong');
    $response->assertSee('Lepas');

    $blade = file_get_contents(resource_path('views/songs/editor-beat.blade.php'))
        . file_get_contents(resource_path('views/songs/partials/editor-top-panel.blade.php'))
        . file_get_contents(resource_path('views/songs/partials/editor-chord-sidebar.blade.php'));
    $js = file_get_contents(resource_path('js/alpine/beat-editor.js'));

    expect($blade)->toContain('border-l-accent');
    expect($blade)->toContain('startDragUnbeatedPlacement');
    expect($blade)->not->toContain('Belum di-beat:');
    expect($blade)->not->toContain('toggleSectionCollapse');
    expect($js)->not->toContain('toggleSectionCollapse');
    expect($blade)->not->toContain('style=');
    expect($blade)->not->toContain('rounded-xl');
    expect($blade)->not->toContain('rounded-2xl');
    expect($blade)->not->toContain('__(');
    expect($blade)->toContain('font-mono');
});

test('algoritma grid birama §7.2 menghitung barAwal, barAkhirTerpakai, ruler Bar 1, dan Bar Kosong secara tepat', function () {
    $builder = new BeatGridBuilder();

    // Contoh §7.2 (4/4, B = 4): Baris 1 punya chord pada beat 1, 3, 5; Baris 2 kosong
    $sections = [
        [
            'id'    => $this->section->id,
            'name'  => 'Verse 1',
            'lines' => [
                [
                    'id'         => $this->line1->id,
                    'content'    => $this->line1->content,
                    'placements' => [
                        ['id' => 1, 'chord_name' => 'C', 'position' => 0, 'start_beat' => 1],
                        ['id' => 2, 'chord_name' => 'G', 'position' => 6, 'start_beat' => 3],
                        ['id' => 3, 'chord_name' => 'Am', 'position' => 12, 'start_beat' => 5],
                    ],
                ],
                [
                    'id'         => $this->line2->id,
                    'content'    => $this->line2->content,
                    'placements' => [],
                ],
            ],
        ],
    ];

    $grid = $builder->buildForSections($sections, 4);

    // Baris 1: bar_awal = 1, bar_akhir_terpakai = 2, tampil Bar 1..3 (Bar 3 = Bar Kosong, beat 9..12)
    expect($grid[$this->line1->id]['bar_awal'])->toBe(1);
    expect($grid[$this->line1->id]['bar_akhir_terpakai'])->toBe(2);
    expect($grid[$this->line1->id]['bars'])->toHaveCount(3);
    expect($grid[$this->line1->id]['bars'][0]['bar_lokal'])->toBe(1);
    expect($grid[$this->line1->id]['bars'][0]['is_empty_trailing'])->toBeFalse();
    expect($grid[$this->line1->id]['bars'][0]['beats'][0]['is_downbeat'])->toBeTrue();
    expect($grid[$this->line1->id]['bars'][0]['beats'][0]['placement']['chord_name'])->toBe('C');
    expect($grid[$this->line1->id]['bars'][2]['is_empty_trailing'])->toBeTrue();
    expect($grid[$this->line1->id]['bars'][2]['beats'][0]['beat_global'])->toBe(9);
    expect($grid[$this->line1->id]['bars'][2]['beats'][3]['beat_global'])->toBe(12);

    // Baris 2 kosong: bar_awal = 3, tampil Bar 1 lokal (bar global 3, beat 9..12)
    expect($grid[$this->line2->id]['bar_awal'])->toBe(3);
    expect($grid[$this->line2->id]['bars'])->toHaveCount(1);
    expect($grid[$this->line2->id]['bars'][0]['bar_global'])->toBe(3);
    expect($grid[$this->line2->id]['bars'][0]['bar_lokal'])->toBe(1);
    expect($grid[$this->line2->id]['bars'][0]['is_empty_trailing'])->toBeTrue();
    expect($grid[$this->line2->id]['bars'][0]['beats'][0]['beat_global'])->toBe(9);

    // Tambah chord beat 9 pada Baris 1 -> Baris 1 tampil Bar 1..4; Baris 2 bergeser mulai bar global 4 (beat 13..16)
    $sections[0]['lines'][0]['placements'][] = [
        'id'         => 4,
        'chord_name' => 'C',
        'position'   => null,
        'start_beat' => 9,
    ];
    $gridAfterBeat9 = $builder->buildForSections($sections, 4);
    expect($gridAfterBeat9[$this->line1->id]['bars'])->toHaveCount(4);
    expect($gridAfterBeat9[$this->line2->id]['bar_awal'])->toBe(4);
    expect($gridAfterBeat9[$this->line2->id]['bars'][0]['beats'][0]['beat_global'])->toBe(13);

    // Pengelompokan berubah untuk time signature 3/4 tanpa mengubah start_beat tersimpan (beat 5 masuk Bar 2 pada 3/4, beat 9 masuk Bar 3)
    $grid34 = $builder->buildForSections($sections, 3);
    expect($grid34[$this->line1->id]['bars'])->toHaveCount(4);
    expect($grid34[$this->line1->id]['bars'][0]['beats'])->toHaveCount(3);
    expect($grid34[$this->line2->id]['bar_awal'])->toBe(4);
});

test('penempatan, pemindahan independen, tabrakan global 409 lintas baris, timpa atomik, dan lepas beat berjalan sesuai §7.3', function () {
    // Placement dari Simple Editor: punya position=4, start_beat=null
    $fromSimple = ChordPlacement::create([
        'lyric_line_id' => $this->line1->id,
        'chord_id'      => $this->chordC->id,
        'position'      => 4,
        'start_beat'    => null,
    ]);

    // 1. Mengisi start_beat pada placement Simple Editor TIDAK mengubah position
    $this->actingAs($this->owner)
        ->patchJson(route('placements.update', $fromSimple), [
            'start_beat' => 5,
        ])
        ->assertOk()
        ->assertJson([
            'placement' => [
                'id'         => $fromSimple->id,
                'position'   => 4,
                'start_beat' => 5,
            ],
        ]);
    expect($fromSimple->fresh()->position)->toBe(4);
    expect($fromSimple->fresh()->start_beat)->toBe(5);

    // 2. Mengubah offset backing track TIDAK mengubah start_beat pada placement manapun
    $this->actingAs($this->owner)
        ->patchJson(route('songs.backing-track.offset', $this->song), [
            'offset' => 6.750,
        ])
        ->assertOk();
    expect($fromSimple->fresh()->start_beat)->toBe(5);

    // 3. Menempatkan chord baru dari Pustaka Chord di Beat Editor pada Baris 2 di beat 5 memicu 409 Conflict lintas baris
    $this->actingAs($this->owner)
        ->postJson(route('lines.placements.store', $this->line2), [
            'chord_id'   => $this->chordG->id,
            'start_beat' => 5,
        ])
        ->assertStatus(409)
        ->assertJson([
            'conflict' => [
                'id'         => $fromSimple->id,
                'chord_name' => 'C',
                'start_beat' => 5,
            ],
        ]);

    // 4. Konfirmasi timpa (force: true) menghapus placement lama secara atomik dan menaruh placement baru di Baris 2 beat 5
    $overwriteRes = $this->actingAs($this->owner)
        ->postJson(route('lines.placements.store', $this->line2), [
            'chord_id'   => $this->chordG->id,
            'start_beat' => 5,
            'force'      => true,
        ]);
    $overwriteRes->assertCreated();
    expect(ChordPlacement::find($fromSimple->id))->toBeNull();
    $newPlacementId = $overwriteRes->json('placement.id');
    expect($overwriteRes->json('placement.position'))->toBeNull();
    expect($overwriteRes->json('placement.start_beat'))->toBe(5);

    // 5. Placement lain punya kedua koordinat, lalu aksi "Lepas" start_beat mengosongkan start_beat tanpa mengubah position
    $complete = ChordPlacement::create([
        'lyric_line_id' => $this->line1->id,
        'chord_id'      => $this->chordAm->id,
        'position'      => 2,
        'start_beat'    => 1,
    ]);

    $this->actingAs($this->owner)
        ->postJson(route('placements.release', $complete), [
            'coordinate' => 'start_beat',
        ])
        ->assertOk()
        ->assertJson([
            'placement' => [
                'id'         => $complete->id,
                'position'   => 2,
                'start_beat' => null,
            ],
        ]);
    expect($complete->fresh()->position)->toBe(2);
    expect($complete->fresh()->start_beat)->toBeNull();

    // 6. Hapus placement di Beat Editor menghapusnya seutuhnya
    $this->actingAs($this->owner)
        ->deleteJson(route('placements.destroy', $newPlacementId))
        ->assertOk();
    expect(ChordPlacement::find($newPlacementId))->toBeNull();
});
