<?php

use App\Models\User;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostEditHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create a post', function () {
    $user = User::create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password_hash' => bcrypt('password'),
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
            'tags' => ['laravel', 'api'],
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.title', 'My First Post');

    $this->assertDatabaseHas('posts', [
        'title' => 'My First Post',
        'user_id' => $user->id,
    ]);
});

test('user can update their own post', function () {
    $user = User::create([
        'username' => 'editor',
        'email' => 'editor@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Old Title',
        'body' => 'Old Body',
    ]);

    $response = $this->actingAs($user)
        ->putJson("/api/posts/{$post->id}", [
            'title' => 'New Title',
            'body' => 'New Body content',
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'New Title',
    ]);

    // Check history was recorded
    $this->assertDatabaseHas('post_edit_history', [
        'post_id' => $post->id,
        'body_before' => 'Old Body',
        'body_after' => 'New Body content',
    ]);
});

test('user can delete their own post', function () {
    $user = User::create([
        'username' => 'deleter',
        'email' => 'deleter@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'To be deleted',
        'body' => 'Body',
    ]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/posts/{$post->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

test('guest can see posts with search and filters', function () {
    $user = User::create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $cat1 = Category::create(['name' => 'Tech', 'slug' => 'tech']);
    $cat2 = Category::create(['name' => 'Life', 'slug' => 'life']);

    Post::create([
        'user_id' => $user->id,
        'category_id' => $cat1->id,
        'title' => 'Laravel API',
        'body' => 'Body',
    ]);

    Post::create([
        'user_id' => $user->id,
        'category_id' => $cat2->id,
        'title' => 'Living Life',
        'body' => 'Body',
    ]);

    // Search
    $response = $this->getJson('/api/posts?search=Laravel');
    $response->assertStatus(200)->assertJsonCount(1, 'data');

    // Category Filter
    $response = $this->getJson("/api/posts?category_id={$cat2->id}");
    $response->assertStatus(200)->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.title', 'Living Life');
});

test('can get trending posts', function () {
    $user = User::create(['username' => 'u', 'email' => 'u@e.c', 'password_hash' => 'p']);
    $cat = Category::create(['name' => 'C', 'slug' => 'c']);

    $p1 = Post::create(['user_id' => $user->id, 'category_id' => $cat->id, 'title' => 'T1', 'body' => 'B', 'view_count' => 10]);
    $p2 = Post::create(['user_id' => $user->id, 'category_id' => $cat->id, 'title' => 'T2', 'body' => 'B', 'view_count' => 100]);

    $response = $this->getJson('/api/posts/trending');
    $response->assertStatus(200);
    $response->assertJsonPath('0.title', 'T2');
});

test('can get post edit history', function () {
    $user = User::create(['username' => 'u', 'email' => 'u@e.c', 'password_hash' => 'p']);
    $cat = Category::create(['name' => 'C', 'slug' => 'c']);
    $post = Post::create(['user_id' => $user->id, 'category_id' => $cat->id, 'title' => 'T', 'body' => 'B1']);

    PostEditHistory::create([
        'post_id' => $post->id,
        'edited_by' => $user->id,
        'body_before' => 'B1',
        'body_after' => 'B2',
    ]);

    $response = $this->getJson("/api/posts/{$post->id}/history");
    $response->assertStatus(200)->assertJsonCount(1);
});
