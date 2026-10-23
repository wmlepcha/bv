<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\MaterialAssignment;
use App\Models\MaterialBatchAssignment;
use App\Models\MaterialFile;
use App\Models\MaterialFolder;
use App\Models\User;
use App\Notifications\MaterialAssignedToTrainer;
use App\Notifications\MaterialSharedWithBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class MaterialAssignmentService
{
    /**
     * @param  list<int>  $trainerIds
     */
    public function assignFolder(User $actor, MaterialFolder $folder, array $trainerIds): void
    {
        $this->syncAssignments($actor, $folder, $trainerIds);
    }

    /**
     * @param  list<int>  $trainerIds
     */
    public function assignFile(User $actor, MaterialFile $file, array $trainerIds): void
    {
        $this->syncAssignments($actor, $file, $trainerIds);
    }

    /**
     * Share a folder with batches (students in those batches), not individual students.
     *
     * @param  list<int>  $batchIds
     */
    public function shareFolderWithBatches(User $actor, MaterialFolder $folder, array $batchIds): void
    {
        $this->assertCanShare($actor, $folder);
        $this->syncBatchAssignments($actor, $folder, $batchIds);
    }

    /**
     * Share a file with batches (students in those batches), not individual students.
     *
     * @param  list<int>  $batchIds
     */
    public function shareFileWithBatches(User $actor, MaterialFile $file, array $batchIds): void
    {
        $this->assertCanShare($actor, $file);
        $this->syncBatchAssignments($actor, $file, $batchIds);
    }

    /**
     * Batches the actor may share materials with.
     * Teachers only see batches where they are the assigned faculty.
     *
     * @return list<array{id: int, name: string}>
     */
    public function shareableBatchesFor(User $actor): array
    {
        if ($actor->isTeacher()) {
            return $actor->teachingBatches()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Batch $batch): array => [
                    'id' => $batch->id,
                    'name' => $batch->name,
                ])
                ->all();
        }

        if ($actor->isTrainingManager()) {
            return Batch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Batch $batch): array => [
                    'id' => $batch->id,
                    'name' => $batch->name,
                ])
                ->all();
        }

        return [];
    }

    /**
     * @return list<int>
     */
    public function sharedBatchIds(MaterialFolder|MaterialFile $assignable): array
    {
        return MaterialBatchAssignment::query()
            ->where('assignable_type', $assignable::class)
            ->where('assignable_id', $assignable->id)
            ->pluck('batch_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function trainerCanAccessFile(User $trainer, MaterialFile $file): bool
    {
        if (! $trainer->isTeacher()) {
            return false;
        }

        if ($this->hasDirectAssignment($file, $trainer->id)) {
            return true;
        }

        $folder = $file->folder()->with('parent')->first();

        while ($folder !== null) {
            if ($this->hasDirectAssignment($folder, $trainer->id)) {
                return true;
            }

            $folder = $folder->parent;
        }

        return false;
    }

    public function trainerCanAccessFolder(User $trainer, MaterialFolder $folder): bool
    {
        if (! $trainer->isTeacher()) {
            return false;
        }

        $current = $folder->loadMissing('parent');

        while ($current !== null) {
            if ($this->hasDirectAssignment($current, $trainer->id)) {
                return true;
            }

            $current = $current->parent;
        }

        return false;
    }

    public function studentCanAccessFolder(User $student, MaterialFolder $folder): bool
    {
        $batchId = $this->studentBatchId($student);

        if ($batchId === null) {
            return false;
        }

        $current = $folder->loadMissing('parent');

        while ($current !== null) {
            if ($this->hasDirectBatchAssignment($current, $batchId)) {
                return true;
            }

            $current = $current->parent;
        }

        return false;
    }

    public function studentCanAccessFile(User $student, MaterialFile $file): bool
    {
        $batchId = $this->studentBatchId($student);

        if ($batchId === null) {
            return false;
        }

        if ($this->hasDirectBatchAssignment($file, $batchId)) {
            return true;
        }

        $folder = $file->folder()->with('parent')->first();

        while ($folder !== null) {
            if ($this->hasDirectBatchAssignment($folder, $batchId)) {
                return true;
            }

            $folder = $folder->parent;
        }

        return false;
    }

    /**
     * @return array{enrolled: bool, folders: list<array<string, mixed>>, files: list<array<string, mixed>>}
     */
    public function materialsForStudent(User $student): array
    {
        $batchId = $this->studentBatchId($student);

        if ($batchId === null) {
            return [
                'enrolled' => false,
                'folders' => [],
                'files' => [],
            ];
        }

        $folderAssignments = MaterialBatchAssignment::query()
            ->where('batch_id', $batchId)
            ->where('assignable_type', MaterialFolder::class)
            ->with('assignable')
            ->get();

        $fileAssignments = MaterialBatchAssignment::query()
            ->where('batch_id', $batchId)
            ->where('assignable_type', MaterialFile::class)
            ->with(['assignable.folder'])
            ->get();

        /** @var Collection<int, MaterialFolder> $assignedFolders */
        $assignedFolders = $folderAssignments
            ->map(fn (MaterialBatchAssignment $assignment) => $assignment->assignable)
            ->filter()
            ->unique('id')
            ->values();

        $assignedFolderIds = $assignedFolders->pluck('id')->all();

        $files = $fileAssignments
            ->map(fn (MaterialBatchAssignment $assignment) => $assignment->assignable)
            ->filter(function (?MaterialFile $file) use ($assignedFolderIds): bool {
                if ($file === null) {
                    return false;
                }

                $folder = $file->folder;

                while ($folder !== null) {
                    if (in_array($folder->id, $assignedFolderIds, true)) {
                        return false;
                    }
                    $folder = $folder->parent;
                }

                return true;
            })
            ->unique('id')
            ->values();

        return [
            'enrolled' => true,
            'folders' => $assignedFolders->map(fn (MaterialFolder $folder): array => [
                'id' => $folder->id,
                'name' => $folder->name,
                'type' => 'folder',
            ])->all(),
            'files' => $files->map(fn (MaterialFile $file): array => $this->presentStudentFile($file))->all(),
        ];
    }

    /**
     * @return array{folders: list<array<string, mixed>>, files: list<array<string, mixed>>}
     */
    public function materialsForTrainer(User $trainer): array
    {
        $folderAssignments = MaterialAssignment::query()
            ->where('trainer_id', $trainer->id)
            ->where('assignable_type', MaterialFolder::class)
            ->with('assignable')
            ->get();

        $fileAssignments = MaterialAssignment::query()
            ->where('trainer_id', $trainer->id)
            ->where('assignable_type', MaterialFile::class)
            ->with(['assignable.folder'])
            ->get();

        /** @var Collection<int, MaterialFolder> $assignedFolders */
        $assignedFolders = $folderAssignments
            ->map(fn (MaterialAssignment $assignment) => $assignment->assignable)
            ->filter()
            ->unique('id')
            ->values();

        $assignedFolderIds = $assignedFolders->pluck('id')->all();

        $files = $fileAssignments
            ->map(fn (MaterialAssignment $assignment) => $assignment->assignable)
            ->filter(function (?MaterialFile $file) use ($assignedFolderIds): bool {
                if ($file === null) {
                    return false;
                }

                // Hide files already covered by an assigned ancestor folder.
                $folder = $file->folder;

                while ($folder !== null) {
                    if (in_array($folder->id, $assignedFolderIds, true)) {
                        return false;
                    }
                    $folder = $folder->parent;
                }

                return true;
            })
            ->unique('id')
            ->values();

        return [
            'folders' => $assignedFolders->map(fn (MaterialFolder $folder): array => [
                'id' => $folder->id,
                'name' => $folder->name,
                'shared_batch_ids' => $this->sharedBatchIds($folder),
                'type' => 'folder',
            ])->all(),
            'files' => $files->map(fn (MaterialFile $file): array => [
                'id' => $file->id,
                'name' => $file->display_name,
                'extension' => $file->extension,
                'kind' => $file->kind ?? MaterialFile::KIND_FILE,
                'url' => $file->url,
                'embed_url' => $file->embedUrl(),
                'playlist_items' => $file->isYoutubePlaylist()
                    ? ($file->playlist_items ?? [])
                    : null,
                'folder_name' => $file->folder?->name,
                'created_at' => $file->created_at?->toIso8601String(),
                'shared_batch_ids' => $this->sharedBatchIds($file),
                'type' => 'file',
            ])->all(),
        ];
    }

    private function assertCanShare(User $actor, MaterialFolder|MaterialFile $assignable): void
    {
        if ($actor->isTrainingManager()) {
            return;
        }

        if ($actor->isTeacher()) {
            $allowed = $assignable instanceof MaterialFolder
                ? $this->trainerCanAccessFolder($actor, $assignable)
                : $this->trainerCanAccessFile($actor, $assignable);

            if ($allowed) {
                return;
            }
        }

        abort(403);
    }

    /**
     * @param  list<int>  $batchIds
     */
    private function syncBatchAssignments(User $actor, MaterialFolder|MaterialFile $assignable, array $batchIds): void
    {
        $batchIds = array_values(array_unique(array_map('intval', $batchIds)));

        $allowedQuery = Batch::query()->whereIn('id', $batchIds);

        if ($actor->isTeacher()) {
            $allowedQuery->where('teacher_id', $actor->id)->where('is_active', true);
        } elseif (! $actor->isTrainingManager()) {
            throw ValidationException::withMessages([
                'batch_ids' => 'You cannot share materials to batches.',
            ]);
        } else {
            $allowedQuery->where('is_active', true);
        }

        $validCount = $allowedQuery->count();

        if ($validCount !== count($batchIds)) {
            throw ValidationException::withMessages([
                'batch_ids' => 'One or more selected batches are invalid or not assigned to you.',
            ]);
        }

        $existing = MaterialBatchAssignment::query()
            ->where('assignable_type', $assignable::class)
            ->where('assignable_id', $assignable->id)
            ->pluck('batch_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $toAdd = array_diff($batchIds, $existing);
        $toRemove = array_diff($existing, $batchIds);

        if ($toRemove !== []) {
            MaterialBatchAssignment::query()
                ->where('assignable_type', $assignable::class)
                ->where('assignable_id', $assignable->id)
                ->whereIn('batch_id', $toRemove)
                ->delete();
        }

        foreach ($toAdd as $batchId) {
            MaterialBatchAssignment::query()->create([
                'assignable_type' => $assignable::class,
                'assignable_id' => $assignable->id,
                'batch_id' => $batchId,
                'assigned_by' => $actor->id,
            ]);
        }

        if ($toAdd !== []) {
            $this->notifyStudentsOfSharedMaterial($assignable, array_values($toAdd));
        }
    }

    /**
     * @param  list<int>  $trainerIds
     */
    private function syncAssignments(User $actor, MaterialFolder|MaterialFile $assignable, array $trainerIds): void
    {
        $trainerIds = array_values(array_unique(array_map('intval', $trainerIds)));

        $validCount = User::query()
            ->where('role', UserRole::Teacher)
            ->whereIn('id', $trainerIds)
            ->count();

        if ($validCount !== count($trainerIds)) {
            throw ValidationException::withMessages([
                'trainer_ids' => 'One or more selected trainers are invalid.',
            ]);
        }

        $existing = MaterialAssignment::query()
            ->where('assignable_type', $assignable::class)
            ->where('assignable_id', $assignable->id)
            ->pluck('trainer_id')
            ->all();

        $toAdd = array_diff($trainerIds, $existing);
        $toRemove = array_diff($existing, $trainerIds);

        if ($toRemove !== []) {
            MaterialAssignment::query()
                ->where('assignable_type', $assignable::class)
                ->where('assignable_id', $assignable->id)
                ->whereIn('trainer_id', $toRemove)
                ->delete();
        }

        foreach ($toAdd as $trainerId) {
            MaterialAssignment::query()->create([
                'assignable_type' => $assignable::class,
                'assignable_id' => $assignable->id,
                'trainer_id' => $trainerId,
                'assigned_by' => $actor->id,
            ]);
        }

        if ($toAdd !== []) {
            $this->notifyTrainersOfAssignedMaterial($assignable, array_values($toAdd));
        }
    }

    /**
     * @param  list<int>  $batchIds
     */
    private function notifyStudentsOfSharedMaterial(MaterialFolder|MaterialFile $assignable, array $batchIds): void
    {
        $title = $this->materialTitle($assignable);
        $type = $assignable instanceof MaterialFolder ? 'folder' : 'item';

        $batches = Batch::query()
            ->whereIn('id', $batchIds)
            ->get()
            ->keyBy('id');

        foreach ($batchIds as $batchId) {
            $batch = $batches->get($batchId);

            if ($batch === null) {
                continue;
            }

            $studentIds = BatchEnrollment::query()
                ->where('batch_id', $batchId)
                ->pluck('student_id')
                ->all();

            if ($studentIds === []) {
                continue;
            }

            $students = User::query()->whereIn('id', $studentIds)->get();

            Notification::send(
                $students,
                new MaterialSharedWithBatch($title, $batch->name, $type),
            );
        }
    }

    /**
     * @param  list<int>  $trainerIds
     */
    private function notifyTrainersOfAssignedMaterial(MaterialFolder|MaterialFile $assignable, array $trainerIds): void
    {
        $trainers = User::query()->whereIn('id', $trainerIds)->get();

        if ($trainers->isEmpty()) {
            return;
        }

        $title = $this->materialTitle($assignable);
        $type = $assignable instanceof MaterialFolder ? 'folder' : 'item';

        Notification::send(
            $trainers,
            new MaterialAssignedToTrainer($title, $type),
        );
    }

    private function materialTitle(MaterialFolder|MaterialFile $assignable): string
    {
        if ($assignable instanceof MaterialFolder) {
            return $assignable->name;
        }

        return $assignable->display_name ?: $assignable->original_name ?: 'Untitled';
    }

    private function hasDirectAssignment(MaterialFolder|MaterialFile $assignable, int $trainerId): bool
    {
        return MaterialAssignment::query()
            ->where('assignable_type', $assignable::class)
            ->where('assignable_id', $assignable->id)
            ->where('trainer_id', $trainerId)
            ->exists();
    }

    private function hasDirectBatchAssignment(MaterialFolder|MaterialFile $assignable, int $batchId): bool
    {
        return MaterialBatchAssignment::query()
            ->where('assignable_type', $assignable::class)
            ->where('assignable_id', $assignable->id)
            ->where('batch_id', $batchId)
            ->exists();
    }

    private function studentBatchId(User $student): ?int
    {
        if (! $student->isStudent()) {
            return null;
        }

        return $student->batchEnrollment?->batch_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function presentStudentFile(MaterialFile $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->display_name,
            'extension' => $file->extension,
            'kind' => $file->kind ?? MaterialFile::KIND_FILE,
            'url' => $file->url,
            'embed_url' => $file->embedUrl(),
            'playlist_items' => $file->isYoutubePlaylist()
                ? ($file->playlist_items ?? [])
                : null,
            'preview_url' => $file->isFile() ? route('student.study-bank.files.preview', $file) : null,
            'folder_name' => $file->folder?->name,
            'created_at' => $file->created_at?->toIso8601String(),
            'type' => 'file',
        ];
    }
}
