<?php

namespace App\Services;

use App\Models\User;

class RegistrationAccessService
{
    public function extendAccess(User $user, int $durationMonths, ?int $planId = null): void
    {
        $user->refresh();

        $expiresAt = $user->registration_expires_at;

        $base = $expiresAt !== null && $expiresAt->isFuture()
            ? $expiresAt
            : now();

        $user->forceFill([
            'registration_paid_at' => now(),
            'registration_expires_at' => $base->copy()->addMonths($durationMonths),
            'registration_plan_id' => $planId,
        ])->save();
    }
}
