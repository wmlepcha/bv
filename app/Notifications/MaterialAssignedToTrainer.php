<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaterialAssignedToTrainer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $materialTitle,
        public readonly string $materialType = 'material',
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
            ->subject('New material assigned to you')
            ->greeting('Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line('A new '.$this->materialType.' “'.$this->materialTitle.'” has been assigned to you.')
            ->action('Open materials', url('/teacher/materials'))
            ->line('You can review it in your trainer materials library.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New material assigned',
            'body' => '“'.$this->materialTitle.'” was assigned to you.',
            'url' => url('/teacher/materials'),
            'material_title' => $this->materialTitle,
        ];
    }
}
