<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AuthenticateUser
{
    public function __invoke(Request $request): ?User
    {
        $user = User::query()
            ->where(Fortify::username(), $request->{Fortify::username()})
            ->first();

        if ($user === null || ! Hash::check($request->password, $user->password)) {
            return null;
        }

        $portal = $request->input('portal', 'student');

        if ($portal === 'training_manager') {
            if (! $user->isTrainingManager()) {
                throw ValidationException::withMessages([
                    Fortify::username() => $this->wrongPortalMessage($user, 'training_manager'),
                ]);
            }

            return $user;
        }

        if ($portal === 'recruitment_manager') {
            if (! $user->isRecruitmentManager()) {
                throw ValidationException::withMessages([
                    Fortify::username() => $this->wrongPortalMessage($user, 'recruitment_manager'),
                ]);
            }

            return $user;
        }

        if ($user->isTrainingManager()) {
            throw ValidationException::withMessages([
                Fortify::username() => 'This account is for training managers. Please use the training manager login.',
            ]);
        }

        if ($user->isRecruitmentManager()) {
            throw ValidationException::withMessages([
                Fortify::username() => 'This account is for recruitment managers. Please use the recruitment manager login.',
            ]);
        }

        if ($user->isSalesTeam()) {
            throw ValidationException::withMessages([
                Fortify::username() => 'This account is for the sales team. Please sign in at the BV admin login.',
            ]);
        }

        if ($portal === 'student' && ! $user->isStudent()) {
            throw ValidationException::withMessages([
                Fortify::username() => $this->wrongPortalMessage($user, 'student'),
            ]);
        }

        if ($portal === 'staff' && ! $user->isStaff()) {
            throw ValidationException::withMessages([
                Fortify::username() => $this->wrongPortalMessage($user, 'staff'),
            ]);
        }

        if ($portal === 'teacher' && ! $user->isTeacher()) {
            throw ValidationException::withMessages([
                Fortify::username() => $this->wrongPortalMessage($user, 'teacher'),
            ]);
        }

        return $user;
    }

    private function wrongPortalMessage(User $user, string $attemptedPortal): string
    {
        if ($user->isStudent()) {
            return 'This account is registered as a candidate. Please use the candidate login.';
        }

        if ($user->isTeacher()) {
            return 'This account is for trainers or teachers. Please use the trainer/teacher login.';
        }

        if ($user->isTrainingManager()) {
            return 'This account is for training managers. Please use the training manager login.';
        }

        if ($user->isRecruitmentManager()) {
            return 'This account is for recruitment managers. Please use the recruitment manager login.';
        }

        if ($attemptedPortal === 'student') {
            return 'This account is for recruiters or employees. Please use the recruiter/employee login.';
        }

        if ($attemptedPortal === 'teacher') {
            return 'This account is for recruiters or employees. Please use the recruiter/employee login.';
        }

        if ($attemptedPortal === 'recruitment_manager') {
            return 'This account is for recruiters or employees. Please use the recruiter/employee login.';
        }

        if ($attemptedPortal === 'training_manager') {
            if ($user->isTeacher()) {
                return 'This account is for trainers or teachers. Please use the trainer/teacher login.';
            }

            if ($user->isStudent()) {
                return 'This account is registered as a candidate. Please use the candidate login.';
            }

            return 'This account is for recruiters or employees. Please use the recruiter/employee login.';
        }

        return 'This account is for trainers or teachers. Please use the trainer/teacher login.';
    }
}
