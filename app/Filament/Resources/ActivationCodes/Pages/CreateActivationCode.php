<?php

namespace App\Filament\Resources\ActivationCodes\Pages;

use App\Filament\Resources\ActivationCodes\ActivationCodeResource;
use App\Models\ActivationCode;
use App\Models\User;
use App\Notifications\ActivationCodeIssued;
use App\Services\ActivationCodeGenerator;
use App\Services\StudentNumberGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateActivationCode extends CreateRecord
{
    protected static string $resource = ActivationCodeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = Auth::id();

        if (blank($data['code'] ?? null)) {
            $data['code'] = app(ActivationCodeGenerator::class)->generate();
        }

        if (ActivationCode::query()->where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages([
                'data.code' => 'This registration code is already in use. Generate a new one.',
            ]);
        }

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

    protected function afterCreate(): void
    {
        /** @var ActivationCode $record */
        $record = $this->record;

        if ($record->assigned_user_id === null) {
            return;
        }

        $student = User::query()->find($record->assigned_user_id);

        if ($student === null) {
            return;
        }

        $student->notify(new ActivationCodeIssued($record->code));
    }
}
