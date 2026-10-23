<?php

namespace App\Filament\Resources\SalesTeam\Pages;

use App\Filament\Resources\SalesTeam\SalesTeamResource;
use App\Filament\Resources\Users\Concerns\HandlesUserMutation;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSalesTeamMember extends EditRecord
{
    use HandlesUserMutation;

    protected static string $resource = SalesTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        return $this->updateUserIdentity($record, $data);
    }
}
