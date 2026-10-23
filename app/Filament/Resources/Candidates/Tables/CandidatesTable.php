<?php

namespace App\Filament\Resources\Candidates\Tables;

use App\Enums\PlacementStatus;
use App\Enums\Profession;
use App\Enums\RegistrationPurpose;
use App\Filament\Resources\Candidates\Actions\MarkAsAlreadyPaidAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student_number')
                    ->label('Candidate ID')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_unlocked')
                    ->label('Unlocked')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->registration_expires_at?->isFuture() ?? false),
                TextColumn::make('studentProfile.registration_purpose')
                    ->label('Registered for')
                    ->formatStateUsing(fn ($state): string => ($state instanceof RegistrationPurpose ? $state : RegistrationPurpose::tryFrom((string) $state) ?? RegistrationPurpose::Training)->label())
                    ->description(fn ($record): ?string => $record->studentProfile?->placement_requested_at ? 'Placement requested' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('studentProfile.placement_status')
                    ->label('Placement')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof PlacementStatus ? $state : PlacementStatus::tryFrom((string) $state) ?? PlacementStatus::Open)->label())
                    ->color(fn ($state): string => ($state instanceof PlacementStatus ? $state : PlacementStatus::tryFrom((string) $state)) === PlacementStatus::Hired ? 'danger' : 'success')
                    ->placeholder('Open for work')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registrationPlan.name')
                    ->label('Plan')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registration_expires_at')
                    ->label('Access until')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registration_fee_paid_via')
                    ->label('Unlocked via')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registration_paid_at')
                    ->label('Unlocked at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('placement_status')
                    ->label('Placement')
                    ->options(PlacementStatus::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $status): Builder => $q->whereHas('studentProfile', fn (Builder $p) => $p->where('placement_status', $status)),
                    )),
                SelectFilter::make('registration_purpose')
                    ->label('Registered for')
                    ->options(RegistrationPurpose::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $purpose): Builder => $q->whereHas('studentProfile', fn (Builder $p) => $p->where('registration_purpose', $purpose)),
                    )),
                SelectFilter::make('profession')
                    ->options(Profession::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $profession): Builder => $q->whereHas('studentProfile', fn (Builder $p) => $p->whereJsonContains('professions', $profession)),
                    )),
            ])
            ->recordActions([
                EditAction::make(),
                MarkAsAlreadyPaidAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
