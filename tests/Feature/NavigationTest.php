<?php

use App\Models\User;

test('chords library page can be rendered', function () {
    $response = $this->get('/chords');

    $response->assertStatus(200);
});

test('my songs requires authentication', function () {
    $response = $this->get('/my/songs');

    $response->assertRedirect('/login');
});

test('my songs can be rendered for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/my/songs');

    $response->assertStatus(200);
});

test('regular users cannot access admin area', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->get('/admin/users');
    $response->assertStatus(403);

    $response = $this->actingAs($user)->get('/admin/songs');
    $response->assertStatus(403);
});

test('admin can access admin area', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/users');
    $response->assertStatus(200);

    $response = $this->actingAs($admin)->get('/admin/songs');
    $response->assertStatus(200);
});

