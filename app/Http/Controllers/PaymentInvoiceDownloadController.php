<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentInvoiceDownloadService;
use Illuminate\Http\Response;

class PaymentInvoiceDownloadController extends Controller
{
    public function __invoke(Payment $payment, PaymentInvoiceDownloadService $invoices): Response
    {
        return $invoices->response($payment);
    }
}
