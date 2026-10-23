<?php

namespace App\Services;

use App\Models\User;
use App\Support\WhatsAppContentMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    public function send(object $notifiable, WhatsAppContentMessage $message): void
    {
        if (! config('services.whatsapp.enabled')) {
            return;
        }

        if (! $notifiable instanceof User) {
            return;
        }

        if ($message->contentSid === '') {
            return;
        }

        $phone = $notifiable->whatsAppNumber();

        if (! filled($phone)) {
            return;
        }

        $provider = (string) config('services.whatsapp.provider', 'twilio');

        if ($provider === 'twilio') {
            $this->sendViaTwilio((string) $phone, $message, $notifiable->id);

            return;
        }

        Log::warning('WhatsApp notifications are enabled but provider is unsupported.', [
            'provider' => $provider,
            'user_id' => $notifiable->id,
        ]);
    }

    private function sendViaTwilio(string $phone, WhatsAppContentMessage $message, int $userId): void
    {
        $sid = (string) config('services.whatsapp.twilio.sid');
        $token = (string) config('services.whatsapp.twilio.token');
        $from = (string) config('services.whatsapp.twilio.from');

        if ($sid === '' || $token === '' || $from === '') {
            Log::warning('WhatsApp via Twilio is enabled but credentials are missing.');

            return;
        }

        $normalized = $this->normalizeToE164($phone);

        if ($normalized === null) {
            Log::warning('WhatsApp notification skipped because phone number is invalid.', [
                'user_id' => $userId,
                'phone' => $phone,
            ]);

            return;
        }

        $to = 'whatsapp:'.$normalized;

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'ContentSid' => $message->contentSid,
                'ContentVariables' => json_encode($message->variables, JSON_UNESCAPED_SLASHES),
            ]);

        if ($response->failed()) {
            Log::error('Failed to send WhatsApp notification.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'user_id' => $userId,
                'content_sid' => $message->contentSid,
            ]);

            return;
        }

        // Twilio accepting the request is not delivery; check the message SID in the Twilio console if it never arrives.
        Log::info('WhatsApp notification accepted by Twilio.', [
            'user_id' => $userId,
            'to' => $normalized,
            'message_sid' => $response->json('sid'),
            'status' => $response->json('status'),
            'content_sid' => $message->contentSid,
        ]);
    }

    public function normalizeToE164(string $phone): ?string
    {
        if (str_starts_with($phone, 'whatsapp:')) {
            $phone = substr($phone, strlen('whatsapp:'));
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^[6-9]\d{9}$/', $digits)) {
            return null;
        }

        return '+91'.$digits;
    }
}
