<?php

namespace App\Filament\Resources\Payments\Actions;

use App\Enums\OfflinePaymentMethod;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Center;
use App\Models\Plan;
use App\Models\User;
use App\Services\OfflinePaymentService;
use DomainException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;

/**
 * Logs money taken outside the payment gateway. It only records the payment; activation keys still unlock access.
 */
class RecordOfflinePaymentAction
{
    public static function make(?User $student = null): Action
    {
        return Action::make('recordOfflinePayment')
            ->label('Record offline payment')
            ->icon('heroicon-o-banknotes')
            ->color('primary')
            ->modalHeading($student ? "Record offline payment for {$student->name}" : 'Record offline payment')
            ->modalDescription('Use this for cash, UPI/QR, bank transfer or cheque received outside the website. It does not unlock the student; create an activation key for that.')
            ->modalSubmitActionLabel('Save payment')
            ->visible(fn (): bool => PaymentResource::canCreate())
            ->fillForm(fn (): array => ['received_on' => now()->toDateString(), 'method' => OfflinePaymentMethod::Cash->value])
            ->schema([
                Select::make('student_id')
                    ->hidden($student !== null)
                    ->dehydratedWhenHidden(false)
                    ->label('Student')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => User::query()
                        ->where('role', 'student')
                        ->where(fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%"))
                        ->orderBy('name')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [$user->id => self::label($user)])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => ($user = User::query()->find($value)) ? self::label($user) : null),
                Select::make('plan_id')
                    ->label('Plan (optional)')
                    ->options(fn (): array => Plan::query()->ordered()->get()->mapWithKeys(fn (Plan $plan): array => [$plan->id => "{$plan->name} · ₹{$plan->price_rupees}"])->all())
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $plan = $state ? Plan::query()->find($state) : null;

                        if ($plan !== null) {
                            $set('amount', $plan->price_rupees);
                        }
                    }),
                TextInput::make('amount')
                    ->label('Amount received')
                    ->numeric()
                    ->prefix('₹')
                    ->minValue(1)
                    ->required()
                    ->helperText('Pick a plan to fill the plan price, then change it if the student got a discount.'),
                Select::make('method')
                    ->label('Received as')
                    ->options(OfflinePaymentMethod::offlineOptions())
                    ->required()
                    ->native(false),
                DatePicker::make('received_on')
                    ->label('Received on')
                    ->required()
                    ->maxDate(now()),
                TextInput::make('reference')
                    ->label('Receipt / UTR / cheque number')
                    ->maxLength(100),
                Select::make('center_id')
                    ->label('Collected by (center)')
                    ->options(fn (): array => Center::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                    ->placeholder('Not set')
                    ->native(false)
                    ->helperText('Optional. You can also set it later from the Payments table.'),
                Textarea::make('notes')
                    ->rows(2)
                    ->maxLength(500),
            ])
            ->action(function (array $data) use ($student): void {
                abort_unless(PaymentResource::canCreate(), 403);

                /** @var User $collector */
                $collector = Filament::auth()->user();
                $recipient = $student ?? User::query()->findOrFail($data['student_id'] ?? null);

                try {
                    app(OfflinePaymentService::class)->record(
                        $recipient,
                        filled($data['plan_id'] ?? null) ? Plan::query()->find($data['plan_id']) : null,
                        (int) round((float) $data['amount']),
                        OfflinePaymentMethod::from($data['method']),
                        now()->parse($data['received_on'])->startOfDay()->setTimeFrom(now()),
                        $data['reference'] ?? null,
                        $data['notes'] ?? null,
                        $collector,
                        filled($data['center_id'] ?? null) ? (int) $data['center_id'] : null,
                    );
                } catch (DomainException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Offline payment recorded')->success()->send();
            });
    }

    private static function label(User $user): string
    {
        return $user->name.($user->student_number ? " ({$user->student_number})" : '')." · {$user->email}";
    }
}
