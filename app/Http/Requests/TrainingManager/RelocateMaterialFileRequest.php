<?php

namespace App\Http\Requests\TrainingManager;

use Illuminate\Foundation\Http\FormRequest;

class RelocateMaterialFileRequest extends FormRequest
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
            'folder_id' => ['required', 'integer', 'exists:material_folders,id'],
        ];
    }
}
