<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationFeePaidVia;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\SiteSetting;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\PaymentVerificationService;
use App\Support\RegistrationFeeAmount;
use App\Support\RegistrationTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RegistrationPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_student_can_create_registration_order_for_early_renew(): void
    {
        $user = User::factory()->student()->paid()->create();
        $plan = Plan::query()->where('duration_months', 12)->firstOrFail();

        $service = Mockery::mock(PaymentVerificationService::class);
        $service->shouldReceive('createRegistrationOrder')
            ->once()
            ->with(Mockery::on(fn (User $u): bool => $u->is($user)), '9876543210', Mockery::on(fn (Plan $p): bool => $p->is($plan)))
            ->andReturn([
                'order_id' => 'order_renew',
                'payment_session_id' => 'session_renew',
                'amount' => $plan->priceInPaise(),
                'currency' => 'INR',
                'environment' => 'production',
            ]);
        $this->app->instance(PaymentVerificationService::class, $service);

        $response = $this->actingAs($user)->postJson(route('payments.registration.order'), [
            'phone' => '9876543210',
            'plan_id' => $plan->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('order_id', 'order_renew');
    }

    public function test_inactive_plan_is_rejected_for_order(): void
    {
        $user = User::factory()->student()->unpaid()->create();
        $plan = Plan::query()->where('duration_months', 12)->firstOrFail();
        $plan->update(['is_active' => false]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
            'mobile_whatsapp' => '9876543210',
        ]);

        $response = $this->actingAs($user)->postJson(route('payments.registration.order'), [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.plan_id.0', 'The selected plan is not available.');
    }

    public function test_unpaid_student_requires_phone_when_profile_has_none(): void
    {
        $user = User::factory()->student()->unpaid()->create();
        $plan = Plan::query()->where('duration_months', 12)->firstOrFail();

        $response = $this->actingAs($user)->postJson(route('payments.registration.order'), [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.phone.0', 'A valid 10-digit mobile number is required to pay.');
    }

    public function test_payment_verification_activates_student(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        Payment::create([
            'user_id' => $user->id,
            'gateway_order_id' => 'order_verify123',
            'amount' => 100,
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
            'duration_months' => 12,
        ]);

        $service = Mockery::mock(PaymentVerificationService::class);
        $service->shouldReceive('fetchOrderAndActivate')
            ->once()
            ->with('order_verify123')
            ->andReturn(true);
        $this->app->instance(PaymentVerificationService::class, $service);

        $response = $this->actingAs($user)->post(route('payments.registration.verify'), [
            'order_id' => 'order_verify123',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_fee_uses_site_setting_over_config(): void
    {
        config(['cashfree.registration_fee_amount' => 500000]);

        SiteSetting::setValue(RegistrationFeeAmount::SITE_SETTING_KEY, '1');

        $this->assertSame(1, RegistrationFeeAmount::amountInRupees());
        $this->assertSame(100, RegistrationFeeAmount::amountInPaise());
    }

    public function test_registration_fee_falls_back_to_config_then_default(): void
    {
        config(['cashfree.registration_fee_amount' => 250000]);

        $this->assertSame(2500, RegistrationFeeAmount::amountInRupees());
        $this->assertSame(250000, RegistrationFeeAmount::amountInPaise());

        config(['cashfree.registration_fee_amount' => 0]);

        $this->assertSame(1, RegistrationFeeAmount::amountInRupees());
        $this->assertSame(100, RegistrationFeeAmount::amountInPaise());
    }

    public function test_registration_terms_fall_back_to_default_when_empty(): void
    {
        $default = RegistrationTerms::html();

        $this->assertStringContainsString('choose a plan', strtolower($default));
        $this->assertStringContainsString('registration code', strtolower($default));

        SiteSetting::setValue(RegistrationTerms::SITE_SETTING_KEY, '<p></p>');

        $this->assertSame($default, RegistrationTerms::html());
    }

    public function test_registration_terms_use_site_setting_when_set(): void
    {
        SiteSetting::setValue(
            RegistrationTerms::SITE_SETTING_KEY,
            '<p>Custom registration terms.</p>',
        );

        $this->assertSame(
            '<p>Custom registration terms.</p>',
            RegistrationTerms::html(),
        );
    }

    public function test_payment_callback_activates_student(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        Payment::create([
            'user_id' => $user->id,
            'gateway_order_id' => 'order_callback123',
            'amount' => 100,
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
            'duration_months' => 12,
        ]);

        $service = Mockery::mock(PaymentVerificationService::class);
        $service->shouldReceive('fetchOrderAndActivate')
            ->once()
            ->with('order_callback123')
            ->andReturn(true);
        $this->app->instance(PaymentVerificationService::class, $service);

        $response = $this->get(route('payments.registration.callback', [
            'order_id' => 'order_callback123',
        ]));

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_payment_callback_rejects_unpaid_order(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        Payment::create([
            'user_id' => $user->id,
            'gateway_order_id' => 'order_bad_status',
            'amount' => 100,
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
            'duration_months' => 12,
        ]);

        $service = Mockery::mock(PaymentVerificationService::class);
        $service->shouldReceive('fetchOrderAndActivate')
            ->once()
            ->with('order_bad_status')
            ->andReturn(false);
        $this->app->instance(PaymentVerificationService::class, $service);

        $response = $this->post(route('payments.registration.callback'), [
            'order_id' => 'order_bad_status',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('error', 'Payment verification failed.');
    }

    public function test_webhook_activates_student_on_payment_success(): void
    {
        $user = User::factory()->student()->unpaid()->create();
        $plan = Plan::query()->where('duration_months', 12)->firstOrFail();

        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'duration_months' => 12,
            'gateway_order_id' => 'order_webhook123',
            'amount' => $plan->priceInPaise(),
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
        ]);

        $payload = json_encode([
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'data' => [
                'order' => ['order_id' => 'order_webhook123'],
                'payment' => ['cf_payment_id' => 'pay_webhook123'],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) time();
        $secret = 'test_webhook_secret';
        config(['cashfree.webhook_secret' => $secret]);

        $signature = base64_encode(hash_hmac('sha256', $timestamp.$payload, $secret, true));

        $response = $this->call(
            'POST',
            route('webhooks.cashfree'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
                'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            ],
            $payload,
        );

        $response->assertOk();

        $payment->refresh();
        $user->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('pay_webhook123', $payment->gateway_payment_id);
        $this->assertNotNull($user->registration_paid_at);
        $this->assertNotNull($user->registration_expires_at);
        $this->assertTrue($user->registration_expires_at->isFuture());
        $this->assertSame(RegistrationFeePaidVia::Cashfree, $user->registration_fee_paid_via);
    }

    public function test_create_order_uses_profile_phone_when_present(): void
    {
        $user = User::factory()->student()->unpaid()->create();
        $plan = Plan::query()->where('duration_months', 12)->firstOrFail();

        StudentProfile::query()->create([
            'user_id' => $user->id,
            'mobile_whatsapp' => '9876543210',
        ]);

        $service = Mockery::mock(PaymentVerificationService::class);
        $service->shouldReceive('createRegistrationOrder')
            ->once()
            ->with(
                Mockery::on(fn (User $u): bool => $u->is($user)),
                '9876543210',
                Mockery::on(fn (Plan $p): bool => $p->is($plan)),
            )
            ->andReturn([
                'order_id' => 'order_profile_phone',
                'payment_session_id' => 'session_test',
                'amount' => $plan->priceInPaise(),
                'currency' => 'INR',
                'environment' => 'production',
            ]);
        $this->app->instance(PaymentVerificationService::class, $service);

        $response = $this->actingAs($user)->postJson(route('payments.registration.order'), [
            'plan_id' => $plan->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('order_id', 'order_profile_phone');
        $response->assertJsonPath('payment_session_id', 'session_test');
    }

    public function test_early_renewal_stacks_remaining_access_time(): void
    {
        $user = User::factory()->student()->paid()->create();
        $user->forceFill([
            'registration_expires_at' => now()->addMonths(4),
        ])->save();
        $user->refresh();

        $this->assertTrue($user->registration_expires_at?->isFuture() ?? false);

        $originalExpiry = $user->registration_expires_at->copy();
        $plan = Plan::query()->where('duration_months', 12)->firstOrFail();

        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'duration_months' => 12,
            'gateway_order_id' => 'order_stack123',
            'amount' => $plan->priceInPaise(),
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
        ]);

        app(PaymentVerificationService::class)->activateStudentFromPayment($payment, 'pay_stack123');

        $user->refresh();

        $this->assertSame(
            $originalExpiry->copy()->addMonths(12)->toDateTimeString(),
            $user->registration_expires_at?->toDateTimeString(),
        );
    }

    public function test_expired_student_cannot_access_student_routes(): void
    {
        $user = User::factory()->student()->expired()->create();

        $this->actingAs($user)->get(route('student.classes.index'))->assertForbidden();
    }
}
