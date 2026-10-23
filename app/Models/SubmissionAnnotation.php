<?php

namespace App\Models;

use App\Enums\SubmissionAnnotationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $submission_id
 * @property int|null $question_id
 * @property int $created_by
 * @property SubmissionAnnotationType $type
 * @property array<string, mixed> $payload
 */
class SubmissionAnnotation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'question_id',
        'created_by',
        'type',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SubmissionAnnotationType::class,
            'payload' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(AssignmentQuestion::class, 'question_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
