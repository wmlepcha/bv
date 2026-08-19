<?php

namespace App\Http\Requests\TrainingManager;

use Illuminate\Foundation\Http\FormRequest;

class RelocateMaterialFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTrainingManager() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('parent_id') === '' || $this->input('parent_id') === 'null') {
            $this->merge(['parent_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:material_folders,id'],
        ];
    }
}
