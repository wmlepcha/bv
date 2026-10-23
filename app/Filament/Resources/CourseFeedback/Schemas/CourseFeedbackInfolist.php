<?php

namespace App\Filament\Resources\CourseFeedback\Schemas;

use App\Enums\CourseLevel;
use App\Models\CourseFeedback;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseFeedbackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Candidate details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('student.name')->label('Student'),
                        TextEntry::make('student.email')->label('Email'),
                        TextEntry::make('form_number')
                            ->label('Feedback form')
                            ->formatStateUsing(fn (int $state): string => $state.' of '.User::COURSE_FEEDBACK_FORM_COUNT),
                        TextEntry::make('level')
                            ->label('Level completed')
                            ->badge()
                            ->formatStateUsing(fn (CourseLevel $state): string => $state->label()),
                        TextEntry::make('batch.name')->label('Batch')->placeholder('—'),
                        TextEntry::make('teacher.name')->label('Trainer')->placeholder('—'),
                        TextEntry::make('center.name')->label('Centre')->placeholder('—'),
                        TextEntry::make('created_at')->label('Submitted')->dateTime(),
                    ]),
                Section::make('Ratings')
                    ->columns(2)
                    ->schema(self::ratingEntries()),
                Section::make('Skills & pace')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('most_improved_skill')
                            ->label('Improved the most')
                            ->formatStateUsing(fn (CourseFeedback $record): string => $record->most_improved_skill->label()),
                        TextEntry::make('needs_improvement_skill')
                            ->label('Needs the most improvement')
                            ->formatStateUsing(fn (CourseFeedback $record): string => $record->needs_improvement_skill->label()),
                        TextEntry::make('pace')
                            ->label('Course pace')
                            ->formatStateUsing(fn (CourseFeedback $record): string => $record->pace->label()),
                    ]),
                Section::make('Comments')
                    ->schema([
                        TextEntry::make('liked_most')->label('What did you like most about this course?'),
                        TextEntry::make('improvement_suggestion')->label('What is the one thing we should improve for the next level?'),
                    ]),
            ]);
    }

    /**
     * @return array<int, TextEntry>
     */
    private static function ratingEntries(): array
    {
        $entries = [];

        foreach (CourseFeedback::RATING_QUESTIONS as $field => $question) {
            $entries[] = TextEntry::make($field)
                ->label($question)
                ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state)." ({$state}/5)");
        }

        return $entries;
    }
}
