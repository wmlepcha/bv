<?php

namespace App\Filament\Resources\AttendanceSessions\Schemas;

use App\Models\AttendanceSession;
use App\Services\AttendanceService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Session')
                ->columns(3)
                ->schema([
                    TextEntry::make('batch.name')->label('Batch'),
                    TextEntry::make('opener.name')->label('Trainer')->placeholder('—'),
                    TextEntry::make('started_at')->label('Started')->dateTime(),
                    TextEntry::make('closed_at')->label('Closed')->dateTime()->placeholder('Still open'),
                    TextEntry::make('absent')
                        ->label('Absent')
                        ->columnSpan(2)
                        ->state(fn (AttendanceSession $record): string => app(AttendanceService::class)->roster($record)
                            ->whereNull('status')
                            ->pluck('name')
                            ->implode(', ') ?: 'Nobody'),
                ]),
        ]);
    }
}
