<?php

namespace App\Filament\Resources\ActivationCodes\Pages;

use App\Filament\Resources\ActivationCodes\ActivationCodeResource;
use App\Models\User;
use App\Services\StudentNumberGenerator;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActivationCode extends EditRecord
{
    protected static string $resource = ActivationCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['assigned_user_id'] ?? null)) {
            $student = User::query()->find($data['assigned_user_id']);

            if ($student !== null) {
                if (blank($student->student_number)) {
                    $student->forceFill([
                        'student_number' => app(StudentNumberGenerator::class)->generateForUser($student),
                    ])->save();
                }

                $data['email'] = $student->email;
                $data['student_number'] = $student->student_number;
            }
        }

        return $data;
    }
}
