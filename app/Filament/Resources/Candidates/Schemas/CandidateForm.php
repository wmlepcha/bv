<?php

namespace App\Filament\Resources\Candidates\Schemas;

use App\Enums\PlacementStatus;
use App\Enums\Profession;
use App\Enums\RegistrationPurpose;
use App\Filament\Resources\Users\Schemas\UserIdentityForm;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CandidateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...UserIdentityForm::components(),
                TextInput::make('student_number')
                    ->label('Candidate ID')
                    ->disabled()
                    ->dehydrated(false),
                Toggle::make('is_unlocked')
                    ->label('Unlocked')
                    ->disabled()
                    ->dehydrated(false),
                Section::make('Placement')
                    ->relationship('studentProfile')
                    ->schema([
                        Select::make('registration_purpose')
                            ->label('Registered for')
                            ->options(RegistrationPurpose::options())
                            ->default(RegistrationPurpose::Training->value)
                            ->required()
                            ->native(false)
                            ->helperText('Recruiters only see candidates registered for Training + Placement.'),
                        Select::make('placement_status')
                            ->label('Placement status')
                            ->options(PlacementStatus::options())
                            ->default(PlacementStatus::Open->value)
                            ->required()
                            ->native(false)
                            ->helperText('Hired candidates are hidden from recruiters and employees.'),
                        CheckboxList::make('professions')
                            ->label('Professions')
                            ->options(Profession::options())
                            ->columns(3),
                    ]),
            ]);
    }
}
