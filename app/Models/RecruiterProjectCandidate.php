<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $recruiter_project_id
 * @property int $student_profile_id
 * @property Carbon|null $approved_at
 * @property int|null $approved_by
 * @property Carbon|null $congratulated_at
 * @property Carbon|null $requirements_sent_at
 * @property Carbon|null $sent_to_recruiter_at
 * @property-read RecruiterProject $project
 * @property-read StudentProfile $studentProfile
 * @property-read Collection<int, ProjectRequirement> $requirements
 */
class RecruiterProjectCandidate extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'recruiter_project_id',
        'student_profile_id',
        'approved_at',
        'approved_by',
        'congratulated_at',
        'requirements_sent_at',
        'sent_to_recruiter_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'congratulated_at' => 'datetime',
            'requirements_sent_at' => 'datetime',
            'sent_to_recruiter_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RecruiterProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(RecruiterProject::class, 'recruiter_project_id');
    }

    /**
     * @return BelongsTo<StudentProfile, $this>
     */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /**
     * @return HasMany<ProjectRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(ProjectRequirement::class)->orderBy('id');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isSentToRecruiter(): bool
    {
        return $this->sent_to_recruiter_at !== null;
    }

    public function allFilesApproved(): bool
    {
        return $this->requirements->isNotEmpty() && $this->requirements->every(fn (ProjectRequirement $requirement): bool => $requirement->isApproved());
    }

    public function hasRejectedFiles(): bool
    {
        return $this->requirements->contains(fn (ProjectRequirement $requirement): bool => $requirement->isRejected());
    }

    public function uploadedCount(): int
    {
        return $this->requirements->whereNotNull('file_path')->count();
    }

    public function isReadyForApproval(): bool
    {
        $total = $this->requirements->count();

        return $total > 0 && $this->uploadedCount() === $total;
    }

    /**
     * @return 'approved'|'changes_requested'|'sent'|'ready'|'awaiting'|'draft'
     */
    public function statusFor(RecruiterProject $project): string
    {
        return match (true) {
            $this->isApproved() => 'approved',
            ! $project->isFinalized() => 'draft',
            $this->isSentToRecruiter() && $this->hasRejectedFiles() => 'changes_requested',
            $this->isSentToRecruiter() => 'sent',
            $this->isReadyForApproval() => 'ready',
            default => 'awaiting',
        };
    }
}
