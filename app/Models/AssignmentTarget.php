<?php

namespace App\Models;

use App\Enums\AssignmentTargetScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $assignment_id
 * @property AssignmentTargetScope $scope
 * @property int|null $student_id
 */
class AssignmentTarget extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'assignment_id',
        'scope',
        'student_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => AssignmentTargetScope::class,
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
}
