<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ChallengeApiCache
{
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya cache request berupa GET (karena GET itu mengambil data)
        if ($request->isMethod('GET')) {
            
            // Bikin nama key cache unik berdasarkan URL + Query String (misal: /api/posts?page=2)
            $cacheKey = 'api_cache_' . md5($request->fullUrl());

            // Jika cache ada, langsung return. Jika tidak, jalankan controller dan simpan hasilnya (durasi: 10 menit)
            return Cache::remember($cacheKey, 600, function () use ($request, $next) {
                return $next($request);
            });
        }

        // Jika method POST/PUT/DELETE, otomatis bersihkan semua cache API (biar data ter-refresh)
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            // Catatan: Jika menggunakan cache driver 'file', kita clear secara global
            Cache::flush(); 
        }

        return $next($request);
    }
}