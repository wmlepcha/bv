<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Models\Inquiry;
use App\Support\InquiryPresenter;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class InquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Overview')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => InquiryPresenter::typeLabel($state))
                            ->color(fn (Inquiry $record): string => InquiryPresenter::typeColor($record->type)),
                        TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                    ]),
                Tabs::make('CandidateDetails')
                    ->contained()
                    ->visible(fn (Inquiry $record): bool => $record->type === 'candidate')
                    ->tabs(self::candidateTabs()),
                Tabs::make('RecruiterDetails')
                    ->contained()
                    ->visible(fn (Inquiry $record): bool => $record->type === 'recruiter')
                    ->tabs(self::recruiterTabs()),
            ]);
    }

    /**
     * @return array<int, Tab>
     */
    private static function candidateTabs(): array
    {
        return [
            Tab::make('Personal')
                ->schema([
                    Section::make()
                        ->columns(2)
                        ->schema([
                            self::textField('first_name'),
                            self::textField('last_name'),
                            self::textField('date_of_birth')->date(),
                        ]),
                ]),
            Tab::make('Contact')
                ->schema([
                    Section::make()
                        ->columns(2)
                        ->schema([
                            self::textField('email'),
                            self::textField('phone'),
                            self::textField('address')->columnSpanFull(),
                            self::textField('city'),
                            self::textField('country'),
                        ]),
                ]),
            Tab::make('Qualifications')
                ->schema([
                    Section::make()
                        ->columns(2)
                        ->schema([
                            self::optionField('education_level'),
                            self::textField('field_of_study'),
                            self::optionField('years_of_experience'),
                            self::textField('languages'),
                            self::textField('career_goals')->columnSpanFull(),
                        ]),
                ]),
            Tab::make('Documents')
                ->schema([
                    Section::make()
                        ->columns(1)
                        ->schema([
                            self::fileField('qualification_certificate'),
                            self::fileField('resume'),
                            self::fileField('id_document'),
                        ]),
                ]),
        ];
    }

    /**
     * @return array<int, Tab>
     */
    private static function recruiterTabs(): array
    {
        return [
            Tab::make('Company')
                ->schema([
                    Section::make()
                        ->columns(2)
                        ->schema([
                            self::textField('company_name'),
                            self::textField('industry'),
                            self::optionField('company_size'),
                            self::textField('website')
                                ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                ->openUrlInNewTab(),
                        ]),
                ]),
            Tab::make('Contact')
                ->schema([
                    Section::make()
                        ->columns(2)
                        ->schema([
                            self::textField('contact_name'),
                            self::textField('designation'),
                            self::textField('email'),
                            self::textField('phone'),
                        ]),
                ]),
            Tab::make('Hiring')
                ->schema([
                    Section::make()
                        ->columns(2)
                        ->schema([
                            self::textField('roles_hiring'),
                            self::optionField('positions_count'),
                            self::textField('required_skills')->columnSpanFull(),
                            self::textField('preferred_locations'),
                            self::optionField('hiring_timeline'),
                        ]),
                ]),
            Tab::make('Additional')
                ->schema([
                    Section::make()
                        ->columns(1)
                        ->schema([
                            self::textField('heard_about'),
                            self::textField('additional_notes'),
                        ]),
                ]),
        ];
    }

    private static function textField(string $key): TextEntry
    {
        return TextEntry::make("data.{$key}")
            ->label(InquiryPresenter::label($key))
            ->placeholder(InquiryPresenter::placeholder());
    }

    private static function optionField(string $key): TextEntry
    {
        return self::textField($key)
            ->formatStateUsing(fn (mixed $state): ?string => InquiryPresenter::formatOption($key, $state));
    }

    private static function fileField(string $key): ViewEntry
    {
        return ViewEntry::make("data.{$key}")
            ->hiddenLabel()
            ->view('filament.infolists.inquiry-document-entry')
            ->viewData(function (?string $state, Inquiry $record) use ($key): array {
                return [
                    'title' => InquiryPresenter::label($key),
                    'url' => InquiryPresenter::fileUrl($state),
                    'filename' => InquiryPresenter::fileLabel($state),
                    'previewType' => InquiryPresenter::filePreviewType($state),
                    'extension' => InquiryPresenter::fileExtension($state),
                    'downloadUrl' => InquiryPresenter::fileDownloadUrl($record, $key),
                ];
            });
    }
}
