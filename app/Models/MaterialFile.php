<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MaterialFile extends Model
{
    public const KIND_FILE = 'file';

    public const KIND_YOUTUBE = 'youtube';

    public const KIND_LINK = 'link';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'folder_id',
        'kind',
        'original_name',
        'display_name',
        'disk',
        'path',
        'url',
        'mime_type',
        'extension',
        'size',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MaterialFolder::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(MaterialAssignment::class, 'assignable');
    }

    public function batchAssignments(): MorphMany
    {
        return $this->morphMany(MaterialBatchAssignment::class, 'assignable');
    }

    public function isFile(): bool
    {
        return ($this->kind ?? self::KIND_FILE) === self::KIND_FILE;
    }

    public function isYoutube(): bool
    {
        return $this->kind === self::KIND_YOUTUBE;
    }

    public function isLink(): bool
    {
        return $this->kind === self::KIND_LINK;
    }

    public function youtubeVideoId(): ?string
    {
        if (! $this->isYoutube() || blank($this->url)) {
            return null;
        }

        return self::extractYoutubeVideoId($this->url);
    }

    public function embedUrl(): ?string
    {
        $videoId = $this->youtubeVideoId();

        if ($videoId === null) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$videoId;
    }

    public static function extractYoutubeVideoId(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $host = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

        if (! in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be'], true)) {
            return null;
        }

        if ($host === 'youtu.be') {
            $path = trim($parts['path'] ?? '', '/');

            return $path !== '' && preg_match('/^[A-Za-z0-9_-]{6,}$/', $path) === 1
                ? $path
                : null;
        }

        $path = $parts['path'] ?? '';

        if (preg_match('#^/(embed|shorts|live)/([A-Za-z0-9_-]{6,})#', $path, $matches) === 1) {
            return $matches[2];
        }

        if (($parts['query'] ?? '') !== '') {
            parse_str($parts['query'], $query);

            $videoId = $query['v'] ?? null;

            if (is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{6,}$/', $videoId) === 1) {
                return $videoId;
            }
        }

        return null;
    }
}
