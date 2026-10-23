<?php

namespace App\Filament\Resources\CourseFeedback;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\CourseFeedback\Pages\ListCourseFeedback;
use App\Filament\Resources\CourseFeedback\Schemas\CourseFeedbackInfolist;
use App\Filament\Resources\CourseFeedback\Tables\CourseFeedbackTable;
use App\Models\CourseFeedback;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CourseFeedbackResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = CourseFeedback::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Training';

    protected static ?string $navigationLabel = 'Course Feedback';

    protected static ?string $modelLabel = 'course feedback';

    protected static ?string $pluralModelLabel = 'course feedback';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return CourseFeedbackInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseFeedbackTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseFeedback::route('/'),
        ];
    }
}
