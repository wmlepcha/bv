<?php

namespace App\Filament\Resources\Batches\RelationManagers;

use App\Models\Batch;
use App\Services\BatchEnrollmentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Students';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('student.name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('student.student_number')
                    ->label('Candidate ID')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('student.email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Enrolled')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('transferred_at')
                    ->label('Last transferred')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('enrollCandidates')
                    ->label('Enroll candidates')
                    ->modalHeading('Enroll candidates')
                    ->modalSubmitActionLabel('Enroll')
                    ->form([
                        Select::make('student_ids')
                            ->label('Candidates')
                            ->multiple()
                            ->options(fn (): array => app(BatchEnrollmentService::class)
                                ->unenrolledStudentOptions(limit: 200))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->getSearchResultsUsing(fn (string $search): array => app(BatchEnrollmentService::class)
                                ->unenrolledStudentOptions($search, 50))
                            ->getOptionLabelsUsing(fn (array $values): array => app(BatchEnrollmentService::class)
                                ->candidateOptionLabels($values)),
                    ])
                    ->action(function (array $data): void {
                        /** @var Batch $batch */
                        $batch = $this->getOwnerRecord();
                        $service = app(BatchEnrollmentService::class);

                        /** @var list<int|string> $studentIds */
                        $studentIds = $data['student_ids'] ?? [];

                        $result = $service->enrollMany($batch, $studentIds);

                        $body = "Enrolled {$result['enrolled']} candidate(s).";

                        if ($result['failed'] !== []) {
                            $body .= ' '.count($result['failed']).' failed: '
                                .implode(' ', $result['failed']);
                        }

                        $notification = Notification::make()
                            ->title('Enrollment finished')
                            ->body($body);

                        if ($result['enrolled'] > 0 && $result['failed'] === []) {
                            $notification->success();
                        } elseif ($result['enrolled'] > 0) {
                            $notification->warning();
                        } else {
                            $notification->danger();
                        }

                        $notification->send();
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove from batch'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Remove selected'),
                ]),
            ]);
    }
}
