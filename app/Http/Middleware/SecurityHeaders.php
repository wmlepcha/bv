<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $isViteDev = app()->environment('local') && Vite::isRunningHot();

        $viteSources = $isViteDev ? $this->viteDevSources() : ['http' => '', 'connect' => ''];

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://checkout.razorpay.com{$viteSources['http']}",
            "style-src 'self' 'unsafe-inline'{$viteSources['http']}",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:{$viteSources['http']}",
            "connect-src 'self' https://checkout.razorpay.com https://api.razorpay.com{$viteSources['connect']}",
            "frame-src 'self' https://checkout.razorpay.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    /**
     * @return array{http: string, connect: string}
     */
    private function viteDevSources(): array
    {
        $httpSources = [
            'http://127.0.0.1:5173',
            'http://localhost:5173',
        ];

        $connectSources = [
            ...$httpSources,
            'ws://127.0.0.1:5173',
            'ws://localhost:5173',
        ];

        if (is_file(Vite::hotFile())) {
            $hotUrl = trim((string) file_get_contents(Vite::hotFile()));
            $parsed = parse_url($hotUrl);

            if (is_array($parsed) && isset($parsed['host'])) {
                $scheme = $parsed['scheme'] ?? 'http';
                $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
                $origin = "{$scheme}://{$parsed['host']}{$port}";

                $httpSources[] = $origin;
                $connectSources[] = $origin;
                $connectSources[] = ($scheme === 'https' ? 'wss' : 'ws')."://{$parsed['host']}{$port}";
            }
        }

        return [
            'http' => ' '.implode(' ', array_unique($httpSources)),
            'connect' => ' '.implode(' ', array_unique($connectSources)),
        ];
    }
}
