<?php

namespace App\Support;

use App\Models\SiteSetting;

class Brand
{
    public static function marketingLogoUrl(): string
    {
        $path = SiteSetting::getValue('header.logo_path', '/storage/images/bv-logo.png');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return asset(ltrim($path, '/'));
        }

        return asset('storage/'.$path);
    }

    public static function loginLogoLight(): string
    {
        return asset('storage/images/bv-vertical-logo-for-filament-for-light-theme.png');
    }

    public static function loginLogoDark(): string
    {
        return asset('storage/images/bv-vertical-logo-for-filament-for-dark-theme.png');
    }

    public static function dashboardLogo(): string
    {
        return asset('storage/images/bv-logo.png');
    }

    public static function loginLogoHeight(): string
    {
        return '4.5rem';
    }

    public static function dashboardLogoHeight(): string
    {
        return '2.25rem';
    }

    public static function primary(): string
    {
        return 'oklch(0.68 0.21 35)';
    }

    public static function info(): string
    {
        return 'oklch(0.55 0.16 250)';
    }
}
