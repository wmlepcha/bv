<?php

namespace App\Http\Requests\Recruiter;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequirementsRequest extends FormRequest
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
            'requirements' => ['required', 'array', 'min:1', 'max:30'],
            'requirements.*' => ['required', 'string', 'max:255'],
            'students' => ['nullable', 'array'],
            'students.*' => ['array', 'max:30'],
            'students.*.*' => ['required', 'string', 'max:255'],
        ];
    }
}
