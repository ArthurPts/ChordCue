<?php

use App\Models\Artist;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->artist = Artist::create(['name' => 'Payung Teduh']);

    $this->owner = User::create([
        'username'   => 'pemilik_lagu',
        'email'      => 'pemilik@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 5,
    ]);

    $this->otherUser = User::create([
        'username'   => 'user_lain',
        'email'      => 'userlain@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'user',
        'song_limit' => 5,
    ]);

    $this->admin = User::create([
        'username'   => 'admin_player',
        'email'      => 'adminplayer@example.com',
        'password'   => bcrypt('password'),
        'role'       => 'admin',
        'song_limit' => 99,
    ]);

    $this->chordC = Chord::create([
        'name'          => 'C',
        'pronunciation' => 'C',
        'file_path'     => 'chords/audio/c.mp3',
    ]);
    Storage::disk('public')->put('chords/audio/c.mp3', 'dummy-c-audio');

    $this->chordG = Chord::create([
        'name'          => 'G',
        'pronunciation' => 'G',
        'file_path'     => 'chords/audio/g.mp3',
    ]);
    Storage::disk('public')->put('chords/audio/g.mp3', 'dummy-g-audio');

    // Lagu publik lengkap dengan backing track dan chord
    Storage::disk('public')->put('songs/audio/resah.mp3', 'dummy backing track');
    $this->publicSong = Song::create([
        'user_id'                    => $this->owner->id,
        'artist_id'                  => $this->artist->id,
        'title'                      => 'Resah',
        'default_key'                => 'C',
        'bpm'                        => 85,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
        'backing_track_path'         => 'songs/audio/resah.mp3',
        'backing_track_offset'       => 2.500,
    ]);

    // Section, lyric line, dan placement lengkap untuk publicSong
    $section = SongSection::create([
        'song_id'  => $this->publicSong->id,
        'name'     => 'Bait 1',
        'sequence' => 1,
    ]);

    $line = LyricLine::create([
        'song_section_id' => $section->id,
        'line_number'     => 1,
        'content'         => 'Aku berjalan menyusuri malam',
    ]);

    ChordPlacement::create([
        'lyric_line_id' => $line->id,
        'chord_id'      => $this->chordC->id,
        'position'      => 0,
        'start_beat'    => 1,
    ]);

    ChordPlacement::create([
        'lyric_line_id' => $line->id,
        'chord_id'      => $this->chordG->id,
        'position'      => 12,
        'start_beat'    => 5,
    ]);

    // Lagu privat milik owner tanpa section/lirik sama sekali
    $this->emptySong = Song::create([
        'user_id'                    => $this->owner->id,
        'artist_id'                  => $this->artist->id,
        'title'                      => 'Lagu Kosong',
        'default_key'                => 'G',
        'bpm'                        => 110,
        'time_signature_numerator'   => 3,
        'time_signature_denominator' => 4,
        'visibility'                 => 'private',
        'backing_track_path'         => null,
        'backing_track_offset'       => null,
    ]);

    // Lagu dengan lirik tapi TANPA penempatan chord sama sekali
    $this->lyricsOnlySong = Song::create([
        'user_id'                    => $this->owner->id,
        'artist_id'                  => $this->artist->id,
        'title'                      => 'Menuju Senja',
        'default_key'                => 'D',
        'bpm'                        => 95,
        'time_signature_numerator'   => 4,
        'time_signature_denominator' => 4,
        'visibility'                 => 'public',
        'backing_track_path'         => null,
        'backing_track_offset'       => null,
    ]);

    $senjaSection = SongSection::create([
        'song_id'  => $this->lyricsOnlySong->id,
        'name'     => 'Intro Lirik',
        'sequence' => 1,
    ]);

    LyricLine::create([
        'song_section_id' => $senjaSection->id,
        'line_number'     => 1,
        'content'         => 'Bersama angin malam kutuliskan kata',
    ]);
    LyricLine::create([
        'song_section_id' => $senjaSection->id,
        'line_number'     => 2,
        'content'         => 'Menanti senja yang tak kunjung tiba',
    ]);
});

test('tamu (guest) dapat mengakses halaman player lagu publik', function () {
    $response = $this->get(route('songs.show', $this->publicSong));

    $response->assertStatus(200);
    $response->assertSee('Resah');
    $response->assertSee('Payung Teduh');
    $response->assertSee('Putar');
    $response->assertSee('Berhenti');
    $response->assertSee('Tempo Sesi (BPM)');
    $response->assertSee('Transpose Sesi');
});

test('tamu (guest) ditolak (403) saat mengakses halaman player lagu privat', function () {
    $response = $this->get(route('songs.show', $this->emptySong));

    $response->assertStatus(403);
});

