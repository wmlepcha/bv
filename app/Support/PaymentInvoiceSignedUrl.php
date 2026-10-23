<?php

namespace App\Support;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use DateTimeInterface;
use Illuminate\Support\Facades\URL;

class PaymentInvoiceSignedUrl
{
    public static function for(Payment $payment, ?DateTimeInterface $expiresAt = null): ?string
    {
        if ($payment->status !== PaymentStatus::Paid) {
            return null;
        }

        $ttlHours = (int) config('services.payment_invoice.signed_url_ttl_hours', 72);
        $expiresAt ??= now()->addHours($ttlHours);

        return URL::temporarySignedRoute(
            'invoices.download',
            $expiresAt,
            ['payment' => $payment->id],
        );
    }

    public static function amountLabel(Payment $payment): string
    {
        return '₹'.number_format(((float) $payment->amount) / 100, 2);
    }

    public static function amountLabelInr(Payment $payment): string
    {
        return 'INR '.number_format(((float) $payment->amount) / 100, 2, '.', '');
    }
}
