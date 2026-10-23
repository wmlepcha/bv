<?php

namespace App\Http\Controllers;

use App\Enums\PreferredCountry;
use App\Enums\ReferralInterest;
use App\Http\Requests\StoreReferralRequest;
use App\Models\Referral;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    /**
     * Hidden form field that humans never fill in; bots usually do.
     */
    public const HONEYPOT_FIELD = 'company_website';

    public function create(): Response
    {
        return Inertia::render('marketing/referral', [
            'interestOptions' => self::options(ReferralInterest::options()),
            'countryOptions' => self::options(PreferredCountry::options()),
        ]);
    }

    public function store(StoreReferralRequest $request): RedirectResponse
    {
        if (! $request->filled(self::HONEYPOT_FIELD)) {
            Referral::query()->create([
                ...$request->validated(),
                'ip_address' => $request->ip(),
            ]);
        }

        return to_route('referral.create');
    }

    /**
     * @param  array<string, string>  $options
     * @return list<array{value: string, label: string}>
     */
    private static function options(array $options): array
    {
        $formatted = [];

        foreach ($options as $value => $label) {
            $formatted[] = ['value' => $value, 'label' => $label];
        }

        return $formatted;
    }
}
