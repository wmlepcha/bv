<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutParagraph extends Model
{
    public const SECTION_OVERVIEW = 'overview';

    public const SECTION_LEAPFROG = 'leapfrog';

    public const SECTION_CORE_VALUES_INTRO = 'core_values_intro';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'section',
        'body',
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
