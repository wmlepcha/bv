<?php

namespace App\Notifications;

use App\Models\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferredToBatch extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Batch $fromBatch,
        public readonly Batch $toBatch,
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
            ->subject('You were transferred to a new batch')
            ->greeting('Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line('You were transferred from '.$this->fromBatch->name.' to '.$this->toBatch->name.'.')
            ->action('View classes', url('/student/classes'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Transferred to a new batch',
            'body' => 'Moved from '.$this->fromBatch->name.' to '.$this->toBatch->name.'.',
            'url' => url('/student/classes'),
            'from_batch_id' => $this->fromBatch->id,
            'to_batch_id' => $this->toBatch->id,
        ];
    }
}
