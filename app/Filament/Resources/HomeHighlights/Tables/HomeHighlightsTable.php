<?php

namespace App\Filament\Resources\HomeHighlights\Tables;

use App\Models\HomeHighlight;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomeHighlightsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('group')
                    ->label('Column')
                    ->formatStateUsing(
                        fn (?string $state): string => HomeHighlight::groupOptions()[$state] ?? ($state ?? ''),
                    )
                    ->sortable(),
                IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([])
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
