<?php

namespace App\Services;

use App\Models\HomeFeatureItem;
use App\Models\HomeHighlight;
use App\Models\HomeProgramCard;
use App\Models\HomeServiceCard;
use App\Models\HomeStat;
use App\Models\HomeTestimonial;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class HomePageService
{
    public const CACHE_KEY = 'site.homepage';

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
                'badge' => SiteSetting::getValue('home.hero.badge', 'Global workforce readiness'),
                'headline' => SiteSetting::getValue('home.hero.headline', 'People flex their linguistic & professional skills here…'),
                'subtitle' => SiteSetting::getValue(
                    'home.hero.subtitle',
                    'Helping people become workplace-ready with strong communication and professional skills.',
                ),
                'cardText' => SiteSetting::getValue(
                    'home.hero.card_text',
                    'With us language becomes your bridge, not a barrier.',
                ),
                'highlightColumns' => $this->buildHighlightColumns(),
                'stats' => HomeStat::query()
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->get(['value', 'suffix', 'label'])
                    ->map(fn (HomeStat $stat): array => [
                        'value' => $stat->value,
                        'suffix' => $stat->suffix,
                        'label' => $stat->label,
                    ])
                    ->values()
                    ->all(),
            ],
            'crafting' => $this->buildContentSection(
                section: HomeFeatureItem::SECTION_CRAFTING,
                testimonialSection: HomeTestimonial::SECTION_CRAFTING,
                badgeKey: 'home.crafting.badge',
                badgeDefault: 'Candidate development',
                headingKey: 'home.crafting.heading',
                headingDefault: 'Crafting people. Linguistically & Professionally.',
                bodyKey: 'home.crafting.body',
                bodyDefault: 'At Bilingual Vitae®, we blend linguistic and professional skills with deep human values while making candidates ready to be a part of the ever-demanding global workforce market.',
                imagePathKey: 'home.crafting.image_path',
                imagePathDefault: 'images/students-of-bilingual-vitae.jpg',
                imageAltKey: 'home.crafting.image_alt',
                imageAltDefault: 'Bilingual Vitae® students in training',
                featuresKey: 'pillars',
            ),
            'recruiters' => $this->buildContentSection(
                section: HomeFeatureItem::SECTION_RECRUITERS,
                testimonialSection: HomeTestimonial::SECTION_RECRUITERS,
                badgeKey: 'home.recruiters.badge',
                badgeDefault: 'For recruiters & employers',
                headingKey: 'home.recruiters.heading',
                headingDefault: 'Recruiters never leave unsatisfied.',
                bodyKey: 'home.recruiters.body',
                bodyDefault: 'At Bilingual Vitae®, recruiters and employers get linguistically and professionally well-prepared candidates who are ready to perform from day one.',
                imagePathKey: 'home.recruiters.image_path',
                imagePathDefault: 'images/recruiters-of-bilingual-vitae.jpg',
                imageAltKey: 'home.recruiters.image_alt',
                imageAltDefault: 'Recruiters partnering with Bilingual Vitae®',
                featuresKey: 'benefits',
            ),
            'services' => [
                'heading' => SiteSetting::getValue(
                    'home.services.heading',
                    'Whether you\'re a student, professional or a recruiter, we\'ve got you covered.',
                ),
                'subtitle' => SiteSetting::getValue('home.services.subtitle', 'You didn\'t come this far to stop'),
                'cards' => HomeServiceCard::query()
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->get(['title', 'href', 'image_path'])
                    ->map(fn (HomeServiceCard $card): array => [
                        'title' => $card->title,
                        'href' => $card->href,
                        'imageUrl' => $this->publicAssetUrl($card->image_path),
                    ])
                    ->values()
                    ->all(),
            ],
            'programs' => [
                'badge' => SiteSetting::getValue('home.programs.badge', 'Our Programs'),
                'heading' => SiteSetting::getValue(
                    'home.programs.heading',
                    'Boost your career with our expertise',
                ),
                'ctaLabel' => SiteSetting::getValue('home.programs.cta_label', 'View All Programs'),
                'ctaHref' => SiteSetting::getValue('home.programs.cta_href', '/services'),
                'cards' => HomeProgramCard::query()
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->get(['title', 'description', 'href', 'image_path'])
                    ->map(fn (HomeProgramCard $card): array => [
                        'title' => $card->title,
                        'description' => $card->description,
                        'href' => $card->href,
                        'imageUrl' => $this->publicAssetUrl($card->image_path),
                    ])
                    ->values()
                    ->all(),
            ],
            'seo' => [
                'title' => SiteSetting::getValue('home.seo.title', 'Language Training & Career Support in Sikkim'),
                'description' => SiteSetting::getValue(
                    'home.seo.description',
                    'Bilingual Vitae® helps people in Sikkim become workplace-ready through language training, employability skills, placement support, and migration guidance.',
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildContentSection(
        string $section,
        string $testimonialSection,
        string $badgeKey,
        string $badgeDefault,
        string $headingKey,
        string $headingDefault,
        string $bodyKey,
        string $bodyDefault,
        string $imagePathKey,
        string $imagePathDefault,
        string $imageAltKey,
        string $imageAltDefault,
        string $featuresKey,
    ): array {
        $features = HomeFeatureItem::query()
            ->where('section', $section)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get(['label'])
            ->map(fn (HomeFeatureItem $item): array => [
                'label' => $item->label,
            ])
            ->values()
            ->all();

        $testimonial = HomeTestimonial::query()
            ->where('section', $testimonialSection)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->first(['quote', 'author', 'role']);

        return [
            'badge' => SiteSetting::getValue($badgeKey, $badgeDefault),
            'heading' => SiteSetting::getValue($headingKey, $headingDefault),
            'body' => SiteSetting::getValue($bodyKey, $bodyDefault),
            'imageUrl' => $this->publicAssetUrl(SiteSetting::getValue($imagePathKey, $imagePathDefault) ?? $imagePathDefault),
            'imageAlt' => SiteSetting::getValue($imageAltKey, $imageAltDefault),
            $featuresKey => $features,
            'testimonial' => $testimonial ? [
                'quote' => $testimonial->quote,
                'author' => $testimonial->author,
                'role' => $testimonial->role,
            ] : null,
        ];
    }

    /**
     * @return list<array{heading: string, items: list<array{label: string}>}>
     */
    protected function buildHighlightColumns(): array
    {
        $columns = [
            [
                'group' => HomeHighlight::GROUP_SOFTWARE_SUITE,
                'heading' => SiteSetting::getValue('home.hero.software_suite_heading', 'Software suite'),
            ],
            [
                'group' => HomeHighlight::GROUP_SERVICES,
                'heading' => SiteSetting::getValue('home.hero.services_heading', 'Services'),
            ],
        ];

        $result = [];

        foreach ($columns as $column) {
            $items = HomeHighlight::query()
                ->where('is_visible', true)
                ->where('group', $column['group'])
                ->orderBy('sort_order')
                ->get(['label'])
                ->map(fn (HomeHighlight $item): array => [
                    'label' => $item->label,
                ])
                ->values()
                ->all();

            if ($items === []) {
                continue;
            }

            $result[] = [
                'heading' => $column['heading'],
                'items' => $items,
            ];
        }

        return $result;
    }

    protected function publicAssetUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset('storage/'.$path);
    }
}
