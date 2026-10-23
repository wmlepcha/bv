<?php

namespace App\Notifications;

use App\Models\Assignment;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentPublished extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
    use Queueable;

    public function __construct(
        public readonly Assignment $assignment,
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
        return (new MailMessage)
            ->subject('New assignment available')
            ->line('A new assignment "'.$this->assignment->title.'" is available for '.$this->assignment->batch->name.'.')
            ->action('Open assignment', url('/student/assignments/'.$this->assignment->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New assignment',
            'body' => '"'.$this->assignment->title.'" is now available.',
            'url' => url('/student/assignments/'.$this->assignment->id),
            'assignment_id' => $this->assignment->id,
        ];
    }
}
