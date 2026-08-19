<?php

namespace App\Services;

use App\Models\EducationLevel;
use Illuminate\Support\Facades\Cache;

class InquiryOptionsService
{
    public const CACHE_KEY = 'inquiry.options';

    /**
     * @return list<array{value: string, label: string}>
     */
    public function educationLevels(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, fn (): array => $this->buildEducationLevels());
    }

    /**
     * @return list<string>
     */
    public function visibleEducationLevelSlugs(): array
    {
        return array_column($this->educationLevels(), 'value');
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    protected function buildEducationLevels(): array
    {
        return EducationLevel::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get(['slug', 'name'])
            ->map(fn (EducationLevel $level): array => [
                'value' => $level->slug,
                'label' => $level->name,
            ])
            ->all();
    }
}
