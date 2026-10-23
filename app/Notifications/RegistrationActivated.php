<?php

namespace App\Notifications;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationFeePaidVia;
use App\Models\Payment;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Services\PaymentInvoiceDownloadService;
use App\Services\PaymentSlipPdfService;
use App\Support\PaymentInvoiceSignedUrl;
use App\Support\StudentNotificationChannels;
use App\Support\WhatsAppContentMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class RegistrationActivated extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray {
        toWhatsAppContent as buildUpdateWhatsAppContent;
    }
    use Queueable;

    public function __construct(
        public readonly ?RegistrationFeePaidVia $via = null,
        public readonly ?int $paymentId = null,
        public readonly bool $isRenewal = false,
        public readonly ?string $expiresAt = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return StudentNotificationChannels::for($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiryLabel = $this->expiryLabel();
        $attachedPdf = $this->attachPaymentSlip($message = $this->baseMailMessage($notifiable, $expiryLabel));

        if ($attachedPdf) {
            $message->line('We have attached your payment slip PDF below for your records.');
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $expiryLabel = $this->expiryLabel();

        if ($this->isRenewalNotification()) {
            return $this->notificationPayload(
                'Access extended',
                $expiryLabel
                    ? 'Your renewal is complete. Your account is now active till '.$expiryLabel.'.'
                    : 'Your renewal is complete. Your dashboard access has been extended.',
            );
        }

        return $this->notificationPayload(
            'Dashboard unlocked',
            $expiryLabel
                ? 'Your registration is complete. Your account will be active till '.$expiryLabel.'.'
                : 'Your registration is complete. You can access your full candidate dashboard.',
        );
    }

    public function toWhatsAppContent(object $notifiable): ?WhatsAppContentMessage
    {
        $payment = $this->payment();
        $registrationSid = (string) config('services.whatsapp.twilio.templates.registration', '');

        if (
            $registrationSid !== ''
            && $payment instanceof Payment
            && $payment->status === PaymentStatus::Paid
            && filled($payment->invoice_number)
        ) {
            $stored = app(PaymentInvoiceDownloadService::class)->storePublicPdf($payment);

            if ($stored !== null) {
                $name = filled($notifiable->name ?? null)
                    ? (string) $notifiable->name
                    : 'Candidate';

                $expiryLabel = $this->expiryLabel() ?? 'your current plan period';

                return new WhatsAppContentMessage($registrationSid, [
                    '1' => $name,
                    '2' => PaymentInvoiceSignedUrl::amountLabelInr($payment),
                    '3' => (string) $payment->invoice_number,
                    '4' => $expiryLabel,
                    '5' => url('/dashboard'),
                    '6' => $stored['path'],
                ]);
            }
        }

        return $this->buildUpdateWhatsAppContent($notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationPayload(string $title, string $body): array
    {
        $payload = [
            'title' => $title,
            'body' => $body,
            'url' => url('/dashboard'),
            'via' => $this->paidVia()?->value,
        ];

        $payment = $this->payment();

        if (
            $payment instanceof Payment
            && $payment->status === PaymentStatus::Paid
            && filled($payment->invoice_number)
        ) {
            $payload['invoice_number'] = (string) $payment->invoice_number;
            $payload['amount_label'] = PaymentInvoiceSignedUrl::amountLabel($payment);
            $payload['invoice_url'] = PaymentInvoiceSignedUrl::for($payment);
        }

        return $payload;
    }

    private function baseMailMessage(object $notifiable, ?string $expiryLabel): MailMessage
    {
        $greeting = 'Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!';

        if ($this->isRenewalNotification()) {
            $message = (new MailMessage)
                ->subject('Your Bilingual Vitae access has been extended')
                ->greeting($greeting)
                ->line($this->renewalIntro());

            if (filled($expiryLabel)) {
                $message->line('Your remaining time has been kept, and your account will now be active till '.$expiryLabel.'.');
            }

            return $message
                ->line('You can continue using your dashboard, CV profile, and training materials without interruption.')
                ->action('Open dashboard', url('/dashboard'));
        }

        $message = (new MailMessage)
            ->subject('Your Bilingual Vitae dashboard is unlocked')
            ->greeting($greeting)
            ->line($this->firstPaymentIntro());

        if (filled($expiryLabel)) {
            $message->line('Your account will be active till '.$expiryLabel.'.');
        }

        return $message
            ->line('You can now access your dashboard, CV profile, and training materials.')
            ->action('Open dashboard', url('/dashboard'));
    }

    private function firstPaymentIntro(): string
    {
        return match ($this->paidVia()) {
            RegistrationFeePaidVia::Cashfree,
            RegistrationFeePaidVia::Razorpay => 'Thank you. Your registration payment was received.',
            RegistrationFeePaidVia::ActivationCode => 'Your registration code was redeemed successfully.',
            default => 'Your candidate account is now unlocked.',
        };
    }

    private function renewalIntro(): string
    {
        return match ($this->paidVia()) {
            RegistrationFeePaidVia::Cashfree,
            RegistrationFeePaidVia::Razorpay => 'Thank you. Your renewal payment was received.',
            RegistrationFeePaidVia::ActivationCode => 'Your registration code was redeemed successfully.',
            default => 'Your dashboard access has been extended.',
        };
    }

    private function paidVia(): ?RegistrationFeePaidVia
    {
        return isset($this->via) ? $this->via : null;
    }

    private function isRenewalNotification(): bool
    {
        return isset($this->isRenewal) && $this->isRenewal === true;
    }

    private function expiryLabel(): ?string
    {
        if (! isset($this->expiresAt) || ! filled($this->expiresAt)) {
            return null;
        }

        $timestamp = strtotime($this->expiresAt);

        if ($timestamp === false) {
            return null;
        }

        return date('j F Y', $timestamp);
    }

    private function payment(): ?Payment
    {
        if (! isset($this->paymentId) || $this->paymentId === null) {
            return null;
        }

        return Payment::query()->find($this->paymentId);
    }

    private function attachPaymentSlip(MailMessage $message): bool
    {
        $payment = $this->payment();

        if (
            ! $payment instanceof Payment
            || $payment->status !== PaymentStatus::Paid
            || blank($payment->invoice_number)
        ) {
            return false;
        }

        try {
            $pdf = app(PaymentSlipPdfService::class)->renderPaymentSlip($payment);
            $filename = 'payment-slip-'.str_replace('/', '-', (string) $payment->invoice_number).'.pdf';

            $message->attachData($pdf, $filename, ['mime' => 'application/pdf']);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
