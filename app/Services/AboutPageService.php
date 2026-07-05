<?php

namespace App\Services;

use App\Models\AboutCoreValue;
use App\Models\AboutJourneyMilestone;
use App\Models\AboutParagraph;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class AboutPageService
{
    public const CACHE_KEY = 'site.about';

    /**
     * @return array<string, mixed>
     */
    public function toInertiaProps(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, fn (): array => $this->buildPayload());
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(): array
    {
        return [
            'hero' => [
                'title' => SiteSetting::getValue('about.hero.title', 'About Us'),
            ],
            'overview' => [
                'tagline' => SiteSetting::getValue(
                    'about.overview.tagline',
                    'Bilingual Vitae, An EdTech and Future Work Company.',
                ),
                'paragraphs' => $this->paragraphBodies(AboutParagraph::SECTION_OVERVIEW),
            ],
            'mission' => SiteSetting::getValue(
                'about.mission',
                'To reach the last mile by making local talent professionally visible and globally employable through language proficiency, technology, and trusted professional profiling.',
            ),
            'vision' => SiteSetting::getValue(
                'about.vision',
                "To become the world's most recognized bilingual proficiency profiling and publishing platform.",
            ),
            'leapfrog' => [
                'title' => SiteSetting::getValue('about.leapfrog.title', 'LEAPFROG'),
                'subtitle' => SiteSetting::getValue(
                    'about.leapfrog.subtitle',
                    'Our Learning & Leadership Philosophy',
                ),
                'paragraphs' => $this->paragraphBodies(AboutParagraph::SECTION_LEAPFROG),
            ],
            'coreValues' => [
                'intro' => $this->paragraphBodies(AboutParagraph::SECTION_CORE_VALUES_INTRO),
                'values' => AboutCoreValue::query()
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->get(['letter', 'title', 'description'])
                    ->map(fn (AboutCoreValue $value): array => [
                        'letter' => $value->letter,
                        'title' => $value->title,
                        'description' => $value->description,
                    ])
                    ->values()
                    ->all(),
            ],
            'impact' => [
                'paragraph' => SiteSetting::getValue(
                    'about.impact.paragraph',
                    'Bilingual Vitae strives to create measurable impact by improving employability, enhancing communication, strengthening digital competence, and preparing learners for lifelong success in an evolving global workforce market.',
                ),
                'closing' => SiteSetting::getValue(
                    'about.impact.closing',
                    'We measure our success not merely by certificates awarded, but by careers launched, confidence built, opportunities created, and lives transformed.',
                ),
            ],
            'journey' => [
                'milestones' => AboutJourneyMilestone::query()
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (AboutJourneyMilestone $milestone): array => [
                        'id' => $milestone->slug,
                        'period' => $milestone->period,
                        'title' => $milestone->title,
                        'bullets' => $milestone->bullets ?? [],
                        'layout' => $milestone->layout,
                        'imageUrl' => filled($milestone->image_path)
                            ? $this->publicAssetUrl($milestone->image_path)
                            : null,
                        'imageAlt' => $milestone->image_alt,
                    ])
                    ->values()
                    ->all(),
            ],
            'seo' => [
                'title' => SiteSetting::getValue('about.seo.title', 'About Us'),
                'description' => SiteSetting::getValue(
                    'about.seo.description',
                    'Bilingual Vitae is an EdTech and future work company specializing in bilingual proficiency profiling, language training, and global employability — empowering local talent with technology-driven career pathways.',
                ),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    protected function paragraphBodies(string $section): array
    {
        return AboutParagraph::query()
            ->where('section', $section)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->pluck('body')
            ->values()
            ->all();
    }

    protected function publicAssetUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset('storage/'.$path);
    }
}
