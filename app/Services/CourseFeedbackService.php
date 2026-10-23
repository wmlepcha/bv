<?php

namespace App\Services;

use App\Models\CourseFeedback;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CourseFeedbackService
{
    /**
     * Store a student's feedback against their earliest open form, snapshotting their current batch, trainer and centre.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException when no form is open or the form was already submitted (e.g. a double submit).
     */
    public function submit(User $student, array $data): CourseFeedback
    {
        $formNumber = $student->openCourseFeedbackFormNumber();

        if ($formNumber === null) {
            throw ValidationException::withMessages([
                'level' => 'There is no feedback form open for you right now.',
            ]);
        }

        $student->loadMissing('batchEnrollment.batch');
        $batch = $student->batchEnrollment?->batch;

        try {
            return CourseFeedback::query()->create([
                ...$data,
                'student_id' => $student->id,
                'form_number' => $formNumber,
                'batch_id' => $batch?->id,
                'teacher_id' => $batch?->teacher_id,
                'center_id' => $batch?->center_id,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'level' => 'You have already submitted this feedback form.',
            ]);
        }
    }
}
