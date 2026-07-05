<?php

namespace App\Observers;

use App\Models\NavigationItem;
use App\Models\OfficeLocation;
use App\Models\PartnerLogo;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Services\SiteLayoutService;

class SiteLayoutCacheObserver
{
    public function saved(NavigationItem|PartnerLogo|OfficeLocation|SocialLink|SiteSetting $model): void
    {
        SiteLayoutService::forgetCache();
    }

    public function deleted(NavigationItem|PartnerLogo|OfficeLocation|SocialLink|SiteSetting $model): void
    {
        SiteLayoutService::forgetCache();
    }
}
