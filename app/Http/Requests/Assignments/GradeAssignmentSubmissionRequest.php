<?php

namespace App\Http\Requests\Assignments;

use App\Enums\SubmissionAnnotationType;
use App\Models\AssignmentSubmission;
use App\Services\AssignmentSubmissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeAssignmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $submission = $this->route('submission');

        if ($user === null || ! $submission instanceof AssignmentSubmission) {
            return false;
        }

        return app(AssignmentSubmissionService::class)->actorCanGradeSubmission($user, $submission);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'final_score' => ['required', 'integer', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'score_overridden' => ['required', 'boolean'],
            'annotations' => ['nullable', 'array'],
            'annotations.*.type' => ['required', Rule::enum(SubmissionAnnotationType::class)],
            'annotations.*.question_id' => ['nullable', 'integer', 'exists:assignment_questions,id'],
            'annotations.*.payload' => ['required', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('annotations')) {
            $this->merge(['annotations' => []]);
        }
    }
}
