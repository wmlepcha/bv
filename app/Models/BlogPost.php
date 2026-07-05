<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogPost extends Model
{
    public const CARD_LAYOUT_WIDE = 'wide';

    public const CARD_LAYOUT_TALL = 'tall';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'blog_category_id',
        'slug',
        'title',
        'excerpt',
        'featured_image_path',
        'featured_image_alt',
        'hero_image_path',
        'hero_image_alt',
        'card_layout',
        'reading_time_minutes',
        'published_at',
        'is_visible',
        'sort_order',
        'author_name',
        'author_bio',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image_path',
        'seo_noindex',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reading_time_minutes' => 'integer',
            'published_at' => 'datetime',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
            'seo_noindex' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<BlogCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    /**
     * @return HasMany<BlogPostSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(BlogPostSection::class)->orderBy('sort_order');
    }

    public function isPublished(): bool
    {
        return $this->is_visible
            && $this->published_at !== null
            && $this->published_at->isPast();
    }
}
