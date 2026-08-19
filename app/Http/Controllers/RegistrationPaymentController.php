<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Services\PaymentVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Illuminate\Support\Str;

class RegistrationPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentVerificationService $paymentVerificationService,
    ) {}

    public function createOrder(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user?->isStudent(), 403);

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $plan = Plan::query()
            ->active()
            ->whereKey($validated['plan_id'])
            ->first();

        if ($plan === null) {
            return response()->json([
                'message' => 'The selected plan is not available.',
                'errors' => [
                    'plan_id' => ['The selected plan is not available.'],
                ],
            ], 422);
        }

        $phone = $this->resolvePhone($user->id, $validated['phone'] ?? null);

        if ($phone === null) {
            return response()->json([
                'message' => 'A valid 10-digit mobile number is required to pay.',
                'errors' => [
                    'phone' => ['A valid 10-digit mobile number is required to pay.'],
                ],
            ], 422);
        }

        StudentProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['mobile_whatsapp' => $phone],
        );

        try {
            $order = $this->paymentVerificationService->createRegistrationOrder($user, $phone, $plan);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to start payment. Please try again.',
            ], 502);
        }

        return response()->json([
            ...$order,
            'name' => config('app.name'),
            'description' => $plan->name,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        return $this->verifyAndActivate($request);
    }

    public function callback(Request $request): RedirectResponse
    {
        return $this->verifyAndActivate($request);
    }

    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('x-webhook-signature', '');
        $timestamp = $request->header('x-webhook-timestamp', '');

        abort_unless(
            $this->paymentVerificationService->verifyWebhookSignature(
                $request->getContent(),
                $signature,
                $timestamp,
            ),
            400,
            'Invalid webhook signature.',
        );

        $payload = $request->json()->all();
        $type = strtoupper((string) ($payload['type'] ?? ''));

        if (in_array($type, ['PAYMENT_SUCCESS', 'PAYMENT_SUCCESS_WEBHOOK'], true)) {
            $orderId = $payload['data']['order']['order_id'] ?? null;
            $paymentId = $payload['data']['payment']['cf_payment_id'] ?? null;

            if (filled($orderId)) {
                $payment = Payment::query()
                    ->where('gateway_order_id', $orderId)
                    ->first();

                if ($payment) {
                    $paymentDetails = $payload['data']['payment'] ?? [];
                    $rawMethod = (string) (
                        $paymentDetails['payment_method']
                        ?? $paymentDetails['payment_mode']
                        ?? $paymentDetails['paymentMethod']
                        ?? $paymentDetails['instrument_type']
                        ?? ''
                    );

                    $rawMethodLower = strtolower(trim($rawMethod));
                    $paymentMode = 'Other';

                    if (Str::contains($rawMethodLower, 'upi')) {
                        $paymentMode = 'UPI';
                    } elseif (Str::contains($rawMethodLower, 'card')) {
                        $paymentMode = 'Card';
                    } elseif (
                        Str::contains($rawMethodLower, 'bank')
                        || Str::contains($rawMethodLower, 'neft')
                        || Str::contains($rawMethodLower, 'imps')
                        || Str::contains($rawMethodLower, 'transfer')
                        || Str::contains($rawMethodLower, 'netbank')
                    ) {
                        $paymentMode = 'Bank Transfer';
                    }

                    $paymentModeDetail = (string) (
                        $paymentDetails['utr']
                        ?? $paymentDetails['reference_id']
                        ?? $paymentDetails['txn_id']
                        ?? ''
                    );

                    $payment->update([
                        'payment_mode' => $paymentMode,
                        'payment_mode_detail' => filled($paymentModeDetail) ? $paymentModeDetail : null,
                    ]);

                    $this->paymentVerificationService->activateStudentFromPayment(
                        $payment,
                        filled($paymentId) ? (string) $paymentId : (string) $orderId,
                    );
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function verifyAndActivate(Request $request): RedirectResponse
    {
        $orderId = $request->input('order_id')
            ?? $request->query('order_id');

        if (blank($orderId) || ! is_string($orderId)) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Payment verification failed.');
        }

        if (! $this->paymentVerificationService->fetchOrderAndActivate($orderId)) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Payment verification failed.');
        }

        return redirect()->route('dashboard');
    }

    private function resolvePhone(int $userId, ?string $inputPhone): ?string
    {
        $normalizedInput = $this->normalizeIndianMobile($inputPhone);

        if ($normalizedInput !== null) {
            return $normalizedInput;
        }

        $profilePhone = StudentProfile::query()
            ->where('user_id', $userId)
            ->value('mobile_whatsapp');

        return $this->normalizeIndianMobile(is_string($profilePhone) ? $profilePhone : null);
    }

    private function normalizeIndianMobile(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^[6-9]\d{9}$/', $digits)) {
            return null;
        }

        return $digits;
    }
}
