<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class UserIdentityForm
{
    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        return [
            TextInput::make('name')
                ->required(),
            TextInput::make('email')
                ->label('Email address')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state)),
        ];
    }
}
