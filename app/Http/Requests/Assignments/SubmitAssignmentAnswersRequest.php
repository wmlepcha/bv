<?php

namespace App\Http\Requests\Assignments;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAssignmentAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:assignment_questions,id'],
            'answers.*.selected_index' => ['nullable', 'integer', 'min:0'],
            'answers.*.text' => ['nullable', 'string', 'max:5000'],
            'answers.*.blanks' => ['nullable', 'array'],
            'answers.*.blanks.*' => ['string', 'max:500'],
            'answers.*.ordered_tokens' => ['nullable', 'array'],
            'answers.*.ordered_tokens.*' => ['string', 'max:200'],
        ];
    }
}
