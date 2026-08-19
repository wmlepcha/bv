<?php

namespace App\Filament\Resources\ActivationCodes\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivationCodeGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ActivationCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('assigned_user_id')
                    ->label('Student')
                    ->options(function (?Model $record): array {
                        $assignedUserId = $record?->getAttribute('assigned_user_id');

                        return User::query()
                            ->where('role', UserRole::Student)
                            ->where(function (Builder $query) use ($assignedUserId): void {
                                $query->whereNull('registration_expires_at')
                                    ->orWhere('registration_expires_at', '<=', now());

                                if ($assignedUserId !== null) {
                                    $query->orWhere('id', $assignedUserId);
                                }
                            })
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [
                                $user->id => "{$user->name} ({$user->email})",
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->live()
                    ->required()
                    ->helperText('Only students without active access are listed.'),
                Placeholder::make('student_number_display')
                    ->label('Candidate ID')
                    ->content(function (Get $get): HtmlString|string {
                        $studentId = $get('assigned_user_id');

                        if (blank($studentId)) {
                            return 'Select a student to see their candidate ID.';
                        }

                        $studentNumber = User::query()->find($studentId)?->student_number;

                        if (blank($studentNumber)) {
                            return 'Will be generated automatically if missing.';
                        }

                        return new HtmlString(
                            '<span class="font-mono text-sm">'.e($studentNumber).'</span>'
                        );
                    })
                    ->helperText('Assigned automatically when the student registered.'),
                TextInput::make('code')
                    ->label('Registration code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->readOnly()
                    ->placeholder('BV-26-XXXX')
                    ->helperText('Click Generate to create a unique BV-26-XXXX code for this student.')
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->dehydrated()
                    ->suffixAction(
                        Action::make('generateCode')
                            ->label('Generate')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->action(function (Set $set): void {
                                $set('code', app(ActivationCodeGenerator::class)->generate());
                            }),
                    ),
            ]);
    }
}
