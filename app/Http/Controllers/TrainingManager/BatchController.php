<?php

namespace App\Http\Controllers\TrainingManager;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingManager\StoreBatchRequest;
use App\Http\Requests\TrainingManager\ToggleBatchActiveRequest;
use App\Http\Requests\TrainingManager\UpdateBatchRequest;
use App\Models\Batch;
use App\Models\Center;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BatchController extends Controller
{
    public function index(): Response
    {
        $batches = Batch::query()
            ->with(['center:id,name', 'teacher:id,name,email'])
            ->withCount('enrollments')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Batch $batch): array => [
                'id' => $batch->id,
                'name' => $batch->name,
                'center_name' => $batch->center?->name,
                'language' => $batch->language->value,
                'language_label' => $batch->language->label(),
                'level' => $batch->level->value,
                'teacher_name' => $batch->teacher?->name,
                'teacher_id' => $batch->teacher_id,
                'is_active' => $batch->is_active,
                'enrollments_count' => $batch->enrollments_count,
            ]);

        return Inertia::render('app/training-manager/batches/index', [
            'batches' => $batches,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('app/training-manager/batches/create', [
            'formOptions' => $this->formOptions(),
        ]);
    }

    public function store(StoreBatchRequest $request): RedirectResponse
    {
        $batch = Batch::query()->create($request->validated());

        return redirect()
            ->route('training-manager.batches.students', $batch)
            ->with('success', 'Batch created. Enroll students next.');
    }

    public function edit(Batch $batch): Response
    {
        return Inertia::render('app/training-manager/batches/edit', [
            'batch' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'center_id' => $batch->center_id,
                'language' => $batch->language->value,
                'level' => $batch->level->value,
                'teacher_id' => $batch->teacher_id,
                'is_active' => $batch->is_active,
            ],
            'formOptions' => $this->formOptions($batch),
        ]);
    }

    public function update(UpdateBatchRequest $request, Batch $batch): RedirectResponse
    {
        $batch->update($request->validated());

        return redirect()
            ->route('training-manager.batches.index')
            ->with('success', 'Batch updated.');
    }

    public function toggleActive(ToggleBatchActiveRequest $request, Batch $batch): RedirectResponse
    {
        $batch->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('training-manager.batches.index')
            ->with(
                'success',
                $batch->is_active ? 'Batch activated.' : 'Batch deactivated.',
            );
    }

    public function destroy(Batch $batch): RedirectResponse
    {
        $batch->delete();

        return redirect()
            ->route('training-manager.batches.index')
            ->with('success', 'Batch deleted.');
    }

    /**
     * @return array{
     *     centers: list<array{id: int, name: string}>,
     *     languages: list<array{value: string, label: string}>,
     *     levels: list<array{value: string, label: string}>,
     *     trainers: list<array{id: int, name: string}>
     * }
     */
    private function formOptions(?Batch $batch = null): array
    {
        $centers = Center::query()
            ->where(function ($query) use ($batch): void {
                $query->where('is_active', true);

                if ($batch?->center_id !== null) {
                    $query->orWhere('id', $batch->center_id);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Center $center): array => [
                'id' => $center->id,
                'name' => $center->name,
            ])
            ->values()
            ->all();

        $trainers = User::query()
            ->where('role', UserRole::Teacher)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $trainer): array => [
                'id' => $trainer->id,
                'name' => $trainer->name,
            ])
            ->values()
            ->all();

        return [
            'centers' => $centers,
            'languages' => collect(CourseLanguage::cases())
                ->map(fn (CourseLanguage $language): array => [
                    'value' => $language->value,
                    'label' => $language->label(),
                ])
                ->values()
                ->all(),
            'levels' => collect(CourseLevel::cases())
                ->map(fn (CourseLevel $level): array => [
                    'value' => $level->value,
                    'label' => $level->label(),
                ])
                ->values()
                ->all(),
            'trainers' => $trainers,
        ];
    }
}
