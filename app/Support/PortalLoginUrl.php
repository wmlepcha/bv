<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

class PortalLoginUrl
{
    public static function forUser(User $user): string
    {
        return self::forRole($user->role);
    }

    public static function forRole(?UserRole $role): string
    {
        $portal = match ($role) {
            UserRole::Teacher => 'teacher',
            UserRole::TrainingManager => 'training_manager',
            UserRole::Admin, UserRole::Recruiter, UserRole::Employee => 'staff',
            default => 'student',
        };

        if ($portal === 'training_manager') {
            return url('/training-manager');
        }

        return url('/login?portal='.$portal);
    }
}
