<?php

namespace App\Notifications;

use App\Models\AssignmentSubmission;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentGraded extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
    use Queueable;

    public function __construct(
        public readonly AssignmentSubmission $submission,
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
        $assignment = $this->submission->assignment;

        return (new MailMessage)
            ->subject('Assignment graded')
            ->line('Your submission for "'.$assignment->title.'" has been graded.')
            ->line('Score: '.($this->submission->final_score ?? 0).' / '.$assignment->max_score)
            ->action('View results', url('/student/assignments/'.$assignment->id.'/submission'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $assignment = $this->submission->assignment;

        return [
            'title' => 'Assignment graded',
            'body' => '"'.$assignment->title.'" was graded: '.($this->submission->final_score ?? 0).' / '.$assignment->max_score,
            'url' => url('/student/assignments/'.$assignment->id.'/submission'),
            'assignment_id' => $assignment->id,
            'submission_id' => $this->submission->id,
        ];
    }
}
