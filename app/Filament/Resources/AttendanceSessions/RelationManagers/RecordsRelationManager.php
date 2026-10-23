<?php

namespace App\Filament\Resources\AttendanceSessions\RelationManagers;

use App\Enums\AttendanceStatus;
use App\Exceptions\AttendanceException;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\BatchEnrollment;
use App\Models\User;
use App\Services\AttendanceService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    protected static ?string $title = 'Marked students';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn ($query) => $query->with('student'))
            ->columns([
                TextColumn::make('student.name')->label('Student')->searchable(),
                TextColumn::make('status')->badge()->color(fn (AttendanceStatus $state): string => $state === AttendanceStatus::Present ? 'success' : 'info'),
                TextColumn::make('method')->badge()->color('gray'),
                TextColumn::make('marked_at')->dateTime(),
                TextColumn::make('note')->placeholder('—'),
            ])
            ->headerActions([
                Action::make('mark')
                    ->label('Mark a student')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Select::make('student_id')
                            ->label('Student')
                            ->required()
                            ->searchable()
                            ->options(fn (): array => $this->enrolledStudents()),
                        Select::make('status')
                            ->options(['present' => 'Present', 'excused' => 'Leave'])
                            ->default('present')
                            ->required(),
                        TextInput::make('reason')->maxLength(255),
                    ])
                    ->action(fn (array $data) => $this->change((int) $data['student_id'], AttendanceStatus::from($data['status']), $data['reason'] ?? null)),
            ])
            ->recordActions([
                Action::make('changeStatus')
                    ->label('Change')
                    ->icon('heroicon-o-pencil-square')
                    ->fillForm(fn (AttendanceRecord $record): array => ['status' => $record->status->value])
                    ->schema([
                        Select::make('status')->options(['present' => 'Present', 'excused' => 'Leave'])->required(),
                        TextInput::make('reason')->maxLength(255),
                    ])
                    ->action(fn (array $data, AttendanceRecord $record) => $this->change($record->student_id, AttendanceStatus::from($data['status']), $data['reason'] ?? null)),
                Action::make('markAbsent')
                    ->label('Mark absent')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([TextInput::make('reason')->maxLength(255)])
                    ->action(fn (array $data, AttendanceRecord $record) => $this->change($record->student_id, null, $data['reason'] ?? null)),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function enrolledStudents(): array
    {
        /** @var AttendanceSession $session */
        $session = $this->getOwnerRecord();

        return BatchEnrollment::query()
            ->where('batch_id', $session->batch_id)
            ->with('student:id,name')
            ->get()
            ->mapWithKeys(fn (BatchEnrollment $enrollment): array => [$enrollment->student_id => $enrollment->student->name])
            ->all();
    }

    private function change(int $studentId, ?AttendanceStatus $status, ?string $reason): void
    {
        /** @var AttendanceSession $session */
        $session = $this->getOwnerRecord();
        /** @var User $admin */
        $admin = Auth::user();

        try {
            app(AttendanceService::class)->setStatus($session, User::query()->findOrFail($studentId), $status, $admin, $reason);
        } catch (AttendanceException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('Attendance updated')->success()->send();
    }
}
