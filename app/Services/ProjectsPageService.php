<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\BlogPostSection;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class ProjectsPageService
{
    public const CACHE_KEY = 'site.projects';

    /**
     * @return array<string, mixed>
     */
    public function toIndexProps(): array
    {
        return Cache::remember(self::CACHE_KEY.'.index', 3600, fn (): array => $this->buildIndexPayload());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toShowProps(string $slug): ?array
    {
        return Cache::remember(
            self::CACHE_KEY.'.show.'.$slug,
            3600,
            fn (): ?array => $this->buildShowPayload($slug),
        );
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY.'.index');

        BlogPost::query()->pluck('slug')->each(function (string $slug): void {
            Cache::forget(self::CACHE_KEY.'.show.'.$slug);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildIndexPayload(): array
    {
        return [
            'hero' => [
                'title' => SiteSetting::getValue('projects.hero.title', 'Projects'),
                'subtitle' => SiteSetting::getValue(
                    'projects.hero.subtitle',
                    'Insights on language learning, multilingualism, and global employability from the Bilingual Vitae editorial team.',
                ),
            ],
            'seo' => [
                'title' => SiteSetting::getValue('projects.seo.title', 'Projects'),
                'description' => SiteSetting::getValue(
                    'projects.seo.description',
                    'Discover Bilingual Vitae projects and initiatives advancing language education, employability, and workforce development across Sikkim and beyond.',
                ),
            ],
            'posts' => BlogPost::query()
                ->with('category')
                ->where('is_visible', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->get()
                ->map(fn (BlogPost $post): array => $this->mapPostCard($post))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildShowPayload(string $slug): ?array
    {
        $post = BlogPost::query()
            ->with(['category', 'sections'])
            ->where('slug', $slug)
            ->where('is_visible', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->first();

        if ($post === null) {
            return null;
        }

        return [
            'post' => [
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'readingTimeMinutes' => $post->reading_time_minutes,
                'publishedAt' => $post->published_at?->toIso8601String(),
                'publishedAtFormatted' => $post->published_at?->format('j F Y'),
                'category' => [
                    'name' => $post->category->name,
                    'slug' => $post->category->slug,
                ],
                'author' => [
                    'name' => $post->author_name,
                    'bio' => $post->author_bio,
                ],
                'heroImageUrl' => filled($post->hero_image_path)
                    ? $this->publicAssetUrl($post->hero_image_path)
                    : (filled($post->featured_image_path)
                        ? $this->publicAssetUrl($post->featured_image_path)
                        : null),
                'heroImageAlt' => $post->hero_image_alt ?? $post->featured_image_alt,
                'sections' => $post->sections
                    ->map(fn (BlogPostSection $section): array => $this->mapSection($section))
                    ->values()
                    ->all(),
                'seo' => [
                    'title' => $post->seo_title ?? $post->title,
                    'description' => $post->seo_description ?? $post->excerpt,
                    'keywords' => $post->seo_keywords,
                    'ogImage' => filled($post->og_image_path)
                        ? $this->publicAssetUrl($post->og_image_path)
                        : (filled($post->featured_image_path)
                            ? $this->publicAssetUrl($post->featured_image_path)
                            : null),
                    'noindex' => $post->seo_noindex,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapPostCard(BlogPost $post): array
    {
        return [
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'cardLayout' => $post->card_layout,
            'readingTimeMinutes' => $post->reading_time_minutes,
            'category' => [
                'name' => $post->category->name,
                'slug' => $post->category->slug,
            ],
            'featuredImageUrl' => filled($post->featured_image_path)
                ? $this->publicAssetUrl($post->featured_image_path)
                : null,
            'featuredImageAlt' => $post->featured_image_alt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapSection(BlogPostSection $section): array
    {
        $content = $section->content ?? [];

        if ($section->type === BlogPostSection::TYPE_IMAGE && isset($content['path'])) {
            $content['url'] = $this->publicAssetUrl($content['path']);
        }

        if ($section->type === BlogPostSection::TYPE_VIDEO && isset($content['path'])) {
            $content['url'] = $this->publicAssetUrl($content['path']);
        }

        return [
            'type' => $section->type,
            'content' => $content,
        ];
    }

    protected function publicAssetUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset('storage/'.$path);
    }
}