test('pengguna bukan pemilik ditolak (403) saat mengakses halaman player lagu privat orang lain', function () {
    $response = $this->actingAs($this->otherUser)->get(route('songs.show', $this->emptySong));

    $response->assertStatus(403);
});

test('pemilik lagu dapat mengakses halaman player lagu privat miliknya', function () {
    $response = $this->actingAs($this->owner)->get(route('songs.show', $this->emptySong));

    $response->assertStatus(200);
    $response->assertSee('Lagu Kosong');
    $response->assertSee('Privat');
    $response->assertSee('Edit Lagu');
});

test('administrator dapat mengakses halaman player lagu privat milik pengguna lain', function () {
    $response = $this->actingAs($this->admin)->get(route('songs.show', $this->emptySong));

    $response->assertStatus(200);
    $response->assertSee('Lagu Kosong');
    $response->assertSee('Edit Lagu');
});

test('halaman player merender metrik musik, informasi offset dengan satuan ms, dan kontrol tape deck dengan benar', function () {
    $response = $this->get(route('songs.show', $this->publicSong));

    $response->assertStatus(200);

    // Verifikasi metrik musik dan offset aktif
    $response->assertSee('85');
    $response->assertSee('4/4');
    $response->assertSee('Offset Backing Track:');
    $response->assertSee('+2500 ms (+2.500s)');
    $response->assertSee('Offset Aba-aba:');
    $response->assertSee('1000 ms');

    // Verifikasi kontrol tape deck
    $response->assertSee('Putar');
    $response->assertSee('Berhenti');
    $response->assertSee('Metronom');
    $response->assertSee('Suara Chord');
    $response->assertSee('Musik Pengiring');
    $response->assertSee('chordsheetPlayer');
});

test('informasi offset tetap ditampilkan dengan jelas pada lagu tanpa file backing track', function () {
    $response = $this->get(route('songs.show', $this->lyricsOnlySong));

    $response->assertStatus(200);
    $response->assertSee('Offset Backing Track:');
    $response->assertSee('0 ms (0.000s)');
    $response->assertSee('Offset Aba-aba:');
    $response->assertSee('1000 ms');
});

test('lirik tampil kontras putih terang pada tema gelap tanpa benturan class text-base', function () {
    $viewContent = file_get_contents(resource_path('views/songs/player.blade.php'));

    // Pastikan tidak ada token invalid text-base yang berubah menjadi warna gelap #0A0F1A
    expect($viewContent)->not->toContain('text-base');
    expect($viewContent)->not->toContain('sm:text-base');

    // Pastikan lirik memakai warna kontras putih dan ukuran font token resmi
    $response = $this->get(route('songs.show', $this->lyricsOnlySong));
    $response->assertStatus(200);
    $response->assertSee('text-title text-white', false);
});

test('lagu dengan lirik tetapi tanpa chord placements tetap menampilkan lirik utuh dan menonaktifkan fitur chord tanpa pesan ganda', function () {
    $response = $this->get(route('songs.show', $this->lyricsOnlySong));

    $response->assertStatus(200);

    // 1. Lirik TETAP tampil dan tidak disembunyikan
    $response->assertSee('Intro Lirik');
    $response->assertSee('Bersama angin malam kutuliskan kata');
    $response->assertSee('Menanti senja yang tak kunjung tiba');

    // 2. Pesan panjang tidak digandakan dalam kartu lirik; ditempatkan di status utama (player.js) menggantikan "Chord belum lengkap"
    $playerJs = file_get_contents(resource_path('js/player.js'));
    expect($playerJs)->toContain('Lagu ini belum memiliki penempatan chord yang lengkap. Pemutaran aba-aba chord dinonaktifkan.');
    expect($playerJs)->not->toContain("'Chord belum lengkap'");

    $bladeView = file_get_contents(resource_path('views/songs/player.blade.php'));
    expect($bladeView)->not->toContain('Chord belum lengkap');

    // 3. Kontrol Suara Chord dinonaktifkan dan Putar dimulai dalam keadaan disabled
    $response->assertSee(':disabled="!hasCompletePlacements"', false);
    $response->assertSee(':disabled="isPlayDisabled"', false);
});

test('slider bpm menyelaraskan progress fill (0..200) secara presisi dengan range thumb (40..240)', function () {
    $response = $this->get(route('songs.show', $this->lyricsOnlySong));

    $response->assertStatus(200);
    // Pastikan <progress> memakai rentang 0..200 dan offset nilai sessionBpm - 40 agar tidak meleset dari bullet range
    $response->assertSee('max="200"', false);
    $response->assertSee(':value="Math.max(0, sessionBpm - 40)"', false);
    $response->assertSee('value="55"', false); // 95 - 40 = 55 untuk BPM 95

    $bladeView = file_get_contents(resource_path('views/songs/player.blade.php'));
    expect($bladeView)->not->toContain('<progress class="ui-progress" min="40"');
});

