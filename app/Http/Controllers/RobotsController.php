<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemapUrl = rtrim(config('app.url'), '/').'/sitemap.xml';

        $content = <<<TXT
User-agent: *
Disallow: /dashboard
Disallow: /settings
Disallow: /login
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /email
Disallow: /two-factor-challenge
Disallow: /student
Disallow: /recruiter
Disallow: /bv-admin
Disallow: /webhooks

Sitemap: {$sitemapUrl}
TXT;

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
