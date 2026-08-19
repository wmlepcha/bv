<?php

use App\Models\AboutCoreValue;
use App\Models\AboutJourneyMilestone;
use App\Models\AboutParagraph;
use App\Models\BlogPost;
use App\Models\BlogPostSection;
use App\Models\SiteSetting;
use App\Support\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->patchModelColumns(SiteSetting::class, ['value']);
        $this->patchModelColumns(AboutParagraph::class, ['body']);
        $this->patchModelColumns(AboutCoreValue::class, ['title', 'description']);
        $this->patchModelColumns(AboutJourneyMilestone::class, ['period', 'title', 'image_alt']);
        $this->patchJsonColumn(AboutJourneyMilestone::class, 'bullets');
        $this->patchModelColumns(BlogPost::class, [
            'title',
            'excerpt',
            'featured_image_alt',
            'hero_image_alt',
            'author_name',
            'author_bio',
            'seo_title',
            'seo_description',
            'seo_keywords',
        ]);
        $this->patchJsonColumn(BlogPostSection::class, 'content');
    }

    public function down(): void
    {
        // Trademark markers are display-only; do not strip on rollback.
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<string>  $columns
     */
    private function patchModelColumns(string $model, array $columns): void
    {
        if (! Schema::hasTable((new $model)->getTable())) {
            return;
        }

        $model::query()->orderBy('id')->chunkById(100, function ($rows) use ($columns): void {
            foreach ($rows as $row) {
                $updates = [];

                foreach ($columns as $column) {
                    $value = $row->getAttribute($column);

                    if (! is_string($value) || $value === '') {
                        continue;
                    }

                    $patched = Brand::applyTrademark($value);

                    if ($patched !== $value) {
                        $updates[$column] = $patched;
                    }
                }

                if ($updates !== []) {
                    $row->update($updates);
                }
            }
        });
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function patchJsonColumn(string $model, string $column): void
    {
        if (! Schema::hasTable((new $model)->getTable())) {
            return;
        }

        $model::query()->orderBy('id')->chunkById(100, function ($rows) use ($column): void {
            foreach ($rows as $row) {
                $value = $row->getAttribute($column);

                if ($value === null) {
                    continue;
                }

                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);

                if (! is_string($encoded)) {
                    continue;
                }

                $patched = Brand::applyTrademark($encoded);

                if ($patched === $encoded) {
                    continue;
                }

                $decoded = json_decode($patched, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }

                $row->update([$column => $decoded]);
            }
        });
    }
};
