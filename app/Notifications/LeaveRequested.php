<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LeaveRequest $leave) {}

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
            ->subject('New leave request')
            ->line($this->body())
            ->action('Review request', url('/teacher/attendance'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New leave request',
            'body' => $this->body(),
            'url' => url('/teacher/attendance'),
            'leave_request_id' => $this->leave->id,
        ];
    }

    private function body(): string
    {
        return ($this->leave->student->name ?? 'A student').' asked for leave from '.$this->leave->from_date->format('j M').' to '.$this->leave->to_date->format('j M').'.';
    }
}
