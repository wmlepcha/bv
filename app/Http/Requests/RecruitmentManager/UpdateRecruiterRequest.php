<?php

namespace App\Http\Requests\RecruitmentManager;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateRecruiterRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->isRecruitmentManager() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $recruiter */
        $recruiter = $this->route('recruiter');

        return [
            ...$this->profileRules($recruiter->id),
            'password' => ['nullable', 'string', Password::default()],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
        ];
    }
}
