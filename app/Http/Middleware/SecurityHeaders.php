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
            "script-src 'self' 'unsafe-inline' https://sdk.cashfree.com{$viteSources['http']}",
            "style-src 'self' 'unsafe-inline'{$viteSources['http']}",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:{$viteSources['http']}",
            "connect-src 'self' https://api.cashfree.com https://sandbox.cashfree.com https://sdk.cashfree.com https://payments.cashfree.com{$viteSources['connect']}",
            "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://sdk.cashfree.com https://api.cashfree.com https://sandbox.cashfree.com https://payments.cashfree.com",
            "child-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://sdk.cashfree.com https://api.cashfree.com https://sandbox.cashfree.com https://payments.cashfree.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' https://api.cashfree.com https://sandbox.cashfree.com https://payments.cashfree.com",
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
            'http://[::1]:5173',
            'http://127.0.0.1:5174',
            'http://localhost:5174',
            'http://[::1]:5174',
        ];

        $connectSources = [
            ...$httpSources,
            'ws://127.0.0.1:5173',
            'ws://localhost:5173',
            'ws://[::1]:5173',
            'ws://127.0.0.1:5174',
            'ws://localhost:5174',
            'ws://[::1]:5174',
        ];

        if (is_file(Vite::hotFile())) {
            $hotUrl = trim((string) file_get_contents(Vite::hotFile()));
            $parsed = parse_url($hotUrl);

            if (is_array($parsed) && isset($parsed['host'])) {
                $scheme = $parsed['scheme'] ?? 'http';
                $host = $parsed['host'];
                $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
                $origin = "{$scheme}://{$host}{$port}";

                $httpSources[] = $origin;
                $connectSources[] = $origin;
                $connectSources[] = ($scheme === 'https' ? 'wss' : 'ws')."://{$host}{$port}";

                // Also allow the IPv4 loopback equivalent when Vite binds to IPv6 ::1
                if ($host === '[::1]' || $host === '::1') {
                    $ipv4Origin = "{$scheme}://127.0.0.1{$port}";
                    $httpSources[] = $ipv4Origin;
                    $connectSources[] = $ipv4Origin;
                    $connectSources[] = ($scheme === 'https' ? 'wss' : 'ws')."://127.0.0.1{$port}";
                }
            }
        }

        return [
            'http' => ' '.implode(' ', array_unique($httpSources)),
            'connect' => ' '.implode(' ', array_unique($connectSources)),
        ];
    }
}
