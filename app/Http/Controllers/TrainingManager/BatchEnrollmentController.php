<?php

namespace App\Http\Controllers\TrainingManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingManager\EnrollBatchStudentsRequest;
use App\Http\Requests\TrainingManager\ImportBatchEnrollmentsRequest;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Services\BatchEnrollmentImportService;
use App\Services\BatchEnrollmentService;
use App\Services\BatchEnrollmentTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BatchEnrollmentController extends Controller
{
    public function __construct(
        private readonly BatchEnrollmentService $enrollments,
        private readonly BatchEnrollmentTemplateService $templates,
        private readonly BatchEnrollmentImportService $imports,
    ) {}

    public function students(Batch $batch): Response
    {
        $batch->load(['center:id,name', 'teacher:id,name']);

        $enrolled = $batch->enrollments()
            ->with(['student:id,name,email,student_number'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (BatchEnrollment $enrollment): array => [
                'id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'name' => $enrollment->student?->name ?? '—',
                'email' => $enrollment->student?->email ?? '—',
                'student_number' => $enrollment->student?->student_number,
                'enrolled_at' => $enrollment->created_at?->toIso8601String(),
            ])
            ->all();

        $candidateOptions = collect($this->enrollments->unenrolledStudentOptions(limit: 200))
            ->map(fn (string $label, int $id): array => [
                'id' => $id,
                'label' => $label,
            ])
            ->values()
            ->all();

        return Inertia::render('app/training-manager/batches/students', [
            'batch' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'center_name' => $batch->center?->name,
                'language_label' => $batch->language->label(),
                'level' => $batch->level->value,
                'teacher_name' => $batch->teacher?->name,
                'is_active' => $batch->is_active,
            ],
            'enrollments' => $enrolled,
            'candidateOptions' => $candidateOptions,
            'importResult' => session('import_result'),
        ]);
    }

    public function searchCandidates(Request $request): JsonResponse
    {
        $search = $request->string('q')->trim()->toString();

        $options = collect($this->enrollments->unenrolledStudentOptions(
            $search !== '' ? $search : null,
            50,
        ))
            ->map(fn (string $label, int $id): array => [
                'id' => $id,
                'label' => $label,
            ])
            ->values()
            ->all();

        return response()->json(['candidates' => $options]);
    }

    public function enroll(EnrollBatchStudentsRequest $request, Batch $batch): RedirectResponse
    {
        $result = $this->enrollments->enrollMany(
            $batch,
            $request->validated('student_ids'),
        );

        $message = "Enrolled {$result['enrolled']} candidate(s).";

        if ($result['failed'] !== []) {
            $message .= ' '.count($result['failed']).' failed: '
                .implode(' ', $result['failed']);
        }

        return redirect()
            ->route('training-manager.batches.students', $batch)
            ->with(
                $result['enrolled'] > 0 ? 'success' : 'error',
                $message,
            );
    }

    public function destroy(Batch $batch, BatchEnrollment $enrollment): RedirectResponse
    {
        abort_unless($enrollment->batch_id === $batch->id, 404);

        $this->enrollments->detach($enrollment);

        return redirect()
            ->route('training-manager.batches.students', $batch)
            ->with('success', 'Student removed from batch.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        return $this->templates->download();
    }

    public function import(ImportBatchEnrollmentsRequest $request, Batch $batch): RedirectResponse
    {
        $file = $request->file('spreadsheet');
        $absolutePath = $file->getRealPath();

        try {
            $importResult = $this->imports->import($batch, $absolutePath);
        } catch (Throwable $exception) {
            return redirect()
                ->route('training-manager.batches.students', $batch)
                ->with('error', 'Import failed: '.$exception->getMessage());
        }

        $enrolled = count($importResult['enrolled']);
        $skipped = count($importResult['skipped']);
        $failed = count($importResult['failed']);

        return redirect()
            ->route('training-manager.batches.students', $batch)
            ->with('success', "Enrollment import finished. Enrolled {$enrolled}, skipped {$skipped}, failed {$failed}.")
            ->with('import_result', $importResult);
    }
}
