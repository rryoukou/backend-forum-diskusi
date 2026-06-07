<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Report;
use App\Models\ModerationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('moderator can ban a user', function () {
    $modRole = Role::create(['name' => 'moderator']);
    $moderator = User::create(['username' => 'mod', 'email' => 'mod@e.c', 'password_hash' => bcrypt('p')]);
    $moderator->roles()->attach($modRole->id, ['assigned_at' => now()]);

    $target = User::create(['username' => 'baduser', 'email' => 'bad@e.c', 'password_hash' => bcrypt('p')]);

    $response = $this->actingAs($moderator)
        ->postJson('/api/moderation/ban', [
            'user_id' => $target->id,
            'reason' => 'Spamming',
        ]);

    $response->assertStatus(200);
    $this->assertTrue($target->fresh()->is_banned);
    $this->assertDatabaseHas('moderation_logs', ['target_user_id' => $target->id, 'action_type' => 'ban']);
});

test('moderator can warn a user', function () {
    $modRole = Role::create(['name' => 'moderator']);
    $moderator = User::create(['username' => 'mod', 'email' => 'mod@e.c', 'password_hash' => bcrypt('p')]);
    $moderator->roles()->attach($modRole->id, ['assigned_at' => now()]);

    $target = User::create(['username' => 'warnuser', 'email' => 'warn@e.c', 'password_hash' => bcrypt('p')]);

    $response = $this->actingAs($moderator)
        ->postJson('/api/moderation/warn', [
            'user_id' => $target->id,
            'reason' => 'Minor rule break',
        ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('moderation_logs', ['target_user_id' => $target->id, 'action_type' => 'warning']);
});

test('moderator can unban a user', function () {
    $modRole = Role::create(['name' => 'moderator']);
    $moderator = User::create(['username' => 'mod', 'email' => 'mod@e.c', 'password_hash' => bcrypt('p')]);
    $moderator->roles()->attach($modRole->id, ['assigned_at' => now()]);

    $target = User::create(['username' => 'banned', 'email' => 'b@e.c', 'password_hash' => bcrypt('p'), 'is_banned' => true]);

    $response = $this->actingAs($moderator)
        ->postJson('/api/moderation/unban', [
            'user_id' => $target->id,
            'reason' => 'Appeal accepted',
        ]);

    $response->assertStatus(200);
    $this->assertFalse($target->fresh()->is_banned);
});

test('moderator can list reports and resolve them', function () {
    $modRole = Role::create(['name' => 'moderator']);
    $moderator = User::create(['username' => 'mod', 'email' => 'mod@e.c', 'password_hash' => bcrypt('p')]);
    $moderator->roles()->attach($modRole->id, ['assigned_at' => now()]);

    $report = Report::create([
        'reporter_id' => $moderator->id,
        'target_id' => '1',
        'target_type' => 'post',
        'reason' => 'Offensive',
    ]);

    $response = $this->actingAs($moderator)->getJson('/api/moderation/reports');
    $response->assertStatus(200)->assertJsonCount(1, 'data');

    $response = $this->actingAs($moderator)->postJson("/api/moderation/reports/{$report->id}/resolve", [
        'status' => 'resolved',
    ]);

    $response->assertStatus(200);
    $this->assertEquals('resolved', $report->fresh()->status);
});

test('regular user cannot access moderation', function () {
    $user = User::create(['username' => 'user', 'email' => 'u@e.c', 'password_hash' => bcrypt('p')]);

    $response = $this->actingAs($user)->getJson('/api/moderation/reports');
    $response->assertStatus(403);
});

test('user can report content', function () {
    $user = User::create(['username' => 'user', 'email' => 'u@e.c', 'password_hash' => bcrypt('p')]);

    $response = $this->actingAs($user)->postJson('/api/reports', [
        'target_id' => '1',
        'target_type' => 'post',
        'reason' => 'Inappropriate',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('reports', ['reporter_id' => $user->id, 'reason' => 'Inappropriate']);
});
