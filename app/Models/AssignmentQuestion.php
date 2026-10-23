<?php

namespace App\Models;

use App\Enums\AssignmentQuestionType;
use Database\Factories\AssignmentQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $assignment_id
 * @property int|null $section_id
 * @property int $sort_order
 * @property AssignmentQuestionType $type
 * @property string $prompt
 * @property int $points
 * @property array<string, mixed>|null $config
 */
class AssignmentQuestion extends Model
{
    /** @use HasFactory<AssignmentQuestionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'assignment_id',
        'section_id',
        'sort_order',
        'type',
        'prompt',
        'points',
        'config',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'type' => AssignmentQuestionType::class,
            'points' => 'integer',
            'config' => 'array',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AssignmentSection::class, 'section_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssignmentAnswer::class, 'question_id');
    }

    public function isMcq(): bool
    {
        return $this->type === AssignmentQuestionType::Mcq;
    }

    /**
     * @return list<string>
     */
    public function mcqOptions(): array
    {
        if (! $this->isMcq()) {
            return [];
        }

        $options = $this->config['options'] ?? [];

        return is_array($options) ? array_values($options) : [];
    }

    public function correctMcqIndex(): ?int
    {
        if (! $this->isMcq()) {
            return null;
        }

        $index = $this->config['correct_index'] ?? null;

        return is_int($index) ? $index : (is_numeric($index) ? (int) $index : null);
    }

    public function isFillInBlank(): bool
    {
        return $this->type === AssignmentQuestionType::FillInBlank;
    }

    public function isWordReorder(): bool
    {
        return $this->type === AssignmentQuestionType::WordReorder;
    }

    public function blankTemplate(): string
    {
        if (! $this->isFillInBlank()) {
            return $this->prompt;
        }

        $template = $this->config['template'] ?? null;

        return is_string($template) && $template !== '' ? $template : $this->prompt;
    }

    public function blankCount(): int
    {
        if (! $this->isFillInBlank()) {
            return 0;
        }

        $count = $this->config['blank_count'] ?? null;

        if (is_int($count)) {
            return $count;
        }

        return substr_count($this->blankTemplate(), '___');
    }

    /**
     * @return list<string>
     */
    public function blankHints(): array
    {
        if (! $this->isFillInBlank()) {
            return [];
        }

        $hints = $this->config['hints'] ?? [];

        return is_array($hints) ? array_values($hints) : [];
    }

    /**
     * @return list<string>
     */
    public function wordTokens(): array
    {
        if (! $this->isWordReorder()) {
            return [];
        }

        $tokens = $this->config['tokens'] ?? [];

        return is_array($tokens) ? array_values($tokens) : [];
    }

    public function isMediaFile(): bool
    {
        return $this->type->isMediaFile();
    }

    public function isMediaItem(): bool
    {
        return $this->type->isMediaItem();
    }

    public function isLink(): bool
    {
        return $this->type === AssignmentQuestionType::Link;
    }

    public function hasMedia(): bool
    {
        if ($this->isLink()) {
            return filled($this->mediaUrl());
        }

        return filled($this->config['disk'] ?? null) && filled($this->config['path'] ?? null);
    }

    public function mediaDisk(): ?string
    {
        if (! $this->isMediaFile()) {
            return null;
        }

        $disk = $this->config['disk'] ?? null;

        return is_string($disk) && $disk !== '' ? $disk : null;
    }

    public function mediaPath(): ?string
    {
        if (! $this->isMediaFile()) {
            return null;
        }

        $path = $this->config['path'] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function mediaOriginalName(): ?string
    {
        if (! $this->isMediaFile()) {
            return null;
        }

        $name = $this->config['original_name'] ?? null;

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function mediaUrl(): ?string
    {
        if (! $this->isLink()) {
            return null;
        }

        $url = $this->config['url'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }
}
