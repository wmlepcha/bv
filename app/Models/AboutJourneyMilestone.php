<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutJourneyMilestone extends Model
{
    public const LAYOUT_CENTER = 'center';

    public const LAYOUT_TEXT_LEFT = 'text-left';

    public const LAYOUT_TEXT_RIGHT = 'text-right';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'period',
        'title',
        'layout',
        'image_path',
        'image_alt',
        'bullets',
        'sort_order',
        'is_visible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bullets' => 'array',
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }
}
