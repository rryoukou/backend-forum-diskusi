<?php

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user cannot create a post if they have less than 10 points', function () {
    $user = User::create([
        'username' => 'newuser',
        'email' => 'new@example.com',
        'password_hash' => bcrypt('password'),
        'reputation_points' => 5, // Less than 10
    ]);

    $category = Category::create([
        'name' => 'General',
        'slug' => 'general',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/posts', [
            'category_id' => $category->id,
            'title' => 'My First Post',
            'body' => 'This is the content of the post.',
        ]);

    $response->assertStatus(403)
        ->assertJsonPath('message', 'You need at least 10 reputation points to create a post.');
});

test('user can create a post if they have 10 or more points', function () {
    $user = User::create([
        'username' => 'reputableuser',
        'email' => 'rep@example.com',
        'password_hash' => bcrypt('password'),
        'reputation_points' => 10,
    ]);

    $category = Category::create([
        'name' => 'General',
        'slug' => 'general',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/posts', [
            'category_id' => $category->id,
            'title' => 'My First Post',
            'body' => 'This is the content of the post.',
        ]);

    $response->assertStatus(201);
});

test('admin can create a post even with 0 points', function () {
    $adminRole = \App\Models\Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
    
    $admin = User::create([
        'username' => 'adminuser',
        'email' => 'admin@example.com',
        'password_hash' => bcrypt('password'),
        'reputation_points' => 0,
    ]);
    $admin->roles()->attach($adminRole->id);

    $category = Category::create([
        'name' => 'General',
        'slug' => 'general',
    ]);

    $response = $this->actingAs($admin)
        ->postJson('/api/posts', [
            'category_id' => $category->id,
            'title' => 'Admin Post',
            'body' => 'Admin can post anyway.',
        ]);

    $response->assertStatus(201);
});
