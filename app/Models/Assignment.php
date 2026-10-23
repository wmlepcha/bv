<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $folder_id
 * @property int $batch_id
 * @property int $created_by
 * @property string $title
 * @property string|null $instructions
 * @property Carbon|null $due_at
 * @property int $max_score
 * @property AssignmentStatus $status
 * @property string|null $audio_disk
 * @property string|null $audio_path
 * @property string|null $audio_original_name
 * @property Carbon|null $published_at
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_id',
        'folder_id',
        'created_by',
        'title',
        'instructions',
        'due_at',
        'max_score',
        'status',
        'audio_disk',
        'audio_path',
        'audio_original_name',
        'published_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'max_score' => 'integer',
            'status' => AssignmentStatus::class,
            'published_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(AssignmentFolder::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(AssignmentTarget::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssignmentQuestion::class)->orderBy('sort_order');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AssignmentSection::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function isPublished(): bool
    {
        return $this->status === AssignmentStatus::Published;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === AssignmentStatus::PendingApproval;
    }

    public function hasAudio(): bool
    {
        return filled($this->audio_disk) && filled($this->audio_path);
    }
}
