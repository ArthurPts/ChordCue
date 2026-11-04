<?php

use App\Models\Artist;
use App\Models\Song;
use App\Models\User;

test('admin can view user management table with statistics and user rows', function () {
    $admin = User::factory()->create(['role' => 'admin', 'username' => 'superadmin']);
    $member = User::factory()->create(['role' => 'user', 'username' => 'musician01']);

    $response = $this->actingAs($admin)->get('/admin/users');

    $response->assertStatus(200);
    $response->assertSee('Total User');
    $response->assertSee('Admin');
    $response->assertSee('musician01');
    $response->assertSee('Member');
});

test('admin can filter users by search query and role', function () {
    $admin = User::factory()->create(['role' => 'admin', 'username' => 'adminboss']);
    $member = User::factory()->create(['role' => 'user', 'username' => 'rockstar']);

    $responseSearch = $this->actingAs($admin)->get('/admin/users?q=rockstar');
    $responseSearch->assertStatus(200);
    $usersInSearch = $responseSearch->viewData('users');
    expect($usersInSearch->pluck('username'))->toContain('rockstar')
        ->not->toContain('adminboss');

    $responseRole = $this->actingAs($admin)->get('/admin/users?role=member');
    $responseRole->assertStatus(200);
    $usersInRole = $responseRole->viewData('users');
    expect($usersInRole->pluck('username'))->toContain('rockstar')
        ->not->toContain('adminboss');
});

test('admin can delete a user and cannot delete self', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $targetUser = User::factory()->create(['role' => 'user']);

    // Admin cannot delete self
    $responseSelf = $this->actingAs($admin)->delete("/admin/users/{$admin->id}");
    $responseSelf->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $admin->id]);

    // Admin can delete target user
    $responseDelete = $this->actingAs($admin)->delete("/admin/users/{$targetUser->id}");
    $responseDelete->assertRedirect();
    $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
});

test('admin can view song management table with stats and action buttons', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $artist = Artist::firstOrCreate(['name' => 'Dewa 19']);

    $song = Song::create([
        'user_id' => $admin->id,
        'title' => 'Kangen',
        'artist_id' => $artist->id,
        'bpm' => 110,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $response = $this->actingAs($admin)->get('/admin/songs');

    $response->assertStatus(200);
    $response->assertSee('Kelola Lagu');
    $response->assertSee('Total Lagu');
    $response->assertSee('Public');
    $response->assertSee('Private');
    $response->assertSee('Kangen');
    $response->assertSee('Dewa 19');
    $response->assertSee('Putar');
    $response->assertSee('Edit');
    $response->assertSee('Hapus');
});
