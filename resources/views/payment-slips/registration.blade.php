<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Payment Slip</title>
        <style>
            .terms-block p,
            .terms-block ul,
            .terms-block ol,
            .terms-block li,
            .terms-block h1,
            .terms-block h2,
            .terms-block h3,
            .terms-block h4,
            .terms-block h5,
            .terms-block h6 {
                margin: 0 0 3px 0;
                padding: 0;
            }
        </style>
    </head>
    <body style="margin: 0; padding: 10px; font-family: DejaVu Sans, sans-serif; color: #1f2937; background: #ffffff; font-size: 10px; line-height: 1.32;">
        @php
            $paidDateLabel = $paidAt?->format('d F Y') ?? '';
            $totalRupees = $paymentAmountTotalRupees ?? 0;
            $baseRupees = $paymentAmountBaseRupees ?? 0;
            $gstRupees = $paymentAmountGstRupees ?? 0;
            $totalRupeesInt = (int) round($totalRupees);
            $monthsInWords = \App\Support\PaymentSlipText::monthsInWords((int) ($durationMonths ?? 0));
            $amountInWords = \App\Support\PaymentSlipText::rupeesInWords($totalRupeesInt);
            $logoSrc = $logoAbsolutePath ?? null;
            $signatureSrc = $signatureAbsolutePath ?? null;
            $stampSrc = $stampAbsolutePath ?? null;
            $candidateName = $customerName ?? '—';
            $mobileNumber = $customerMobile ?? '—';
            $emailId = $customerEmail ?? '—';
            $candidateId = $candidateStudentNumber ?? '—';
            $candidateAddress = $customerAddress ?? '—';
            $paymentMode = $paymentMode ?? 'Other';
            $isUpi = $paymentMode === 'UPI';
            $isCard = $paymentMode === 'Card';
            $isBank = $paymentMode === 'Bank Transfer';
            $isOther = $paymentMode === 'Other';
            $mark = fn (bool $checked): string => $checked ? '■' : '□';
            $companyName = $companyName ?? 'AIS - Bilingual Vitae Pvt. Ltd.';
            $companyAddress = $companyAddress ?? '—';
            $companyPhone = $companyPhone ?? '—';
            $companyEmail = $companyEmail ?? '—';
            $companyGstin = $companyGstin ?? null;
        @endphp

        <div style="border: 1px solid #d8e4f0; padding: 9px;">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; width: 52%; vertical-align: top;">
                    @if (filled($logoSrc))
                        <img src="{{ $logoSrc }}" style="max-height: 36px; width: auto;" alt="">
                    @else
                        <div style="font-size: 26px; font-weight: 700; color: #fc5c2e; letter-spacing: 1px;">BV</div>
                    @endif
                    <div style="margin-top: 5px; line-height: 1.35;">
                        <div style="font-weight: 700;">From:</div>
                        <div style="font-weight: 700;">{{ $companyName }}</div>
                        <div>{{ $companyAddress }}</div>
                        <div>{{ $companyPhone }}</div>
                        <div>{{ $companyEmail }}</div>
                        @if (filled($companyGstin))
                            <div>GSTIN: {{ $companyGstin }}</div>
                        @endif
                    </div>
                </div>
                <div style="display: table-cell; width: 48%; vertical-align: top; text-align: right;">
                    <div style="font-size: 24px; font-weight: 800; color: #2a80cc; letter-spacing: 0.8px;">PAYMENT SLIP</div>
                    <div style="margin-top: 4px; line-height: 1.45;">
                        <div><strong>Receipt No.:</strong> {{ $invoiceNumber ?? 'BV/REG-SUB/-----' }}</div>
                        <div><strong>Date:</strong> {{ $paidDateLabel }}</div>
                    </div>
                    <div style="margin-top: 6px; text-align: left; padding-left: 30px; line-height: 1.35;">
                        <div style="font-weight: 700;">To:</div>
                        <div style="font-weight: 700;">{{ $candidateName }}</div>
                        <div>{{ $candidateAddress }}</div>
                        <div>{{ $mobileNumber }}</div>
                        <div>{{ $emailId }}</div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 8px; background: #2a80cc; color: #fff; padding: 5px 8px; font-size: 10.5px; font-weight: 700;">
                PAYMENT DETAILS
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 9.8px;">
                <thead>
                    <tr style="background: #f2f8fd;">
                        <th style="border: 1px solid #c5d8e8; text-align: left; padding: 5px;">Particulars</th>
                        <th style="border: 1px solid #c5d8e8; text-align: center; padding: 5px; width: 24%;">Validity</th>
                        <th style="border: 1px solid #c5d8e8; text-align: right; padding: 5px; width: 22%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border: 1px solid #d5e1ec; padding: 6px; line-height: 1.25;">Registration &amp; Subscription Fee for Digital account/Subscription</td>
                        <td style="border: 1px solid #d5e1ec; padding: 6px; text-align: center;">{{ $monthsInWords }}</td>
                        <td style="border: 1px solid #d5e1ec; padding: 6px; text-align: right;">₹{{ number_format($baseRupees, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #d5e1ec; padding: 6px;">GST @ 18%</td>
                        <td style="border: 1px solid #d5e1ec; padding: 6px;"></td>
                        <td style="border: 1px solid #d5e1ec; padding: 6px; text-align: right;">₹{{ number_format($gstRupees, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #d5e1ec; padding: 6px; font-weight: 700;">TOTAL AMOUNT PAID</td>
                        <td style="border: 1px solid #d5e1ec; padding: 6px;"></td>
                        <td style="border: 1px solid #d5e1ec; padding: 6px; text-align: right; font-weight: 700;">₹{{ number_format($totalRupees, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 6px;">
                <strong>Amount in Words:</strong> Rupees {{ $amountInWords }}
            </div>

            <div class="terms-block" style="margin-top: 7px; border: 1px solid #d5e1ec; padding: 6px; font-size: 8.8px; line-height: 1.22;">
                <div style="font-weight: 700; color: #2a80cc; margin-bottom: 3px; font-size: 9.6px;">Terms and Policy</div>
                <div style="margin: 0; padding: 0;">
                    {!! $termsHtml !!}
                </div>
            </div>

            <div style="margin-top: 7px; display: table; width: 100%;">
                <div style="display: table-cell; width: 62%; vertical-align: top; padding-right: 10px;">
                    <div style="font-weight: 700; margin-bottom: 3px; color: #2a80cc;">Payment & Candidate Details</div>
                    <table style="width: 100%; border-collapse: collapse; line-height: 1.24; font-size: 9.3px;">
                        <tr><td style="width: 42%; font-weight: 700; padding: 1px 0;">Candidate ID:</td><td>{{ $candidateId }}</td></tr>
                        <tr><td style="font-weight: 700; padding: 1px 0;">Date of Payment:</td><td>{{ $paidDateLabel }}</td></tr>
                        <tr><td style="font-weight: 700; padding: 1px 0;">Payment Mode:</td><td>{{ $mark($isUpi) }} UPI  {{ $mark($isCard) }} Card  {{ $mark($isBank) }} Bank Transfer  {{ $mark($isOther) }} Other</td></tr>
                        @if ($isOffline ?? false)
                            <tr><td style="font-weight: 700; padding: 1px 0;">Received As:</td><td>{{ $offlineMethodLabel ?? 'Offline' }} (offline payment)</td></tr>
                            <tr><td style="font-weight: 700; padding: 1px 0;">Receipt / Reference No.:</td><td>{{ filled($offlineReference ?? null) ? $offlineReference : '—' }}</td></tr>
                        @else
                            <tr><td style="font-weight: 700; padding: 1px 0;">Transaction / Reference ID:</td><td>{{ $gatewayPaymentId ?? '—' }}</td></tr>
                            <tr><td style="font-weight: 700; padding: 1px 0;">Gateway Order ID:</td><td>{{ $gatewayOrderId ?? '—' }}</td></tr>
                        @endif
                    </table>
                </div>

                <div style="display: table-cell; width: 38%; text-align: right; vertical-align: top;">
                    <div style="font-size: 9.8px; font-weight: 700;">Authorized By</div>
                    <div style="font-size: 9.8px;">{{ $authorizedBy ?? 'AIS - Bilingual Vitae Pvt. Ltd.' }}</div>
                    @if (filled($stampSrc) || filled($signatureSrc))
                        <div style="position: relative; width: 120px; height: 84px; margin-top: 4px; margin-left: auto;">
                            @if (filled($signatureSrc))
                                <img src="{{ $signatureSrc }}" style="position: absolute; right: 10px; top: 18px; height: 52px; width: auto;" alt="">
                            @endif
                            @if (filled($stampSrc))
                                <img src="{{ $stampSrc }}" style="position: absolute; right: 0; top: 0; height: 84px; width: auto;" alt="">
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div style="margin-top: 6px; border-top: 1px solid #d5e1ec; padding-top: 4px; font-size: 8.7px; color: #5b6470;">
                {!! $digitalFooterHtml !!}
            </div>
        </div>
    </body>
</html>

