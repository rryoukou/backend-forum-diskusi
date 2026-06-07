<?php

use App\Models\User;
use App\Models\Category;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can like and unlike a post', function () {
    $user = User::create([
        'username' => 'liker',
        'email' => 'liker@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Like Me',
        'body' => 'Body',
    ]);

    // Like
    $response = $this->actingAs($user)
        ->postJson('/api/like', [
            'target_id' => $post->id,
            'target_type' => 'post',
        ]);

    $response->assertStatus(200)->assertJsonPath('is_liked', true);
    $this->assertDatabaseHas('likes', [
        'user_id' => $user->id,
        'target_id' => $post->id,
        'target_type' => 'post',
    ]);

    // Unlike
    $response = $this->actingAs($user)
        ->postJson('/api/like', [
            'target_id' => $post->id,
            'target_type' => 'post',
        ]);

    $response->assertStatus(200)->assertJsonPath('is_liked', false);
    $this->assertDatabaseMissing('likes', [
        'user_id' => $user->id,
        'target_id' => $post->id,
    ]);
});

test('user can like a comment', function () {
    $user = User::create([
        'username' => 'liker',
        'email' => 'liker@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Post',
        'body' => 'Body',
    ]);

    $comment = Comment::create([
        'post_id' => $post->id,
        'user_id' => $user->id,
        'body' => 'Comment',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/like', [
            'target_id' => $comment->id,
            'target_type' => 'comment',
        ]);

    $response->assertStatus(200)->assertJsonPath('is_liked', true);
    $this->assertDatabaseHas('likes', [
        'user_id' => $user->id,
        'target_id' => $comment->id,
        'target_type' => 'comment',
    ]);
});
