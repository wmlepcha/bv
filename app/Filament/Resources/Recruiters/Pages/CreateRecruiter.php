<?php

namespace App\Filament\Resources\Recruiters\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Recruiters\RecruiterResource;
use App\Filament\Resources\Users\Concerns\HandlesUserMutation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRecruiter extends CreateRecord
{
    use HandlesUserMutation;

    protected static string $resource = RecruiterResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return $this->createUserWithRole($data, UserRole::Recruiter);
    }
}