test('metronom tetap dapat diputar meski lagu belum memiliki penempatan chord, sementara chorded song tetap mewajibkan audio chord siap', function () {
    $audioEngineJs = file_get_contents(resource_path('js/audio/audio-engine.js'));
    $playerJs = file_get_contents(resource_path('js/player.js'));

    // Pastikan AudioEngine memiliki penjagaan canPlay() sebelum memulai playback/metronom
    expect($audioEngineJs)->toContain('canPlay()');
    expect($audioEngineJs)->toContain('if (!this.canPlay())');
    expect($audioEngineJs)->toContain('if (!this.audioContext || !this.isPlaying) return;');
    expect($audioEngineJs)->toContain('this.metronomeGain.gain.setValueAtTime(this.isMetronomeMuted ? 0 : 1, now)');

    // Pastikan mode metronom kontinyu tersedia saat tidak ada chord maupun backing track
    expect($audioEngineJs)->toContain('isContinuousMetronome');
    expect($audioEngineJs)->toContain('completePlacementCount === 0');

    // Pastikan jika ada chord lengkap (completePlacements.length > 0), audio chord wajib ter-preload sebelum play
    expect($audioEngineJs)->toContain('if (completePlacements.length > 0)');
    expect($audioEngineJs)->toContain('failedUrls');

    // Pastikan player.js tetap mengizinkan metronom tanpa chord via isPreloaded, dan mewajibkan audioReady bila chord ada
    expect($playerJs)->toContain('!this.isPlayReady');
    expect($playerJs)->toContain('this.preloadFailed');
    expect($playerJs)->toContain('if (this.hasCompletePlacements)');

    // Pastikan metronom menggunakan sintesis Web Audio OscillatorNode secara offline tanpa ketergantungan file eksternal
    expect($audioEngineJs)->toContain('createOscillator()');
});

test('lagu tanpa lirik sama sekali menampilkan empty state lirik yang tepat', function () {
    $response = $this->actingAs($this->owner)->get(route('songs.show', $this->emptySong));

    $response->assertStatus(200);
    $response->assertSee('Belum Ada Lirik');
    $response->assertSee('Buka Editor Lagu');
});

test('kartu lagu di katalog publik dan lagu saya mengarah ke route player songs.show', function () {
    // Di homepage / katalog
    $responseHome = $this->get(route('home'));
    $responseHome->assertStatus(200);
    $responseHome->assertSee(route('songs.show', $this->publicSong));

    // Di my songs
    $responseMySongs = $this->actingAs($this->owner)->get(route('my.songs'));
    $responseMySongs->assertStatus(200);
    $responseMySongs->assertSee(route('songs.show', $this->publicSong));
});

test('timeline yang dikonsumsi player hanya memuat placement lengkap dan backing_track_offset', function () {
    // Tambahkan placement tidak lengkap (start_beat null)
    $section = $this->publicSong->sections()->first();
    $line = $section->lines()->first();

    ChordPlacement::create([
        'lyric_line_id' => $line->id,
        'chord_id'      => $this->chordC->id,
        'position'      => 5,
        'start_beat'    => null, // Incomplete
    ]);

    $response = $this->get(route('songs.timeline', $this->publicSong));
    $response->assertStatus(200);

    $data = $response->json();
    expect($data['song']['backing_track_offset'])->toEqual(2.500);
    expect($data['song']['backing_track']['offset'])->toEqual(2.500);

    // Pastikan hanya 2 placement lengkap yang dikembalikan, bukan 3
    $placements = $data['sections'][0]['lines'][0]['placements'];
    expect($placements)->toHaveCount(2);
    expect($placements[0]['start_beat'])->toEqual(1);
    expect($placements[1]['start_beat'])->toEqual(5);
});

test('transpose sesi di timeline player tidak mengubah data default_key lagu di database', function () {
    expect($this->publicSong->default_key)->toEqual('C');

    $response = $this->get(route('songs.timeline', ['song' => $this->publicSong, 'transpose' => 2]));
    $response->assertStatus(200);

    $data = $response->json();
    expect($data['song']['key'])->toEqual('D');
    expect($data['transpose'])->toEqual(2);

    // Verifikasi database tidak tersentuh
    $this->publicSong->refresh();
    expect($this->publicSong->default_key)->toEqual('C');
});

