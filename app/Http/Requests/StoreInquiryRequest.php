<?php

namespace App\Http\Requests;

use App\Services\InquiryOptionsService;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInquiryRequest extends FormRequest
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
        $type = $this->input('type');

        $shared = [
            'type' => ['required', Rule::in(['candidate', 'recruiter'])],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30', PhoneNumber::rule()],
        ];

        if ($type === 'candidate') {
            return array_merge($shared, [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'date_of_birth' => ['required', 'date', 'before:today'],
                'address' => ['nullable', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:100'],
                'country' => ['required', 'string', 'max:100'],
                'education_level' => ['required', 'string', Rule::in(app(InquiryOptionsService::class)->visibleEducationLevelSlugs())],
                'field_of_study' => ['nullable', 'string', 'max:150'],
                'years_of_experience' => ['required', 'string', 'max:50'],
                'languages' => ['required', 'string', 'max:255'],
                'career_goals' => ['nullable', 'string', 'max:1000'],
                'qualification_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
                'id_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ]);
        }

        if ($type === 'recruiter') {
            return array_merge($shared, [
                'company_name' => ['required', 'string', 'max:150'],
                'industry' => ['required', 'string', 'max:100'],
                'company_size' => ['nullable', 'string', 'max:50'],
                'website' => ['nullable', 'url', 'max:255'],
                'contact_name' => ['required', 'string', 'max:100'],
                'designation' => ['required', 'string', 'max:100'],
                'roles_hiring' => ['required', 'string', 'max:255'],
                'positions_count' => ['required', 'string', 'max:50'],
                'required_skills' => ['required', 'string', 'max:500'],
                'preferred_locations' => ['nullable', 'string', 'max:255'],
                'heard_about' => ['nullable', 'string', 'max:255'],
                'hiring_timeline' => ['nullable', 'string', 'max:100'],
                'additional_notes' => ['nullable', 'string', 'max:1000'],
            ]);
        }

        return $shared;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => PhoneNumber::normalize(
                    is_string($this->input('phone')) ? $this->input('phone') : null,
                ),
            ]);
        }
    }
}
