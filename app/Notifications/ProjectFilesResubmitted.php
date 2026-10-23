<?php

namespace App\Notifications;

use App\Models\RecruiterProjectCandidate;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectFilesResubmitted extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
    use Queueable;

    public function __construct(public readonly RecruiterProjectCandidate $entry) {}

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
            ->line($this->body())
            ->action('Review files', $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'project_id' => $this->entry->recruiter_project_id,
        ];
    }

    private function title(): string
    {
        return 'Updated files ready to review';
    }

    private function body(): string
    {
        return 'BV updated the documents of '.($this->entry->studentProfile->user->name ?? 'a candidate').' in "'.$this->entry->project->name.'" after your feedback. Please review them again.';
    }

    private function url(): string
    {
        return url('/recruiter/projects/'.$this->entry->recruiter_project_id);
    }
}
