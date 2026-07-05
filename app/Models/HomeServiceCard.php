<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeServiceCard extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'href',
        'image_path',
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
