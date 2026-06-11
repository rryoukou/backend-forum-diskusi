<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AppRateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Gunakan app()->environment() bawaan Laravel, lebih akurat membaca runtime state
        if (app()->environment('production')) {
            
            // Gunakan IP, jika kosong fallback ke string aman 'global' agar key tidak berubah-ubah di test environment
            $key = $request->ip() ?? 'global-limiter'; 
            
            // Batasi: maksimal 3 request per 1 menit
            if (RateLimiter::tooManyAttempts($key, $perMinute = 200)) {
                return response()->json([
                    'status' => 429,
                    'message' => 'Too many requests. Please slow down.'
                ], 429);
            }

            // Catat hit request jika belum melewati batas
            RateLimiter::hit($key, $decaySeconds = 60);
        }

        return $next($request);
    }
}