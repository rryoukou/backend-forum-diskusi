<?php

use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can list their notifications', function () {
    $user = User::create([
        'username' => 'notified',
        'email' => 'notified@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    Notification::create([
        'user_id' => $user->id,
        'type' => 'upvote',
        'is_read' => false,
    ]);

    $response = $this->actingAs($user)->getJson('/api/notifications');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('user can mark notification as read', function () {
    $user = User::create([
        'username' => 'notified',
        'email' => 'notified@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'upvote',
        'is_read' => false,
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/notifications/{$notification->id}/read");

    $response->assertStatus(200);

    $this->assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'is_read' => true,
    ]);
});

test('user can mark all notifications as read', function () {
    $user = User::create([
        'username' => 'notified',
        'email' => 'notified@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    Notification::create(['user_id' => $user->id, 'type' => 'upvote', 'is_read' => false]);
    Notification::create(['user_id' => $user->id, 'type' => 'follow', 'is_read' => false]);

    $response = $this->actingAs($user)
        ->postJson("/api/notifications/read-all");

    $response->assertStatus(200);

    $this->assertEquals(0, Notification::where('user_id', $user->id)->where('is_read', false)->count());
});

test('user can get unread notification count', function () {
    $user = User::create([
        'username' => 'notified',
        'email' => 'notified@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    Notification::create(['user_id' => $user->id, 'type' => 'upvote', 'is_read' => false]);

    $response = $this->actingAs($user)->getJson('/api/notifications/unread-count');

    $response->assertStatus(200)
        ->assertJsonPath('unread_count', 1);
});

test('user can delete a notification', function () {
    $user = User::create([
        'username' => 'notified',
        'email' => 'notified@example.com',
        'password_hash' => bcrypt('password'),
    ]);

    $notification = Notification::create(['user_id' => $user->id, 'type' => 'upvote', 'is_read' => false]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/notifications/{$notification->id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('notifications', [
        'id' => $notification->id,
    ]);
});
