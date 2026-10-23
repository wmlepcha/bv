<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Resources\Candidates\Actions\MarkAsAlreadyPaidAction;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Filament\Resources\Users\Concerns\HandlesUserMutation;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCandidate extends EditRecord
{
    use HandlesUserMutation;

    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            MarkAsAlreadyPaidAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $record */
        $record = $this->getRecord();
        $data['is_unlocked'] = $record->registration_expires_at?->isFuture() ?? false;

        return $data;
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
