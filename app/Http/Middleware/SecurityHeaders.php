<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil nonce yang sudah di-register di AppServiceProvider
        $cspNonce = app('csp_nonce');
        
        // Simpan ke config agar bisa diakses di Blade
        config(['app.csp_nonce' => $cspNonce]);

        /** @var Response $response */
        $response = $next($request);

        // Remove headers that leak version information or internal timestamps
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('X-Response-Time');
        $response->headers->remove('X-Runtime');
        $response->headers->remove('X-Vite-ID');

        // 1. X-Frame-Options: Mencegah clickjacking
        // SAMEORIGIN memperbolehkan framing hanya dari domain yang sama
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 2. Strict-Transport-Security (HSTS): Memaksa HTTPS
        // Hanya aktifkan jika request aman (HTTPS)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // 3. Content-Security-Policy (CSP): Membatasi sumber daya yang dapat dimuat
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-eval' 'nonce-$cspNonce' cdnjs.cloudflare.com cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' 'nonce-$cspNonce' fonts.googleapis.com cdnjs.cloudflare.com cdn.jsdelivr.net",
            "img-src 'self' data: cdnjs.cloudflare.com i.pravatar.cc",
            "font-src 'self' data: fonts.gstatic.com cdnjs.cloudflare.com",
            "connect-src 'self' https://cdn.jsdelivr.net",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "upgrade-insecure-requests",
        ];
        
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        // Additional Security Headers
        // X-Content-Type-Options: Mencegah MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer-Policy: Mengontrol informasi referrer yang dikirim
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 4. Cache-Control: Mencegah caching data sensitif di browser
        // Terutama untuk halaman HTML (dashboard, data absensi, dll)
        if ($request->isMethod('GET') && str_contains($response->headers->get('Content-Type') ?? '', 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        // Permissions-Policy: Membatasi fitur browser yang bisa digunakan guna mencegah XSS mengakses hardware
        $permissions = [
            'camera=()',
            'microphone=()',
            'geolocation=()',
            'browsing-topics=()',
            'interest-cohort=()',
            'gyroscope=()',
            'magnetometer=()',
            'payment=()',
            'usb=()'
        ];
        $response->headers->set('Permissions-Policy', implode(', ', $permissions));

        return $response;
    }
}
