<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\BatchTransferService;
use App\Services\CvTranslationService;
use App\Support\CvProfilePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class StudentProfileController extends Controller
{
    public function index(): Response
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        $query = StudentProfile::query()
            ->with('user:id,name,email,student_number,first_name,last_name');

        if ($viewer->isTeacher()) {
            $query->whereHas('user.batchEnrollment.batch', function ($q) use ($viewer): void {
                $q->where('teacher_id', $viewer->id);
            });
        }

        $profiles = $query
            ->latest()
            ->get()
            ->map(fn (StudentProfile $profile) => [
                'id' => $profile->id,
                'name' => $profile->user->name,
                'email' => $profile->user->email,
                'student_number' => $profile->user->student_number,
                'headline' => $profile->headline,
                'photo_url' => $this->publicStorageUrl(
                    $profile->passport_photo_path ?: $profile->full_body_photo_path,
                ),
            ]);

        return Inertia::render('app/recruiter/students/index', [
            'profiles' => $profiles,
        ]);
    }

    public function show(
        StudentProfile $studentProfile,
        BatchTransferService $transfers,
    ): Response {
        /** @var User $viewer */
        $viewer = Auth::user();

        $this->ensureTeacherCanAccess($viewer, $studentProfile);

        $studentProfile->load('user.batchEnrollment.batch.teacher', 'user.batchEnrollment.batch.center');

        $enrollment = $studentProfile->user->batchEnrollment;
        $batch = $enrollment?->batch;

        $assignment = null;
        $transferTargets = [];

        if ($batch !== null) {
            $assignment = [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'center_name' => $batch->center?->name,
                'language' => $batch->language->value,
                'language_label' => $batch->language->label(),
                'level' => $batch->level->value,
                'teacher_name' => $batch->teacher?->name,
            ];

            if ($viewer->isTeacher()) {
                $transferTargets = $transfers->eligibleTargets($batch);
            }
        }

        return Inertia::render('app/recruiter/students/show', [
            'cv' => CvProfilePresenter::present($studentProfile->user, $studentProfile),
            'profileId' => $studentProfile->id,
            'assignment' => $assignment,
            'transferTargets' => $transferTargets,
            'canTransfer' => $viewer->isTeacher() && $assignment !== null,
        ]);
    }

    public function translate(
        Request $request,
        StudentProfile $studentProfile,
        CvTranslationService $translations,
    ): JsonResponse {
        /** @var User $viewer */
        $viewer = Auth::user();

        $this->ensureTeacherCanAccess($viewer, $studentProfile);

        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:de'],
        ]);

        $studentProfile->load('user');

        try {
            $cv = $translations->translateForPrint(
                $studentProfile->user,
                $studentProfile,
                $validated['locale'],
            );
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();

            if (str_contains($message, 'not configured')) {
                abort(422, 'German CV translation is not configured.');
            }

            abort(502, 'Unable to prepare the German CV right now. Please try again.');
        }

        return response()->json([
            'cv' => $cv,
        ]);
    }

    private function ensureTeacherCanAccess(User $viewer, StudentProfile $studentProfile): void
    {
        if (! $viewer->isTeacher()) {
            return;
        }

        $ownsStudent = BatchEnrollment::query()
            ->where('student_id', $studentProfile->user_id)
            ->whereHas('batch', fn ($q) => $q->where('teacher_id', $viewer->id))
            ->exists();

        if (! $ownsStudent) {
            abort(403);
        }
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}
