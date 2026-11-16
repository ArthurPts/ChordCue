<?php

use App\Jobs\GenerateChordAudio;
use App\Models\Artist;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->artist = Artist::create(['name' => 'Sheila on 7']);

    $this->owner = User::create([
        'username'   => 'musisi',
        'email'      => 'musisi@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 5,
    ]);

    $this->stranger = User::create([
        'username'   => 'oranglain',
        'email'      => 'oranglain@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 5,
    ]);

    $this->admin = User::create([
        'username'   => 'admincc',
        'email'      => 'admincc@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'admin',
        'song_limit' => 99,
    ]);

    // Buat chord dasar
    $this->chordC = Chord::create([
        'name'          => 'C',
        'pronunciation' => 'C',
        'file_path'     => 'chords/audio/c.mp3',
    ]);
    Storage::disk('public')->put('chords/audio/c.mp3', 'dummy audio');

    $this->chordAm = Chord::create([
        'name'          => 'Am',
        'pronunciation' => 'A minor',
        'file_path'     => 'chords/audio/am.mp3',
    ]);
    Storage::disk('public')->put('chords/audio/am.mp3', 'dummy audio');

    // Buat lagu publik lengkap dengan section, line, dan placement
    $this->publicSong = Song::create([
        'user_id'                    => $this->owner->id,
        'title'                      => 'Dan',
        'artist_id'                  => $this->artist->id,
        'default_key'                => 'C',
        'bpm'                        => 85,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
        'backing_track_path'         => 'backing-tracks/dan.mp3',
        'backing_track_offset'       => 3.250,
    ]);
    Storage::disk('public')->put('backing-tracks/dan.mp3', 'dummy backing track');

    $this->section = SongSection::create([
        'song_id'  => $this->publicSong->id,
        'name'     => 'Verse 1',
        'sequence' => 1,
    ]);

    $this->line = LyricLine::create([
        'song_section_id' => $this->section->id,
        'line_number'     => 1,
        'content'         => 'Dan bila esok datang kembali',
    ]);

    // Placement 1: Lengkap (position=0, start_beat=1, chord=C)
    $this->completePlacement1 = ChordPlacement::create([
        'lyric_line_id' => $this->line->id,
        'chord_id'      => $this->chordC->id,
        'position'      => 0,
        'start_beat'    => 1,
    ]);

    // Placement 2: Lengkap (position=9, start_beat=5, chord=Am)
    $this->completePlacement2 = ChordPlacement::create([
        'lyric_line_id' => $this->line->id,
        'chord_id'      => $this->chordAm->id,
        'position'      => 9,
        'start_beat'    => 5,
    ]);

    // Placement 3: Tidak lengkap (hanya position, tanpa start_beat)
    $this->incompletePlacementNoBeat = ChordPlacement::create([
        'lyric_line_id' => $this->line->id,
        'chord_id'      => $this->chordC->id,
        'position'      => 15,
        'start_beat'    => null,
    ]);

    // Placement 4: Tidak lengkap (hanya start_beat, tanpa position)
    $this->incompletePlacementNoPos = ChordPlacement::create([
        'lyric_line_id' => $this->line->id,
        'chord_id'      => $this->chordAm->id,
        'position'      => null,
        'start_beat'    => 9,
    ]);
});

