<?php

namespace App\Notifications;

use App\Models\RecruiterProject;
use App\Support\PlacementUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectUpdatedByRecruiter extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly RecruiterProject $project) {}

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
            ->subject('Project updated: '.$this->project->name)
            ->line($this->body())
            ->action('Review changes', $this->url($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Placement project updated',
            'body' => $this->body(),
            'url' => $this->url($notifiable),
            'project_id' => $this->project->id,
        ];
    }

    private function body(): string
    {
        return ($this->project->owner->name ?? 'A recruiter').' changed the students or requirements of "'.$this->project->name.'".';
    }

    private function url(object $notifiable): string
    {
        return PlacementUrls::project($notifiable, $this->project->id);
    }
}
