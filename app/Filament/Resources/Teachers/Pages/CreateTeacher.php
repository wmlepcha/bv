<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\Users\Concerns\HandlesUserMutation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTeacher extends CreateRecord
{
    use HandlesUserMutation;

    protected static string $resource = TeacherResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return $this->createUserWithRole($data, UserRole::Teacher);
    }
}
