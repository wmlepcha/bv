<?php

namespace App\Filament\Resources\RecruiterProjects\Pages;

use App\Filament\Resources\RecruiterProjects\Actions\MessageStudentsAction;
use App\Filament\Resources\RecruiterProjects\RecruiterProjectResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRecruiterProject extends ViewRecord
{
    protected static string $resource = RecruiterProjectResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->forceFill(['recruiter_updated_at' => null])->save();
    }

    protected function getHeaderActions(): array
    {
        return [
            MessageStudentsAction::congratulate(),
            MessageStudentsAction::requirements(),
        ];
    }
}
