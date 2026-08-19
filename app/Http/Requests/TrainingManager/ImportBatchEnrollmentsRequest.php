<?php

namespace App\Http\Requests\TrainingManager;

use Illuminate\Foundation\Http\FormRequest;

class ImportBatchEnrollmentsRequest extends FormRequest
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
            'spreadsheet' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ];
    }
}
