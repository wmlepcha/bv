<?php

namespace Database\Factories;

use App\Enums\PreferredCountry;
use App\Enums\ReferralInterest;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referral>
 */
class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'referrer_name' => fake()->name(),
            'referrer_phone' => '+91'.fake()->numerify('98########'),
            'referred_name' => fake()->name(),
            'referred_phone' => '+91'.fake()->numerify('97########'),
            'referred_email' => fake()->optional()->safeEmail(),
            'interest' => fake()->randomElement(ReferralInterest::cases()),
            'preferred_country' => fake()->randomElement(PreferredCountry::cases()),
            'ip_address' => fake()->ipv4(),
        ];
    }
}
