<?php

namespace App\Observers;

use App\Models\ServiceGroup;
use App\Models\ServiceItem;
use App\Models\SiteSetting;
use App\Services\ServicesPageService;

class ServicesPageCacheObserver
{
    public function saved(ServiceGroup|ServiceItem|SiteSetting $model): void
    {
        if ($model instanceof SiteSetting && ! str_starts_with($model->key, 'services.')) {
            return;
        }

        ServicesPageService::forgetCache();
    }

    public function deleted(ServiceGroup|ServiceItem|SiteSetting $model): void
    {
        if ($model instanceof SiteSetting && ! str_starts_with($model->key, 'services.')) {
            return;
        }

        ServicesPageService::forgetCache();
    }
}
