<?php

namespace App\Observers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogPostSection;
use App\Models\SiteSetting;
use App\Services\ProjectsPageService;

class ProjectsCacheObserver
{
    public function saved(BlogPost|BlogPostSection|BlogCategory|SiteSetting $model): void
    {
        if ($model instanceof SiteSetting && ! str_starts_with($model->key, 'projects.')) {
            return;
        }

        ProjectsPageService::forgetCache();
    }

    public function deleted(BlogPost|BlogPostSection|BlogCategory|SiteSetting $model): void
    {
        if ($model instanceof SiteSetting && ! str_starts_with($model->key, 'projects.')) {
            return;
        }

        ProjectsPageService::forgetCache();
    }
}
