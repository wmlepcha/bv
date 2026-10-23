<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $type
 * @property array<string, mixed> $data
 */
class Inquiry extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
