<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentInvoiceDownloadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentInvoiceController extends Controller
{
    public function __invoke(Payment $payment, Request $request, PaymentInvoiceDownloadService $invoices): Response
    {
        $user = $request->user();

        abort_unless($user?->isStudent(), 403);
        abort_unless($payment->user_id === $user->id, 403);

        return $invoices->response($payment);
    }
}
