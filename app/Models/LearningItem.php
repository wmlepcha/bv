<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'material_file_id',
        'note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function materialFile(): BelongsTo
    {
        return $this->belongsTo(MaterialFile::class);
    }
}
