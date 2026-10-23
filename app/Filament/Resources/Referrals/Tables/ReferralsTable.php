<?php

namespace App\Filament\Resources\Referrals\Tables;

use App\Enums\PreferredCountry;
use App\Enums\ReferralInterest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReferralsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('referred_name')
                    ->label('Referred person')
                    ->searchable(),
                TextColumn::make('referred_phone')
                    ->label('Their mobile')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('referred_email')
                    ->label('Their email')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('interest')
                    ->badge()
                    ->formatStateUsing(fn (ReferralInterest $state): string => $state->label()),
                TextColumn::make('preferred_country')
                    ->label('Country')
                    ->formatStateUsing(fn (PreferredCountry $state): string => $state->label()),
                TextColumn::make('referrer_name')
                    ->label('Referred by')
                    ->description(fn ($record): string => $record->referrer_phone)
                    ->searchable(['referrer_name', 'referrer_phone']),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('interest')
                    ->options(ReferralInterest::options()),
                SelectFilter::make('preferred_country')
                    ->label('Country')
                    ->options(PreferredCountry::options()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Referral'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
