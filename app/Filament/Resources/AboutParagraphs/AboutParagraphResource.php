<?php

namespace App\Filament\Resources\AboutParagraphs;

use App\Filament\Resources\AboutParagraphs\Pages\CreateAboutParagraph;
use App\Filament\Resources\AboutParagraphs\Pages\EditAboutParagraph;
use App\Filament\Resources\AboutParagraphs\Pages\ListAboutParagraphs;
use App\Filament\Resources\AboutParagraphs\Schemas\AboutParagraphForm;
use App\Filament\Resources\AboutParagraphs\Tables\AboutParagraphsTable;
use App\Models\AboutParagraph;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AboutParagraphResource extends Resource
{
    protected static ?string $model = AboutParagraph::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'About Page';

    protected static ?string $navigationLabel = 'Paragraphs';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return AboutParagraphForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AboutParagraphsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAboutParagraphs::route('/'),
            'create' => CreateAboutParagraph::route('/create'),
            'edit' => EditAboutParagraph::route('/{record}/edit'),
        ];
    }
}
