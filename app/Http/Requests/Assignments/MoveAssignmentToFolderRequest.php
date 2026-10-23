<?php

namespace App\Http\Requests\Assignments;

use Illuminate\Foundation\Http\FormRequest;

class MoveAssignmentToFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isTrainingManager();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'folder_id' => ['nullable', 'integer', 'exists:assignment_folders,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('folder_id') === '' || $this->input('folder_id') === 'null') {
            $this->merge(['folder_id' => null]);
        }
    }
}
