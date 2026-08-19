<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('gateway_order_id')
                    ->label('Gateway order ID')
                    ->required(),
                TextInput::make('gateway_payment_id')
                    ->label('Gateway payment ID'),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('INR'),
                Select::make('status')
                    ->options(PaymentStatus::class)
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
