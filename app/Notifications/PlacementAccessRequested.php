<?php

namespace App\Notifications;

use App\Models\StudentProfile;
use App\Notifications\Concerns\BuildsWhatsAppBodyFromArray;
use App\Support\PlacementUrls;
use App\Support\StudentNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlacementAccessRequested extends Notification implements ShouldQueue
{
    use BuildsWhatsAppBodyFromArray;
    use Queueable;

    public function __construct(public readonly StudentProfile $profile) {}

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
            ->subject('Placement access requested')
            ->line($this->body())
            ->action('View candidates', $this->url($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Placement access requested',
            'body' => $this->body(),
            'url' => $this->url($notifiable),
            'student_profile_id' => $this->profile->id,
        ];
    }

    private function body(): string
    {
        return ($this->profile->user->name ?? 'A student').' registered for training only and asked to be considered for placement.';
    }

    private function url(object $notifiable): string
    {
        return PlacementUrls::candidates($notifiable);
    }
}
