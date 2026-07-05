<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RegistrationPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_student_cannot_create_registration_order(): void
    {
        $user = User::factory()->student()->paid()->create();

        $response = $this->actingAs($user)->postJson(route('payments.registration.order'));

        $response->assertForbidden();
    }

    public function test_payment_verification_activates_student(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        Payment::create([
            'user_id' => $user->id,
            'razorpay_order_id' => 'order_verify123',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
        ]);

        $service = Mockery::mock(PaymentVerificationService::class);
        $service->shouldReceive('verifyCheckoutSignature')
            ->once()
            ->andReturn(true);
        $service->shouldReceive('activateStudentFromPayment')
            ->once()
            ->with(Mockery::type(Payment::class), 'pay_verify123');
        $this->app->instance(PaymentVerificationService::class, $service);

        $response = $this->actingAs($user)->post(route('payments.registration.verify'), [
            'razorpay_order_id' => 'order_verify123',
            'razorpay_payment_id' => 'pay_verify123',
            'razorpay_signature' => 'signature_test',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
