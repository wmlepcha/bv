<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Resources\BlogPosts\Concerns\NormalizesBlogPostData;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    use NormalizesBlogPostData;

    protected static string $resource = BlogPostResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['sections']) && is_array($data['sections'])) {
            $data['sections'] = collect($data['sections'])
                ->map(fn (array $section): array => $this->prepareSectionForForm($section))
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalizeBlogPostData($data);
    }
}
