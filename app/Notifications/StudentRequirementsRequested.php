<?php

namespace App\Notifications;

use App\Models\ProjectRequirement;
use App\Models\RecruiterProjectCandidate;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentRequirementsRequested extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
    use Queueable;

    public function __construct(
        public readonly RecruiterProjectCandidate $entry,
        public readonly string $message,
    ) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return StudentNotificationChannels::for($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Documents required')
            ->greeting('Hello'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line($this->message);

        foreach ($this->requirementTitles() as $title) {
            $mail->line('• '.$title);
        }

        return $mail->action('Open your dashboard', url('/dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $bullets = collect($this->requirementTitles())->map(fn (string $title): string => '• '.$title)->implode("\n");

        return [
            'title' => 'Documents required',
            'body' => trim($this->message."\n".$bullets),
            'url' => url('/dashboard'),
            'kind' => 'requirements',
            'project_id' => $this->entry->recruiter_project_id,
            'requirements' => $this->requirementTitles(),
        ];
    }

    /**
     * @return list<string>
     */
    private function requirementTitles(): array
    {
        return $this->entry->requirements
            ->map(fn (ProjectRequirement $requirement): string => $requirement->title)
            ->values()
            ->all();
    }
}
