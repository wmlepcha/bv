<?php

namespace App\Filament\Resources\AttendanceSessions\Pages;

use App\Filament\Resources\AttendanceSessions\AttendanceSessionResource;
use App\Models\Batch;
use App\Services\AttendanceExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceSessions extends ListRecords
{
    protected static string $resource = AttendanceSessionResource::class;

    protected function getHeaderActions(): array
    {
        $batchSelect = fn (): Select => Select::make('batch_id')
            ->label('Batch')
            ->placeholder('All batches')
            ->options(fn (): array => Batch::query()->orderBy('name')->pluck('name', 'id')->all());

        return [
            Action::make('exportSummary')
                ->label('Export summary CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('Attendance percentage per student')
                ->schema([$batchSelect()])
                ->action(fn (array $data) => app(AttendanceExportService::class)->summary($data['batch_id'] ?? null)),
            Action::make('exportSessions')
                ->label('Export sessions CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('Every class, student by student')
                ->schema([
                    $batchSelect(),
                    DatePicker::make('from')->label('From'),
                    DatePicker::make('until')->label('Until'),
                ])
                ->action(fn (array $data) => app(AttendanceExportService::class)->sessions(
                    $data['batch_id'] ?? null,
                    $data['from'] ?? null,
                    $data['until'] ?? null,
                )),
        ];
    }
}
