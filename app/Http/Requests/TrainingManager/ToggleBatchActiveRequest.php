<?php

namespace App\Http\Requests\TrainingManager;

use Illuminate\Foundation\Http\FormRequest;

class ToggleBatchActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTrainingManager() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var(
                    $this->input('is_active'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                ),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
