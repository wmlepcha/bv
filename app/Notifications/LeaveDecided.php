<?php

namespace App\Notifications;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveDecided extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
    use Queueable;

    public function __construct(public readonly LeaveRequest $leave) {}

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
            ->subject($this->title())
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
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => url('/student/attendance'),
            'kind' => 'info',
            'leave_request_id' => $this->leave->id,
        ];
    }

    private function title(): string
    {
        return $this->leave->status === LeaveStatus::Approved ? 'Leave approved' : 'Leave not approved';
    }

    private function body(): string
    {
        $range = $this->leave->from_date->format('j M').($this->leave->from_date->equalTo($this->leave->to_date) ? '' : ' – '.$this->leave->to_date->format('j M'));
        $note = filled($this->leave->decision_note) ? ' Note: '.$this->leave->decision_note : '';

        return $this->leave->status === LeaveStatus::Approved
            ? "Your leave for {$range} was approved and is marked on your attendance calendar.{$note}"
            : "Your leave request for {$range} was not approved.{$note}";
    }
}
