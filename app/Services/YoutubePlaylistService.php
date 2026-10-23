<?php

namespace App\Services;

use App\Models\MaterialFile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class YoutubePlaylistService
{
    public const MAX_ITEMS = 100;

    public function hasApiKey(): bool
    {
        return filled(config('services.youtube.api_key'));
    }

    /**
     * @return list<array{video_id: string, title: string}>
     */
    public function fetchItems(string $playlistId): array
    {
        if (! $this->hasApiKey()) {
            return [];
        }

        $apiKey = (string) config('services.youtube.api_key');
        $items = [];
        $pageToken = null;

        do {
            $query = [
                'part' => 'snippet',
                'playlistId' => $playlistId,
                'maxResults' => 50,
                'key' => $apiKey,
            ];

            if (is_string($pageToken) && $pageToken !== '') {
                $query['pageToken'] = $pageToken;
            }

            try {
                $response = Http::timeout(5)
                    ->connectTimeout(3)
                    ->retry(1, 200)
                    ->acceptJson()
                    ->get('https://www.googleapis.com/youtube/v3/playlistItems', $query);
            } catch (ConnectionException $exception) {
                throw new RuntimeException('Unable to reach YouTube API.', 0, $exception);
            }

            if ($response->failed()) {
                throw new RuntimeException('YouTube API request failed.');
            }

            /** @var list<array<string, mixed>> $pageItems */
            $pageItems = $response->json('items') ?? [];

            foreach ($pageItems as $item) {
                $snippet = $item['snippet'] ?? null;

                if (! is_array($snippet)) {
                    continue;
                }

                $resourceId = $snippet['resourceId'] ?? null;
                $videoId = is_array($resourceId) ? ($resourceId['videoId'] ?? null) : null;

                if (! is_string($videoId) || $videoId === '') {
                    continue;
                }

                $title = $snippet['title'] ?? 'Untitled video';

                if (! is_string($title) || $title === '' || $title === 'Private video' || $title === 'Deleted video') {
                    continue;
                }

                $items[] = [
                    'video_id' => $videoId,
                    'title' => $title,
                ];

                if (count($items) >= self::MAX_ITEMS) {
                    return $items;
                }
            }

            $pageToken = $response->json('nextPageToken');
        } while (is_string($pageToken) && $pageToken !== '');

        return $items;
    }

    /**
     * @return list<array{video_id: string, title: string}>
     */
    public function resolveItemsFor(MaterialFile $file): array
    {
        if (! $file->isYoutubePlaylist()) {
            return [];
        }

        $cached = $file->playlist_items;

        if (is_array($cached) && $cached !== []) {
            return $this->normalizeItems($cached);
        }

        $playlistId = $file->youtubePlaylistId();

        if ($playlistId === null || ! $this->hasApiKey()) {
            return [];
        }

        try {
            $items = $this->fetchItems($playlistId);
        } catch (RuntimeException $exception) {
            Log::warning('YouTube playlist fetch failed', [
                'playlist_id' => $playlistId,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        $file->forceFill(['playlist_items' => $items])->save();

        return $items;
    }

    /**
     * @param  list<mixed>  $items
     * @return list<array{video_id: string, title: string}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $videoId = $item['video_id'] ?? null;
            $title = $item['title'] ?? null;

            if (! is_string($videoId) || $videoId === '' || ! is_string($title) || $title === '') {
                continue;
            }

            $normalized[] = [
                'video_id' => $videoId,
                'title' => $title,
            ];
        }

        return $normalized;
    }
}
