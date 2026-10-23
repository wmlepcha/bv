<?php

namespace Database\Factories;

use App\Enums\AssignmentQuestionType;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentQuestion>
 */
class AssignmentQuestionFactory extends Factory
{
    protected $model = AssignmentQuestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'sort_order' => 0,
            'type' => AssignmentQuestionType::Mcq,
            'prompt' => 'Wählen Sie die richtige Antwort.',
            'points' => 1,
            'config' => [
                'options' => ['Option A', 'Option B', 'Option C'],
                'correct_index' => 0,
            ],
        ];
    }

    public function shortText(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssignmentQuestionType::ShortText,
            'prompt' => 'Schreiben Sie einen Satz auf Deutsch.',
            'config' => null,
        ]);
    }

    public function link(string $url = 'https://example.com/resource'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssignmentQuestionType::Link,
            'prompt' => 'Open the link and answer the question.',
            'config' => ['url' => $url],
        ]);
    }

    /**
     * @param  array{disk?: string, path: string, original_name?: string}  $media
     */
    public function pdf(array $media): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssignmentQuestionType::Pdf,
            'prompt' => 'Read the PDF and answer.',
            'config' => [
                'disk' => $media['disk'] ?? 'local',
                'path' => $media['path'],
                'original_name' => $media['original_name'] ?? 'worksheet.pdf',
            ],
        ]);
    }
}
