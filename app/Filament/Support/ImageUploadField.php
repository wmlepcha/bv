<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;

class ImageUploadField
{
    public const MAX_SIZE_KB = 5120;

    public static function configure(FileUpload $field, ?string $helperText = null): FileUpload
    {
        return $field
            ->maxSize(self::MAX_SIZE_KB)
            ->helperText($helperText ?? 'JPG, PNG, WebP, or GIF. Maximum size: 5 MB.')
            ->validationMessages([
                'max' => 'This image is too big. Please upload a file under 5 MB.',
                'uploaded' => 'This image is too big. Please upload a file under 5 MB.',
            ]);
    }
}
