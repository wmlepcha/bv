<?php

namespace App\Filament\Resources\Candidates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student_number')
                    ->label('Candidate ID')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_unlocked')
                    ->label('Unlocked')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->registration_expires_at?->isFuture() ?? false)
                    ->toggleable(),
                TextColumn::make('registrationPlan.name')
                    ->label('Plan')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('registration_expires_at')
                    ->label('Access until')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('registration_fee_paid_via')
                    ->label('Unlocked via')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('registration_paid_at')
                    ->label('Unlocked at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
