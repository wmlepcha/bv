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

        if ($portal === 'staff' && $user->isStudent()) {
            throw ValidationException::withMessages([
                Fortify::username() => 'This account is registered as a candidate. Please use the candidate login.',
            ]);
        }

        if ($portal === 'student' && $user->isStaff()) {
            throw ValidationException::withMessages([
                Fortify::username() => 'This account is for recruiters or employees. Please use the recruiter/employee login.',
            ]);
        }

        return $user;
    }
}
