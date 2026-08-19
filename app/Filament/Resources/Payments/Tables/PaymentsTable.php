<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('gateway_order_id')
                    ->label('Gateway order ID')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('INR', divideBy: 100)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('download_payment_slip')
                    ->label('Payment Slip')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Paid)
                    ->url(fn (Payment $record): string => route('filament.bv-admin.payments.slip.download', ['payment' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
