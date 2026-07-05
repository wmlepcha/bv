<?php

namespace App\Filament\Resources\ActivationCodes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ActivationCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->placeholder('003-2001-9001'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->helperText('Optional. When set, only this email can redeem the code.'),
                TextInput::make('student_number')
                    ->required()
                    ->maxLength(50)
                    ->placeholder('003-2001-9001'),
            ]);
    }
}
