<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\OfflinePaymentMethod;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Center;
use App\Models\Payment;
use App\Services\OfflinePaymentService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'collector', 'center']))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->description(fn (Payment $record): ?string => $record->user?->student_number)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('user', fn (Builder $user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%"))),
                TextColumn::make('channel')
                    ->badge()
                    ->formatStateUsing(fn (PaymentChannel $state): string => $state->label())
                    ->color(fn (PaymentChannel $state): string => match ($state) {
                        PaymentChannel::Online => 'info',
                        PaymentChannel::Offline => 'warning',
                        PaymentChannel::Legacy => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                // ponytail: temporary, lets staff correct earlier website rows that were really paid offline; lock Online rows again once new registrations record their channel correctly.
                SelectColumn::make('payment_mode')
                    ->label('Method')
                    ->options(OfflinePaymentMethod::options())
                    ->placeholder('Website (Cashfree)')
                    ->selectablePlaceholder(false)
                    ->updateStateUsing(function (Payment $record, ?string $state): ?string {
                        $record->forceFill([
                            'payment_mode' => $state,
                            'channel' => $record->channel === PaymentChannel::Online ? PaymentChannel::Offline : $record->channel,
                            'collected_by_user_id' => $record->collected_by_user_id ?? Filament::auth()->id(),
                        ])->save();

                        return $state;
                    })
                    ->disabled(fn (Payment $record): bool => ! PaymentResource::canCreate() || $record->status === PaymentStatus::Voided),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('INR', divideBy: 100)
                    ->sortable()
                    ->summarize(Sum::make()->label('Collected')->money('INR', divideBy: 100)->query(fn ($query) => $query->where('status', PaymentStatus::Paid))),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => ucfirst($state->value))
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Failed => 'danger',
                        PaymentStatus::Voided => 'gray',
                    })
                    ->tooltip(fn (Payment $record): ?string => $record->void_reason),
                TextColumn::make('reference')
                    ->label('Reference')
                    ->state(fn (Payment $record): ?string => $record->isRecordedByStaff() ? $record->payment_mode_detail : ($record->gateway_payment_id ?: $record->gateway_order_id))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(fn (Builder $inner) => $inner
                        ->where('payment_mode_detail', 'like', "%{$search}%")
                        ->orWhere('gateway_payment_id', 'like', "%{$search}%")
                        ->orWhere('gateway_order_id', 'like', "%{$search}%")
                        ->orWhere('invoice_number', 'like', "%{$search}%")))
                    ->placeholder('—')
                    ->limit(28)
                    ->toggleable(isToggledHiddenByDefault: true),
                SelectColumn::make('center_id')
                    ->label('Collected by')
                    ->options(fn (): array => Center::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                    ->placeholder('Not set')
                    ->selectablePlaceholder()
                    ->disabled(fn (Payment $record): bool => ! PaymentResource::canCreate() || $record->status === PaymentStatus::Voided)
                    ->sortable(),
                TextColumn::make('collector.name')
                    ->label('Entered by')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->label('Paid on')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('plan_name')
                    ->label('Plan')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                Group::make('user_id')
                    ->label('Student')
                    ->getTitleFromRecordUsing(fn (Payment $record): string => $record->user?->name ?? 'Unknown student')
                    ->collapsible(),
                Group::make('center_id')
                    ->label('Collected by')
                    ->getTitleFromRecordUsing(fn (Payment $record): string => $record->center?->name ?? 'Not set')
                    ->collapsible(),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options(collect(PaymentChannel::cases())->mapWithKeys(fn (PaymentChannel $channel): array => [$channel->value => $channel->label()])->all()),
                SelectFilter::make('payment_mode')
                    ->label('Offline method')
                    ->options(OfflinePaymentMethod::options()),
                SelectFilter::make('status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $status): array => [$status->value => ucfirst($status->value)])->all()),
                SelectFilter::make('center_id')
                    ->label('Collected by')
                    ->relationship('center', 'name'),
                Filter::make('no_center')
                    ->label('Collected by not set')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNull('center_id')),
                SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name'),
                Filter::make('paid_between')
                    ->label('Paid between')
                    ->schema([
                        DatePicker::make('from')->label('Paid from'),
                        DatePicker::make('until')->label('Paid until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('paid_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('paid_at', '<=', $date))),
            ])
            ->recordActions([
                Action::make('download_payment_slip')
                    ->label('Payment Slip')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Paid)
                    ->url(fn (Payment $record): string => route('filament.bv-admin.payments.slip.download', ['payment' => $record]))
                    ->openUrlInNewTab(),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Payment $record): bool => $record->isRecordedByStaff()
                        && $record->status !== PaymentStatus::Voided
                        && Filament::auth()->user()?->isAdmin() === true)
                    ->modalDescription('The record is kept for the audit trail but no longer counts in totals. Use this for a wrong amount or the wrong student, then record the payment again. Voiding an earlier (mapped) payment also removes the access it gave the student.')
                    ->schema([
                        Textarea::make('reason')->label('Reason for voiding')->required()->maxLength(500),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        abort_unless(Filament::auth()->user()?->isAdmin() === true, 403);

                        try {
                            app(OfflinePaymentService::class)->void($record, $data['reason'], Filament::auth()->user());
                        } catch (DomainException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Payment voided')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Filament::auth()->user()?->isAdmin() === true),
                ]),
            ]);
    }
}
