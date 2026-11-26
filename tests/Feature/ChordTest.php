<?php

use App\Jobs\GenerateChordAudio;
use App\Models\Chord;
use App\Models\ChordDiagram;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('guest can view pustaka chord without admin controls', function () {
    $chord = Chord::firstOrCreate(['name' => 'C'], ['pronunciation' => 'C']);

    $response = $this->get('/chords');

    $response->assertStatus(200);
    $response->assertSee('Pustaka Chord');
    $response->assertDontSee('CHORD TERPILIH');
    $response->assertSee('C');
    $response->assertSee('No_Image_Available.jpg');
    $response->assertDontSee('Tambah Chord');
    $response->assertDontSee('Buat Chord Baru');
    $response->assertDontSee('Perbarui informasi chord atau perbarui diagram visual');
});

test('regular user can view pustaka chord without admin controls', function () {
    $user = User::factory()->create(['role' => 'user']);
    $chord = Chord::firstOrCreate(['name' => 'C'], ['pronunciation' => 'C']);

    $response = $this->actingAs($user)->get('/chords');

    $response->assertStatus(200);
    $response->assertSee('Pustaka Chord');
    $response->assertSee('C');
    $response->assertDontSee('Tambah Chord');
    $response->assertDontSee('Buat Chord Baru');
    $response->assertDontSee('Perbarui informasi chord atau perbarui diagram visual');
});

test('admin can view pustaka chord with admin controls', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $chord = Chord::firstOrCreate(['name' => 'C'], ['pronunciation' => 'C']);

    $response = $this->actingAs($admin)->get('/chords');

    $response->assertStatus(200);
    $response->assertSee('Tambah Chord');
    $response->assertSee('Buat Chord Baru');
    $response->assertSee('Perbarui informasi chord atau perbarui diagram visual');
});

test('admin can search chord in pustaka chord', function () {
    Chord::firstOrCreate(
        ['name' => 'F#m7'],
        ['pronunciation' => 'F sharp minor seven']
    );

    // Pencarian berdasarkan nama chord berhasil menemukan chord terkait
    $responseByName = $this->get('/chords?q=F%23m7');
    $responseByName->assertStatus(200);
    $responseByName->assertSee('F#m7');

    // Pencarian berdasarkan pronunciation tidak memunculkan chord
    $responseByPronunciation = $this->get('/chords?q=sharp%20minor');
    $responseByPronunciation->assertStatus(200);
    $responseByPronunciation->assertSee('Chord Tidak Ditemukan');
});

