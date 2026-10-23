<?php

namespace App\Notifications;

use App\Models\RecruiterProjectCandidate;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentShortlisted extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
    use Queueable;

    public function __construct(
        public readonly RecruiterProjectCandidate $entry,
        public readonly string $message,
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
            ->subject("Congratulations! You've been shortlisted")
            ->greeting('Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line($this->message)
            ->action('Open your dashboard', url('/dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Congratulations! You've been shortlisted",
            'body' => $this->message,
            'url' => url('/dashboard'),
            'kind' => 'celebration',
            'project_id' => $this->entry->recruiter_project_id,
        ];
    }
}
