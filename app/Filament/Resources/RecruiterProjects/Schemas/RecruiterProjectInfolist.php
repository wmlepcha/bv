<?php

namespace App\Filament\Resources\RecruiterProjects\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RecruiterProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project')
                ->columns(3)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('country'),
                    TextEntry::make('sector'),
                    TextEntry::make('timeline'),
                    TextEntry::make('owner.name')->label('Recruiter'),
                    TextEntry::make('owner.email')->label('Recruiter email'),
                    TextEntry::make('finalized_at')->label('Sent to BV')->dateTime(),
                    TextEntry::make('requirement_template')
                        ->label('Requirements for every student')
                        ->badge()
                        ->columnSpan(2),
                ]),
        ]);
    }
}
