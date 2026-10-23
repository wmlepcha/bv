<?php

namespace App\Filament\Resources\RecruiterProjects\Tables;

use App\Models\RecruiterProject;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecruiterProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('owner')
                ->withCount([
                    'entries as students_count',
                    'entries as approved_count' => fn (Builder $q) => $q->whereNotNull('approved_at'),
                ]))
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('owner.name')->label('Recruiter')->searchable(),
                TextColumn::make('country')->searchable(),
                TextColumn::make('sector')->searchable(),
                TextColumn::make('timeline'),
                TextColumn::make('progress')
                    ->label('Approved')
                    ->state(fn (RecruiterProject $record): string => $record->approved_count.' / '.$record->students_count)
                    ->badge()
                    ->color(fn (RecruiterProject $record): string => $record->students_count > 0 && $record->approved_count === $record->students_count ? 'success' : 'warning'),
                IconColumn::make('recruiter_updated_at')
                    ->label('Updated by recruiter')
                    ->boolean()
                    ->state(fn (RecruiterProject $record): bool => $record->recruiter_updated_at !== null)
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->falseIcon('')
                    ->trueColor('warning'),
                TextColumn::make('finalized_at')->label('Sent')->dateTime()->sortable(),
            ])
            ->defaultSort('finalized_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }
}