test('tamu (guest) dapat mengakses timeline lagu publik dengan struktur §9.3 lengkap', function () {
    $response = $this->getJson(route('songs.timeline', $this->publicSong));

    $response->assertOk()
        ->assertJsonStructure([
            'song' => [
                'id',
                'title',
                'artist',
                'key',
                'bpm',
                'time_signature' => ['numerator', 'denominator'],
                'backing_track'  => ['url', 'offset'],
                'backing_track_offset',
            ],
            'transpose',
            'audio_ready',
            'pending_chords',
            'sections' => [
                '*' => [
                    'name',
                    'lines' => [
                        '*' => [
                            'id',
                            'content',
                            'placements' => [
                                '*' => [
                                    'id',
                                    'chord' => ['name', 'audio_url'],
                                    'position',
                                    'start_beat',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

    $data = $response->json();
    expect($data['song']['backing_track_offset'])->toBe(3.25);
    expect($data['song']['backing_track']['offset'])->toBe(3.25);
    expect($data['transpose'])->toBe(0);
    expect($data['audio_ready'])->toBeTrue();
    expect($data['pending_chords'])->toBe(0);

    // Pastikan seluruh placement yang memiliki position (walau start_beat masih null) dikembalikan urut position
    // dan placement tanpa position (hanya start_beat) tetap dikecualikan
    $placements = $data['sections'][0]['lines'][0]['placements'];
    expect($placements)->toHaveCount(3);
    expect($placements[0]['chord']['name'])->toBe('C');
    expect($placements[0]['position'])->toBe(0);
    expect($placements[0]['start_beat'])->toBe(1);
    expect($placements[1]['chord']['name'])->toBe('Am');
    expect($placements[1]['position'])->toBe(9);
    expect($placements[1]['start_beat'])->toBe(5);
    expect($placements[2]['chord']['name'])->toBe('C');
    expect($placements[2]['position'])->toBe(15);
    expect($placements[2]['start_beat'])->toBeNull();
});

test('otorisasi melindungi timeline lagu privat', function () {
    $privateSong = Song::create([
        'user_id'                    => $this->owner->id,
        'title'                      => 'Lagu Rahasia',
        'artist_id'                  => $this->artist->id,
        'bpm'                        => 100,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'private',
    ]);

    // Guest ditolak
    $this->getJson(route('songs.timeline', $privateSong))
        ->assertForbidden();

    // User lain ditolak
    $this->actingAs($this->stranger)
        ->getJson(route('songs.timeline', $privateSong))
        ->assertForbidden();

    // Pemilik diizinkan
    $this->actingAs($this->owner)
        ->getJson(route('songs.timeline', $privateSong))
        ->assertOk();

    // Admin diizinkan
    $this->actingAs($this->admin)
        ->getJson(route('songs.timeline', $privateSong))
        ->assertOk();
});

test('parameter transpose menggeser nada dasar dan chord secara tepat', function () {
    // Transpose +2 semitone: Key C => D; Chord C => D; Chord Am => Bm
    $response = $this->getJson(route('songs.timeline', [
        'song'      => $this->publicSong,
        'transpose' => 2,
    ]));

    $response->assertOk();
    $data = $response->json();

    expect($data['song']['key'])->toBe('D');
    expect($data['transpose'])->toBe(2);

    $placements = $data['sections'][0]['lines'][0]['placements'];
    expect($placements[0]['chord']['name'])->toBe('D');
    expect($placements[1]['chord']['name'])->toBe('Bm');
});

test('transpose ke chord yang belum ada otomatis membuatnya di DB dan mendispatch TTS job', function () {
    Queue::fake();

    // Pastikan chord D dan Bm belum ada di database
    Chord::whereIn('name', ['D', 'Bm'])->delete();

    $response = $this->getJson(route('songs.timeline', [
        'song'      => $this->publicSong,
        'transpose' => 2,
    ]));

    $response->assertOk();

    // Verifikasi chord D dan Bm telah dibuat di database dengan pengucapan fonetik
    $chordD = Chord::where('name', 'D')->first();
    expect($chordD)->not->toBeNull();
    expect($chordD->pronunciation)->toBe('D');
    expect($chordD->file_path)->toBeNull();

    $chordBm = Chord::where('name', 'Bm')->first();
    expect($chordBm)->not->toBeNull();
    expect($chordBm->pronunciation)->toBe('B minor');
    expect($chordBm->file_path)->toBeNull();

    // Verifikasi Job GenerateChordAudio di-dispatch untuk chord-chord baru tersebut
    Queue::assertPushed(GenerateChordAudio::class, 2);

    // Karena audio chord baru belum siap di disk, audio_ready harus bernilai false
    $data = $response->json();
    expect($data['audio_ready'])->toBeFalse();
    expect($data['pending_chords'])->toBe(2);
});

test('validasi menolak parameter transpose di luar rentang -11 sampai +11', function () {
    $this->getJson(route('songs.timeline', ['song' => $this->publicSong, 'transpose' => 12]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['transpose']);

    $this->getJson(route('songs.timeline', ['song' => $this->publicSong, 'transpose' => -12]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['transpose']);

    $this->getJson(route('songs.timeline', ['song' => $this->publicSong, 'transpose' => 'bukan-angka']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['transpose']);
});

