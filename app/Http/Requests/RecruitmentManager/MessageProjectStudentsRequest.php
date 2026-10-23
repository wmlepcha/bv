<?php

namespace App\Http\Requests\RecruitmentManager;

use Illuminate\Foundation\Http\FormRequest;

class MessageProjectStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRecruitmentManager() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:congratulate,requirements'],
            'message' => ['required', 'string', 'max:600'],
            'student_profile_id' => ['nullable', 'integer'],
        ];
    }
}
