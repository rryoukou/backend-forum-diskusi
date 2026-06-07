<?php

use App\Models\Category;
use App\Models\Tag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can list all categories', function () {
    Category::create(['name' => 'General', 'slug' => 'general']);
    Category::create(['name' => 'Tech', 'slug' => 'tech']);

    $response = $this->getJson('/api/categories');

    $response->assertStatus(200)->assertJsonCount(2);
});

test('user can list all tags', function () {
    Tag::create(['name' => 'Laravel', 'slug' => 'laravel']);
    Tag::create(['name' => 'Vue', 'slug' => 'vue']);

    $response = $this->getJson('/api/tags');

    $response->assertStatus(200)->assertJsonCount(2);
});

test('user can filter posts by tag', function () {
    $user = User::create(['username' => 'u', 'email' => 'u@e.c', 'password_hash' => 'p']);
    $cat = Category::create(['name' => 'C', 'slug' => 'c']);
    $tag = Tag::create(['name' => 'Laravel', 'slug' => 'laravel']);

    $post1 = Post::create(['user_id' => $user->id, 'category_id' => $cat->id, 'title' => 'T1', 'body' => 'B']);
    $post1->tags()->attach($tag->id);

    $post2 = Post::create(['user_id' => $user->id, 'category_id' => $cat->id, 'title' => 'T2', 'body' => 'B']);

    $response = $this->getJson('/api/posts?tag=laravel');

    $response->assertStatus(200)->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.title', 'T1');
});
