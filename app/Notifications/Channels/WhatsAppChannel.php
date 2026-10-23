<?php

namespace App\Notifications\Channels;

use App\Services\WhatsAppNotificationService;
use App\Support\WhatsAppContentMessage;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(
        private readonly WhatsAppNotificationService $whatsapp,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsAppContent')) {
            return;
        }

        $message = $notification->toWhatsAppContent($notifiable);

        if (! $message instanceof WhatsAppContentMessage) {
            return;
        }

        if ($message->contentSid === '') {
            return;
        }

        $this->whatsapp->send($notifiable, $message);
    }
}
