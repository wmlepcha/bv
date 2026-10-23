<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentRegistered extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return StudentNotificationChannels::for($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to Bilingual Vitae')
            ->greeting('Welcome'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line('Your candidate account has been created successfully.')
            ->line('To unlock your dashboard, pay the registration fee or redeem a registration code.')
            ->action('Go to dashboard', url('/dashboard'))
            ->line('If you did not create this account, please ignore this email.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Welcome to Bilingual Vitae',
            'body' => 'Your account is ready. Pay the registration fee or redeem a code to unlock your dashboard.',
            'url' => url('/dashboard'),
        ];
    }
}
