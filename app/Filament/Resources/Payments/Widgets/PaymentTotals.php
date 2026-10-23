<?php

namespace App\Filament\Resources\Payments\Widgets;

use App\Enums\PaymentChannel;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class PaymentTotals extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $online = Payment::query()->collected()->where('channel', PaymentChannel::Online);
        $offline = Payment::query()->collected()->where('channel', PaymentChannel::Offline);
        $earlier = Payment::query()->collected()->where('channel', PaymentChannel::Legacy);

        return [
            Stat::make('Online (website)', self::rupees((int) $online->sum('amount')))
                ->description($online->count().' payments'),
            Stat::make('Offline (cash, UPI, bank, cheque)', self::rupees((int) $offline->sum('amount')))
                ->description($offline->count().' payments'),
            Stat::make('Earlier (mapped students)', self::rupees((int) $earlier->sum('amount')))
                ->description($earlier->count().' payments'),
            Stat::make('Total collected', self::rupees((int) Payment::query()->collected()->sum('amount')))
                ->description('Voided and unpaid are not counted'),
        ];
    }

    private static function rupees(int $paise): string
    {
        return (string) Number::currency($paise / 100, 'INR');
    }
}
