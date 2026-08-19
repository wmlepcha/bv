<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeHighlight extends Model
{
    public const GROUP_SOFTWARE_SUITE = 'software_suite';

    public const GROUP_SERVICES = 'services';

    /**
     * @return array<string, string>
     */
    public static function groupOptions(): array
    {
        return [
            self::GROUP_SOFTWARE_SUITE => 'Software suite',
            self::GROUP_SERVICES => 'Services',
        ];
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'label',
        'group',
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
