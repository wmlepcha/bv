<?php

namespace App\Notifications;

use App\Models\AssignmentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AssignmentSubmission $submission,
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
        $assignment = $this->submission->assignment;
        $student = $this->submission->student;

        return (new MailMessage)
            ->subject('Assignment submitted')
            ->line($student->name.' submitted "'.$assignment->title.'".')
            ->action('Review submission', url('/training-manager/assignments/'.$assignment->id.'/submissions/'.$this->submission->id.'/grade'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $assignment = $this->submission->assignment;

        return [
            'title' => 'Assignment submitted',
            'body' => ($this->submission->student->name ?? 'A student').' submitted "'.$assignment->title.'".',
            'url' => url('/training-manager/assignments/'.$assignment->id.'/submissions/'.$this->submission->id.'/grade'),
            'assignment_id' => $assignment->id,
            'submission_id' => $this->submission->id,
        ];
    }
}
