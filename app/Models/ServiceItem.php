<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceItem extends Model
{
    public const KIND_COURSE = 'course';

    public const KIND_PLACEMENT = 'placement';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'service_group_id',
        'title',
        'slug',
        'family_label',
        'family_description',
        'kind',
        'delivery_modes',
        'level_range',
        'hours',
        'duration_months',
        'timeline',
        'notes',
        'centres',
        'intake_periods',
        'venues',
        'certificates',
        'sectors',
        'image_path',
        'image_alt',
        'sort_order',
        'is_visible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_group_id' => 'integer',
            'delivery_modes' => 'array',
            'centres' => 'array',
            'intake_periods' => 'array',
            'venues' => 'array',
            'certificates' => 'array',
            'sectors' => 'array',
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ServiceGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class, 'service_group_id');
    }
}
