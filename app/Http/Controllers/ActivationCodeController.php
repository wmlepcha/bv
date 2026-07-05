<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationFeePaidVia;
use App\Models\ActivationCode;
use App\Models\StudentProfile;
use App\Services\StudentNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivationCodeController extends Controller
{
    public function __construct(
        private readonly StudentNumberGenerator $studentNumberGenerator,
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

        if ($code->email !== null && strcasecmp($code->email, $user->email) !== 0) {
            return back()->withErrors([
                'code' => 'This activation code is not valid for your email address.',
            ]);
        }

        DB::transaction(function () use ($code, $user): void {
            $code->update([
                'used_at' => now(),
                'used_by_user_id' => $user->id,
            ]);

            $user->forceFill([
                'registration_paid_at' => now(),
                'registration_fee_paid_via' => RegistrationFeePaidVia::ActivationCode,
                'student_number' => $this->studentNumberGenerator->fromActivationCode($code),
            ])->save();

            StudentProfile::query()->firstOrCreate([
                'user_id' => $user->id,
            ]);
        });

        return redirect()->route('dashboard');
    }
}
