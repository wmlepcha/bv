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

class ProjectFilesRejected extends Notification implements ShouldQueue
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
            ->line($this->summary().' Open the project to read the notes.')
            ->action('View project', $this->url($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url($notifiable),
            'project_id' => $this->entry->recruiter_project_id,
        ];
    }

    private function title(): string
    {
        return 'Recruiter requested changes';
    }

    private function summary(): string
    {
        $count = $this->entry->requirements->filter->isRejected()->count();

        return ($this->entry->project->owner->name ?? 'The recruiter').' did not approve '.$count.' file(s) of '.($this->entry->studentProfile->user->name ?? 'a candidate').' in "'.$this->entry->project->name.'".';
    }

    private function body(): string
    {
        $notes = $this->entry->requirements->filter->isRejected()
            ->map(fn ($file): string => $file->title.': '.$file->rejection_note)
            ->implode(' | ');

        return $this->summary().' '.$notes;
    }

    private function url(object $notifiable): string
    {
        return PlacementUrls::project($notifiable, $this->entry->recruiter_project_id);
    }
}
