<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest can see user profile', function () {
    User::create([
        'username' => 'profileuser',
        'email' => 'profile@example.com',
        'password_hash' => bcrypt('password'),
        'bio' => 'Hello world',
    ]);

    $response = $this->getJson('/api/profiles/profileuser');

    $response->assertStatus(200)
        ->assertJsonPath('username', 'profileuser')
        ->assertJsonPath('bio', 'Hello world');
});

test('user can update their own profile', function () {
    $user = User::create([
        'username' => 'editor',
        'email' => 'editor@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user)
        ->putJson('/api/profile', [
            'bio' => 'My new bio',
            'avatar_url' => 'https://example.com/avatar.png',
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'bio' => 'My new bio',
        'avatar_url' => 'https://example.com/avatar.png',
    ]);
});

test('user can see their own stats', function () {
    $user = User::create([
        'username' => 'statsuser',
        'email' => 'stats@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user)->getJson('/api/profile/stats');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'reputation_points',
            'posts_count',
            'comments_count',
            'badges_count',
            'followers_count',
            'following_count',
        ]);
});

test('guest can see leaderboard', function () {
    User::create([
        'username' => 'topuser',
        'email' => 'top@example.com',
        'password_hash' => bcrypt('password'),
        'reputation_points' => 1000,
    ]);

    $response = $this->getJson('/api/leaderboard');

    $response->assertStatus(200)
        ->assertJsonPath('0.username', 'topuser');
});
