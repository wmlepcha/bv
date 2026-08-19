<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Enums\SpecialId;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\StudentRegistered;
use App\Services\StudentNumberGenerator;
use App\Support\IndianStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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
            'states' => IndianStates::names(),
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

        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\'.\-]+$/u', 'not_regex:/[<>]/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\'.\-]+$/u', 'not_regex:/[<>]/'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'state' => ['required', 'string', Rule::in(IndianStates::names())],
            'special_id' => ['required', 'string', Rule::enum(SpecialId::class)],
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

        StudentProfile::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        $user->notify(new StudentRegistered);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
