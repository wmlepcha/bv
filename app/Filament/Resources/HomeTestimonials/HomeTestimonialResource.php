<?php

namespace App\Filament\Resources\HomeTestimonials;

use App\Filament\Resources\HomeTestimonials\Pages\CreateHomeTestimonial;
use App\Filament\Resources\HomeTestimonials\Pages\EditHomeTestimonial;
use App\Filament\Resources\HomeTestimonials\Pages\ListHomeTestimonials;
use App\Filament\Resources\HomeTestimonials\Schemas\HomeTestimonialForm;
use App\Filament\Resources\HomeTestimonials\Tables\HomeTestimonialsTable;
use App\Models\HomeTestimonial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HomeTestimonialResource extends Resource
{
    protected static ?string $model = HomeTestimonial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?string $navigationLabel = 'Testimonials';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return HomeTestimonialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeTestimonialsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeTestimonials::route('/'),
            'create' => CreateHomeTestimonial::route('/create'),
            'edit' => EditHomeTestimonial::route('/{record}/edit'),
        ];
    }
}
