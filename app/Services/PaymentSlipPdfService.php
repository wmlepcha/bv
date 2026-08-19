<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\OfficeLocation;
use App\Models\SiteSetting;
use App\Models\StudentProfile;
use App\Support\Brand;
use App\Support\RegistrationTerms;
use Dompdf\Dompdf;

class PaymentSlipPdfService
{
    public function renderPaymentSlip(Payment $payment): string
    {
        $payment->loadMissing(['user']);

        $user = $payment->user;
        $studentProfile = StudentProfile::query()
            ->where('user_id', $user?->id ?? -1)
            ->first();
        $office = OfficeLocation::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->first();

        $durationMonths = (int) ($payment->duration_months ?? 12);
        $totalRupees = ((float) $payment->amount) / 100;
        $baseRupees = round($totalRupees / 1.18, 2);
        $gstRupees = round($totalRupees - $baseRupees, 2);

        $paidAt = $payment->paid_at;

        $paymentMode = $this->normalizePaymentMode((string) ($payment->payment_mode ?? 'Other'));

        $configuredLogoPath = SiteSetting::getValue('payment_slip.logo_path');
        $logoSrc = $this->resolveLocalAbsolutePath($configuredLogoPath);

        if (! filled($logoSrc)) {
            $logoSrc = $this->resolveLocalAbsolutePath(Brand::marketingLogoUrl());
        }

        $signatureSrc = $this->resolveLocalAbsolutePath(
            SiteSetting::getValue('payment_slip.signature_image_path'),
            preserveTransparency: true,
        );

        $stampSrc = $this->resolveLocalAbsolutePath(
            SiteSetting::getValue('payment_slip.stamp_image_path'),
            preserveTransparency: true,
        );

        $digitalFooterHtml = SiteSetting::getValue(
            'payment_slip.digital_footer_html',
            '<p>This is a digital generated bill. No signature required.</p>',
        );

        $authorizedBy = SiteSetting::getValue(
            'payment_slip.authorized_by',
            'AIS - Bilingual Vitae Pvt. Ltd.',
        );
        $companyEmail = SiteSetting::getValue(
            'payment_slip.company_email',
            SiteSetting::getValue('footer.contact_email', 'hello@bilingualvitae.com'),
        );
        $companyPhone = SiteSetting::getValue(
            'payment_slip.company_phone',
            SiteSetting::getValue('footer.contact_phone', '+91 8250312500'),
        );
        $companyAddress = SiteSetting::getValue('payment_slip.company_address')
            ?: ($office?->address ?? null);
        $companyGstin = SiteSetting::getValue('payment_slip.gstin');

        $termsHtml = RegistrationTerms::html();
        $invoiceNumber = filled($payment->invoice_number)
            ? (string) $payment->invoice_number
            : 'BV/REG-SUB/'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT);

        $html = view('payment-slips.registration', [
            'payment' => $payment,
            'invoiceNumber' => $invoiceNumber,
            'paidAt' => $paidAt,
            'paidDateLabel' => $paidAt?->format('d F Y'),
            'durationMonths' => $durationMonths,
            'paymentAmountTotalRupees' => $totalRupees,
            'paymentAmountBaseRupees' => $baseRupees,
            'paymentAmountGstRupees' => $gstRupees,
            'customerName' => $user?->name,
            'customerEmail' => $user?->email,
            'candidateStudentNumber' => $user?->student_number,
            'customerMobile' => $studentProfile?->mobile_whatsapp,
            'customerAddress' => $studentProfile?->current_address,
            'customerPaidAt' => $paidAt,
            'customerPaymentMode' => $paymentMode,
            'paymentMode' => $paymentMode,
            'gatewayPaymentId' => $payment->gateway_payment_id,
            'gatewayOrderId' => $payment->gateway_order_id,
            'termsHtml' => $termsHtml,
            'authorizedBy' => $authorizedBy,
            'digitalFooterHtml' => $digitalFooterHtml,
            'companyName' => Brand::name(),
            'companyAddress' => $companyAddress,
            'companyEmail' => $companyEmail,
            'companyPhone' => $companyPhone,
            'companyGstin' => $companyGstin,
            'logoAbsolutePath' => $logoSrc,
            'signatureAbsolutePath' => $signatureSrc,
            'stampAbsolutePath' => $stampSrc,
        ])->render();

        $dompdf = new Dompdf([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => true,
            'chroot' => [
                public_path(),
                storage_path('app/public'),
            ],
        ]);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }

    private function normalizePaymentMode(string $paymentMode): string
    {
        $value = strtolower(trim($paymentMode));

        return match (true) {
            str_contains($value, 'upi') => 'UPI',
            str_contains($value, 'card') => 'Card',
            str_contains($value, 'bank') => 'Bank Transfer',
            str_contains($value, 'transfer') => 'Bank Transfer',
            default => 'Other',
        };
    }

    private function resolveLocalAbsolutePath(?string $path, bool $preserveTransparency = false): ?string
    {
        $absolutePath = $this->findLocalImagePath($path);

        if ($absolutePath === null) {
            return null;
        }

        return $this->preparePdfImageDataUri($absolutePath, preserveTransparency: $preserveTransparency);
    }

    private function preparePdfImageDataUri(
        string $absolutePath,
        int $maxWidth = 900,
        int $maxHeight = 180,
        bool $preserveTransparency = false,
    ): ?string {
        $previousMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '256M');

        try {
            $contents = file_get_contents($absolutePath);

            if ($contents === false) {
                return null;
            }

            $source = @imagecreatefromstring($contents);

            if ($source === false) {
                return null;
            }

            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min($maxWidth / max($width, 1), $maxHeight / max($height, 1), 1.0);
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($preserveTransparency) {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
                imagealphablending($canvas, true);
            } else {
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);
            }

            imagecopyresampled(
                $canvas,
                $source,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $width,
                $height,
            );
            imagedestroy($source);

            ob_start();
            if ($preserveTransparency) {
                imagepng($canvas, null, 6);
                $encoded = ob_get_clean();
                $mime = 'image/png';
            } else {
                imagejpeg($canvas, null, 85);
                $encoded = ob_get_clean();
                $mime = 'image/jpeg';
            }
            imagedestroy($canvas);

            if ($encoded === false || $encoded === '') {
                return null;
            }

            return 'data:'.$mime.';base64,'.base64_encode($encoded);
        } finally {
            ini_set('memory_limit', $previousMemoryLimit);
        }
    }

    private function findLocalImagePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parsedPath = parse_url($path, PHP_URL_PATH);
            $path = is_string($parsedPath) ? $parsedPath : $path;
        }

        $relative = $path;

        foreach (['/storage/', 'storage/'] as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                $relative = substr($relative, strlen($prefix));
                break;
            }
        }

        $candidates = [
            storage_path('app/public/'.$relative),
            public_path('storage/'.$relative),
            public_path(ltrim($path, '/')),
        ];

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);

            if ($realPath !== false && is_file($realPath)) {
                return $realPath;
            }
        }

        return null;
    }
}

