<?php

namespace App\Notifications;

use App\Models\Batch;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrolledInBatch extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
    use Queueable;

    public function __construct(
        public readonly Batch $batch,
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
        $details = trim(implode(' · ', array_filter([
            $this->batch->name,
            $this->batch->language?->label(),
            $this->batch->level?->label(),
        ])));

        return (new MailMessage)
            ->subject('You have been enrolled in a batch')
            ->greeting('Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line('You have been enrolled in: '.$details.'.')
            ->action('View study bank', url('/student/study-bank'))
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
            'url' => url('/student/study-bank'),
            'batch_id' => $this->batch->id,
            'batch_name' => $this->batch->name,
        ];
    }
}
