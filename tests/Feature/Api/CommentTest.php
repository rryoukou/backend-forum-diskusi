<?php

use App\Models\User;
use App\Models\Category;
use App\Models\Post;
use App\Models\Comment;
use App\Models\CommentEditHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create a comment on a post', function () {
    $user = User::create([
        'username' => 'commenter',
        'email' => 'commenter@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Post to Comment',
        'body' => 'Post body',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/comments', [
            'post_id' => $post->id,
            'body' => 'This is my first comment!',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.body', 'This is my first comment!');

    $this->assertDatabaseHas('comments', [
        'post_id' => $post->id,
        'user_id' => $user->id,
        'body' => 'This is my first comment!',
    ]);
});

test('user can reply to a comment', function () {
    $user = User::create([
        'username' => 'replier',
        'email' => 'replier@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Post to Reply',
        'body' => 'Post body',
    ]);

    $parentComment = Comment::create([
        'post_id' => $post->id,
        'user_id' => $user->id,
        'body' => 'Parent comment',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/comments', [
            'post_id' => $post->id,
            'parent_id' => $parentComment->id,
            'body' => 'This is a reply!',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.parent_id', $parentComment->id);

    $this->assertDatabaseHas('comments', [
        'parent_id' => $parentComment->id,
        'body' => 'This is a reply!',
    ]);
});

test('user can update their own comment', function () {
    $user = User::create([
        'username' => 'editor',
        'email' => 'editor@example.com',
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
        'body' => 'Original comment',
    ]);

    $response = $this->actingAs($user)
        ->putJson("/api/comments/{$comment->id}", [
            'body' => 'Updated comment body',
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'body' => 'Updated comment body',
    ]);

    // Check history
    $this->assertDatabaseHas('comment_edit_history', [
        'comment_id' => $comment->id,
        'body_before' => 'Original comment',
        'body_after' => 'Updated comment body',
    ]);
});

test('can list comments for a post with nesting', function () {
    $user = User::create(['username' => 'u', 'email' => 'u@e.c', 'password_hash' => 'p']);
    $cat = Category::create(['name' => 'C', 'slug' => 'c']);
    $post = Post::create(['user_id' => $user->id, 'category_id' => $cat->id, 'title' => 'T', 'body' => 'B']);

    $parent = Comment::create(['post_id' => $post->id, 'user_id' => $user->id, 'body' => 'Parent']);
    $child = Comment::create(['post_id' => $post->id, 'user_id' => $user->id, 'parent_id' => $parent->id, 'body' => 'Child']);

    $response = $this->getJson("/api/comments?post_id={$post->id}");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data') // Only 1 top level comment
        ->assertJsonPath('data.0.children.0.body', 'Child');
});

test('can get comment edit history', function () {
    $user = User::create(['username' => 'u', 'email' => 'u@e.c', 'password_hash' => 'p']);
    $cat = Category::create(['name' => 'C', 'slug' => 'c']);
    $post = Post::create(['user_id' => $user->id, 'category_id' => $cat->id, 'title' => 'T', 'body' => 'B']);
    $comment = Comment::create(['post_id' => $post->id, 'user_id' => $user->id, 'body' => 'Original']);

    CommentEditHistory::create([
        'comment_id' => $comment->id,
        'edited_by' => $user->id,
        'body_before' => 'Original',
        'body_after' => 'New',
    ]);

    $response = $this->getJson("/api/comments/{$comment->id}/history");
    $response->assertStatus(200)->assertJsonCount(1);
});

test('user can delete their own comment', function () {
    $user = User::create([
        'username' => 'deleter',
        'email' => 'deleter@example.com',
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
        'body' => 'To be deleted',
    ]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/comments/{$comment->id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('comments', [
        'id' => $comment->id,
    ]);
});

test('post owner can accept a comment as answer', function () {
    $owner = User::create([
        'username' => 'owner',
        'email' => 'owner@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $commenter = User::create([
        'username' => 'commenter',
        'email' => 'commenter@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $category = Category::create(['name' => 'General', 'slug' => 'general']);
    $post = Post::create([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'title' => 'Question Post',
        'body' => 'Help me!',
    ]);

    $comment = Comment::create([
        'post_id' => $post->id,
        'user_id' => $commenter->id,
        'body' => 'This is the answer.',
    ]);

    $response = $this->actingAs($owner)
        ->postJson("/api/comments/{$comment->id}/accept");

    $response->assertStatus(200);

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'is_accepted' => true,
    ]);

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'is_answered' => true,
        'accepted_answer_id' => $comment->id,
    ]);
});
