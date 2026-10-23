<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PaymentInvoiceDownloadService
{
    public function __construct(
        private readonly PaymentSlipPdfService $pdfService,
    ) {}

    public function assertDownloadable(Payment $payment): void
    {
        abort_unless($payment->status === PaymentStatus::Paid, 404);
    }

    public function filename(Payment $payment): string
    {
        $invoiceNumber = filled($payment->invoice_number)
            ? (string) $payment->invoice_number
            : 'BV-REG-SUB-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT);

        return 'payment-slip-'.str_replace('/', '-', $invoiceNumber).'.pdf';
    }

    public function response(Payment $payment): Response
    {
        $this->assertDownloadable($payment);

        $payment->loadMissing(['user']);

        $pdf = $this->pdfService->renderPaymentSlip($payment);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($payment).'"',
        ]);
    }

    /**
     * Persist the invoice PDF on the public disk for WhatsApp media fetch.
     *
     * @return array{path: string, url: string}|null
     */
    public function storePublicPdf(Payment $payment): ?array
    {
        if (
            $payment->status !== PaymentStatus::Paid
            || blank($payment->invoice_number)
        ) {
            return null;
        }

        try {
            $payment->loadMissing(['user']);

            $pdf = $this->pdfService->renderPaymentSlip($payment);
            $relativePath = 'whatsapp-invoices/'.$payment->id.'/payment-slip.pdf';

            Storage::disk('public')->put($relativePath, $pdf);

            return [
                'path' => 'storage/'.$relativePath,
                'url' => Storage::disk('public')->url($relativePath),
            ];
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
