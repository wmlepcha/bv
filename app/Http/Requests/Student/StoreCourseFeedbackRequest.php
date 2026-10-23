<?php

namespace App\Http\Requests\Student;

use App\Enums\CourseLevel;
use App\Enums\CoursePace;
use App\Enums\FeedbackSkill;
use App\Models\CourseFeedback;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseFeedbackRequest extends FormRequest
{
    public const MAX_ANSWER_LENGTH = 800;

    public function authorize(): bool
    {
        return $this->user()?->canSubmitCourseFeedback() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rating = ['required', 'integer', 'between:1,5'];

        $answer = ['required', 'string', 'max:'.self::MAX_ANSWER_LENGTH, 'not_regex:/[<>]/'];

        $ratingRules = array_fill_keys(array_keys(CourseFeedback::RATING_QUESTIONS), $rating);

        return [
            'level' => ['required', Rule::enum(CourseLevel::class)],
            ...$ratingRules,
            'most_improved_skill' => ['required', Rule::enum(FeedbackSkill::class)],
            'needs_improvement_skill' => ['required', Rule::enum(FeedbackSkill::class)],
            'pace' => ['required', Rule::enum(CoursePace::class)],
            'liked_most' => $answer,
            'improvement_suggestion' => $answer,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'liked_most.max' => 'Your answer may not be longer than :max characters.',
            'improvement_suggestion.max' => 'Your answer may not be longer than :max characters.',
            'liked_most.not_regex' => 'Please remove the < and > characters from your answer.',
            'improvement_suggestion.not_regex' => 'Please remove the < and > characters from your answer.',
            '*.between' => 'Please choose a rating from 1 to 5 stars.',
            'overall_satisfaction.required' => 'Please rate this question.',
            'trainer_quality.required' => 'Please rate this question.',
            'explanation_clarity.required' => 'Please rate this question.',
            'class_engagement.required' => 'Please rate this question.',
            'materials_usefulness.required' => 'Please rate this question.',
            'skills_improvement.required' => 'Please rate this question.',
            'confidence.required' => 'Please rate this question.',
            'support_satisfaction.required' => 'Please rate this question.',
        ];
    }

    /**
     * Normalise free-text answers: strip control characters and surrounding whitespace.
     */
    protected function prepareForValidation(): void
    {
        foreach (['liked_most', 'improvement_suggestion'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([
                    $field => trim((string) preg_replace('/[^\P{C}\n\t]/u', '', str_replace("\r\n", "\n", $value))),
                ]);
            }
        }
    }
}
