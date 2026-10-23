<?php

namespace App\Filament\Resources\RecruiterProjects\RelationManagers;

use App\Filament\Resources\RecruiterProjects\Actions\MessageStudentsAction;
use App\Models\ProjectRequirement;
use App\Models\RecruiterProjectCandidate;
use App\Models\User;
use App\Services\ProjectApprovalService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Text;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    protected static ?string $title = 'Students';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'requirements',
                'studentProfile.user.batchEnrollment.batch',
            ]))
            ->columns([
                TextColumn::make('studentProfile.user.name')->label('Student')->searchable(),
                TextColumn::make('studentProfile.mobile_whatsapp')->label('Phone')->placeholder('—'),
                TextColumn::make('studentProfile.user.email')->label('Email'),
                TextColumn::make('studentProfile.user.batchEnrollment.batch.name')->label('Batch')->placeholder('—'),
                TextColumn::make('documents')
                    ->label('Documents')
                    ->state(fn (RecruiterProjectCandidate $record): string => $record->uploadedCount().' / '.$record->requirements->count())
                    ->badge()
                    ->color(fn (RecruiterProjectCandidate $record): string => $record->isReadyForApproval() ? 'success' : 'warning'),
                TextColumn::make('status')
                    ->state(fn (RecruiterProjectCandidate $record): string => match (true) {
                        $record->isApproved() => 'Approved',
                        $record->isSentToRecruiter() && $record->hasRejectedFiles() => 'Changes requested',
                        $record->isSentToRecruiter() => 'Awaiting recruiter',
                        $record->isReadyForApproval() => 'Ready to send',
                        default => 'Awaiting documents',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Changes requested' => 'danger',
                        'Awaiting recruiter' => 'warning',
                        'Ready to send' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('congratulated_at')
                    ->label('Congratulated')
                    ->boolean()
                    ->state(fn (RecruiterProjectCandidate $record): bool => $record->congratulated_at !== null)
                    ->tooltip(fn (RecruiterProjectCandidate $record): ?string => $record->congratulated_at?->format('j M Y, H:i')),
                IconColumn::make('requirements_sent_at')
                    ->label('Requirements sent')
                    ->boolean()
                    ->state(fn (RecruiterProjectCandidate $record): bool => $record->requirements_sent_at !== null)
                    ->tooltip(fn (RecruiterProjectCandidate $record): ?string => $record->requirements_sent_at?->format('j M Y, H:i')),
            ])
            ->recordActions([
                MessageStudentsAction::congratulate(true),
                MessageStudentsAction::requirements(true),
                $this->documentsAction(),
                $this->sendAction(),
            ]);
    }

    private function documentsAction(): Action
    {
        return Action::make('documents')
            ->label('Documents')
            ->icon('heroicon-o-folder-open')
            ->slideOver()
            ->modalHeading(fn (RecruiterProjectCandidate $record): string => $record->studentProfile->user->name.' · documents')
            ->modalDescription(fn (RecruiterProjectCandidate $record): string => $record->uploadedCount().' of '.$record->requirements->count().' uploaded')
            ->modalSubmitAction(fn (RecruiterProjectCandidate $record) => $record->isApproved() ? false : null)
            ->fillForm(fn (RecruiterProjectCandidate $record): array => [
                'requirements' => $record->requirements->map(fn (ProjectRequirement $requirement): array => [
                    'id' => $requirement->id,
                    'title' => $requirement->title,
                    'file' => $requirement->file_path,
                    'original_name' => $requirement->original_name,
                    'review' => $this->reviewSummary($requirement),
                ])->all(),
            ])
            ->schema(fn (RecruiterProjectCandidate $record): array => [
                Repeater::make('requirements')
                    ->hiddenLabel()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->schema([
                        Hidden::make('id'),
                        Hidden::make('title'),
                        Hidden::make('original_name'),
                        Hidden::make('review'),
                        Text::make(fn (array $state): string => (string) ($state['title'] ?? ''))
                            ->weight('bold'),
                        Text::make(fn (array $state): string => (string) ($state['review'] ?? ''))
                            ->visible(fn (array $state): bool => filled($state['review'] ?? null))
                            ->color('danger'),
                        FileUpload::make('file')
                            ->hiddenLabel()
                            ->disk(ProjectRequirement::DISK)
                            ->directory("project-documents/{$record->recruiter_project_id}/{$record->id}")
                            ->visibility('private')
                            ->storeFileNamesIn('original_name')
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->disabled($record->isApproved()),
                    ]),
            ])
            ->action(function (array $data, RecruiterProjectCandidate $record): void {
                if ($record->isApproved()) {
                    return;
                }

                /** @var User $admin */
                $admin = Auth::user();
                $submitted = collect($data['requirements'] ?? [])->keyBy('id');
                $approvals = app(ProjectApprovalService::class);
                $answeredRejection = false;

                foreach ($record->requirements as $requirement) {
                    $row = $submitted->get($requirement->id);
                    $file = is_array($row['file'] ?? null) ? (reset($row['file']) ?: null) : ($row['file'] ?? null);

                    if ($file === $requirement->file_path) {
                        continue;
                    }

                    $answeredRejection = $approvals->replaceFile($requirement, $file, $file ? ($row['original_name'] ?? basename($file)) : null, $admin) || $answeredRejection;
                }

                if ($answeredRejection) {
                    $approvals->notifyResubmitted($record);
                }

                Notification::make()->title('Documents saved')->success()->send();
            });
    }

    private function sendAction(): Action
    {
        return Action::make('sendToRecruiter')
            ->label('Send to recruiter')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('The recruiter will be told these documents are approved by BV and can review them. They are the final approver.')
            ->visible(fn (RecruiterProjectCandidate $record): bool => ! $record->isSentToRecruiter() && $record->isReadyForApproval())
            ->action(function (RecruiterProjectCandidate $record): void {
                try {
                    app(ProjectApprovalService::class)->sendToRecruiter($record);
                } catch (DomainException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Sent to recruiter')->success()->send();
            });
    }

    private function reviewSummary(ProjectRequirement $requirement): ?string
    {
        if (! $requirement->isRejected()) {
            return null;
        }

        return 'Rejected: '.$requirement->rejection_note.($requirement->hasSample() ? ' (sample: '.$requirement->sample_name.')' : '');
    }
}
