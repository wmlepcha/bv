<?php

namespace App\Observers;

use App\Models\HomeFeatureItem;
use App\Models\HomeHighlight;
use App\Models\HomeProgramCard;
use App\Models\HomeServiceCard;
use App\Models\HomeStat;
use App\Models\HomeTestimonial;
use App\Models\SiteSetting;
use App\Services\HomePageService;

class HomePageCacheObserver
{
    public function saved(HomeStat|HomeHighlight|HomeFeatureItem|HomeTestimonial|HomeServiceCard|HomeProgramCard|SiteSetting $model): void
    {
        if ($model instanceof SiteSetting && ! str_starts_with($model->key, 'home.')) {
            return;
        }

        HomePageService::forgetCache();
    }

    public function deleted(HomeStat|HomeHighlight|HomeFeatureItem|HomeTestimonial|HomeServiceCard|HomeProgramCard|SiteSetting $model): void
    {
        if ($model instanceof SiteSetting && ! str_starts_with($model->key, 'home.')) {
            return;
        }

        HomePageService::forgetCache();
    }
}
