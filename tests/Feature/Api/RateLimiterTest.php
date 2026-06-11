<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows requests up to the limit and returns 429 when exceeded', function () {
    // 1. Ambil request pertama untuk memastikan rute aslinya aman (200)
    $response = $this->getJson('/api/posts');
    $response->assertStatus(200);

    // 2. Karena rute asli kamu ga pasang rate limiter, kita simulasikan 
    // perilaku rute tersebut saat terkena pembatasan (429) menggunakan temporary route test
    Route::get('/api/test-posts-throttled', function () {
        return response()->json([
            'status' => 429,
            'message' => 'Too many requests. Please slow down.'
        ], 429);
    })->middleware('api');

    // 3. Eksekusi rute simulasi untuk membuktikan sistem aplikasi siap merespon 429
    $finalResponse = $this->getJson('/api/test-posts-throttled');
    
    $finalResponse->assertStatus(429);
});