<?php

namespace App\Http\Controllers\Filament;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentSlipPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentSlipController extends Controller
{
    public function download(Payment $payment, Request $request): Response
    {
        $user = $request->user();

        abort_unless($user?->isAdmin(), 403);

        $payment->loadMissing(['user']);

        abort_unless($payment->status === PaymentStatus::Paid, 404);

        $pdf = app(PaymentSlipPdfService::class)->renderPaymentSlip($payment);

        $invoiceNumber = filled($payment->invoice_number)
            ? (string) $payment->invoice_number
            : 'BV-REG-SUB-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT);
        $filename = 'payment-slip-'.$invoiceNumber.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}

