<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Actions\RecordOfflinePaymentAction;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\Widgets\PaymentTotals;
use App\Models\Payment;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    /**
     * Filament remembers each person's column choices in their session, which would keep showing the columns
     * we just hid by default. Bump the version when the default columns change so everyone gets the new default once.
     */
    public function getTableColumnsSessionKey(): string
    {
        return parent::getTableColumnsSessionKey().'_v2';
    }

    protected function getHeaderActions(): array
    {
        return [
            RecordOfflinePaymentAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [PaymentTotals::class];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Payment::query()->count()),
            'online' => Tab::make('Online')
                ->badge(Payment::query()->where('channel', PaymentChannel::Online)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('channel', PaymentChannel::Online)),
            'offline' => Tab::make('Offline')
                ->badge(Payment::query()->where('channel', PaymentChannel::Offline)->where('status', '!=', PaymentStatus::Voided)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('channel', PaymentChannel::Offline)->where('status', '!=', PaymentStatus::Voided)),
            'earlier' => Tab::make('Earlier (mapped)')
                ->badge(Payment::query()->where('channel', PaymentChannel::Legacy)->where('status', '!=', PaymentStatus::Voided)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('channel', PaymentChannel::Legacy)->where('status', '!=', PaymentStatus::Voided)),
            'voided' => Tab::make('Voided')
                ->badge(Payment::query()->where('status', PaymentStatus::Voided)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PaymentStatus::Voided)),
        ];
    }
}
