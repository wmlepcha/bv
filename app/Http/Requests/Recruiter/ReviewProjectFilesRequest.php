<?php

namespace App\Http\Requests\Recruiter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class ReviewProjectFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decisions' => ['required', 'array', 'min:1'],
            'decisions.*.approve' => ['required', 'boolean'],
            'decisions.*.note' => ['nullable', 'string', 'max:2000'],
            'decisions.*.sample' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $decisions = $this->input('decisions');

            foreach (is_array($decisions) ? $decisions : [] as $id => $decision) {
                if (! is_array($decision)) {
                    continue;
                }

                if (! filter_var($decision['approve'] ?? true, FILTER_VALIDATE_BOOLEAN) && blank($decision['note'] ?? null)) {
                    $validator->errors()->add("decisions.{$id}.note", 'Explain what is wrong with this file.');
                }
            }
        }];
    }

    /**
     * @return array<int, array{approve: bool, note: string|null, sample: UploadedFile|null}>
     */
    public function decisions(): array
    {
        $decisions = [];

        foreach ($this->validated('decisions') as $id => $decision) {
            $decisions[(int) $id] = [
                'approve' => (bool) $decision['approve'],
                'note' => $decision['note'] ?? null,
                'sample' => $this->file("decisions.{$id}.sample"),
            ];
        }

        return $decisions;
    }
}
