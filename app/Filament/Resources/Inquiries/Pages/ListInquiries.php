<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInquiries extends ListRecords
{
    protected static string $resource = InquiryResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'candidates' => Tab::make('Candidates')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('type', 'candidate'))
                ->badge(Inquiry::query()->where('type', 'candidate')->count()),
            'recruiters' => Tab::make('Recruiters')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('type', 'recruiter'))
                ->badge(Inquiry::query()->where('type', 'recruiter')->count()),
        ];
    }
}
