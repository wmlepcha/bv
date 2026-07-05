<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeStat extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'value',
        'suffix',
        'label',
        'sort_order',
        'is_visible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }
}
