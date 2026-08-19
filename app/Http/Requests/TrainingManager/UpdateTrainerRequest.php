<?php

namespace App\Http\Requests\TrainingManager;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateTrainerRequest extends FormRequest
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
        /** @var User $trainer */
        $trainer = $this->route('trainer');

        return [
            ...$this->profileRules($trainer->id),
            'password' => ['nullable', 'string', Password::default()],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }
}
