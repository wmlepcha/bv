<?php

namespace App\Filament\Resources\AboutJourneyMilestones\Pages;

use App\Filament\Resources\AboutJourneyMilestones\AboutJourneyMilestoneResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAboutJourneyMilestone extends CreateRecord
{
    protected static string $resource = AboutJourneyMilestoneResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizeMilestoneData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeMilestoneData(array $data): array
    {
        $data['bullets'] = collect($data['bullets'] ?? [])
            ->map(fn (mixed $bullet): string => is_array($bullet) ? (string) ($bullet['text'] ?? '') : (string) $bullet)
            ->filter()
            ->values()
            ->all();

        if (isset($data['image_path']) && is_array($data['image_path'])) {
            $data['image_path'] = $data['image_path'][0] ?? null;
        }

        return $data;
    }
}
