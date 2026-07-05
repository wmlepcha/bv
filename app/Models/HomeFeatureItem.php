<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeFeatureItem extends Model
{
    public const SECTION_CRAFTING = 'crafting';

    public const SECTION_RECRUITERS = 'recruiters';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'section',
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
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }
}
