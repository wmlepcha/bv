<?php

namespace App\Notifications;

use App\Models\RecruiterProjectCandidate;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\PlacementUrls;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentApproved extends Notification implements ShouldQueue
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
            ->subject('Candidate approved')
            ->line($this->body())
            ->action('View project', $this->url($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Candidate approved',
            'body' => $this->body(),
            'url' => $this->url($notifiable),
            'project_id' => $this->entry->recruiter_project_id,
        ];
    }

    private function body(): string
    {
        return ($this->entry->studentProfile->user->name ?? 'A candidate').' was approved by '.($this->entry->project->owner->name ?? 'the recruiter').' in "'.$this->entry->project->name.'". All their documents are accepted.';
    }

    private function url(object $notifiable): string
    {
        return PlacementUrls::project($notifiable, $this->entry->recruiter_project_id);
    }
}
