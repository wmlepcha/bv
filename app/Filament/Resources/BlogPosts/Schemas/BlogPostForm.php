<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Filament\Support\ImageUploadField;
use App\Models\BlogPost;
use App\Models\BlogPostSection;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('BlogPost')
                    ->tabs([
                        Tab::make('General')
                            ->schema(self::generalTab()),
                        Tab::make('Card display')
                            ->schema(self::cardTab()),
                        Tab::make('Hero')
                            ->schema(self::heroTab()),
                        Tab::make('SEO')
                            ->schema(self::seoTab()),
                        Tab::make('Content sections')
                            ->schema(self::sectionsTab()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return list<Component>
     */
    protected static function generalTab(): array
    {
        return [
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, ?string $operation): void {
                    if ($operation === 'create' && filled($state)) {
                        $set('slug', Str::slug($state));
                    }
                }),
            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('blog_category_id')
                ->label('Category')
                ->relationship('category', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Textarea::make('excerpt')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('author_name')
                ->maxLength(255),
            Textarea::make('author_bio')
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('reading_time_minutes')
                ->label('Reading time (minutes)')
                ->numeric()
                ->default(5)
                ->required(),
            DateTimePicker::make('published_at')
                ->label('Published at')
                ->seconds(false),
            TextInput::make('sort_order')
                ->numeric()
                ->default(0)
                ->required(),
            Toggle::make('is_visible')
                ->label('Visible')
                ->default(true),
        ];
    }

    /**
     * @return list<Component>
     */
    protected static function cardTab(): array
    {
        return [
            ImageUploadField::configure(
                FileUpload::make('featured_image_path')
                    ->label('Featured image')
                    ->image()
                    ->disk('public')
                    ->directory('images/blog')
                    ->maxFiles(1),
            ),
            TextInput::make('featured_image_alt')
                ->label('Featured image alt text')
                ->maxLength(255),
            Select::make('card_layout')
                ->options([
                    BlogPost::CARD_LAYOUT_WIDE => 'Wide',
                    BlogPost::CARD_LAYOUT_TALL => 'Tall',
                ])
                ->default(BlogPost::CARD_LAYOUT_TALL)
                ->required(),
        ];
    }

    /**
     * @return list<Component>
     */
    protected static function heroTab(): array
    {
        return [
            ImageUploadField::configure(
                FileUpload::make('hero_image_path')
                    ->label('Hero image')
                    ->image()
                    ->disk('public')
                    ->directory('images/blog')
                    ->maxFiles(1),
                'Optional. Falls back to featured image if empty. JPG, PNG, WebP, or GIF. Maximum size: 5 MB.',
            ),
            TextInput::make('hero_image_alt')
                ->label('Hero image alt text')
                ->maxLength(255),
        ];
    }

    /**
     * @return list<Component>
     */
    protected static function seoTab(): array
    {
        return [
            TextInput::make('seo_title')
                ->label('SEO title')
                ->maxLength(255),
            Textarea::make('seo_description')
                ->label('Meta description')
                ->rows(3),
            TextInput::make('seo_keywords')
                ->label('Meta keywords')
                ->maxLength(255)
                ->helperText('Comma-separated keywords.'),
            ImageUploadField::configure(
                FileUpload::make('og_image_path')
                    ->label('Open Graph image')
                    ->image()
                    ->disk('public')
                    ->directory('images/blog')
                    ->maxFiles(1),
            ),
            Toggle::make('seo_noindex')
                ->label('Noindex')
                ->default(false),
        ];
    }

    /**
     * @return list<Component>
     */
    protected static function sectionsTab(): array
    {
        return [
            Repeater::make('sections')
                ->relationship()
                ->schema(self::sectionSchema())
                ->defaultItems(0)
                ->reorderable()
                ->orderColumn('sort_order')
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => isset($state['type'])
                    ? ucfirst(str_replace('_', ' ', (string) $state['type']))
                    : 'Section')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return list<Component>
     */
    protected static function sectionSchema(): array
    {
        return [
            Select::make('type')
                ->options([
                    BlogPostSection::TYPE_HEADING => 'Heading',
                    BlogPostSection::TYPE_PARAGRAPH => 'Paragraph',
                    BlogPostSection::TYPE_BULLET_LIST => 'Bullet list',
                    BlogPostSection::TYPE_FAQ => 'FAQ',
                    BlogPostSection::TYPE_QUOTE => 'Quote',
                    BlogPostSection::TYPE_IMAGE => 'Image',
                    BlogPostSection::TYPE_VIDEO => 'Video',
                ])
                ->required()
                ->live(),
            Select::make('content.level')
                ->label('Heading level')
                ->options([
                    1 => 'H1',
                    2 => 'H2',
                    3 => 'H3',
                    4 => 'H4',
                    5 => 'H5',
                    6 => 'H6',
                ])
                ->default(2)
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_HEADING),
            TextInput::make('content.heading_text')
                ->label('Heading text')
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_HEADING),
            RichEditor::make('content.paragraph_text')
                ->label('Paragraph text')
                ->toolbarButtons([
                    ['bold', 'italic', 'link'],
                ])
                ->fileAttachments(false)
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_PARAGRAPH)
                ->columnSpanFull(),
            Repeater::make('content.bullet_items')
                ->label('Bullet items')
                ->schema([
                    TextInput::make('text')
                        ->label('Item')
                        ->required(),
                ])
                ->defaultItems(0)
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_BULLET_LIST)
                ->columnSpanFull(),
            Repeater::make('content.faq_items')
                ->label('FAQ items')
                ->schema([
                    TextInput::make('question')
                        ->required(),
                    Textarea::make('answer')
                        ->required()
                        ->rows(2),
                ])
                ->defaultItems(0)
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_FAQ)
                ->columnSpanFull(),
            Textarea::make('content.quote_text')
                ->label('Quote text')
                ->rows(3)
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_QUOTE)
                ->columnSpanFull(),
            TextInput::make('content.attribution')
                ->label('Quote attribution')
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_QUOTE),
            ImageUploadField::configure(
                FileUpload::make('content.path')
                    ->label('Image')
                    ->image()
                    ->disk('public')
                    ->directory('images/blog')
                    ->maxFiles(1)
                    ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_IMAGE),
            ),
            TextInput::make('content.alt')
                ->label('Image alt text')
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_IMAGE),
            TextInput::make('content.caption')
                ->label('Image caption')
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_IMAGE),
            Select::make('content.placement')
                ->label('Image placement')
                ->options([
                    'full' => 'Full width',
                    'inline' => 'Inline',
                    'below' => 'Below paragraph',
                ])
                ->default('full')
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_IMAGE),
            Select::make('content.source')
                ->label('Video source')
                ->options([
                    'youtube' => 'YouTube',
                    'vimeo' => 'Vimeo',
                    'upload' => 'Upload',
                ])
                ->default('youtube')
                ->live()
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_VIDEO),
            TextInput::make('content.url')
                ->label('Video URL')
                ->url()
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_VIDEO
                    && in_array($get('content.source'), ['youtube', 'vimeo'], true)),
            FileUpload::make('content.path')
                ->label('Video file')
                ->disk('public')
                ->directory('videos/blog')
                ->acceptedFileTypes(['video/mp4', 'video/webm'])
                ->maxFiles(1)
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_VIDEO
                    && $get('content.source') === 'upload'),
            TextInput::make('content.caption')
                ->label('Video caption')
                ->visible(fn (callable $get): bool => $get('type') === BlogPostSection::TYPE_VIDEO),
        ];
    }
}