test('admin can create a new chord with optional diagram upload', function () {
    Storage::fake('public');
    Queue::fake();
    $admin = User::factory()->create(['role' => 'admin']);

    $file = UploadedFile::fake()->image('chord_diagram.png');

    $response = $this->actingAs($admin)->post('/chords', [
        'name' => 'Cmaj9',
        'pronunciation' => 'C Major Nine',
        'diagram' => $file,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('chords', [
        'name' => 'Cmaj9',
        'pronunciation' => 'C Major Nine',
    ]);

    $createdChord = Chord::where('name', 'Cmaj9')->first();
    $this->assertDatabaseHas('chord_diagrams', [
        'chord_id' => $createdChord->id,
    ]);
    Queue::assertPushed(GenerateChordAudio::class, function (GenerateChordAudio $job) use ($createdChord) {
        return $job->chordId === $createdChord->id;
    });
});

test('admin can create a new chord without diagram and uses default placeholder', function () {
    Queue::fake();
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post('/chords', [
        'name' => 'Cdim7',
        'pronunciation' => 'C Diminished Seven',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('chords', [
        'name' => 'Cdim7',
    ]);

    $createdChord = Chord::where('name', 'Cdim7')->first();
    expect($createdChord->diagram_url)->toContain('No_Image_Available.jpg');
    Queue::assertPushed(GenerateChordAudio::class, function (GenerateChordAudio $job) use ($createdChord) {
        return $job->chordId === $createdChord->id;
    });
});

test('regular user cannot create a chord', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->post('/chords', [
        'name' => 'Xchord',
        'pronunciation' => 'X Chord',
    ]);

    $response->assertStatus(403);
    $this->assertDatabaseMissing('chords', [
        'name' => 'Xchord',
    ]);
});

test('admin can update an existing chord', function () {
    Queue::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $chord = Chord::create([
        'name' => 'OldChord',
        'pronunciation' => 'Old Pronunciation',
    ]);

    $response = $this->actingAs($admin)->put("/chords/{$chord->id}", [
        'name' => 'UpdatedChord',
        'pronunciation' => 'Updated Pronunciation',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('chords', [
        'id' => $chord->id,
        'name' => 'UpdatedChord',
        'pronunciation' => 'Updated Pronunciation',
    ]);
    Queue::assertPushed(GenerateChordAudio::class, function (GenerateChordAudio $job) use ($chord) {
        return $job->chordId === $chord->id && $job->force === true;
    });
});

test('updating chord name or pronunciation removes old audio file and queues tts regeneration', function () {
    Storage::fake('public');
    Queue::fake();
    $admin = User::factory()->create(['role' => 'admin']);

    $oldAudioPath = 'chords/audio/oldaudio.mp3';
    Storage::disk('public')->put($oldAudioPath, 'old-audio-bytes');

    $chord = Chord::create([
        'name' => 'F#mTest',
        'pronunciation' => 'Fis minor lama',
        'file_path' => $oldAudioPath,
    ]);

    $response = $this->actingAs($admin)->put("/chords/{$chord->id}", [
        'name' => 'F#mTest',
        'pronunciation' => 'Fis minor baru',
    ]);

    $response->assertRedirect();
    Storage::disk('public')->assertMissing($oldAudioPath);
    expect($chord->fresh()->file_path)->toBeNull();
    Queue::assertPushed(GenerateChordAudio::class, function (GenerateChordAudio $job) use ($chord) {
        return $job->chordId === $chord->id && $job->force === true;
    });
});

test('regular user cannot update a chord', function () {
    $user = User::factory()->create(['role' => 'user']);
    $chord = Chord::create([
        'name' => 'ChordProtected',
        'pronunciation' => 'Protected',
    ]);

    $response = $this->actingAs($user)->put("/chords/{$chord->id}", [
        'name' => 'HackedChord',
        'pronunciation' => 'Hacked',
    ]);

    $response->assertStatus(403);
});

test('admin can delete a chord', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $chord = Chord::create([
        'name' => 'DeleteMeChord',
        'pronunciation' => 'Delete Me',
    ]);

    $response = $this->actingAs($admin)->delete("/chords/{$chord->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('chords', [
        'id' => $chord->id,
    ]);
});

test('deleting a chord removes its diagram image and tts audio files from storage', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    $diagramPath = 'chords/diagrams/custom-delete-test.png';
    $audioPath = 'chords/audio/customdeletetest.mp3';
    Storage::disk('public')->put($diagramPath, 'fake-image');
    Storage::disk('public')->put($audioPath, 'fake-audio');

    $chord = Chord::create([
        'name' => 'CustomDeleteTest',
        'pronunciation' => 'Custom Delete Test',
        'file_path' => $audioPath,
    ]);
    ChordDiagram::create([
        'chord_id' => $chord->id,
        'file_path' => $diagramPath,
    ]);

    $response = $this->actingAs($admin)->delete("/chords/{$chord->id}");

    $response->assertRedirect(route('chords.index'));
    $this->assertDatabaseMissing('chords', ['id' => $chord->id]);
    $this->assertDatabaseMissing('chord_diagrams', ['chord_id' => $chord->id]);
    Storage::disk('public')->assertMissing($diagramPath);
    Storage::disk('public')->assertMissing($audioPath);
});

test('deleting a chord using default placeholder preserves No_Image_Available.jpg in storage', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    $placeholderPath = ChordDiagram::DEFAULT_PLACEHOLDER_PATH;
    Storage::disk('public')->put($placeholderPath, 'placeholder-image');

    $chord = Chord::create([
        'name' => 'PlaceholderDeleteTest',
        'pronunciation' => 'Placeholder Delete Test',
    ]);
    ChordDiagram::create([
        'chord_id' => $chord->id,
        'file_path' => $placeholderPath,
    ]);

    $response = $this->actingAs($admin)->delete("/chords/{$chord->id}");

    $response->assertRedirect(route('chords.index'));
    $this->assertDatabaseMissing('chords', ['id' => $chord->id]);
    Storage::disk('public')->assertExists($placeholderPath);
});

test('updating a chord diagram removes the old diagram file from storage', function () {
    Storage::fake('public');
    Queue::fake();
    $admin = User::factory()->create(['role' => 'admin']);

    $oldPath = 'chords/diagrams/old-diagram.png';
    $audioPath = 'chords/audio/replacediagramchord.mp3';
    Storage::disk('public')->put($oldPath, 'old-image');
    Storage::disk('public')->put($audioPath, 'existing-audio');

    $chord = Chord::create([
        'name' => 'ReplaceDiagramChord',
        'pronunciation' => 'Replace Diagram Chord',
        'file_path' => $audioPath,
    ]);
    ChordDiagram::create([
        'chord_id' => $chord->id,
        'file_path' => $oldPath,
    ]);

    $newFile = UploadedFile::fake()->image('new-diagram.png');
    $response = $this->actingAs($admin)->put("/chords/{$chord->id}", [
        'name' => 'ReplaceDiagramChord',
        'pronunciation' => 'Replace Diagram Chord',
        'diagram' => $newFile,
    ]);

    $response->assertRedirect();
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($audioPath);
    Queue::assertNotPushed(GenerateChordAudio::class);
    $newDiagram = $chord->fresh()->diagrams()->first();
    expect($newDiagram)->not->toBeNull();
    Storage::disk('public')->assertExists($newDiagram->file_path);
});

test('deleting a chord still used in chord_placements is rejected and preserves its files', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    $diagramPath = 'chords/diagrams/in-use-chord.png';
    $audioPath = 'chords/audio/inusechord.mp3';
    Storage::disk('public')->put($diagramPath, 'fake-image');
    Storage::disk('public')->put($audioPath, 'fake-audio');

    $chord = Chord::create([
        'name' => 'InUseChord',
        'pronunciation' => 'In Use Chord',
        'file_path' => $audioPath,
    ]);
    ChordDiagram::create([
        'chord_id' => $chord->id,
        'file_path' => $diagramPath,
    ]);

    $song = Song::create([
        'user_id' => $admin->id,
        'title' => 'Lagu Pemakai Chord',
        'bpm' => 100,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);
    $section = SongSection::create(['song_id' => $song->id, 'name' => 'Verse', 'sequence' => 1]);
    $line = LyricLine::create(['song_section_id' => $section->id, 'line_number' => 1, 'content' => 'Baris uji']);
    ChordPlacement::create([
        'lyric_line_id' => $line->id,
        'chord_id' => $chord->id,
        'position' => 0,
        'start_beat' => 1,
    ]);

    $response = $this->actingAs($admin)->delete("/chords/{$chord->id}");

    $response->assertRedirect(route('chords.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('chords', ['id' => $chord->id]);
    Storage::disk('public')->assertExists($diagramPath);
    Storage::disk('public')->assertExists($audioPath);
});

test('regular user cannot delete a chord', function () {
    $user = User::factory()->create(['role' => 'user']);
    $chord = Chord::create([
        'name' => 'SafeChord',
        'pronunciation' => 'Safe',
    ]);

    $response = $this->actingAs($user)->delete("/chords/{$chord->id}");

    $response->assertStatus(403);
    $this->assertDatabaseHas('chords', [
        'id' => $chord->id,
    ]);
});
