<?php

namespace App\Support;

use App\Models\BlogPostSection;
use Illuminate\Support\Str;

class BlogPostSectionContentNormalizer
{
    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function normalize(string $type, array $content): array
    {
        return match ($type) {
            BlogPostSection::TYPE_HEADING => [
                'level' => (int) ($content['level'] ?? 2),
                'text' => (string) ($content['heading_text'] ?? $content['text'] ?? ''),
            ],
            BlogPostSection::TYPE_PARAGRAPH => [
                'text' => Str::sanitizeHtml(
                    (string) ($content['paragraph_text'] ?? $content['text'] ?? ''),
                ),
            ],
            BlogPostSection::TYPE_BULLET_LIST => [
                'items' => collect($content['bullet_items'] ?? $content['items'] ?? [])
                    ->map(fn (mixed $item): string => is_array($item) ? (string) ($item['text'] ?? '') : (string) $item)
                    ->filter()
                    ->values()
                    ->all(),
            ],
            BlogPostSection::TYPE_FAQ => [
                'items' => collect($content['faq_items'] ?? $content['items'] ?? [])
                    ->map(fn (array $item): array => [
                        'question' => (string) ($item['question'] ?? ''),
                        'answer' => (string) ($item['answer'] ?? ''),
                    ])
                    ->filter(fn (array $item): bool => filled($item['question']))
                    ->values()
                    ->all(),
            ],
            BlogPostSection::TYPE_QUOTE => [
                'text' => (string) ($content['quote_text'] ?? $content['text'] ?? ''),
                'attribution' => filled($content['attribution'] ?? null) ? (string) $content['attribution'] : null,
            ],
            BlogPostSection::TYPE_IMAGE => self::normalizeImage($content),
            BlogPostSection::TYPE_VIDEO => self::normalizeVideo($content),
            default => $content,
        };
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function forForm(string $type, array $content): array
    {
        return match ($type) {
            BlogPostSection::TYPE_HEADING => [
                'level' => $content['level'] ?? 2,
                'heading_text' => $content['text'] ?? '',
            ],
            BlogPostSection::TYPE_PARAGRAPH => [
                'paragraph_text' => $content['text'] ?? '',
            ],
            BlogPostSection::TYPE_BULLET_LIST => [
                'bullet_items' => collect($content['items'] ?? [])
                    ->map(fn (mixed $item): array => ['text' => is_string($item) ? $item : (string) ($item['text'] ?? '')])
                    ->values()
                    ->all(),
            ],
            BlogPostSection::TYPE_FAQ => [
                'faq_items' => $content['items'] ?? [],
            ],
            BlogPostSection::TYPE_QUOTE => [
                'quote_text' => $content['text'] ?? '',
                'attribution' => $content['attribution'] ?? null,
            ],
            default => $content,
        };
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    protected static function normalizeImage(array $content): array
    {
        if (isset($content['path']) && is_array($content['path'])) {
            $content['path'] = $content['path'][0] ?? null;
        }

        return [
            'path' => $content['path'] ?? null,
            'alt' => $content['alt'] ?? null,
            'caption' => $content['caption'] ?? null,
            'placement' => $content['placement'] ?? 'full',
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    protected static function normalizeVideo(array $content): array
    {
        if (($content['source'] ?? '') === 'upload' && isset($content['path']) && is_array($content['path'])) {
            $content['path'] = $content['path'][0] ?? null;
        }

        return [
            'source' => $content['source'] ?? 'youtube',
            'url' => $content['url'] ?? null,
            'path' => $content['path'] ?? null,
            'caption' => $content['caption'] ?? null,
        ];
    }
}
