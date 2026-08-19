<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\BatchTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BatchTransferController extends Controller
{
    public function store(
        Request $request,
        StudentProfile $studentProfile,
        BatchTransferService $transfers,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = Auth::user();

        if (! $actor->isTeacher()) {
            abort(403);
        }

        $validated = $request->validate([
            'target_batch_id' => ['required', 'integer', 'exists:batches,id'],
        ]);

        $studentProfile->load('user');

        $ownsStudent = BatchEnrollment::query()
            ->where('student_id', $studentProfile->user_id)
            ->whereHas('batch', fn ($q) => $q->where('teacher_id', $actor->id))
            ->exists();

        if (! $ownsStudent) {
            abort(403);
        }

        $targetBatch = Batch::query()->findOrFail($validated['target_batch_id']);

        try {
            $transfers->transfer($actor, $studentProfile->user, $targetBatch);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'target_batch_id' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('recruiter.students.index')
            ->with('success', 'Candidate transferred successfully.');
    }
}
