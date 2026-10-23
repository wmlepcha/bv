<?php

namespace App\Notifications\Concerns;

use App\Support\WhatsAppContentMessage;

trait BuildsWhatsAppBodyFromArray
{
    public function toWhatsAppContent(object $notifiable): ?WhatsAppContentMessage
    {
        $contentSid = (string) config('services.whatsapp.twilio.templates.update', '');

        if ($contentSid === '') {
            return null;
        }

        /** @var array{title?: string, body?: string, url?: string|null} $data */
        $data = $this->toArray($notifiable);

        $name = filled($notifiable->name ?? null)
            ? (string) $notifiable->name
            : 'Candidate';

        $details = trim(implode(' ', array_filter([
            $data['title'] ?? null,
            $data['body'] ?? null,
        ], fn (?string $line): bool => filled($line))));

        // WhatsApp template variables cannot contain line breaks.
        $details = trim((string) preg_replace('/\s+/u', ' ', $details));

        if ($details === '') {
            $details = 'You have a new update in your Bilingual Vitae account.';
        }

        $url = isset($data['url']) && filled($data['url'])
            ? (string) $data['url']
            : url('/dashboard');

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = url($url);
        }

        return new WhatsAppContentMessage($contentSid, [
            '1' => $name,
            '2' => $details,
            '3' => $url,
        ]);
    }

    public function toWhatsApp(object $notifiable): string
    {
        $content = $this->toWhatsAppContent($notifiable);

        if ($content === null) {
            return '';
        }

        $lines = array_filter([
            $content->variables['1'] ?? null,
            $content->variables['2'] ?? null,
            $content->variables['3'] ?? null,
        ], fn (?string $line): bool => filled($line));

        return implode("\n\n", $lines);
    }
}
