<?php

namespace App\Notifications;

use App\Models\RecruiterProject;
use App\Support\PlacementUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectFinalized extends Notification implements ShouldQueue
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
            ->subject('New placement project: '.$this->project->name)
            ->line($this->body())
            ->action('Open project', $this->url($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New placement project',
            'body' => $this->body(),
            'url' => $this->url($notifiable),
            'project_id' => $this->project->id,
        ];
    }

    private function body(): string
    {
        return ($this->project->owner->name ?? 'A recruiter').' finalized "'.$this->project->name.'" and needs documents prepared.';
    }

    private function url(object $notifiable): string
    {
        return PlacementUrls::project($notifiable, $this->project->id);
    }
}
