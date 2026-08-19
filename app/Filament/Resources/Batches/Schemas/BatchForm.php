<?php

namespace App\Filament\Resources\Batches\Schemas;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\UserRole;
use App\Models\Center;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class BatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('German B1 · Morning · July 2026'),
                Select::make('center_id')
                    ->label('Center')
                    ->options(function (?Model $record): array {
                        $currentCenterId = $record?->getAttribute('center_id');

                        return Center::query()
                            ->where(function ($query) use ($currentCenterId): void {
                                $query->where('is_active', true);

                                if ($currentCenterId !== null) {
                                    $query->orWhere('id', $currentCenterId);
                                }
                            })
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->required()
                    ->native(false),
                Select::make('language')
                    ->options(CourseLanguage::options())
                    ->required()
                    ->native(false),
                Select::make('level')
                    ->options(CourseLevel::options())
                    ->required()
                    ->native(false),
                Select::make('teacher_id')
                    ->label('Faculty')
                    ->options(
                        fn (): array => User::query()
                            ->where('role', UserRole::Teacher)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all(),
                    )
                    ->searchable()
                    ->required()
                    ->native(false),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
