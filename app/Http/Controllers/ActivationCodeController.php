<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationFeePaidVia;
use App\Models\ActivationCode;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Notifications\RegistrationActivated;
use App\Services\RegistrationAccessService;
use App\Services\StudentNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivationCodeController extends Controller
{
    public function __construct(
        private readonly StudentNumberGenerator $studentNumberGenerator,
        private readonly RegistrationAccessService $registrationAccessService,
    ) {}

    public function redeem(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->isStudent() && ! $user->hasPaidRegistration(), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $code = ActivationCode::query()
            ->where('code', $validated['code'])
            ->first();

        if ($code === null) {
            return back()->withErrors([
                'code' => 'This activation code is invalid.',
            ]);
        }

        if ($code->isUsed()) {
            return back()->withErrors([
                'code' => 'This activation code has already been used.',
            ]);
        }

        if ($code->assigned_user_id === null || $code->assigned_user_id !== $user->id) {
            return back()->withErrors([
                'code' => 'This activation code is not valid for your account.',
            ]);
        }

        if ($code->email !== null && strcasecmp($code->email, $user->email) !== 0) {
            return back()->withErrors([
                'code' => 'This activation code is not valid for your email address.',
            ]);
        }

        $twoYearPlan = Plan::query()
            ->active()
            ->where('duration_months', 24)
            ->ordered()
            ->first();

        $isRenewal = $user->registration_paid_at !== null
            || $user->registration_expires_at !== null;

        DB::transaction(function () use ($code, $user, $twoYearPlan): void {
            $code->update([
                'used_at' => now(),
                'used_by_user_id' => $user->id,
            ]);

            $studentNumber = $user->student_number
                ?? $code->student_number;

            if (blank($studentNumber) && filled($user->state_code) && filled($user->special_id) && filled($user->registration_year)) {
                $studentNumber = $this->studentNumberGenerator->generateForUser($user);
            }

            if (filled($studentNumber)) {
                $user->forceFill(['student_number' => $studentNumber])->save();
            }

            $user->forceFill([
                'registration_fee_paid_via' => RegistrationFeePaidVia::ActivationCode,
            ])->save();

            $this->registrationAccessService->extendAccess(
                $user,
                24,
                $twoYearPlan?->id,
            );

            StudentProfile::query()->firstOrCreate([
                'user_id' => $user->id,
            ]);
        });

        $user = $user->fresh();

        $user->notify((new RegistrationActivated(
            RegistrationFeePaidVia::ActivationCode,
            null,
            $isRenewal,
            $user->registration_expires_at?->toIso8601String(),
        ))->afterCommit());

        return redirect()->route('dashboard');
    }
}
