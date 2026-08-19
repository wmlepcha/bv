<?php

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InquirySubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Inquiry $inquiry,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = is_array($this->inquiry->data) ? $this->inquiry->data : [];
        $name = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        if ($name === '') {
            $name = (string) ($data['contact_name'] ?? $data['name'] ?? 'Unknown');
        }

        $email = (string) ($data['email'] ?? 'n/a');
        $type = ucfirst((string) $this->inquiry->type);

        $message = (new MailMessage)
            ->subject("New {$type} inquiry — Bilingual Vitae")
            ->greeting('New inquiry received')
            ->line("Type: {$type}")
            ->line("Name: {$name}")
            ->line("Email: {$email}");

        if (filled($data['phone'] ?? null) || filled($data['mobile'] ?? null)) {
            $message->line('Phone: '.($data['phone'] ?? $data['mobile']));
        }

        if (filled($data['message'] ?? null)) {
            $message->line('Message: '.$data['message']);
        }

        $adminUrl = url('/admin/inquiries');

        return $message
            ->action('View inquiries', $adminUrl)
            ->line('This alert was sent to the ops inbox.');
    }
}
