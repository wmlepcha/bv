<?php

namespace App\Models;

use Database\Factories\RecruiterProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $country
 * @property string $sector
 * @property string $timeline
 * @property Carbon|null $finalized_at
 * @property Carbon|null $recruiter_updated_at
 * @property list<string>|null $requirement_template
 * @property int|null $candidates_count
 * @property int|null $students_count
 * @property int|null $sent_count
 * @property int|null $approved_count
 * @property-read User $owner
 */
class RecruiterProject extends Model
{
    /** @use HasFactory<RecruiterProjectFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'country',
        'sector',
        'timeline',
        'finalized_at',
        'requirement_template',
        'recruiter_updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'finalized_at' => 'datetime',
            'recruiter_updated_at' => 'datetime',
            'requirement_template' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany<StudentProfile, $this>
     */
    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'recruiter_project_candidates')->withTimestamps();
    }

    /**
     * @return HasMany<RecruiterProjectCandidate, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(RecruiterProjectCandidate::class);
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }

    /**
     * Draft until finalized; approved once every shortlisted student is approved.
     *
     * @return 'draft'|'in_progress'|'approved'
     */
    public function stage(): string
    {
        if (! $this->isFinalized()) {
            return 'draft';
        }

        $total = $this->entries()->count();
        $approved = $this->entries()->whereNotNull('approved_at')->count();

        return $total > 0 && $approved === $total ? 'approved' : 'in_progress';
    }
}
