<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\StudentRegistered;
use Illuminate\Auth\Events\Verified;

class SendStudentWelcomeAfterVerification
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->role !== UserRole::Student) {
            return;
        }

        $alreadyWelcomed = $user->notifications()
            ->where('type', StudentRegistered::class)
            ->exists();

        if (! $alreadyWelcomed) {
            $user->notify(new StudentRegistered);
        }
    }
}
