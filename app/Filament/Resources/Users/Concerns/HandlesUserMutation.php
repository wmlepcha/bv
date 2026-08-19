<?php

namespace App\Filament\Resources\Users\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\WelcomeWithCredentials;

trait HandlesUserMutation
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function createUserWithRole(array $data, UserRole $role): User
    {
        /** @var User $user */
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->forceFill([
            'role' => $role,
            'registration_paid_at' => $role !== UserRole::Student ? now() : null,
            'email_verified_at' => $role !== UserRole::Student ? now() : null,
        ])->save();

        $user->notify(new WelcomeWithCredentials(
            plainPassword: (string) $data['password'],
            role: $role,
        ));

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function updateUserIdentity(User $record, array $data): User
    {
        $record->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (! empty($data['password'])) {
            $record->password = $data['password'];
        }

        $record->save();

        return $record;
    }
}
