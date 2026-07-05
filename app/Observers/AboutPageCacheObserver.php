<?php

namespace App\Observers;

use App\Models\AboutCoreValue;
use App\Models\AboutJourneyMilestone;
use App\Models\AboutParagraph;
use App\Models\SiteSetting;
use App\Services\AboutPageService;

class AboutPageCacheObserver
{
    public function saved(AboutParagraph|AboutCoreValue|AboutJourneyMilestone|SiteSetting $model): void
    {
        if ($model instanceof SiteSetting && ! str_starts_with($model->key, 'about.')) {
            return;
        }

        AboutPageService::forgetCache();
    }

    public function deleted(AboutParagraph|AboutCoreValue|AboutJourneyMilestone|SiteSetting $model): void
    {
        if ($model instanceof SiteSetting && ! str_starts_with($model->key, 'about.')) {
            return;
        }

        AboutPageService::forgetCache();
    }
}
