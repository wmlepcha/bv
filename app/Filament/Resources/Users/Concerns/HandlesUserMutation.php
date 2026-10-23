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
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);

        $user->forceFill([
            'role' => $role,
            'registration_paid_at' => $role !== UserRole::Student ? now() : null,
            'email_verified_at' => now(),
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

        if (array_key_exists('whatsapp_number', $data)) {
            $record->whatsapp_number = $data['whatsapp_number'];
        }

        if (! empty($data['password'])) {
            $record->password = $data['password'];
        }

        $record->save();

        return $record;
    }
}
