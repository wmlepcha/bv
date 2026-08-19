<?php

namespace App\Http\Requests\TrainingManager;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreTrainerRequest extends FormRequest
{
    use PasswordValidationRules;
    use ProfileValidationRules;

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
            ...$this->profileRules(),
            'password' => ['required', 'string', Password::default()],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
