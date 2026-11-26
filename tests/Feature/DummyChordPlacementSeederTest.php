<?php

use App\Models\Artist;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use Database\Seeders\DummyChordPlacementSeeder;
use Illuminate\Support\Facades\DB;

test('dummy chord placement seeder mengisi kedua lagu target sesuai aturan sistem dan bersifat idempoten', function () {
    // 1. Siapkan data user, artis, chord, dan struktur 2 lagu target + 1 lagu pembanding
    $owner = User::create([
        'username'   => 'tester_seed',
        'email'      => 'tester_seed@chordcue.test',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 5,
    ]);

    $artistSempurna = Artist::create(['name' => 'Andra and the Backbone']);
    $artistWaltz = Artist::create(['name' => 'Duo Dummy']);

    $chordNames = [
        'A', 'Am', 'Bb', 'Bdim', 'Bm7b5', 'Cadd9', 'Caug', 'Cdim7',
        'Csus2', 'Csus4', 'C7', 'Cmaj7', 'Cm7', 'Cmaj9', 'C7#5', 'C7b9',
        'D7sus4', 'E/G#', 'Fmaj7', 'G13',
    ];
    foreach ($chordNames as $chordName) {
        Chord::create([
            'name'          => $chordName,
            'pronunciation' => $chordName,
            'file_path'     => null,
        ]);
    }

    // Buat lagu Sempurna (default_key NULL, 9 section, 32 baris sesuai seed)
    $sempurna = Song::create([
        'user_id'                    => $owner->id,
        'title'                      => 'Sempurna',
        'artist_id'                  => $artistSempurna->id,
        'default_key'                => null,
        'bpm'                        => 80,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
    ]);

    $sempurnaLyrics = [
        1 => ['Kau begitu sempurna', 'Di mataku kau begitu indah', 'Kau membuat diriku', 'Akan selalu memujamu'],
        2 => ['Di setiap langkahku', "'Ku 'kan selalu memikirkan dirimu", 'Tak bisa ku bayangkan', 'Hidupku tanpa cintamu'],
        3 => ['Janganlah kau tinggalkan diriku', 'Takkan mampu menghadapi semua', "Hanya bersamamu 'ku akan bisa"],
        4 => ['Kau adalah darahku', 'Kau adalah jantungku', 'Kau adalah hidupku', 'Lengkapi diriku', 'Oh, sayangku kau begitu'],
        5 => ['Sempurna', 'Sempurna'],
        6 => ['Kau genggam tanganku', 'Saat diriku lemah dan terjatuh', 'Kau bisikkan kata', 'Dan hapus semua sesalku'],
        7 => ['Janganlah kau tinggalkan diriku', 'Takkan mampu menghadapi semua', "Hanya bersamamu 'ku akan bisa"],
        8 => ['Kau adalah darahku', 'Kau adalah jantungku', 'Kau adalah hidupku', 'Lengkapi diriku', 'Oh, sayangku kau begitu'],
        9 => ['Sempurna', 'Sempurna'],
    ];

    foreach ($sempurnaLyrics as $seq => $lines) {
        $sec = SongSection::create(['song_id' => $sempurna->id, 'name' => "Section {$seq}", 'sequence' => $seq]);
        foreach ($lines as $idx => $content) {
            LyricLine::create(['song_section_id' => $sec->id, 'line_number' => $idx + 1, 'content' => $content]);
        }
    }

    // Buat lagu Dummy Waltz (3/4, key C, 7 section, 23 baris)
    $waltz = Song::create([
        'user_id'                    => $owner->id,
        'title'                      => 'Dummy Waltz',
        'artist_id'                  => $artistWaltz->id,
        'default_key'                => 'C',
        'bpm'                        => 90,
        'time_signature_numerator'   => 3,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
    ]);

    $waltzStructure = [
        1 => ['Intro', 2], 2 => ['Verse 1', 4], 3 => ['Chorus', 4],
        4 => ['Verse 2', 4], 5 => ['Chorus', 4], 6 => ['Bridge', 3], 7 => ['Outro', 2],
    ];
    foreach ($waltzStructure as $seq => [$secName, $count]) {
        $sec = SongSection::create(['song_id' => $waltz->id, 'name' => $secName, 'sequence' => $seq]);
        for ($n = 1; $n <= $count; $n++) {
            LyricLine::create(['song_section_id' => $sec->id, 'line_number' => $n, 'content' => "Contoh baris lirik {$n} ({$secName})"]);
        }
    }

    // Lagu pembanding yang tidak boleh tersentuh
    $otherSong = Song::create([
        'user_id'                    => $owner->id,
        'title'                      => 'Lagu Lain Tetap Bersih',
        'artist_id'                  => $artistWaltz->id,
        'default_key'                => null,
        'bpm'                        => 100,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
    ]);

    // 2. Jalankan seeder DUA KALI untuk membuktikan idempotensi
    (new DummyChordPlacementSeeder())->run();
    (new DummyChordPlacementSeeder())->run();

    // 3. Periksa hasil metadata & jumlah placement per status
    $sempurna->refresh();
    $otherSong->refresh();
    expect($sempurna->default_key)->toBe('C');
    expect($otherSong->default_key)->toBeNull();

    $sempurnaCount = ChordPlacement::whereHas('lyricLine.section', fn ($q) => $q->where('song_id', $sempurna->id))->count();
    $sempurnaComplete = ChordPlacement::whereHas('lyricLine.section', fn ($q) => $q->where('song_id', $sempurna->id))
        ->whereNotNull('position')
        ->whereNotNull('start_beat')
        ->count();
    expect($sempurnaCount)->toBe(60);
    expect($sempurnaComplete)->toBe(60);

    $waltzPlacements = ChordPlacement::whereHas('lyricLine.section', fn ($q) => $q->where('song_id', $waltz->id))->get();
    expect($waltzPlacements)->toHaveCount(45);
    expect($waltzPlacements->filter(fn ($p) => $p->position !== null && $p->start_beat !== null))->toHaveCount(40);
    expect($waltzPlacements->filter(fn ($p) => $p->position !== null && $p->start_beat === null))->toHaveCount(2);
    expect($waltzPlacements->filter(fn ($p) => $p->position === null && $p->start_beat !== null))->toHaveCount(2);
    expect($waltzPlacements->filter(fn ($p) => $p->position === null && $p->start_beat === null))->toHaveCount(1);

    // 4. Pastikan tidak ada duplikasi start_beat per lagu & tidak ada position invalid
    foreach ([$sempurna, $waltz] as $targetSong) {
        $beats = DB::table('chord_placements')
            ->join('lyric_lines', 'chord_placements.lyric_line_id', '=', 'lyric_lines.id')
            ->join('song_sections', 'lyric_lines.song_section_id', '=', 'song_sections.id')
            ->where('song_sections.song_id', $targetSong->id)
            ->whereNotNull('chord_placements.start_beat')
            ->pluck('chord_placements.start_beat');

        expect($beats->count())->toBe($beats->unique()->count());
        expect($beats->min())->toBeGreaterThanOrEqual(1);
    }
});

