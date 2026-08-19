<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use App\Notifications\EnrolledInBatch;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BatchEnrollmentService
{
    public function enroll(Batch $batch, User $student): BatchEnrollment
    {
        if (! $student->isStudent()) {
            throw new InvalidArgumentException('Only candidates can be enrolled in a batch.');
        }

        $existing = BatchEnrollment::query()
            ->where('student_id', $student->id)
            ->first();

        if ($existing !== null) {
            if ($existing->batch_id === $batch->id) {
                return $existing;
            }

            throw new RuntimeException(
                'This candidate is already enrolled in another batch. Transfer or remove them first.',
            );
        }

        $enrollment = BatchEnrollment::query()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $student->notify(new EnrolledInBatch($batch));

        return $enrollment;
    }

    /**
     * @param  list<int|string>  $studentIds
     * @return array{enrolled: int, failed: list<string>}
     */
    public function enrollMany(Batch $batch, array $studentIds): array
    {
        $enrolled = 0;
        $failed = [];

        $uniqueIds = array_values(array_unique(array_map('intval', $studentIds)));

        if ($uniqueIds === []) {
            return ['enrolled' => 0, 'failed' => []];
        }

        $students = User::query()
            ->whereIn('id', $uniqueIds)
            ->get()
            ->keyBy('id');

        foreach ($uniqueIds as $studentId) {
            $student = $students->get($studentId);

            if ($student === null) {
                $failed[] = "Candidate #{$studentId} was not found.";

                continue;
            }

            try {
                $beforeCount = BatchEnrollment::query()
                    ->where('batch_id', $batch->id)
                    ->where('student_id', $student->id)
                    ->exists();

                $this->enroll($batch, $student);

                if (! $beforeCount) {
                    $enrolled++;
                }
            } catch (Throwable $exception) {
                $failed[] = sprintf(
                    '%s: %s',
                    $this->candidateLabel($student),
                    $exception->getMessage(),
                );
            }
        }

        return [
            'enrolled' => $enrolled,
            'failed' => $failed,
        ];
    }

    public function detach(BatchEnrollment $enrollment): void
    {
        $enrollment->delete();
    }

    /**
     * Candidates who are not yet enrolled in any batch.
     *
     * @return Builder<User>
     */
    public function unenrolledStudentsQuery(?string $search = null): Builder
    {
        $query = User::query()
            ->where('role', UserRole::Student)
            ->whereDoesntHave('batchEnrollment')
            ->orderBy('name');

        if (filled($search)) {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('student_number', 'like', $term);
            });
        }

        return $query;
    }

    public function candidateLabel(User $user): string
    {
        return sprintf(
            '%s%s — %s',
            $user->name,
            $user->student_number ? " ({$user->student_number})" : '',
            $user->email,
        );
    }

    /**
     * @return array<int, string>
     */
    public function unenrolledStudentOptions(?string $search = null, int $limit = 50): array
    {
        return $this->unenrolledStudentsQuery($search)
            ->limit($limit)
            ->get(['id', 'name', 'email', 'student_number'])
            ->mapWithKeys(fn (User $user): array => [
                $user->id => $this->candidateLabel($user),
            ])
            ->all();
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int, string>
     */
    public function candidateOptionLabels(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'email', 'student_number'])
            ->mapWithKeys(fn (User $user): array => [
                $user->id => $this->candidateLabel($user),
            ])
            ->all();
    }
}
