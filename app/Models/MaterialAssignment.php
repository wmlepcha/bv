<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MaterialAssignment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'trainer_id',
        'assigned_by',
    ];

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
