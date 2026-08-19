<?php

namespace App\Support;

use App\Models\SiteSetting;

class Brand
{
    private const DEFAULT_MARKETING_LOGO = '/storage/images/bv-logo.png';

    private const DEFAULT_MARKETING_LOGO_LIGHT = '/storage/images/bv-logo-light.png';

    public static function name(): string
    {
        return (string) config('app.name', 'Bilingual Vitae');
    }

    public static function trademarkedName(): string
    {
        return static::name().'®';
    }

    /**
     * Replace bare brand name occurrences with the registered form (idempotent).
     * Also normalizes legacy ™ marks to ®.
     */
    public static function applyTrademark(string $text): string
    {
        $plain = static::name();

        if ($plain === '') {
            return $text;
        }

        $marked = static::trademarkedName();

        // Normalize legacy trademark symbol to the registered mark.
        $text = str_replace($plain.'™', $marked, $text);

        return (string) preg_replace(
            '/'.preg_quote($plain, '/').'(?![™®])/u',
            $marked,
            $text,
        );
    }

    public static function marketingLogoLightUrl(): string
    {
        return static::resolveMarketingLogoUrl(
            SiteSetting::getValue('header.logo_light_path'),
            SiteSetting::getValue('header.logo_dark_path'),
            self::DEFAULT_MARKETING_LOGO_LIGHT,
        );
    }

    public static function marketingLogoDarkUrl(): string
    {
        return static::resolveMarketingLogoUrl(
            SiteSetting::getValue('header.logo_dark_path'),
            SiteSetting::getValue('header.logo_light_path'),
            self::DEFAULT_MARKETING_LOGO,
        );
    }

    public static function marketingLogoUrl(): string
    {
        return static::marketingLogoDarkUrl();
    }

    public static function loginLogoLight(): string
    {
        return asset('storage/images/bv-vertical-logo-for-filament-for-light-theme.png');
    }

    public static function loginLogoDark(): string
    {
        return asset('storage/images/bv-vertical-logo-for-filament-for-dark-theme.png');
    }

    public static function dashboardLogoLight(): string
    {
        return static::loginLogoLight();
    }

    public static function dashboardLogoDark(): string
    {
        return static::loginLogoDark();
    }

    public static function dashboardLogo(): string
    {
        return static::dashboardLogoLight();
    }

    public static function loginLogoHeight(): string
    {
        return '8rem';
    }

    public static function dashboardLogoHeight(): string
    {
        return '2.25rem';
    }

    public static function primary(): string
    {
        // #FC5C2E — brand-warm
        return 'oklch(0.681 0.204 36.2)';
    }

    public static function info(): string
    {
        // #2A80CC — brand-cool
        return 'oklch(0.587 0.141 249.5)';
    }

    protected static function resolveMarketingLogoUrl(
        ?string $preferred,
        ?string $fallback,
        string $default = self::DEFAULT_MARKETING_LOGO,
    ): string {
        $path = filled($preferred)
            ? $preferred
            : (filled($fallback)
                ? $fallback
                : (SiteSetting::getValue('header.logo_path') ?? $default));

        return static::publicAssetUrl($path);
    }

    protected static function publicAssetUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Root-relative paths so cached logo URLs work on any host (Herd, artisan serve, etc.).
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return '/storage/'.$path;
    }
}
