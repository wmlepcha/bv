<?php

namespace App\Models;

use App\Enums\AssignmentSubmissionStatus;
use Database\Factories\AssignmentSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $assignment_id
 * @property int $student_id
 * @property AssignmentSubmissionStatus $status
 * @property Carbon $submitted_at
 * @property int|null $auto_score
 * @property int|null $final_score
 * @property bool $score_overridden
 * @property string|null $feedback
 * @property int|null $graded_by
 * @property Carbon|null $graded_at
 */
class AssignmentSubmission extends Model
{
    /** @use HasFactory<AssignmentSubmissionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'assignment_id',
        'student_id',
        'status',
        'submitted_at',
        'auto_score',
        'final_score',
        'score_overridden',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AssignmentSubmissionStatus::class,
            'submitted_at' => 'datetime',
            'auto_score' => 'integer',
            'final_score' => 'integer',
            'score_overridden' => 'boolean',
            'graded_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssignmentAnswer::class, 'submission_id');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(SubmissionAnnotation::class, 'submission_id');
    }

    public function isGraded(): bool
    {
        return $this->status === AssignmentSubmissionStatus::Graded;
    }
}
