<?php

namespace App\Http\Requests\RecruitmentManager;

use Illuminate\Foundation\Http\FormRequest;

class UploadProjectDocumentRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }
}
