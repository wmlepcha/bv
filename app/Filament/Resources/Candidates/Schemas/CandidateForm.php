<?php

namespace App\Filament\Resources\Candidates\Schemas;

use App\Filament\Resources\Users\Schemas\UserIdentityForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
            ]);
    }
}
