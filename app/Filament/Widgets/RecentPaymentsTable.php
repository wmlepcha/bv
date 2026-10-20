<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Filament\Widgets\Concerns\UsesDashboardRange;
use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentPaymentsTable extends TableWidget
{
    use UsesDashboardRange;

    protected static ?string $heading = 'Recent payments';

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 7];

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->inRange(Payment::query()->with(['user:id,name', 'center:id,name']), $this->paidAtColumn())
                ->orderByDesc($this->paidAtColumn()))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('user.name')->label('Student'),
                TextColumn::make('center.name')->label('Center')->placeholder('Unassigned')->color('gray'),
                TextColumn::make('amount')->money('INR', divideBy: 100)->weight('semibold'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => ucfirst($state->value))
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Failed => 'danger',
                        PaymentStatus::Voided => 'gray',
                    }),
                TextColumn::make('paid_at')
                    ->label('Date')
                    ->state(fn (Payment $payment) => $payment->paid_at ?? $payment->created_at)
                    ->dateTime('d M Y, h:i A')
                    ->color('gray'),
            ]);
    }
}
