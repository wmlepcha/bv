<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Support\PortalLoginUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeWithCredentials extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $plainPassword,
        public readonly ?UserRole $role = null,
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
        $roleLabel = $this->roleLabel();
        $loginUrl = PortalLoginUrl::forRole($this->role ?? ($notifiable->role ?? null));

        return (new MailMessage)
            ->subject('Your Bilingual Vitae account')
            ->greeting('Welcome'.(filled($notifiable->name ?? null) ? ', '.$notifiable->name : '').'!')
            ->line('An account has been created for you on Bilingual Vitae'.($roleLabel !== null ? " as {$roleLabel}" : '').'.')
            ->line('Email: '.$notifiable->email)
            ->line('Temporary password: '.$this->plainPassword)
            ->action('Sign in', $loginUrl)
            ->line('Please sign in and change your password after your first login.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Welcome to Bilingual Vitae',
            'body' => 'Your account is ready. Sign in with the credentials sent to your email.',
            'url' => PortalLoginUrl::forRole($this->role ?? ($notifiable->role ?? null)),
        ];
    }

    private function roleLabel(): ?string
    {
        return match ($this->role) {
            UserRole::Teacher => 'a trainer',
            UserRole::TrainingManager => 'a training manager',
            UserRole::Admin => 'an admin',
            UserRole::Recruiter => 'a recruiter',
            UserRole::Employee => 'staff',
            UserRole::Student => 'a candidate',
            default => null,
        };
    }
}
