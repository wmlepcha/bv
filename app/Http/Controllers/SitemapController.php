<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $pages = config('seo.pages', []);

        $staticUrls = collect($pages)->map(function (array $page) use ($baseUrl): string {
            return $this->buildUrlEntry(
                $baseUrl.$page['path'],
                $page['changefreq'],
                $page['priority'],
            );
        });

        $postUrls = BlogPost::query()
            ->where('is_visible', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at'])
            ->map(fn (BlogPost $post): string => $this->buildUrlEntry(
                $baseUrl.'/success-stories/'.$post->slug,
                'monthly',
                '0.6',
                $post->updated_at?->toAtomString(),
            ));

        $urls = $staticUrls->merge($postUrls)->implode("\n");

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$urls}
</urlset>
XML;

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    protected function buildUrlEntry(
        string $loc,
        string $changefreq,
        string $priority,
        ?string $lastmod = null,
    ): string {
        $loc = htmlspecialchars($loc, ENT_XML1);
        $changefreq = htmlspecialchars($changefreq, ENT_XML1);
        $priority = htmlspecialchars($priority, ENT_XML1);
        $lastmodLine = $lastmod
            ? '    <lastmod>'.htmlspecialchars($lastmod, ENT_XML1)."</lastmod>\n"
            : '';

        return "  <url>\n".
            "    <loc>{$loc}</loc>\n".
            $lastmodLine.
            "    <changefreq>{$changefreq}</changefreq>\n".
            "    <priority>{$priority}</priority>\n".
            '  </url>';
    }
}
