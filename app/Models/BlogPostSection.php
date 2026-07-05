<?php

namespace App\Models;

use App\Support\BlogPostSectionContentNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostSection extends Model
{
    public const TYPE_HEADING = 'heading';

    public const TYPE_PARAGRAPH = 'paragraph';

    public const TYPE_BULLET_LIST = 'bullet_list';

    public const TYPE_FAQ = 'faq';

    public const TYPE_QUOTE = 'quote';

    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_HEADING,
            self::TYPE_PARAGRAPH,
            self::TYPE_BULLET_LIST,
            self::TYPE_FAQ,
            self::TYPE_QUOTE,
            self::TYPE_IMAGE,
            self::TYPE_VIDEO,
        ];
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'blog_post_id',
        'type',
        'content',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BlogPost, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    protected static function booted(): void
    {
        static::saving(function (BlogPostSection $section): void {
            $section->content = BlogPostSectionContentNormalizer::normalize(
                $section->type,
                $section->content ?? [],
            );
        });
    }
}
