<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Enums\RegistrationPurpose;
use App\Enums\SpecialId;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use App\Rules\TurnstileToken;
use App\Services\StudentNumberGenerator;
use App\Support\IndianStates;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentRegistrationController extends Controller
{
    use PasswordValidationRules;

    public function __construct(
        private readonly StudentNumberGenerator $studentNumberGenerator,
    ) {}

    public function create(): Response
    {
        return Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'portal' => 'student',
            'turnstileSiteKey' => config('services.turnstile.site_key'),
            'formToken' => Crypt::encryptString((string) now()->timestamp),
            'states' => IndianStates::names(),
            'purposes' => collect(RegistrationPurpose::cases())
                ->map(fn (RegistrationPurpose $purpose): array => [
                    'value' => $purpose->value,
                    'label' => $purpose->label(),
                ])
                ->values()
                ->all(),
            'specialIds' => collect(SpecialId::cases())
                ->map(fn (SpecialId $id): array => [
                    'value' => $id->value,
                    'label' => $id->label(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $input = request()->all();

        $this->rejectLikelyBot($input);

        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\'.\-]+$/u', 'not_regex:/[<>]/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\'.\-]+$/u', 'not_regex:/[<>]/'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'state' => ['required', 'string', Rule::in(IndianStates::names())],
            'special_id' => ['required', 'string', Rule::enum(SpecialId::class)],
            'registration_purpose' => ['required', 'string', Rule::enum(RegistrationPurpose::class)],
            'cf-turnstile-response' => filled(config('services.turnstile.secret')) ? ['required', new TurnstileToken] : [],
        ], [
            'cf-turnstile-response.required' => 'Please complete the security check and try again.',
        ])->validate();

        $stateCode = IndianStates::codeForName($input['state']);
        $specialId = SpecialId::from($input['special_id']);
        $registrationYear = (int) now()->format('Y');

        $firstName = trim($input['first_name']);
        $lastName = trim($input['last_name']);

        $user = User::create([
            'name' => trim("{$firstName} {$lastName}"),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $user->forceFill([
            'role' => UserRole::Student,
            'state_code' => $stateCode,
            'special_id' => $specialId,
            'registration_year' => $registrationYear,
            'student_number' => $this->studentNumberGenerator->generate(
                $stateCode,
                $specialId,
                $registrationYear,
            ),
            'registration_paid_at' => null,
        ])->save();

        StudentProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['registration_purpose' => RegistrationPurpose::from($input['registration_purpose'])],
        );

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    /**
     * Honeypot field must stay empty and the form must have been open for a few seconds.
     *
     * @param  array<string, mixed>  $input
     */
    private function rejectLikelyBot(array $input): void
    {
        $filledHoneypot = filled($input['website'] ?? null);

        try {
            $renderedAt = (int) Crypt::decryptString((string) ($input['form_token'] ?? ''));
        } catch (DecryptException) {
            $renderedAt = now()->getTimestamp();
        }

        if ($filledHoneypot || now()->getTimestamp() - $renderedAt < 3) {
            throw ValidationException::withMessages([
                'email' => 'We could not process your registration. Please reload the page and try again.',
            ]);
        }
    }
}
