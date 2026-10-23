<?php

namespace Tests\Feature\Referrals;

use App\Enums\PreferredCountry;
use App\Enums\ReferralInterest;
use App\Models\Referral;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_page_renders_with_options(): void
    {
        $this->get('/referral')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketing/referral')
                ->has('interestOptions', 4)
                ->where('interestOptions.0', ['value' => 'ausbildung', 'label' => 'Ausbildung'])
                ->has('countryOptions', 3)
                ->where('countryOptions.2', ['value' => 'not_sure', 'label' => 'Not Sure']));
    }

    public function test_referral_can_be_submitted(): void
    {
        $this->from('/referral')
            ->post(route('referral.store'), $this->validPayload())
            ->assertRedirect(route('referral.create'))
            ->assertSessionHasNoErrors();

        $referral = Referral::query()->sole();

        $this->assertSame('Asha Rai', $referral->referrer_name);
        $this->assertSame('+919832581198', $referral->referrer_phone);
        $this->assertSame('Pema Lepcha', $referral->referred_name);
        $this->assertSame('+491701234567', $referral->referred_phone);
        $this->assertSame('pema@example.com', $referral->referred_email);
        $this->assertSame(ReferralInterest::Nursing, $referral->interest);
        $this->assertSame(PreferredCountry::Germany, $referral->preferred_country);
        $this->assertSame('127.0.0.1', $referral->ip_address);
    }

    public function test_email_is_optional(): void
    {
        $this->post(route('referral.store'), [...$this->validPayload(), 'referred_email' => ''])
            ->assertSessionHasNoErrors();

        $this->assertNull(Referral::query()->sole()->referred_email);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->post(route('referral.store'), [])
            ->assertSessionHasErrors([
                'referrer_name',
                'referrer_phone',
                'referred_name',
                'referred_phone',
                'interest',
                'preferred_country',
            ])
            ->assertSessionDoesntHaveErrors('referred_email');

        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->post(route('referral.store'), [
            ...$this->validPayload(),
            'referrer_name' => '<script>alert(1)</script>',
            'referred_name' => 'A',
            'referrer_phone' => '12345',
            'referred_email' => 'not-an-email',
            'interest' => 'engineering',
            'preferred_country' => 'france',
        ])->assertSessionHasErrors([
            'referrer_name',
            'referred_name',
            'referrer_phone',
            'referred_email',
            'interest',
            'preferred_country',
        ]);

        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_referred_phone_must_differ_from_referrer_phone(): void
    {
        $this->post(route('referral.store'), [
            ...$this->validPayload(),
            'referrer_phone' => '98325 81198',
            'referred_phone' => '+91 98325-81198',
        ])->assertSessionHasErrors('referred_phone');
    }

    public function test_indian_ten_digit_numbers_get_country_code(): void
    {
        $this->post(route('referral.store'), [
            ...$this->validPayload(),
            'referrer_phone' => '098325 81198',
            'referred_phone' => '(70012) 34567',
        ])->assertSessionHasNoErrors();

        $referral = Referral::query()->sole();

        $this->assertSame('+919832581198', $referral->referrer_phone);
        $this->assertSame('+917001234567', $referral->referred_phone);
    }

    public function test_names_are_trimmed_and_unexpected_fields_are_ignored(): void
    {
        $this->post(route('referral.store'), [
            ...$this->validPayload(),
            'referrer_name' => "  Asha    Rai \n",
            'ip_address' => '8.8.8.8',
            'id' => 999,
        ])->assertSessionHasNoErrors();

        $referral = Referral::query()->sole();

        $this->assertSame('Asha Rai', $referral->referrer_name);
        $this->assertSame('127.0.0.1', $referral->ip_address);
        $this->assertNotSame(999, $referral->id);
    }

    public function test_honeypot_submissions_are_silently_discarded(): void
    {
        $this->post(route('referral.store'), [...$this->validPayload(), 'company_website' => 'https://spam.test'])
            ->assertRedirect(route('referral.create'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_submissions_are_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('referral.store'), [])->assertSessionHasErrors();
        }

        $this->post(route('referral.store'), $this->validPayload())
            ->assertTooManyRequests();

        $this->assertDatabaseCount('referrals', 0);
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'referrer_name' => 'Asha Rai',
            'referrer_phone' => '+91 98325 81198',
            'referred_name' => 'Pema Lepcha',
            'referred_phone' => '+49 170 1234567',
            'referred_email' => ' Pema@Example.com ',
            'interest' => 'nursing',
            'preferred_country' => 'germany',
        ];
    }
}
