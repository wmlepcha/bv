<?php

namespace App\Http\Requests\TrainingManager;

use Illuminate\Foundation\Http\FormRequest;

class AssignMaterialRequest extends FormRequest
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
            'trainer_ids' => ['nullable', 'array'],
            'trainer_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('trainer_ids')) {
            $this->merge(['trainer_ids' => []]);
        }
    }
}
