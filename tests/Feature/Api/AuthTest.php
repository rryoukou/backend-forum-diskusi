<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register', function () {
    // Password diubah jadi 'Password123' agar lolos aturan mixedCase bawaan AuthController
    $response = $this->postJson('/api/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'username', 'email'],
            'access_token',
            'token_type',
        ]);

    $this->assertDatabaseHas('users', [
        'username' => 'testuser',
        'email' => 'test@example.com',
    ]);
});

test('user can login', function () {
    $user = User::create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password_hash' => Hash::make('Password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'Password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'access_token',
            'token_type',
            'user' => ['id', 'username', 'email'],
        ]);
});

test('banned user cannot login', function () {
    $user = User::create([
        'username' => 'banneduser',
        'email' => 'banned@example.com',
        'password_hash' => Hash::make('Password123'),
        'is_banned' => true,
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'banned@example.com',
        'password' => 'Password123',
    ]);

    $response->assertStatus(403)
        ->assertJson(['message' => 'Your account has been banned.']);
});

test('authenticated user can get their profile', function () {
    $user = User::create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password_hash' => Hash::make('Password123'),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/me');

    $response->assertStatus(200)
        ->assertJson(['username' => 'testuser']);
});

test('authenticated user can logout', function () {
    $user = User::create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password_hash' => Hash::make('Password123'),
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully']);

    $this->assertCount(0, $user->tokens);
});