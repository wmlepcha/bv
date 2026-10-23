<?php

namespace App\Filament\Resources\RecruitmentManagers\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\RecruitmentManagers\RecruitmentManagerResource;
use App\Filament\Resources\Users\Concerns\HandlesUserMutation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRecruitmentManager extends CreateRecord
{
    use HandlesUserMutation;

    protected static string $resource = RecruitmentManagerResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return $this->createUserWithRole($data, UserRole::RecruitmentManager);
    }
}
