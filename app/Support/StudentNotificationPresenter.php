<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;

class StudentNotificationPresenter
{
    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     body: string,
     *     url: string|null,
     *     kind: string,
     *     read_at: string|null,
     *     created_at: string|null
     * }
     */
    public static function format(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => (string) ($notification->data['title'] ?? 'Notification'),
            'body' => (string) ($notification->data['body'] ?? ''),
            'url' => self::relativeUrl(
                isset($notification->data['url']) ? (string) $notification->data['url'] : null,
            ),
            'kind' => (string) ($notification->data['kind'] ?? 'info'),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    public static function relativeUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['path'])) {
            return $url;
        }

        $relative = $parts['path'];

        if (isset($parts['query'])) {
            $relative .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $relative .= '#'.$parts['fragment'];
        }

        return $relative;
    }
}
