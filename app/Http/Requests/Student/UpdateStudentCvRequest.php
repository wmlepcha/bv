<?php

namespace App\Http\Requests\Student;

use App\Enums\CivilStatus;
use App\Enums\Gender;
use App\Support\CvInputSanitizer;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentCvRequest extends FormRequest
{
    private const NAME_REGEX = '/^[\p{L}\s\'.\-]+$/u';

    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $noAngles = 'not_regex:/[<>]/';
        $plain = ['nullable', 'string', 'max:255', $noAngles];
        $plain100 = ['nullable', 'string', 'max:100', $noAngles];
        $plain50 = ['nullable', 'string', 'max:50', $noAngles];
        $longText = ['nullable', 'string', 'max:2000'];
        $veryLongText = ['nullable', 'string', 'max:5000'];
        $introductionText = ['nullable', 'string', 'max:500'];

        return [
            'first_name' => ['required', 'string', 'max:255', 'regex:'.self::NAME_REGEX, $noAngles],
            'last_name' => ['required', 'string', 'max:255', 'regex:'.self::NAME_REGEX, $noAngles],
            'mobile_whatsapp' => ['nullable', 'string', 'max:50', PhoneNumber::rule()],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'place_of_birth' => $plain,
            'civil_status' => ['nullable', 'string', Rule::in(CivilStatus::values())],
            'gender' => ['nullable', 'string', Rule::in(Gender::values())],
            'secondary_email' => ['nullable', 'email', 'max:255'],
            'secondary_mobile' => ['nullable', 'string', 'max:50', PhoneNumber::rule()],
            'current_address' => $longText,
            'permanent_address' => $longText,
            'nationality' => $plain100,
            'passport_or_id_number' => $plain100,
            'drivers_license' => $plain,
            'introduction' => $introductionText,
            'hobbies' => $longText,
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100', $noAngles],
            'gap_reasons' => $veryLongText,
            'current_status' => $plain,
            'pursuing_details' => $longText,
            'headline' => $plain,

            'passport_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'full_body_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8192'],
            'passport_document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'drivers_license_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],

            'vitae' => ['nullable', 'array'],
            'vitae.is_newbie' => ['sometimes', 'boolean'],

            'vitae.experiences' => ['nullable', 'array'],
            'vitae.experiences.*.duration' => $plain,
            'vitae.experiences.*.start_date' => $plain50,
            'vitae.experiences.*.end_date' => $plain50,
            'vitae.experiences.*.designation' => $plain,
            'vitae.experiences.*.department' => $plain,
            'vitae.experiences.*.organization' => $plain,
            'vitae.experiences.*.city' => $plain,
            'vitae.experiences.*.state' => $plain,
            'vitae.experiences.*.cases_handled' => $veryLongText,
            'vitae.experiences.*.bed_capacity' => $plain,
            'vitae.experiences.*.medical_equipment' => ['nullable', 'string', 'max:1000', $noAngles],
            'vitae.experiences.*.departments_offered' => ['nullable', 'string', 'max:1000', $noAngles],
            'vitae.experiences.*.accreditations' => ['nullable', 'string', 'max:1000', $noAngles],
            'vitae.experiences.*.critical_cases' => $veryLongText,
            'vitae.experiences.*.has_experience_letter' => ['sometimes', 'boolean'],
            'vitae.experiences.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            'vitae.qualifications' => ['nullable', 'array'],
            'vitae.qualifications.*.start_date' => $plain50,
            'vitae.qualifications.*.end_date' => $plain50,
            'vitae.qualifications.*.title' => $plain,
            'vitae.qualifications.*.college' => $plain,
            'vitae.qualifications.*.university' => $plain,
            'vitae.qualifications.*.city' => $plain,
            'vitae.qualifications.*.state' => $plain,
            'vitae.qualifications.*.country' => $plain,
            'vitae.qualifications.*.board' => $plain,
            'vitae.qualifications.*.language_of_instruction' => $plain100,

            'vitae.trainings' => ['nullable', 'array'],
            'vitae.trainings.*.date' => $plain50,
            'vitae.trainings.*.topic' => $plain,
            'vitae.trainings.*.organization_location' => $plain,
            'vitae.trainings.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            'vitae.seminars' => ['nullable', 'array'],
            'vitae.seminars.*.date' => $plain50,
            'vitae.seminars.*.topic' => $plain,
            'vitae.seminars.*.organization' => $plain,
            'vitae.seminars.*.location' => $plain,
            'vitae.seminars.*.evidence_type' => ['nullable', 'string', Rule::in(['file', 'link'])],
            'vitae.seminars.*.link_url' => ['nullable', 'string', 'max:2048', 'url'],
            'vitae.seminars.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            'vitae.presentations' => ['nullable', 'array'],
            'vitae.presentations.*.date' => $plain50,
            'vitae.presentations.*.topic' => $plain,
            'vitae.presentations.*.organization' => $plain,
            'vitae.presentations.*.location' => $plain,
            'vitae.presentations.*.evidence_type' => ['nullable', 'string', Rule::in(['file', 'link'])],
            'vitae.presentations.*.link_url' => ['nullable', 'string', 'max:2048', 'url'],
            'vitae.presentations.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            'vitae.publications' => ['nullable', 'array'],
            'vitae.publications.*.date' => $plain50,
            'vitae.publications.*.topic' => $plain,
            'vitae.publications.*.organization' => $plain,
            'vitae.publications.*.location' => $plain,
            'vitae.publications.*.evidence_type' => ['nullable', 'string', Rule::in(['file', 'link'])],
            'vitae.publications.*.link_url' => ['nullable', 'string', 'max:2048', 'url'],
            'vitae.publications.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            'vitae.certifications' => ['nullable', 'array'],
            'vitae.certifications.*.name' => $plain,
            'vitae.certifications.*.description' => ['nullable', 'string', 'max:255', $noAngles],
            'vitae.certifications.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            'vitae.licenses' => ['nullable', 'array'],
            'vitae.licenses.*.title' => $plain,
            'vitae.licenses.*.registration_number' => $plain,
            'vitae.licenses.*.issued_date' => $plain50,
            'vitae.licenses.*.valid_till' => $plain50,
            'vitae.licenses.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            'vitae.bilingual' => ['nullable', 'array'],
            'vitae.bilingual.*.language' => $plain,
            'vitae.bilingual.*.language_type' => ['nullable', 'string', Rule::in(['Regional', 'Regional/Foreign', 'Foreign Language'])],
            'vitae.bilingual.*.proficiency' => ['nullable', 'string', Rule::in(['Beginner', 'Intermediate', 'Professional', 'Native'])],
            'vitae.bilingual.*.certification_level' => ['nullable', 'string', Rule::in(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])],
            'vitae.bilingual.*.certificates' => ['nullable', 'string', 'max:1000', $noAngles],
            'vitae.bilingual.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'First name may only contain letters, spaces, apostrophes, and hyphens.',
            'last_name.regex' => 'Last name may only contain letters, spaces, apostrophes, and hyphens.',
            'nationality.not_regex' => 'This field cannot contain < or > characters.',
            'first_name.not_regex' => 'This field cannot contain < or > characters.',
            'last_name.not_regex' => 'This field cannot contain < or > characters.',
            'place_of_birth.not_regex' => 'This field cannot contain < or > characters.',
            'passport_or_id_number.not_regex' => 'This field cannot contain < or > characters.',
            'drivers_license.not_regex' => 'This field cannot contain < or > characters.',
            'current_status.not_regex' => 'This field cannot contain < or > characters.',
            'headline.not_regex' => 'This field cannot contain < or > characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $skills = $this->input('skills');

        if (is_string($skills)) {
            $skills = collect(explode(',', $skills))
                ->map(fn (string $skill): string => trim($skill))
                ->filter()
                ->values()
                ->all();

            $this->merge(['skills' => $skills]);
        }

        if ($this->has('vitae.is_newbie')) {
            $this->merge([
                'vitae' => array_merge($this->input('vitae', []), [
                    'is_newbie' => filter_var($this->input('vitae.is_newbie'), FILTER_VALIDATE_BOOLEAN),
                ]),
            ]);
        }

        $payload = $this->except([
            'passport_photo',
            'full_body_photo',
            'passport_document',
            'drivers_license_document',
        ]);

        $sanitized = CvInputSanitizer::sanitize($payload);
        $sanitized = self::blankStringsToNull($sanitized);

        if (array_key_exists('mobile_whatsapp', $sanitized)) {
            $sanitized['mobile_whatsapp'] = PhoneNumber::normalize(
                is_string($sanitized['mobile_whatsapp'] ?? null)
                    ? $sanitized['mobile_whatsapp']
                    : null,
            );
        }

        if (array_key_exists('secondary_mobile', $sanitized)) {
            $sanitized['secondary_mobile'] = PhoneNumber::normalize(
                is_string($sanitized['secondary_mobile'] ?? null)
                    ? $sanitized['secondary_mobile']
                    : null,
            );
        }

        $this->merge($sanitized);
    }

    private static function blankStringsToNull(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];

            foreach ($value as $key => $item) {
                $result[$key] = self::blankStringsToNull($item);
            }

            return $result;
        }

        if (is_string($value) && $value === '') {
            return null;
        }

        return $value;
    }
}
