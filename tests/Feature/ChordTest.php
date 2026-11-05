<?php

use App\Models\Chord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
    $response = $this->get('/chords?q=Dsus4');

    $response->assertStatus(200);
    $response->assertSee('Dsus4');
});

test('admin can create a new chord with optional diagram upload', function () {
    Storage::fake('public');
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
});

test('admin can create a new chord without diagram and uses default placeholder', function () {
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
