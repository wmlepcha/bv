<?php

namespace App\Services;

use App\Models\ServiceCategory;
use App\Models\ServiceGroup;
use App\Models\ServiceItem;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class ServicesPageService
{
    public const CACHE_KEY = 'site.services';

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
                'title' => SiteSetting::getValue('services.hero.title', 'Products & Services'),
                'subtitle' => SiteSetting::getValue(
                    'services.hero.subtitle',
                    'Language training, employability skills, and global placement pathways designed to make local talent globally employable.',
                ),
            ],
            'categories' => ServiceCategory::query()
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->with([
                    'groups' => fn ($query) => $query
                        ->where('is_visible', true)
                        ->orderBy('sort_order')
                        ->with([
                            'items' => fn ($itemsQuery) => $itemsQuery
                                ->where('is_visible', true)
                                ->orderBy('sort_order'),
                        ]),
                ])
                ->get()
                ->map(fn (ServiceCategory $category): array => [
                    'id' => $category->slug,
                    'title' => $category->title,
                    'groups' => $category->groups
                        ->map(fn (ServiceGroup $group): array => [
                            'id' => $group->slug,
                            'title' => $group->title,
                            'items' => $group->items
                                ->map(fn (ServiceItem $item): array => $this->mapItem($item))
                                ->values()
                                ->all(),
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'seo' => [
                'title' => SiteSetting::getValue('services.seo.title', 'Products & Services'),
                'description' => SiteSetting::getValue(
                    'services.seo.description',
                    'Explore Bilingual Vitae courses in German, Japanese, French, and Spanish, employability training, expatriate programmes, and placement support in Germany and Austria.',
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapItem(ServiceItem $item): array
    {
        return [
            'id' => $item->slug,
            'title' => $item->title,
            'familyLabel' => $item->family_label,
            'familyDescription' => $item->family_description,
            'kind' => $item->kind,
            'deliveryModes' => $item->delivery_modes ?? [],
            'levelRange' => $item->level_range,
            'hours' => $item->hours,
            'durationMonths' => $item->duration_months,
            'centres' => $item->centres ?? [],
            'intakePeriods' => $item->intake_periods ?? [],
            'venues' => $item->venues ?? [],
            'timeline' => $item->timeline,
            'certificates' => $item->certificates ?? [],
            'sectors' => $item->sectors ?? [],
            'notes' => $item->notes,
            'imageUrl' => filled($item->image_path)
                ? $this->publicAssetUrl($item->image_path)
                : null,
            'imageAlt' => $item->image_alt,
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
