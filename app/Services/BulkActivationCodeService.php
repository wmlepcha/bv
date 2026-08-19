<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ActivationCode;
use App\Models\User;
use App\Notifications\ActivationCodeIssued;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulkActivationCodeService
{
    public function __construct(
        private readonly ActivationCodeGenerator $activationCodeGenerator,
    ) {}

    /**
     * Generate registration codes for unpaid students who do not already have an unused assigned code.
     *
     * @param  list<int>|null  $userIds  Limit to specific users; null = all unpaid students without an unused code.
     * @return list<array{email: string, candidate_id: string|null, registration_code: string}>
     */
    public function generateForUnpaidStudents(?array $userIds = null): array
    {
        $query = User::query()
            ->where('role', UserRole::Student)
            ->where(function ($query): void {
                $query->whereNull('registration_expires_at')
                    ->orWhere('registration_expires_at', '<=', now());
            });

        if ($userIds !== null) {
            $query->whereIn('id', $userIds);
        }

        /** @var Collection<int, User> $students */
        $students = $query->orderBy('id')->get();

        $results = [];

        foreach ($students as $student) {
            $existing = ActivationCode::query()
                ->where('assigned_user_id', $student->id)
                ->whereNull('used_at')
                ->first();

            if ($existing !== null) {
                $results[] = [
                    'email' => $student->email,
                    'candidate_id' => $student->student_number,
                    'registration_code' => $existing->code,
                ];

                continue;
            }

            $code = DB::transaction(function () use ($student): ActivationCode {
                return ActivationCode::query()->create([
                    'code' => $this->activationCodeGenerator->generate(),
                    'email' => $student->email,
                    'assigned_user_id' => $student->id,
                    'student_number' => $student->student_number,
                    'created_by_user_id' => Auth::id(),
                ]);
            });

            $student->notify((new ActivationCodeIssued($code->code))->afterCommit());

            $results[] = [
                'email' => $student->email,
                'candidate_id' => $student->student_number,
                'registration_code' => $code->code,
            ];
        }

        return $results;
    }
}
