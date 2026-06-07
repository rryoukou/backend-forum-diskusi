<?php

use App\Models\User;
use App\Models\Category;
use App\Services\ReputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user levels up when reaching points threshold', function () {
    $user = User::create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password_hash' => bcrypt('password'),
        'reputation_points' => 95,
        'level' => 1,
    ]);

    // Add 10 points to cross level 2 threshold (100)
    ReputationService::addPoints($user, 10, 'test_action', null, 'Testing level up');

    expect($user->fresh()->level)->toBe(2);
});

test('user gets notification on level up', function () {
    $user = User::create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password_hash' => bcrypt('password'),
        'reputation_points' => 95,
        'level' => 1,
    ]);

    ReputationService::addPoints($user, 10, 'test_action', null, 'Testing level up');

    $this->assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'type' => 'level_up',
    ]);
});
