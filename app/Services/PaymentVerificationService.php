<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationFeePaidVia;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\RegistrationActivated;
use Cashfree\Cashfree;
use Cashfree\Model\CreateOrderRequest;
use Cashfree\Model\CustomerDetails;
use Cashfree\Model\OrderEntity;
use Cashfree\Model\OrderMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PaymentVerificationService
{
    public function __construct(
        private readonly StudentNumberGenerator $studentNumberGenerator,
        private readonly RegistrationAccessService $registrationAccessService,
    ) {}

    /**
     * @return array{order_id: string, payment_session_id: string, amount: int, currency: string, environment: string}
     */
    public function createRegistrationOrder(User $user, string $phone, Plan $plan): array
    {
        $amountPaise = $plan->priceInPaise();
        $amountRupees = $plan->price_rupees;
        $currency = (string) config('cashfree.currency', 'INR');
        $orderId = 'reg_'.$user->id.'_'.Str::lower(Str::random(12));

        $customer = new CustomerDetails;
        $customer->setCustomerId('user_'.$user->id);
        $customer->setCustomerPhone($phone);
        $customer->setCustomerEmail($user->email);
        $customer->setCustomerName($user->name);

        $meta = new OrderMeta;
        $meta->setReturnUrl(route('payments.registration.callback', absolute: true).'?order_id={order_id}');
        $meta->setNotifyUrl(route('webhooks.cashfree', absolute: true));

        $request = new CreateOrderRequest;
        $request->setOrderId($orderId);
        $request->setOrderAmount((float) $amountRupees);
        $request->setOrderCurrency($currency);
        $request->setCustomerDetails($customer);
        $request->setOrderMeta($meta);

        $apiVersion = (string) config('cashfree.api_version', '2023-08-01');

        try {
            /** @var array{0: OrderEntity, 1: int, 2: array<string, list<string>>} $result */
            $result = $this->client()->PGCreateOrder($apiVersion, $request);
        } catch (Throwable $e) {
            throw new RuntimeException('Unable to create Cashfree order: '.$e->getMessage(), previous: $e);
        }

        $order = $result[0] ?? null;

        if (! $order instanceof OrderEntity || blank($order->getPaymentSessionId())) {
            throw new RuntimeException('Cashfree did not return a payment session.');
        }

        Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'duration_months' => $plan->duration_months,
            'gateway_order_id' => $order->getOrderId() ?: $orderId,
            'amount' => $amountPaise,
            'currency' => $currency,
            'status' => PaymentStatus::Pending,
        ]);

        return [
            'order_id' => $order->getOrderId() ?: $orderId,
            'payment_session_id' => $order->getPaymentSessionId(),
            'amount' => $amountPaise,
            'currency' => $currency,
            'environment' => $this->environmentName(),
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $timestamp): bool
    {
        $secret = (string) (config('cashfree.webhook_secret') ?: config('cashfree.secret_key'));

        if ($secret === '' || $signature === '' || $timestamp === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $timestamp.$payload, $secret, true));

        return hash_equals($expected, $signature);
    }

    public function fetchOrderAndActivate(string $orderId): bool
    {
        $payment = Payment::query()
            ->where('gateway_order_id', $orderId)
            ->first();

        if (! $payment) {
            return false;
        }

        if ($payment->status === PaymentStatus::Paid) {
            return true;
        }

        $apiVersion = (string) config('cashfree.api_version', '2023-08-01');

        try {
            /** @var array{0: OrderEntity, 1: int, 2: array<string, list<string>>} $result */
            $result = $this->client()->PGFetchOrder($apiVersion, $orderId);
        } catch (Throwable) {
            return false;
        }

        $order = $result[0] ?? null;

        if (! $order instanceof OrderEntity) {
            return false;
        }

        if (strtoupper((string) $order->getOrderStatus()) !== 'PAID') {
            return false;
        }

        $gatewayPaymentId = (string) ($order->getCfOrderId() ?: $orderId);

        $this->activateStudentFromPayment($payment, $gatewayPaymentId);

        return true;
    }

    public function activateStudentFromPayment(Payment $payment, string $gatewayPaymentId): void
    {
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        $activatedUser = null;
        $isRenewal = false;

        DB::transaction(function () use ($payment, $gatewayPaymentId, &$activatedUser, &$isRenewal): void {
            $payment->refresh();

            if ($payment->status === PaymentStatus::Paid) {
                return;
            }

            $invoiceNumber = filled($payment->invoice_number)
                ? (string) $payment->invoice_number
                : 'BV/REG-SUB/'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT);

            $payment->update([
                'gateway_payment_id' => $gatewayPaymentId,
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'invoice_number' => $invoiceNumber,
            ]);

            $user = $payment->user()->first();
            $isRenewal = $user->registration_paid_at !== null
                || $user->registration_expires_at !== null;

            $durationMonths = $payment->duration_months ?? 12;
            $planId = $payment->plan_id;

            $studentNumber = $user->student_number;

            if (blank($studentNumber) && filled($user->state_code) && filled($user->special_id) && filled($user->registration_year)) {
                $studentNumber = $this->studentNumberGenerator->generateForUser($user);
            }

            if (filled($studentNumber)) {
                $user->forceFill(['student_number' => $studentNumber])->save();
            }

            $user->forceFill([
                'registration_fee_paid_via' => RegistrationFeePaidVia::Cashfree,
            ])->save();

            $this->registrationAccessService->extendAccess($user, $durationMonths, $planId);

            StudentProfile::query()->firstOrCreate([
                'user_id' => $user->id,
            ]);

            $activatedUser = $user->fresh();
        });

        if ($activatedUser !== null) {
            $payment->refresh();

            $activatedUser->notify((new RegistrationActivated(
                RegistrationFeePaidVia::Cashfree,
                $payment->fresh()?->id,
                $isRenewal,
                $activatedUser->registration_expires_at?->toIso8601String(),
            ))->afterCommit());
        }
    }

    public function client(): Cashfree
    {
        Cashfree::$XClientId = (string) config('cashfree.app_id');
        Cashfree::$XClientSecret = (string) config('cashfree.secret_key');
        Cashfree::$XApiVersion = (string) config('cashfree.api_version', '2023-08-01');
        Cashfree::$XEnableErrorAnalytics = false;
        Cashfree::$XEnvironment = $this->environmentName() === 'production'
            ? Cashfree::$PRODUCTION
            : Cashfree::$SANDBOX;

        return new Cashfree;
    }

    public function environmentName(): string
    {
        $environment = strtolower((string) config('cashfree.environment', 'production'));

        return $environment === 'sandbox' ? 'sandbox' : 'production';
    }
}
