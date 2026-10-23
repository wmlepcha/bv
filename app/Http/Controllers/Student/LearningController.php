<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningItem;
use App\Models\MaterialFile;
use App\Services\MaterialAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningController extends Controller
{
    public function __construct(
        private readonly MaterialAssignmentService $assignments,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $items = $user->learningItems()
            ->with('materialFile.folder')
            ->latest()
            ->get()
            ->filter(fn (LearningItem $item): bool => $item->materialFile !== null)
            ->map(fn (LearningItem $item): array => [
                'id' => $item->id,
                'note' => $item->note,
                'added_at' => $item->created_at?->toIso8601String(),
                'material' => $this->assignments->presentStudentFile($item->materialFile),
            ])
            ->values()
            ->all();

        return Inertia::render('app/student/my-learning/index', [
            'items' => $items,
            'openItemId' => $request->integer('item') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'material_file_id' => ['required', 'integer', 'exists:material_files,id'],
        ]);

        $file = MaterialFile::findOrFail($validated['material_file_id']);

        abort_unless($this->assignments->studentCanAccessFile($user, $file), 403);

        LearningItem::query()->firstOrCreate([
            'user_id' => $user->id,
            'material_file_id' => $file->id,
        ]);

        return back();
    }

    public function destroy(Request $request, int $learningItem): RedirectResponse
    {
        $request->user()->learningItems()->whereKey($learningItem)->firstOrFail()->delete();

        return back();
    }

    public function updateNote(Request $request, int $learningItem): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $item = $request->user()->learningItems()->whereKey($learningItem)->firstOrFail();
        $item->update(['note' => $validated['note'] ?? null]);

        return back();
    }
}
