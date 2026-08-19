<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MaterialBatchAssignment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'batch_id',
        'assigned_by',
    ];

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
