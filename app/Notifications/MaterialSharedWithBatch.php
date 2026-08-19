<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaterialSharedWithBatch extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $materialTitle,
        public readonly string $batchName,
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
            ->subject('New training material available')
            ->greeting('Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line('New '.$this->materialType.' “'.$this->materialTitle.'” was shared with your batch ('.$this->batchName.').')
            ->action('Open materials', url('/student/pdfs'))
            ->line('Sign in to view or download it in the app.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New material available',
            'body' => '“'.$this->materialTitle.'” was shared with '.$this->batchName.'.',
            'url' => url('/student/pdfs'),
            'material_title' => $this->materialTitle,
            'batch_name' => $this->batchName,
        ];
    }
}
