<?php

use App\Models\User;
use App\Models\Follow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can follow another user', function () {
    $follower = User::create([
        'username' => 'follower',
        'email' => 'follower@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $following = User::create([
        'username' => 'following',
        'email' => 'following@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $response = $this->actingAs($follower)
        ->postJson('/api/follow', [
            'user_id' => $following->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('is_following', true);

    $this->assertDatabaseHas('follows', [
        'follower_id' => $follower->id,
        'following_id' => $following->id,
    ]);
});

test('user can unfollow a user', function () {
    $follower = User::create([
        'username' => 'follower',
        'email' => 'follower@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $following = User::create([
        'username' => 'following',
        'email' => 'following@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    Follow::create([
        'follower_id' => $follower->id,
        'following_id' => $following->id,
    ]);

    $response = $this->actingAs($follower)
        ->postJson('/api/follow', [
            'user_id' => $following->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('is_following', false);

    $this->assertDatabaseMissing('follows', [
        'follower_id' => $follower->id,
        'following_id' => $following->id,
    ]);
});

test('user cannot follow themselves', function () {
    $user = User::create([
        'username' => 'myself',
        'email' => 'myself@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/follow', [
            'user_id' => $user->id,
        ]);

    $response->assertStatus(422);
});

test('can list followers and following', function () {
    $userA = User::create(['username' => 'userA', 'email' => 'a@ex.com', 'password_hash' => '...']);
    $userB = User::create(['username' => 'userB', 'email' => 'b@ex.com', 'password_hash' => '...']);

    Follow::create(['follower_id' => $userA->id, 'following_id' => $userB->id]);

    // Check userB's followers
    $response = $this->getJson("/api/profiles/userB/followers");
    $response->assertStatus(200)
        ->assertJsonPath('data.0.username', 'userA');

    // Check userA's following
    $response = $this->getJson("/api/profiles/userA/following");
    $response->assertStatus(200)
        ->assertJsonPath('data.0.username', 'userB');
});
