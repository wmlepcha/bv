<?php

namespace App\Http\Requests\Recruiter;

use App\Enums\CourseLevel;
use App\Enums\PlacementStatus;
use App\Enums\Profession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandidateFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'profession' => ['nullable', Rule::in(Profession::values())],
            'experience' => ['nullable', Rule::in(['fresher', 'experienced'])],
            'level' => ['nullable', Rule::in(CourseLevel::values())],
            'status' => ['nullable', Rule::in(array_column(PlacementStatus::cases(), 'value'))],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }
}
