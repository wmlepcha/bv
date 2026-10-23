<?php

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentInvoiceDownloadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentSlipController extends Controller
{
    public function download(Payment $payment, Request $request, PaymentInvoiceDownloadService $invoices): Response
    {
        $user = $request->user();

        abort_unless($user?->isAdmin() || $user?->isSalesTeam(), 403);

        return $invoices->response($payment);
    }
}
