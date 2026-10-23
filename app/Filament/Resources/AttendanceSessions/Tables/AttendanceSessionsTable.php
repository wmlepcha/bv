<?php

namespace App\Filament\Resources\AttendanceSessions\Tables;

use App\Models\AttendanceSession;
use App\Models\BatchEnrollment;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['batch', 'opener'])->withCount('records'))
            ->columns([
                TextColumn::make('started_at')->label('Date')->dateTime()->sortable(),
                TextColumn::make('batch.name')->label('Batch')->searchable(),
                TextColumn::make('opener.name')->label('Trainer')->placeholder('—'),
                TextColumn::make('attendance')
                    ->label('Present / enrolled')
                    ->state(fn (AttendanceSession $record): string => $record->records_count.' / '.BatchEnrollment::query()->where('batch_id', $record->batch_id)->count())
                    ->badge(),
                TextColumn::make('status')
                    ->state(fn (AttendanceSession $record): string => $record->isOpen() ? 'Open' : 'Closed')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Open' ? 'success' : 'gray'),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('batch_id')->label('Batch')->relationship('batch', 'name'),
            ])
            ->recordActions([ViewAction::make()]);
    }
}
