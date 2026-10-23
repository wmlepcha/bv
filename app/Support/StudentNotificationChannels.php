<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;

class StudentNotificationChannels
{
    /**
     * @return list<string|class-string>
     */
    public static function for(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if (self::shouldSendWhatsApp($notifiable)) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public static function shouldSendWhatsApp(object $notifiable): bool
    {
        if (! config('services.whatsapp.enabled')) {
            return false;
        }

        if (! $notifiable instanceof User) {
            return false;
        }

        return filled($notifiable->whatsAppNumber());
    }
}
