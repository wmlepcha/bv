<?php

namespace App\Filament\Resources\TrainingManagers\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\TrainingManagers\TrainingManagerResource;
use App\Filament\Resources\Users\Concerns\HandlesUserMutation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTrainingManager extends CreateRecord
{
    use HandlesUserMutation;

    protected static string $resource = TrainingManagerResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return $this->createUserWithRole($data, UserRole::TrainingManager);
    }
}
