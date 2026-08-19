<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MaterialFolder extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'created_by',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function files(): HasMany
    {
        return $this->hasMany(MaterialFile::class, 'folder_id')->orderBy('display_name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(MaterialAssignment::class, 'assignable');
    }

    public function batchAssignments(): MorphMany
    {
        return $this->morphMany(MaterialBatchAssignment::class, 'assignable');
    }

    /**
     * @return list<int>
     */
    public function ancestorIdsIncludingSelf(): array
    {
        $ids = [$this->id];
        $current = $this->parent;

        while ($current !== null) {
            $ids[] = $current->id;
            $current = $current->parent;
        }

        return $ids;
    }
}
