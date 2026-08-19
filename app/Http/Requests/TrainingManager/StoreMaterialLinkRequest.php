<?php

namespace App\Http\Requests\TrainingManager;

use App\Models\MaterialFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMaterialLinkRequest extends FormRequest
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
            'url' => ['required', 'url', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $url = (string) $this->input('url', '');

            if ($url === '' || MaterialFile::extractYoutubeVideoId($url) === null) {
                return;
            }

            $validator->errors()->add(
                'url',
                'Use the YouTube option to add YouTube videos.',
            );
        });
    }
}
