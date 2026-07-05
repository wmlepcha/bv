<?php

namespace App\Filament\Resources\Inquiries\Tables;

use App\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Models\Inquiry;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ...self::candidateColumns(),
                ...self::recruiterColumns(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn (Inquiry $record): string => $record->type === 'candidate'
                        ? 'Candidate inquiry'
                        : 'Recruiter inquiry')
                    ->modalWidth(Width::FourExtraLarge),
            ]);
    }

    /**
     * @return array<int, TextColumn>
     */
    private static function candidateColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Name')
                ->state(fn (Inquiry $record): string => trim(
                    ($record->data['first_name'] ?? '').' '.($record->data['last_name'] ?? '')
                ))
                ->searchable(query: function ($query, string $search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('data->first_name', 'like', "%{$search}%")
                            ->orWhere('data->last_name', 'like', "%{$search}%");
                    });
                })
                ->visible(fn (ListInquiries $livewire): bool => self::isCandidatesTab($livewire)),
            TextColumn::make('candidate_email')
                ->label('Email')
                ->state(fn (Inquiry $record): ?string => $record->data['email'] ?? null)
                ->searchable(query: function ($query, string $search): void {
                    $query->where('data->email', 'like', "%{$search}%");
                })
                ->visible(fn (ListInquiries $livewire): bool => self::isCandidatesTab($livewire)),
            TextColumn::make('candidate_phone')
                ->label('Phone')
                ->state(fn (Inquiry $record): ?string => $record->data['phone'] ?? null)
                ->searchable(query: function ($query, string $search): void {
                    $query->where('data->phone', 'like', "%{$search}%");
                })
                ->visible(fn (ListInquiries $livewire): bool => self::isCandidatesTab($livewire)),
            TextColumn::make('data.city')
                ->label('City')
                ->searchable()
                ->visible(fn (ListInquiries $livewire): bool => self::isCandidatesTab($livewire)),
            TextColumn::make('data.country')
                ->label('Country')
                ->searchable()
                ->visible(fn (ListInquiries $livewire): bool => self::isCandidatesTab($livewire)),
        ];
    }

    /**
     * @return array<int, TextColumn>
     */
    private static function recruiterColumns(): array
    {
        return [
            TextColumn::make('data.company_name')
                ->label('Company')
                ->searchable()
                ->visible(fn (ListInquiries $livewire): bool => self::isRecruitersTab($livewire)),
            TextColumn::make('data.contact_name')
                ->label('Contact name')
                ->searchable()
                ->visible(fn (ListInquiries $livewire): bool => self::isRecruitersTab($livewire)),
            TextColumn::make('recruiter_email')
                ->label('Email')
                ->state(fn (Inquiry $record): ?string => $record->data['email'] ?? null)
                ->searchable(query: function ($query, string $search): void {
                    $query->where('data->email', 'like', "%{$search}%");
                })
                ->visible(fn (ListInquiries $livewire): bool => self::isRecruitersTab($livewire)),
            TextColumn::make('recruiter_phone')
                ->label('Phone')
                ->state(fn (Inquiry $record): ?string => $record->data['phone'] ?? null)
                ->searchable(query: function ($query, string $search): void {
                    $query->where('data->phone', 'like', "%{$search}%");
                })
                ->visible(fn (ListInquiries $livewire): bool => self::isRecruitersTab($livewire)),
            TextColumn::make('data.industry')
                ->label('Industry')
                ->searchable()
                ->visible(fn (ListInquiries $livewire): bool => self::isRecruitersTab($livewire)),
        ];
    }

    private static function isCandidatesTab(ListInquiries $livewire): bool
    {
        return ($livewire->activeTab ?? 'candidates') === 'candidates';
    }

    private static function isRecruitersTab(ListInquiries $livewire): bool
    {
        return $livewire->activeTab === 'recruiters';
    }
}
