<?php

namespace App\Filament\Resources\Candidates\RelationManagers;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Actions\RecordOfflinePaymentAction;
use App\Models\Payment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                TextColumn::make('paid_at')->label('Paid on')->dateTime('d M Y')->placeholder('—')->sortable(),
                TextColumn::make('channel')
                    ->badge()
                    ->formatStateUsing(fn (PaymentChannel $state): string => $state->label())
                    ->color(fn (PaymentChannel $state): string => match ($state) {
                        PaymentChannel::Online => 'info',
                        PaymentChannel::Offline => 'warning',
                        PaymentChannel::Legacy => 'gray',
                    }),
                TextColumn::make('method')
                    ->state(fn (Payment $record): string => $record->isRecordedByStaff()
                        ? ($record->offlineMethod()?->label() ?? (string) $record->payment_mode)
                        : ($record->payment_mode ?: 'Website (Cashfree)')),
                TextColumn::make('amount')
                    ->money('INR', divideBy: 100)
                    ->summarize(Sum::make()->label('Collected')->money('INR', divideBy: 100)->query(fn ($query) => $query->where('status', PaymentStatus::Paid))),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => ucfirst($state->value))
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Failed => 'danger',
                        PaymentStatus::Voided => 'gray',
                    }),
                TextColumn::make('payment_mode_detail')->label('Reference')->placeholder('—'),
                TextColumn::make('center.name')->label('Collected by')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                RecordOfflinePaymentAction::make($this->getOwnerRecord()),
            ]);
    }
}
