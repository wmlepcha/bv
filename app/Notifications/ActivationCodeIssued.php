<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivationCodeIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $code,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Bilingual Vitae registration code')
            ->greeting('Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line('Use this registration code to unlock your candidate dashboard:')
            ->line('**'.$this->code.'**')
            ->action('Redeem code', url('/dashboard'))
            ->line('Sign in, then enter the code on your dashboard when prompted.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Registration code issued',
            'body' => 'Your code is '.$this->code.'. Redeem it on your dashboard to unlock access.',
            'url' => url('/dashboard'),
            'code' => $this->code,
        ];
    }
}
