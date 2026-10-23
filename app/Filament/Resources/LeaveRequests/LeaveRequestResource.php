<?php

namespace App\Filament\Resources\LeaveRequests;

use App\Enums\LeaveStatus;
use App\Exceptions\AttendanceException;
use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $slug = 'leave-requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    protected static string|UnitEnum|null $navigationGroup = 'Training';

    protected static ?string $navigationLabel = 'Leave requests';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = LeaveRequest::query()->where('status', LeaveStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['student', 'batch', 'decider']))
            ->columns([
                TextColumn::make('student.name')->label('Student')->searchable(),
                TextColumn::make('batch.name')->label('Batch'),
                TextColumn::make('from_date')->date(),
                TextColumn::make('to_date')->date(),
                TextColumn::make('reason')->limit(60)->tooltip(fn (LeaveRequest $record): string => $record->reason),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (LeaveStatus $state): string => $state->label())
                    ->color(fn (LeaveStatus $state): string => match ($state) {
                        LeaveStatus::Pending => 'warning',
                        LeaveStatus::Approved => 'success',
                        LeaveStatus::Rejected => 'danger',
                    }),
                TextColumn::make('decider.name')->label('Decided by')->placeholder('—'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options(collect(LeaveStatus::cases())->mapWithKeys(fn (LeaveStatus $s): array => [$s->value => $s->label()])->all()),
            ])
            ->recordActions([
                self::decision('approve', 'Approve', 'heroicon-o-check', 'success', true),
                self::decision('reject', 'Reject', 'heroicon-o-x-mark', 'danger', false),
            ]);
    }

    private static function decision(string $name, string $label, string $icon, string $color, bool $approve): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (LeaveRequest $record): bool => $record->isPending())
            ->schema([TextInput::make('note')->label('Note to student (optional)')->maxLength(255)])
            ->action(function (array $data, LeaveRequest $record) use ($approve): void {
                /** @var User $admin */
                $admin = Auth::user();

                try {
                    app(LeaveService::class)->decide($record, $admin, $approve, $data['note'] ?? null);
                } catch (AttendanceException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title($approve ? 'Leave approved' : 'Leave rejected')->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveRequests::route('/'),
        ];
    }
}
