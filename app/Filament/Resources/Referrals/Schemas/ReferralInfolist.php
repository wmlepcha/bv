<?php

namespace App\Filament\Resources\Referrals\Schemas;

use App\Enums\PreferredCountry;
use App\Enums\ReferralInterest;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReferralInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Referred person')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('referred_name')->label('Name'),
                        TextEntry::make('referred_phone')->label('Mobile number')->copyable(),
                        TextEntry::make('referred_email')->label('Email')->placeholder('—')->copyable(),
                        TextEntry::make('interest')
                            ->label('Interested in')
                            ->badge()
                            ->formatStateUsing(fn (ReferralInterest $state): string => $state->label()),
                        TextEntry::make('preferred_country')
                            ->label('Preferred country')
                            ->formatStateUsing(fn (PreferredCountry $state): string => $state->label()),
                    ]),
                Section::make('Referred by')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('referrer_name')->label('Name'),
                        TextEntry::make('referrer_phone')->label('Mobile number')->copyable(),
                        TextEntry::make('created_at')->label('Submitted')->dateTime(),
                        TextEntry::make('ip_address')->label('IP address')->placeholder('—'),
                    ]),
            ]);
    }
}
