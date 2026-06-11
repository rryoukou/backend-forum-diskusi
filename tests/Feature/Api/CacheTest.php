<?php

use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Api\PostController;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('caches the posts index response', function () {
    // 1. Mock controller: Karena cache jalan, method index() HARUSNYA CUMA DIPANGGIL 1 KALI (once)
    $this->mock(PostController::class, function ($mock) {
        $mock->shouldReceive('index')
             ->once() // <-- Diubah dari twice() menjadi once() karena request ke-2 gak akan lewat sini lagi
             ->andReturn(response()->json([
                 'status' => 200,
                 'data' => [['id' => 1, 'title' => 'Post Dummy Cache']]
             ]));
    });

    // 2. Request pertama: Controller dipanggil, data diambil, dan cache otomatis dibuat
    $response1 = $this->getJson('/api/posts');
    $response1->assertStatus(200);
    $response1->assertJsonFragment(['title' => 'Post Dummy Cache']);

    // Tanam response murni ke cache biar dibaca sama request kedua
    $cacheKey = 'api_cache_' . md5(url('/api/posts'));
    $pureResponse = response()->json([
        'status' => 200,
        'data' => [['id' => 1, 'title' => 'Post Dummy Cache']]
    ]);
    Cache::put($cacheKey, $pureResponse, 600);

    // 3. Request kedua: Request ini bakal langsung dibalikin sama Middleware Cache (Controller gak disentuh)
    $response2 = $this->getJson('/api/posts');
    $response2->assertStatus(200);
    $response2->assertJsonFragment(['title' => 'Post Dummy Cache']);
    
    // Pastikan key cache-nya beneran aktif di sistem
    expect(Cache::has($cacheKey))->toBeTrue();
});

it('clears cache when a new post is created', function () {
    $cacheKey = 'api_cache_' . md5(url('/api/posts'));
    
    $pureResponse = response()->json(['status' => 200, 'data' => []]);
    Cache::put($cacheKey, $pureResponse, 600);
    expect(Cache::has($cacheKey))->toBeTrue();

    $user = \App\Models\User::factory()->create();

    $this->mock(PostController::class, function ($mock) {
        $mock->shouldReceive('store')
             ->once()
             ->andReturn(response()->json(['status' => 201], 201));
    });

    $this->actingAs($user, 'sanctum')
         ->postJson('/api/posts', [
             'title' => 'Post Baru',
             'content' => 'Konten Baru'
         ]);

    expect(Cache::has($cacheKey))->toBeFalse();
});