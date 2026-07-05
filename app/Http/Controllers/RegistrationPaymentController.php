<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrationPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentVerificationService $paymentVerificationService,
    ) {}

    public function createOrder(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user?->isStudent() && ! $user->hasPaidRegistration(), 403);

        $amount = (int) config('razorpay.registration_fee_amount');
        $currency = config('razorpay.currency');

        $order = $this->paymentVerificationService->api()->order->create([
            'receipt' => 'registration_'.$user->id.'_'.Str::lower(Str::random(8)),
            'amount' => $amount,
            'currency' => $currency,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'razorpay_order_id' => $order['id'],
            'amount' => $amount,
            'currency' => $currency,
            'status' => PaymentStatus::Pending,
        ]);

        return response()->json([
            'order_id' => $order['id'],
            'amount' => $amount,
            'currency' => $currency,
            'key' => config('razorpay.key'),
            'name' => config('app.name'),
            'description' => 'Registration fee',
            'prefill' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = Payment::query()
            ->where('razorpay_order_id', $validated['razorpay_order_id'])
            ->where('user_id', $request->user()?->id)
            ->firstOrFail();

        abort_unless(
            $this->paymentVerificationService->verifyCheckoutSignature(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $validated['razorpay_signature'],
            ),
            422,
            'Payment verification failed.',
        );

        $this->paymentVerificationService->activateStudentFromPayment(
            $payment,
            $validated['razorpay_payment_id'],
        );

        return redirect()->route('dashboard');
    }

    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('X-Razorpay-Signature', '');

        abort_unless(
            $this->paymentVerificationService->verifyWebhookSignature(
                $request->getContent(),
                $signature,
            ),
            400,
            'Invalid webhook signature.',
        );

        $payload = $request->json()->all();
        $event = $payload['event'] ?? null;

        if ($event === 'payment.captured') {
            $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
            $orderId = $paymentEntity['order_id'] ?? null;
            $paymentId = $paymentEntity['id'] ?? null;

            if ($orderId && $paymentId) {
                $payment = Payment::query()
                    ->where('razorpay_order_id', $orderId)
                    ->first();

                if ($payment) {
                    $this->paymentVerificationService->activateStudentFromPayment($payment, $paymentId);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
