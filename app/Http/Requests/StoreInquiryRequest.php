<?php

namespace App\Http\Requests;

use App\Rules\TurnstileToken;
use App\Services\InquiryOptionsService;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInquiryRequest extends FormRequest
{
    private const NAME_PATTERN = '/^[\p{L}\p{M}\s\'.\-]+$/u';

    private const PLACE_PATTERN = '/^[\p{L}\p{M}\p{N}\s\'.,\-()]+$/u';

    /**
     * Free-text fields: tags are stripped and surrounding whitespace trimmed before validation.
     *
     * @var list<string>
     */
    private const TEXT_FIELDS = [
        'first_name', 'last_name', 'address', 'city', 'country', 'field_of_study', 'languages', 'career_goals',
        'company_name', 'industry', 'contact_name', 'designation', 'roles_hiring', 'required_skills',
        'preferred_locations', 'heard_about', 'additional_notes', 'years_of_experience', 'positions_count',
        'company_size', 'hiring_timeline',
    ];

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
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30', PhoneNumber::rule()],
            // Honeypot: real users never see this field.
            'fax_number' => ['prohibited'],
            'cf-turnstile-response' => filled(config('services.turnstile.secret')) ? ['required', new TurnstileToken] : [],
        ];

        if ($type === 'candidate') {
            return array_merge($shared, [
                'first_name' => ['required', 'string', 'min:2', 'max:100', 'regex:'.self::NAME_PATTERN],
                'last_name' => ['required', 'string', 'min:2', 'max:100', 'regex:'.self::NAME_PATTERN],
                'date_of_birth' => ['required', 'date', 'before:today', 'after:1920-01-01'],
                'address' => ['nullable', 'string', 'min:5', 'max:255'],
                'city' => ['required', 'string', 'min:2', 'max:100', 'regex:'.self::PLACE_PATTERN],
                'country' => ['required', 'string', 'min:2', 'max:100', 'regex:'.self::PLACE_PATTERN],
                'education_level' => ['required', 'string', Rule::in(app(InquiryOptionsService::class)->visibleEducationLevelSlugs())],
                'field_of_study' => ['nullable', 'string', 'min:2', 'max:150'],
                'years_of_experience' => ['required', 'string', Rule::in(['0-1', '1-3', '3-5', '5+'])],
                'languages' => ['required', 'string', 'min:2', 'max:255'],
                'career_goals' => ['nullable', 'string', 'min:10', 'max:1000'],
                'qualification_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
                'id_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ]);
        }

        if ($type === 'recruiter') {
            return array_merge($shared, [
                'company_name' => ['required', 'string', 'min:2', 'max:150'],
                'industry' => ['required', 'string', 'min:2', 'max:100'],
                'company_size' => ['nullable', 'string', Rule::in(['1-10', '11-50', '51-200', '201-500', '500+'])],
                'website' => ['nullable', 'url:http,https', 'max:255'],
                'contact_name' => ['required', 'string', 'min:2', 'max:100', 'regex:'.self::NAME_PATTERN],
                'designation' => ['required', 'string', 'min:2', 'max:100'],
                'roles_hiring' => ['required', 'string', 'min:3', 'max:255'],
                'positions_count' => ['required', 'string', Rule::in(['1-5', '6-15', '16-30', '30+'])],
                'required_skills' => ['required', 'string', 'min:10', 'max:500'],
                'preferred_locations' => ['nullable', 'string', 'min:2', 'max:255'],
                'heard_about' => ['nullable', 'string', 'max:255'],
                'hiring_timeline' => ['nullable', 'string', Rule::in(['immediate', '1-3-months', '3-6-months', 'flexible'])],
                'additional_notes' => ['nullable', 'string', 'min:10', 'max:1000'],
            ]);
        }

        return $shared;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];

        foreach (self::TEXT_FIELDS as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = trim(strip_tags($this->input($field)));
            }
        }

        if (is_string($this->input('email'))) {
            $clean['email'] = mb_strtolower(trim($this->input('email')));
        }

        if ($this->has('phone')) {
            $clean['phone'] = PhoneNumber::normalize(
                is_string($this->input('phone')) ? $this->input('phone') : null,
            );
        }

        $this->merge($clean);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cf-turnstile-response.required' => 'Please complete the security check and try again.',
            'fax_number.prohibited' => 'We could not process your request. Please reload the page and try again.',
        ];
    }
}
