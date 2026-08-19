<?php

namespace App\Filament\Resources\Batches\Tables;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('center.name')
                    ->label('Center')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('language')
                    ->badge()
                    ->formatStateUsing(fn (CourseLanguage|string $state): string => $state instanceof CourseLanguage
                        ? $state->label()
                        : CourseLanguage::from($state)->label()),
                TextColumn::make('level')
                    ->badge()
                    ->formatStateUsing(fn (CourseLevel|string $state): string => $state instanceof CourseLevel
                        ? $state->label()
                        : (string) $state),
                TextColumn::make('teacher.name')
                    ->label('Faculty')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Students'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
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
