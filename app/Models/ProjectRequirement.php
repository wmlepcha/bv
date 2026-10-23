<?php

namespace App\Models;

use App\Enums\RequirementReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $recruiter_project_candidate_id
 * @property string $title
 * @property string|null $file_path
 * @property string|null $original_name
 * @property Carbon|null $uploaded_at
 * @property RequirementReviewStatus $review_status
 * @property string|null $rejection_note
 * @property string|null $sample_path
 * @property string|null $sample_name
 * @property Carbon|null $reviewed_at
 * @property-read RecruiterProjectCandidate $entry
 */
class ProjectRequirement extends Model
{
    public const DISK = 'local';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'recruiter_project_candidate_id',
        'title',
        'file_path',
        'original_name',
        'uploaded_by',
        'uploaded_at',
        'review_status',
        'rejection_note',
        'sample_path',
        'sample_name',
        'reviewed_at',
        'reviewed_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'review_status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'review_status' => RequirementReviewStatus::class,
        ];
    }

    /**
     * @return BelongsTo<RecruiterProjectCandidate, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(RecruiterProjectCandidate::class, 'recruiter_project_candidate_id');
    }

    public function isUploaded(): bool
    {
        return filled($this->file_path);
    }

    public function isApproved(): bool
    {
        return $this->review_status === RequirementReviewStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->review_status === RequirementReviewStatus::Rejected;
    }

    public function hasSample(): bool
    {
        return filled($this->sample_path);
    }

    public function deleteSample(): void
    {
        if ($this->hasSample()) {
            Storage::disk(self::DISK)->delete($this->sample_path);
        }
    }

    public function deleteFile(): void
    {
        if ($this->isUploaded()) {
            Storage::disk(self::DISK)->delete($this->file_path);
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (ProjectRequirement $requirement): void {
            $requirement->deleteFile();
            $requirement->deleteSample();
        });
    }
}
