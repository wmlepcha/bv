<?php

namespace App\Filament\Resources\BlogPosts\Concerns;

use App\Support\BlogPostSectionContentNormalizer;

trait NormalizesBlogPostData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeBlogPostData(array $data): array
    {
        foreach (['featured_image_path', 'hero_image_path', 'og_image_path'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = $data[$field][0] ?? null;
            }
        }

        if (isset($data['sections']) && is_array($data['sections'])) {
            $data['sections'] = collect($data['sections'])
                ->map(fn (array $section): array => $this->normalizeSectionData($section))
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    protected function normalizeSectionData(array $section): array
    {
        $type = (string) ($section['type'] ?? '');
        $content = $section['content'] ?? [];
        $section['content'] = BlogPostSectionContentNormalizer::normalize($type, $content);

        return $section;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    protected function prepareSectionForForm(array $section): array
    {
        $type = (string) ($section['type'] ?? '');
        $content = $section['content'] ?? [];
        $section['content'] = BlogPostSectionContentNormalizer::forForm($type, $content);

        return $section;
    }
}
