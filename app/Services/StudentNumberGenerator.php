<?php

namespace App\Services;

use App\Models\ActivationCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentNumberGenerator
{
    public function generate(): string
    {
        return DB::transaction(function (): string {
            $sequence = User::query()
                ->whereNotNull('student_number')
                ->count() + 1;

            $year = now()->format('Y');

            return sprintf('%03d-%s-%04d', $sequence % 1000, $year, $sequence);
        });
    }

    public function fromActivationCode(ActivationCode $activationCode): string
    {
        return $activationCode->student_number;
    }
}
