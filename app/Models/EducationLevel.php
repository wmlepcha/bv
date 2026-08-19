<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationLevel extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_visible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }
}
