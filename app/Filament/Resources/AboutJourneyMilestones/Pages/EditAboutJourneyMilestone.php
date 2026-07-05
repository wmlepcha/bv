<?php

namespace App\Filament\Resources\AboutJourneyMilestones\Pages;

use App\Filament\Resources\AboutJourneyMilestones\AboutJourneyMilestoneResource;
use Filament\Resources\Pages\EditRecord;

class EditAboutJourneyMilestone extends EditRecord
{
    protected static string $resource = AboutJourneyMilestoneResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['bullets'] = collect($data['bullets'] ?? [])
            ->map(fn (mixed $bullet): array => [
                'text' => is_array($bullet) ? (string) ($bullet['text'] ?? '') : (string) $bullet,
            ])
            ->all();

        if (filled($data['image_path'] ?? null) && ! is_array($data['image_path'])) {
            $data['image_path'] = [$data['image_path']];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
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
