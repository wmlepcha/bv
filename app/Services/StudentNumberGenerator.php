<?php

namespace App\Services;

use App\Enums\SpecialId;
use App\Models\ActivationCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentNumberGenerator
{
    public function generate(string $stateCode, string|SpecialId $specialId, int $registrationYear): string
    {
        $special = $specialId instanceof SpecialId ? $specialId->value : strtoupper($specialId);
        $stateCode = strtoupper($stateCode);
        $prefix = sprintf('%s-%s-%d-', $stateCode, $special, $registrationYear);

        return DB::transaction(function () use ($prefix): string {
            $existing = User::query()
                ->where('student_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->pluck('student_number');

            $maxSequence = 0;

            foreach ($existing as $studentNumber) {
                if (! is_string($studentNumber)) {
                    continue;
                }

                if (preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $studentNumber, $matches) !== 1) {
                    continue;
                }

                $maxSequence = max($maxSequence, (int) $matches[1]);
            }

            $next = $maxSequence + 1;

            return $prefix.str_pad((string) $next, 2, '0', STR_PAD_LEFT);
        });
    }

    public function generateForUser(User $user): string
    {
        if (blank($user->state_code) || blank($user->special_id) || blank($user->registration_year)) {
            throw new \InvalidArgumentException(
                'Cannot generate candidate ID without state_code, special_id, and registration_year.'
            );
        }

        return $this->generate(
            $user->state_code,
            $user->special_id instanceof SpecialId ? $user->special_id : (string) $user->special_id,
            (int) $user->registration_year,
        );
    }

    public function fromActivationCode(ActivationCode $activationCode): string
    {
        return $activationCode->student_number;
    }
}
