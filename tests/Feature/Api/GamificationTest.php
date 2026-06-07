<?php

use App\Models\User;
use App\Models\Badge;
use App\Models\PointsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can list available badges', function () {
    $user = User::create(['username' => 'u', 'email' => 'u@e.c', 'password_hash' => 'p']);
    Badge::create([
        'name' => 'First Post',
        'description' => 'Created your first post',
        'icon_url' => 'icon.png',
        'tier' => 'bronze',
        'condition_type' => 'posts_count',
        'condition_value' => 1,
    ]);

    $response = $this->actingAs($user)->getJson('/api/badges');

    $response->assertStatus(200)->assertJsonCount(1);
});

test('user can see their earned badges', function () {
    $user = User::create(['username' => 'u', 'email' => 'u@e.c', 'password_hash' => 'p']);
    $badge = Badge::create([
        'name' => 'First Post',
        'description' => 'D',
        'icon_url' => 'i',
        'tier' => 'bronze',
        'condition_type' => 'posts_count',
        'condition_value' => 1,
    ]);

    $user->badges()->attach($badge->id, [
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'earned_at' => now()
    ]);

    $response = $this->actingAs($user)->getJson('/api/my-badges');

    $response->assertStatus(200)->assertJsonCount(1);
    $response->assertJsonPath('0.name', 'First Post');
});

test('user can see reputation points history', function () {
    $user = User::create(['username' => 'u', 'email' => 'u@e.c', 'password_hash' => 'p']);
    PointsLog::create([
        'user_id' => $user->id,
        'points' => 10,
        'action_type' => 'post_created',
        'description' => 'Created a post',
    ]);

    $response = $this->actingAs($user)->getJson('/api/reputation-history');

    $response->assertStatus(200)->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.points', 10);
});
