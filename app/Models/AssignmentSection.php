<?php

namespace App\Models;

use Database\Factories\AssignmentSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $assignment_id
 * @property string $label
 * @property string|null $title
 * @property string|null $instructions
 * @property int $sort_order
 */
class AssignmentSection extends Model
{
    /** @use HasFactory<AssignmentSectionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'assignment_id',
        'label',
        'title',
        'instructions',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssignmentQuestion::class, 'section_id')->orderBy('sort_order');
    }
}
