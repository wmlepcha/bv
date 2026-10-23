<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowAttendanceWarning extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
    use Queueable;

    public function __construct(
        public readonly int $percentage,
        public readonly int $threshold,
    ) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return StudentNotificationChannels::for($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your attendance is low')
            ->greeting('Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line($this->body())
            ->action('View attendance', url('/student/attendance'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Your attendance is low',
            'body' => $this->body(),
            'url' => url('/student/attendance'),
            'kind' => 'info',
            'percentage' => $this->percentage,
        ];
    }

    private function body(): string
    {
        return "Your attendance is {$this->percentage}%, below the {$this->threshold}% we expect. Please attend upcoming classes, or speak to your trainer if you have a valid reason for missing them.";
    }
}
