<?php

use App\Models\User;
use App\Models\Category;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can upvote a post', function () {
    $user = User::create([
        'username' => 'voter',
        'email' => 'voter@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Vote Me',
        'body' => 'Vote for this post.',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/vote', [
            'target_id' => $post->id,
            'target_type' => 'post',
            'vote_type' => 'upvote',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('current_score', 1);

    $this->assertDatabaseHas('votes', [
        'user_id' => $user->id,
        'target_id' => $post->id,
        'vote_type' => 'upvote',
    ]);
});

test('user can downvote a comment', function () {
    $user = User::create([
        'username' => 'voter',
        'email' => 'voter@example.com',
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
        'body' => 'Comment to vote',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/vote', [
            'target_id' => $comment->id,
            'target_type' => 'comment',
            'vote_type' => 'downvote',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('current_score', -1);

    $this->assertDatabaseHas('votes', [
        'user_id' => $user->id,
        'target_id' => $comment->id,
        'vote_type' => 'downvote',
    ]);
});

test('user can toggle vote off', function () {
    $user = User::create([
        'username' => 'voter',
        'email' => 'voter@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Toggle Me',
        'body' => 'Body',
    ]);

    // First vote
    $this->actingAs($user)->postJson('/api/vote', [
        'target_id' => $post->id,
        'target_type' => 'post',
        'vote_type' => 'upvote',
    ]);

    // Second vote (same type) should remove it
    $response = $this->actingAs($user)->postJson('/api/vote', [
        'target_id' => $post->id,
        'target_type' => 'post',
        'vote_type' => 'upvote',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Vote removed')
        ->assertJsonPath('current_score', 0);

    $this->assertDatabaseMissing('votes', [
        'user_id' => $user->id,
        'target_id' => $post->id,
    ]);
});

test('user can change vote type', function () {
    $user = User::create([
        'username' => 'voter',
        'email' => 'voter@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Change Me',
        'body' => 'Body',
    ]);

    // Upvote (+1)
    $this->actingAs($user)->postJson('/api/vote', [
        'target_id' => $post->id,
        'target_type' => 'post',
        'vote_type' => 'upvote',
    ]);

    // Change to downvote (-1 from 0, so net change -2 from +1)
    $response = $this->actingAs($user)->postJson('/api/vote', [
        'target_id' => $post->id,
        'target_type' => 'post',
        'vote_type' => 'downvote',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('current_score', -1);

    $this->assertDatabaseHas('votes', [
        'user_id' => $user->id,
        'target_id' => $post->id,
        'vote_type' => 'downvote',
    ]);
});
