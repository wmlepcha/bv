<?php

namespace App\Notifications;

use App\Models\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrolledInBatch extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Batch $batch,
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
        $details = trim(implode(' · ', array_filter([
            $this->batch->name,
            $this->batch->language,
            $this->batch->level,
        ])));

        return (new MailMessage)
            ->subject('You have been enrolled in a batch')
            ->greeting('Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line('You have been enrolled in: '.$details.'.')
            ->action('View classes', url('/student/classes'))
            ->line('Training materials shared with this batch will appear in your account.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Enrolled in batch',
            'body' => 'You were enrolled in '.$this->batch->name.'.',
            'url' => url('/student/classes'),
            'batch_id' => $this->batch->id,
            'batch_name' => $this->batch->name,
        ];
    }
}
