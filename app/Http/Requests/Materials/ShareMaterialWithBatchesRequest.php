<?php

namespace App\Http\Requests\Materials;

use Illuminate\Foundation\Http\FormRequest;

class ShareMaterialWithBatchesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isTeacher() || $user->isTrainingManager());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => ['integer', 'exists:batches,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('batch_ids')) {
            $this->merge(['batch_ids' => []]);
        }
    }
}
