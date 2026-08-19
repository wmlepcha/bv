<?php

namespace App\Services;

use App\Models\NavigationItem;
use App\Models\OfficeLocation;
use App\Models\PartnerLogo;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Support\Brand;
use Illuminate\Support\Facades\Cache;

class SiteLayoutService
{
    public const CACHE_KEY = 'site.layout';

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
            'header' => [
                'logoUrlLight' => Brand::marketingLogoLightUrl(),
                'logoUrlDark' => Brand::marketingLogoDarkUrl(),
                'partnerLabel' => SiteSetting::getValue(
                    'header.partner_label',
                    'Official partner of Government of Sikkim and NSDC International',
                ),
                'showAppearanceToggle' => SiteSetting::getValue(
                    'header.show_appearance_toggle',
                    '1',
                ) === '1',
                'navigation' => NavigationItem::query()
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->get(['label', 'href', 'icon'])
                    ->map(fn (NavigationItem $item): array => [
                        'label' => $item->label,
                        'href' => $item->href,
                        'icon' => $item->icon,
                    ])
                    ->values()
                    ->all(),
                'partners' => PartnerLogo::query()
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->get(['name', 'image_path', 'alt_text'])
                    ->map(fn (PartnerLogo $partner): array => [
                        'name' => $partner->name,
                        'imageUrl' => $this->publicAssetUrl($partner->image_path),
                        'altText' => $partner->alt_text,
                    ])
                    ->values()
                    ->all(),
            ],
            'footer' => [
                'contactEmail' => SiteSetting::getValue('footer.contact_email', 'hello@bilingualvitae.com'),
                'contactPhone' => SiteSetting::getValue('footer.contact_phone', '+91 8250312500'),
                'copyright' => SiteSetting::getValue('footer.copyright', 'Bilingual Vitae®. All rights reserved.'),
                'offices' => OfficeLocation::query()
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->get(['name', 'address'])
                    ->map(fn (OfficeLocation $office): array => [
                        'name' => $office->name,
                        'address' => $office->address,
                    ])
                    ->values()
                    ->all(),
                'socialLinks' => SocialLink::query()
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->get(['label', 'url'])
                    ->map(fn (SocialLink $link): array => [
                        'label' => $link->label,
                        'url' => $link->url,
                    ])
                    ->values()
                    ->all(),
            ],
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
