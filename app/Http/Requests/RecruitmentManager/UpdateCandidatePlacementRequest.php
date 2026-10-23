<?php

namespace App\Http\Requests\RecruitmentManager;

use App\Enums\PlacementStatus;
use App\Enums\RegistrationPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidatePlacementRequest extends FormRequest
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
            'registration_purpose' => ['sometimes', 'required', Rule::enum(RegistrationPurpose::class)],
            'placement_status' => ['sometimes', 'required', Rule::enum(PlacementStatus::class)],
        ];
    }
}
