<?php

namespace App\Filament\Resources\SalesTeam\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\SalesTeam\SalesTeamResource;
use App\Filament\Resources\Users\Concerns\HandlesUserMutation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalesTeamMember extends CreateRecord
{
    use HandlesUserMutation;

    protected static string $resource = SalesTeamResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return $this->createUserWithRole($data, UserRole::SalesTeam);
    }
}
