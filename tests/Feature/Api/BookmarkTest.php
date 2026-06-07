<?php

use App\Models\User;
use App\Models\Category;
use App\Models\Post;
use App\Models\Bookmark;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can bookmark a post', function () {
    $user = User::create([
        'username' => 'bookmarker',
        'email' => 'bookmarker@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Bookmark Me',
        'body' => 'Save this post.',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/bookmarks', [
            'post_id' => $post->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('is_bookmarked', true);

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $user->id,
        'post_id' => $post->id,
    ]);
});

test('user can remove a bookmark', function () {
    $user = User::create([
        'username' => 'bookmarker',
        'email' => 'bookmarker@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Post',
        'body' => 'Body',
    ]);

    Bookmark::create([
        'user_id' => $user->id,
        'post_id' => $post->id,
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/bookmarks', [
            'post_id' => $post->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('is_bookmarked', false);

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $user->id,
        'post_id' => $post->id,
    ]);
});

test('user can list their bookmarks', function () {
    $user = User::create([
        'username' => 'bookmarker',
        'email' => 'bookmarker@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Bookmarked Post',
        'body' => 'Body',
    ]);

    Bookmark::create([
        'user_id' => $user->id,
        'post_id' => $post->id,
    ]);

    $response = $this->actingAs($user)->getJson('/api/bookmarks');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.post.title', 'Bookmarked Post');
});
