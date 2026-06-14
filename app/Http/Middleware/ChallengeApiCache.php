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
        // Dan jangan cache jika ada header Authorization (biar data user-specific aman)
        if ($request->isMethod('GET') && !$request->hasHeader('Authorization')) {
            
            // Bikin nama key cache unik berdasarkan URL + Query String (misal: /api/posts?page=2)
            $cacheKey = 'api_cache_' . md5($request->fullUrl());

            // Jika cache ada, langsung return (Tapi pastikan bukan hasil error sebelumnya)
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Jalankan request ke arah controller
            $response = $next($request);

            // HANYA simpan ke cache jika request BERHASIL (Status 200-299)
            // Ini krusial: jangan sampai error 401/404/500 ter-cache!
            if ($response->isSuccessful()) {
                Cache::put($cacheKey, $response, 600);
            }

            return $response;
        }

        // Jika method POST/PUT/DELETE, otomatis bersihkan semua cache API (biar data ter-refresh)
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            Cache::flush(); 
        }

        return $next($request);
    }
}