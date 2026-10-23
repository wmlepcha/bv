<?php

namespace App\Filament\Resources\Candidates\Actions;

use App\Enums\OfflinePaymentMethod;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Center;
use App\Models\Plan;
use App\Models\User;
use App\Services\PaidStudentMappingService;
use DomainException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;

/**
 * For older students who paid before payments were tracked: records the payment and unlocks them without notifying them.
 */
class MarkAsAlreadyPaidAction
{
    public static function make(): Action
    {
        return Action::make('markAsAlreadyPaid')
            ->label('Mark as already paid')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->modalHeading(fn (User $record): string => "Mark {$record->name} as already paid")
            ->modalDescription('Records their earlier payment and unlocks their dashboard. Access runs from today for the plan length unless you set an end date. They are not notified.')
            ->modalSubmitActionLabel('Record and unlock')
            ->visible(fn (User $record): bool => PaymentResource::canCreate() && $record->isStudent() && ! $record->hasActiveRegistration())
            ->fillForm(fn (): array => [
                'duration_months' => PaidStudentMappingService::DEFAULT_DURATION_MONTHS,
                'method' => OfflinePaymentMethod::Cash->value,
                'paid_on' => now()->toDateString(),
            ])
            ->schema([
                Select::make('plan_id')
                    ->label('Plan (optional)')
                    ->options(fn (): array => Plan::query()->ordered()->get()->mapWithKeys(fn (Plan $plan): array => [$plan->id => "{$plan->name} · {$plan->duration_months} months · ₹{$plan->price_rupees}"])->all())
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $plan = $state ? Plan::query()->find($state) : null;

                        if ($plan !== null) {
                            $set('duration_months', $plan->duration_months);
                            $set('amount', $plan->price_rupees);
                        }
                    }),
                TextInput::make('amount')
                    ->label('Amount they paid')
                    ->numeric()
                    ->prefix('₹')
                    ->minValue(1)
                    ->required(),
                DatePicker::make('paid_on')
                    ->label('Paid on')
                    ->required()
                    ->maxDate(now()),
                Select::make('method')
                    ->label('Paid how')
                    ->options(OfflinePaymentMethod::options())
                    ->required()
                    ->native(false),
                TextInput::make('reference')
                    ->label('Receipt / reference number')
                    ->maxLength(100),
                TextInput::make('duration_months')
                    ->label('Access length (months)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(120)
                    ->required(),
                Select::make('center_id')
                    ->label('Collected by (center)')
                    ->options(fn (): array => Center::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                    ->placeholder('Not set')
                    ->native(false)
                    ->helperText('Optional. You can also set it later from the Payments table.'),
                DatePicker::make('access_until')
                    ->label('Access ends on (optional)')
                    ->minDate(now()->addDay())
                    ->helperText('Leave empty to count the access length from today.'),
            ])
            ->action(function (array $data, User $record): void {
                abort_unless(PaymentResource::canCreate(), 403);

                try {
                    app(PaidStudentMappingService::class)->map(
                        $record,
                        filled($data['plan_id'] ?? null) ? Plan::query()->find($data['plan_id']) : null,
                        (int) $data['duration_months'],
                        (int) round((float) $data['amount']),
                        now()->parse($data['paid_on'])->startOfDay(),
                        OfflinePaymentMethod::from($data['method']),
                        $data['reference'] ?? null,
                        filled($data['access_until'] ?? null) ? now()->parse($data['access_until'])->endOfDay() : null,
                        Filament::auth()->user(),
                        filled($data['center_id'] ?? null) ? (int) $data['center_id'] : null,
                    );
                } catch (DomainException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title("{$record->name} is marked as paid and unlocked")->success()->send();
            });
    }
}