test('seluruh teks antarmuka player menggunakan Bahasa Indonesia murni tanpa fungsi translasi', function () {
    $viewContent = file_get_contents(resource_path('views/songs/player.blade.php'));

    // Pastikan tidak ada fungsi lokalisasi __() atau {{ __('...') }}
    expect($viewContent)->not->toContain('__(');
    expect($viewContent)->not->toContain('@lang(');

    // Pastikan copy Bahasa Indonesia hadir
    expect($viewContent)->toContain('Kembali ke Katalog Lagu');
    expect($viewContent)->toContain('Putar');
    expect($viewContent)->toContain('Berhenti');
    expect($viewContent)->toContain('Tempo Sesi (BPM)');
    expect($viewContent)->toContain('Transpose Sesi');
    expect($viewContent)->toContain('Suara Chord');
    expect($viewContent)->toContain('Musik Pengiring');
});

test('konfigurasi disk public memakai path relatif /storage agar bebas error CORS lintas port localhost', function () {
    expect(config('filesystems.disks.public.url'))->toBe('/storage');

    $response = $this->getJson(route('songs.timeline', $this->publicSong));
    $response->assertOk();

    $data = $response->json();
    expect($data['song']['backing_track']['url'])->toStartWith('/storage/');
    expect($data['sections'][0]['lines'][0]['placements'][0]['chord']['audio_url'])->toStartWith('/storage/');
});

test('player memasangkan unit chord di atas potongan lirik presisi dan AudioEngine menunda AudioContext hingga klik Putar', function () {
    $viewContent = file_get_contents(resource_path('views/songs/player.blade.php'));
    $playerJs = file_get_contents(resource_path('js/player.js'));
    $engineJs = file_get_contents(resource_path('js/audio/audio-engine.js'));

    // Chord dan lirik dirender dalam unit vertikal berpasangan tanpa spasi terpisah yang menggeser posisi
    expect($viewContent)->toContain('buildLineUnitsForLine');
    expect($viewContent)->toContain('getLyricSliceClass');
    expect($playerJs)->toContain('buildLineUnits(line)');

    // Sticky Tape Deck + indikator Bar/Beat + strip LED selalu tampil
    expect($viewContent)->toContain('Birama <strong');
    expect($viewContent)->toContain('Beat <strong');

    // AudioEngine mendownload ArrayBuffer saat preload dan baru membuat AudioContext pada play()
    expect($engineJs)->toContain('fetchAudioArrayBuffer(');
    expect($engineJs)->toContain('ensureBuffersDecoded()');

    // Penjadwalan hardware presisi: await resume() sebelum t0, computeChordScheduleTime (Tbeat - D_speech - cue_margin_ms), clamp ke minStartTime, dan rAF visual sync
    expect($engineJs)->toContain('await this.audioContext.resume()');
    expect($engineJs)->toContain('export function computeChordScheduleTime(');
    expect($engineJs)->toContain('Math.max(idealStart, minStartTime)');
    expect($engineJs)->toContain('requestAnimationFrame(() => this.runVisualLoop())');
});

test('sinkronisasi offset, deteksi akhir ucapan TTS, dan konfigurasi threshold/trim/cue_margin terhubung ke player serta lirik tetap putih', function () {
    $viewContent = file_get_contents(resource_path('views/songs/player.blade.php'));
    $playerJs = file_get_contents(resource_path('js/player.js'));
    $engineJs = file_get_contents(resource_path('js/audio/audio-engine.js'));

    // 1. Konfigurasi cue_margin_ms (1000 ms), speech_silence_threshold_db (-40 dBFS) & chord_cue_trim_ms diteruskan ke JS Player
    expect(config('chordcue.cue_margin_ms'))->toBe(1000);
    expect(config('chordcue.speech_silence_threshold_db'))->toBe(-40.0);
    expect(config('chordcue.chord_cue_trim_ms'))->toBe(0);
    expect($viewContent)->toContain('cueMarginMs:');
    expect($viewContent)->toContain('speechSilenceThresholdDb:');
    expect($viewContent)->toContain('chordCueTrimMs:');

    // 2. Teks lirik tidak berubah warna saat pergantian chord
    expect($playerJs)->toContain('getLyricSliceClass(placementId)');
    expect($playerJs)->toContain("return 'text-white';");
    expect($playerJs)->not->toContain("return 'text-ink-secondary';");
    expect($playerJs)->not->toContain('border-b-2 border-accent');

    // 3. Fungsi murni computeAnchors & measureSpeechEnd terhubung ke AudioEngine
    expect($engineJs)->toContain('export function measureSpeechEnd(');
    expect($engineJs)->toContain('export function computeAnchors(');
    expect($engineJs)->toContain('getEffectiveCueDuration(');
    expect($engineJs)->toContain('this.activeBackingSource.start(backingStartAudioTime, backingBufferOffset);');
});
