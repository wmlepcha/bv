<?php

namespace App\Http\Requests\TrainingManager;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\UserRole;
use App\Models\Center;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTrainingManager() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'center_id' => ['required', 'integer', Rule::exists(Center::class, 'id')],
            'language' => ['required', Rule::enum(CourseLanguage::class)],
            'level' => ['required', Rule::enum(CourseLevel::class)],
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id')->where('role', UserRole::Teacher->value),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
