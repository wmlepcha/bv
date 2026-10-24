<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTurnstileTest extends TestCase
{
    use RefreshDatabase;

    public function test_csp_allows_cloudflare_turnstile_widget(): void
    {
        $csp = $this->get('/login')->headers->get('Content-Security-Policy');

        foreach (['script-src', 'frame-src'] as $directive) {
            $this->assertMatchesRegularExpression(
                "/{$directive}[^;]*https:\/\/challenges\.cloudflare\.com/",
                (string) $csp,
            );
        }
    }
}
