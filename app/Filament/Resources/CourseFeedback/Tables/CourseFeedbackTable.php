<?php

namespace App\Filament\Resources\CourseFeedback\Tables;

use App\Enums\CourseLevel;
use App\Models\CourseFeedback;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourseFeedbackTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['student', 'batch', 'teacher', 'center']))
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable(),
                TextColumn::make('form_number')
                    ->label('Form')
                    ->formatStateUsing(fn (int $state): string => $state.' of '.User::COURSE_FEEDBACK_FORM_COUNT)
                    ->sortable(),
                TextColumn::make('level')
                    ->badge()
                    ->formatStateUsing(fn (CourseLevel $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('batch.name')
                    ->label('Batch')
                    ->placeholder('—'),
                TextColumn::make('teacher.name')
                    ->label('Trainer')
                    ->placeholder('—'),
                TextColumn::make('center.name')
                    ->label('Centre')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('overall_satisfaction')
                    ->label('Overall')
                    ->formatStateUsing(fn (int $state): string => $state.' / 5')
                    ->sortable(),
                TextColumn::make('average_rating')
                    ->label('Average')
                    ->state(fn (CourseFeedback $record): string => $record->averageRating().' / 5'),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('form_number')
                    ->label('Form')
                    ->options(array_combine(
                        range(1, User::COURSE_FEEDBACK_FORM_COUNT),
                        array_map(fn (int $number): string => 'Form '.$number, range(1, User::COURSE_FEEDBACK_FORM_COUNT)),
                    )),
                SelectFilter::make('level')
                    ->options(CourseLevel::options()),
                SelectFilter::make('batch')
                    ->relationship('batch', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('center')
                    ->label('Centre')
                    ->relationship('center', 'name'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Course completion feedback')
                    ->modalWidth(Width::FourExtraLarge),
            ]);
    }
}
