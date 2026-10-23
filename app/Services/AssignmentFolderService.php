<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\AssignmentFolder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AssignmentFolderService
{
    public function __construct(
        private readonly AssignmentService $assignments,
    ) {}

    public function create(User $actor, string $name): AssignmentFolder
    {
        $this->assertIsTrainingManager($actor);

        return AssignmentFolder::query()->create([
            'name' => $name,
            'created_by' => $actor->id,
            'sort_order' => (int) AssignmentFolder::query()->max('sort_order') + 1,
        ]);
    }

    public function rename(User $actor, AssignmentFolder $folder, string $name): AssignmentFolder
    {
        $this->assertIsTrainingManager($actor);

        $folder->update(['name' => $name]);

        return $folder->fresh();
    }

    public function delete(User $actor, AssignmentFolder $folder): void
    {
        $this->assertIsTrainingManager($actor);

        Assignment::query()
            ->where('folder_id', $folder->id)
            ->update(['folder_id' => null]);

        $folder->delete();
    }

    public function moveAssignment(User $actor, Assignment $assignment, ?int $folderId): Assignment
    {
        $this->assertIsTrainingManager($actor);

        if ($folderId !== null) {
            AssignmentFolder::query()->findOrFail($folderId);
        }

        $assignment->update(['folder_id' => $folderId]);

        return $assignment->fresh();
    }

    /**
     * @param  list<AssignmentStatus>|null  $statuses
     * @return Collection<int, array{id: int, name: string, assignments_count: int}>
     */
    public function foldersForIndex(?array $statuses = null, ?User $actor = null, ?int $creatorId = null): Collection
    {
        return AssignmentFolder::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (AssignmentFolder $folder) use ($statuses, $actor, $creatorId): array {
                $countQuery = Assignment::query()->where('folder_id', $folder->id);

                if ($actor !== null) {
                    $countQuery = $this->assignments->scopeVisibleTo($countQuery, $actor);
                }

                if ($creatorId !== null) {
                    $countQuery->where('created_by', $creatorId);
                }

                if ($statuses !== null) {
                    $countQuery->whereIn('status', $statuses);
                }

                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'assignments_count' => $countQuery->count(),
                ];
            });
    }

    /**
     * @return list<AssignmentStatus>
     */
    public function statusesForTab(string $tab): array
    {
        return match ($tab) {
            'published' => [AssignmentStatus::Published],
            'draft' => [
                AssignmentStatus::Draft,
                AssignmentStatus::Rejected,
                AssignmentStatus::PendingApproval,
            ],
            default => [AssignmentStatus::Published],
        };
    }

    private function assertIsTrainingManager(User $user): void
    {
        if (! $user->isTrainingManager()) {
            throw ValidationException::withMessages([
                'folder' => 'Only training managers can manage assignment folders.',
            ]);
        }
    }
}
