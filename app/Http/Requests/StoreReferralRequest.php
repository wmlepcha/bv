<?php

namespace App\Http\Requests;

use App\Enums\PreferredCountry;
use App\Enums\ReferralInterest;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferralRequest extends FormRequest
{
    /**
     * Letters (any script), spaces, dots, apostrophes and hyphens.
     */
    private const NAME_PATTERN = "/^[\pL\pM][\pL\pM\s.'-]*$/u";

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $name = ['required', 'string', 'min:2', 'max:100', 'regex:'.self::NAME_PATTERN];
        $phone = ['required', 'string', 'max:20', PhoneNumber::rule()];

        return [
            'referrer_name' => $name,
            'referrer_phone' => $phone,
            'referred_name' => $name,
            'referred_phone' => [...$phone, 'different:referrer_phone'],
            'referred_email' => ['nullable', 'string', 'email:rfc', 'max:255'],
            'interest' => ['required', Rule::enum(ReferralInterest::class)],
            'preferred_country' => ['required', Rule::enum(PreferredCountry::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'referrer_name' => 'your name',
            'referrer_phone' => 'your mobile number',
            'referred_name' => 'name of the person you are referring',
            'referred_phone' => 'their mobile number',
            'referred_email' => 'their email',
            'interest' => 'interest',
            'preferred_country' => 'preferred country',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'referrer_name.regex' => 'Please use letters only for your name.',
            'referred_name.regex' => 'Please use letters only for their name.',
            'referred_phone.different' => 'Their mobile number must be different from yours.',
            'interest.required' => 'Please choose what they are interested in.',
            'preferred_country.required' => 'Please choose a preferred country.',
        ];
    }

    /**
     * Collapse whitespace in names, normalise phone numbers (10-digit Indian mobiles get +91) and lowercase the email.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['referrer_name', 'referred_name'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized[$field] = trim((string) preg_replace('/\s+/u', ' ', $value));
            }
        }

        foreach (['referrer_phone', 'referred_phone'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $phone = PhoneNumber::normalize($value);

                if ($phone !== null && preg_match('/^(?:0)?([6-9]\d{9})$/', $phone, $matches) === 1) {
                    $phone = '+91'.$matches[1];
                }

                $normalized[$field] = $phone;
            }
        }

        $email = $this->input('referred_email');

        if (is_string($email)) {
            $normalized['referred_email'] = trim($email) === '' ? null : mb_strtolower(trim($email));
        }

        $this->merge($normalized);
    }
}
