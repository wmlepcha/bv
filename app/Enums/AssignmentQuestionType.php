<?php

namespace App\Enums;

enum AssignmentQuestionType: string
{
    case Mcq = 'mcq';
    case ShortText = 'short_text';
    case FillInBlank = 'fill_in_blank';
    case WordReorder = 'word_reorder';
    case Pdf = 'pdf';
    case Image = 'image';
    case Audio = 'audio';
    case Link = 'link';

    public function label(): string
    {
        return match ($this) {
            self::Mcq => 'Multiple choice',
            self::ShortText => 'Short text',
            self::FillInBlank => 'Fill in the blank',
            self::WordReorder => 'Word reorder',
            self::Pdf => 'PDF',
            self::Image => 'Image',
            self::Audio => 'Audio',
            self::Link => 'Link',
        };
    }

    public function isMediaFile(): bool
    {
        return match ($this) {
            self::Pdf, self::Image, self::Audio => true,
            default => false,
        };
    }

    public function isMediaItem(): bool
    {
        return $this->isMediaFile() || $this === self::Link;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
