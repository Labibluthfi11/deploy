<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyKpiApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ambil token dari file .env
        $validKey = env('KPI_INTEGRATION_KEY');
        
        // Cek API Key dari Header (atau bearer token)
        $providedKey = $request->header('X-API-KEY') ?? $request->bearerToken();

        if (!$validKey || $providedKey !== $validKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. API Key tidak valid.'
            ], 401);
        }

        return $next($request);
    }
}
