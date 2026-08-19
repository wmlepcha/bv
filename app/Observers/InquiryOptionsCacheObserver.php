<?php

namespace App\Observers;

use App\Models\EducationLevel;
use App\Services\InquiryOptionsService;

class InquiryOptionsCacheObserver
{
    public function saved(EducationLevel $model): void
    {
        InquiryOptionsService::forgetCache();
    }

    public function deleted(EducationLevel $model): void
    {
        InquiryOptionsService::forgetCache();
    }
}
