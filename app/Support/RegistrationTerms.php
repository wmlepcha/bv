<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Str;

class RegistrationTerms
{
    public const SITE_SETTING_KEY = 'payments.registration_terms_html';

    public static function html(): string
    {
        $fromSetting = SiteSetting::getValue(self::SITE_SETTING_KEY);

        if (self::hasMeaningfulHtml($fromSetting)) {
            return Str::sanitizeHtml($fromSetting);
        }

        return Str::sanitizeHtml(self::defaultHtml());
    }

    public static function defaultHtml(): string
    {
        $brand = (string) config('app.name', 'Bilingual Vitae');

        return <<<HTML
<p>Before you choose a plan, please read these terms.</p>
<p>Selecting a 1-year or 2-year plan (or redeeming a valid registration code) gives you access to classes, PDFs, notifications, and your CV profile on the {$brand} student dashboard for that period.</p>
<p>When your plan ends, dashboard access is paused until you choose a plan again. Renewing early keeps any remaining time and adds your new plan on top.</p>
<p>If you already paid offline, use the registration code provided by {$brand} instead of paying online again.</p>
<p>If you have questions about registration, plans, or access, contact support.</p>
HTML;
    }

    private static function hasMeaningfulHtml(?string $html): bool
    {
        if (! is_string($html) || $html === '') {
            return false;
        }

        return filled(trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
