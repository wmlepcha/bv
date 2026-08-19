<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use App\Notifications\TransferredToBatch;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BatchTransferService
{
    public function transfer(User $actor, User $student, Batch $targetBatch): BatchEnrollment
    {
        if (! $actor->isTeacher() && ! $actor->isAdmin()) {
            throw new InvalidArgumentException('Only faculty or admins can transfer students.');
        }

        if (! $student->isStudent()) {
            throw new InvalidArgumentException('Only candidates can be transferred.');
        }

        if (! $targetBatch->is_active) {
            throw new RuntimeException('The target batch is not active.');
        }

        $enrollment = BatchEnrollment::query()
            ->with('batch')
            ->where('student_id', $student->id)
            ->first();

        if ($enrollment === null) {
            throw new RuntimeException('This candidate is not enrolled in a batch.');
        }

        /** @var Batch $sourceBatch */
        $sourceBatch = $enrollment->batch;

        if ($actor->isTeacher() && $sourceBatch->teacher_id !== $actor->id) {
            throw new RuntimeException('You can only transfer candidates from your own batches.');
        }

        if ($targetBatch->id === $sourceBatch->id) {
            throw new RuntimeException('Choose a different batch to transfer to.');
        }

        if (
            $targetBatch->language !== $sourceBatch->language
            || $targetBatch->level !== $sourceBatch->level
        ) {
            throw new RuntimeException(
                'Transfers are only allowed to batches with the same language and level.',
            );
        }

        return DB::transaction(function () use ($enrollment, $sourceBatch, $targetBatch, $actor, $student): BatchEnrollment {
            $enrollment->forceFill([
                'batch_id' => $targetBatch->id,
                'transferred_from_batch_id' => $sourceBatch->id,
                'transferred_by' => $actor->id,
                'transferred_at' => now(),
            ])->save();

            $student->notify((new TransferredToBatch($sourceBatch, $targetBatch))->afterCommit());

            return $enrollment->fresh(['batch.teacher']) ?? $enrollment;
        });
    }

    /**
     * Eligible active batches for transferring a student (same language + level, different batch).
     *
     * @return list<array{id: int, name: string, center_name: string, language: string, level: string, teacher_name: string}>
     */
    public function eligibleTargets(Batch $sourceBatch): array
    {
        return Batch::query()
            ->with(['teacher:id,name', 'center:id,name'])
            ->where('is_active', true)
            ->where('id', '!=', $sourceBatch->id)
            ->where('language', $sourceBatch->language)
            ->where('level', $sourceBatch->level)
            ->orderBy('name')
            ->get()
            ->map(fn (Batch $batch): array => [
                'id' => $batch->id,
                'name' => $batch->name,
                'center_name' => $batch->center?->name ?? '—',
                'language' => $batch->language->value,
                'level' => $batch->level->value,
                'teacher_name' => $batch->teacher?->name ?? 'Unassigned',
            ])
            ->all();
    }
}
