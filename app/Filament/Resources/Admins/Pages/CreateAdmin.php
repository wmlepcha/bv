<?php

namespace App\Filament\Resources\Admins\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Admins\AdminResource;
use App\Filament\Resources\Users\Concerns\HandlesUserMutation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdmin extends CreateRecord
{
    use HandlesUserMutation;

    protected static string $resource = AdminResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return $this->createUserWithRole($data, UserRole::Admin);
    }
}
