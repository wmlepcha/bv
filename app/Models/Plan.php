<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'price_rupees',
        'duration_months',
        'features',
        'badge_text',
        'is_recommended',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_recommended' => 'boolean',
            'is_active' => 'boolean',
            'duration_months' => 'integer',
            'price_rupees' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Plan>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Plan>  $query
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function priceInPaise(): int
    {
        return $this->price_rupees * 100;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'registration_plan_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toCheckoutArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price_rupees' => $this->price_rupees,
            'duration_months' => $this->duration_months,
            'features' => $this->features ?? [],
            'badge_text' => $this->badge_text,
            'is_recommended' => $this->is_recommended,
        ];
    }
}
