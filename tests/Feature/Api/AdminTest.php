<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
    $this->userRole = Role::create(['name' => 'user', 'display_name' => 'User']);
    
    $this->admin = User::create([
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password_hash' => bcrypt('password'),
    ]);
    $this->admin->roles()->attach($this->adminRole->id);

    $this->regularUser = User::create([
        'username' => 'user',
        'email' => 'user@example.com',
        'password_hash' => bcrypt('password'),
    ]);
    $this->regularUser->roles()->attach($this->userRole->id);
});

test('admin can list users', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/users');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('regular user cannot list users', function () {
    $response = $this->actingAs($this->regularUser)
        ->getJson('/api/admin/users');

    $response->assertStatus(403);
});

test('admin can update user role', function () {
    $moderatorRole = Role::create(['name' => 'moderator', 'display_name' => 'Moderator']);

    $response = $this->actingAs($this->admin)
        ->postJson("/api/admin/users/{$this->regularUser->id}/roles", [
            'roles' => ['moderator']
        ]);

    $response->assertStatus(200);
    expect($this->regularUser->fresh()->hasRole('moderator'))->toBeTrue();
});

test('admin can create category', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/api/admin/categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
            'description' => 'Description'
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('categories', ['name' => 'New Category']);
});

test('admin can update category', function () {
    $category = Category::create(['name' => 'Old', 'slug' => 'old']);

    $response = $this->actingAs($this->admin)
        ->putJson("/api/admin/categories/{$category->id}", [
            'name' => 'Updated Name',
            'slug' => 'updated-slug'
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated Name']);
});

test('admin can delete category', function () {
    $category = Category::create(['name' => 'To Delete', 'slug' => 'to-delete']);

    $response = $this->actingAs($this->admin)
        ->deleteJson("/api/admin/categories/{$category->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});
