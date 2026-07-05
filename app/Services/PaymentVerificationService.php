<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationFeePaidVia;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class PaymentVerificationService
{
    public function __construct(
        private readonly StudentNumberGenerator $studentNumberGenerator,
    ) {}

    public function verifyCheckoutSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $api = $this->api();

        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);

            return true;
        } catch (SignatureVerificationError) {
            return false;
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('razorpay.webhook_secret');

        if (empty($secret)) {
            return false;
        }

        $api = $this->api();

        try {
            $api->utility->verifyWebhookSignature($payload, $signature, $secret);

            return true;
        } catch (SignatureVerificationError) {
            return false;
        }
    }

    public function activateStudentFromPayment(Payment $payment, string $razorpayPaymentId): void
    {
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($payment, $razorpayPaymentId): void {
            $payment->refresh();

            if ($payment->status === PaymentStatus::Paid) {
                return;
            }

            $payment->update([
                'razorpay_payment_id' => $razorpayPaymentId,
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            $user = $payment->user;

            if ($user->hasPaidRegistration()) {
                return;
            }

            $user->forceFill([
                'registration_paid_at' => now(),
                'registration_fee_paid_via' => RegistrationFeePaidVia::Razorpay,
                'student_number' => $user->student_number ?? $this->studentNumberGenerator->generate(),
            ])->save();

            StudentProfile::query()->firstOrCreate([
                'user_id' => $user->id,
            ]);
        });
    }

    public function api(): Api
    {
        return new Api(
            config('razorpay.key'),
            config('razorpay.secret'),
        );
    }
}
