<?php

namespace App\Notifications;

use App\Models\Batch;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferredToBatch extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
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
        return StudentNotificationChannels::for($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You were transferred to a new batch')
            ->greeting('Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line('You were transferred from '.$this->fromBatch->name.' to '.$this->toBatch->name.'.')
            ->action('View study bank', url('/student/study-bank'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Transferred to a new batch',
            'body' => 'Moved from '.$this->fromBatch->name.' to '.$this->toBatch->name.'.',
            'url' => url('/student/study-bank'),
            'from_batch_id' => $this->fromBatch->id,
            'to_batch_id' => $this->toBatch->id,
        ];
    }
}
