<?php

namespace App\Http\Requests\TrainingManager;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialFileRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx',
            ],
        ];
    }
}
