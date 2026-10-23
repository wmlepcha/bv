<?php

namespace App\Models;

use App\Enums\PreferredCountry;
use App\Enums\ReferralInterest;
use Database\Factories\ReferralFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $referrer_name
 * @property string $referrer_phone
 * @property string $referred_name
 * @property string $referred_phone
 * @property string|null $referred_email
 * @property ReferralInterest $interest
 * @property PreferredCountry $preferred_country
 * @property string|null $ip_address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Referral extends Model
{
    /** @use HasFactory<ReferralFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'referrer_name',
        'referrer_phone',
        'referred_name',
        'referred_phone',
        'referred_email',
        'interest',
        'preferred_country',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interest' => ReferralInterest::class,
            'preferred_country' => PreferredCountry::class,
        ];
    }
}
