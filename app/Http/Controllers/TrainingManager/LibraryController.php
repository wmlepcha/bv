<?php

namespace App\Http\Controllers\TrainingManager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Materials\ShareMaterialWithBatchesRequest;
use App\Http\Requests\TrainingManager\AssignMaterialRequest;
use App\Http\Requests\TrainingManager\RelocateMaterialFileRequest;
use App\Http\Requests\TrainingManager\RelocateMaterialFolderRequest;
use App\Http\Requests\TrainingManager\RenameMaterialFileRequest;
use App\Http\Requests\TrainingManager\StoreMaterialFileRequest;
use App\Http\Requests\TrainingManager\StoreMaterialFolderRequest;
use App\Http\Requests\TrainingManager\StoreMaterialLinkRequest;
use App\Http\Requests\TrainingManager\StoreMaterialYoutubePlaylistRequest;
use App\Http\Requests\TrainingManager\StoreMaterialYoutubeRequest;
use App\Models\MaterialAssignment;
use App\Models\MaterialFile;
use App\Models\MaterialFolder;
use App\Models\User;
use App\Services\MaterialAssignmentService;
use App\Services\MaterialLibraryService;
use App\Services\YoutubePlaylistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibraryController extends Controller
{
    public function __construct(
        private readonly MaterialLibraryService $library,
        private readonly MaterialAssignmentService $assignments,
    ) {}

    public function index(Request $request): Response
    {
        $folderId = $request->integer('folder') ?: null;
        $currentFolder = null;
        $breadcrumbs = [];

        if ($folderId !== null) {
            $currentFolder = MaterialFolder::query()->findOrFail($folderId);
            $breadcrumbs = $this->breadcrumbTrail($currentFolder);
        }

        $folders = MaterialFolder::query()
            ->where('parent_id', $folderId)
            ->withCount(['children', 'files'])
            ->with(['assignments.trainer:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function (MaterialFolder $folder): array {
                $assignedTrainers = $folder->assignments
                    ->map(fn (MaterialAssignment $assignment): ?array => $assignment->trainer === null
                        ? null
                        : [
                            'id' => $assignment->trainer->id,
                            'name' => $assignment->trainer->name,
                        ])
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'children_count' => $folder->children_count,
                    'files_count' => $folder->files_count,
                    'assigned_trainers' => $assignedTrainers,
                    'assigned_trainer_ids' => array_column($assignedTrainers, 'id'),
                    'shared_batch_ids' => $this->assignments->sharedBatchIds($folder),
                ];
            });

        $files = MaterialFile::query()
            ->where('folder_id', $folderId)
            ->when($folderId === null, fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['assignments.trainer:id,name'])
            ->orderBy('display_name')
            ->get()
            ->map(function (MaterialFile $file): array {
                $assignedTrainers = $file->assignments
                    ->map(fn (MaterialAssignment $assignment): ?array => $assignment->trainer === null
                        ? null
                        : [
                            'id' => $assignment->trainer->id,
                            'name' => $assignment->trainer->name,
                        ])
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => $file->id,
                    'display_name' => $file->display_name,
                    'extension' => $file->extension,
                    'kind' => $file->kind ?? MaterialFile::KIND_FILE,
                    'url' => $file->url,
                    'embed_url' => $file->embedUrl(),
                    'playlist_items' => $file->isYoutubePlaylist()
                        ? ($file->playlist_items ?? [])
                        : null,
                    'size' => $file->size,
                    'created_at' => $file->created_at?->toIso8601String(),
                    'assigned_trainers' => $assignedTrainers,
                    'assigned_trainer_ids' => array_column($assignedTrainers, 'id'),
                    'shared_batch_ids' => $this->assignments->sharedBatchIds($file),
                ];
            });

        $trainers = User::query()
            ->where('role', UserRole::Teacher)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $trainer): array => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'email' => $trainer->email,
            ]);

        return Inertia::render('app/training-manager/library/index', [
            'currentFolder' => $currentFolder === null ? null : [
                'id' => $currentFolder->id,
                'name' => $currentFolder->name,
                'parent_id' => $currentFolder->parent_id,
            ],
            'breadcrumbs' => $breadcrumbs,
            'folders' => $folders,
            'files' => $files,
            'folderOptions' => $this->library->folderOptionsTree(),
            'trainers' => $trainers,
            'batches' => $this->assignments->shareableBatchesFor($request->user()),
        ]);
    }

    public function storeFolder(StoreMaterialFolderRequest $request): RedirectResponse
    {
        $folder = $this->library->createFolder(
            $request->user(),
            $request->validated('name'),
            $request->validated('parent_id'),
        );

        return redirect()
            ->route('training-manager.library.index', [
                'folder' => $folder->parent_id,
            ])
            ->with('success', 'Folder created.');
    }

    public function storeFile(StoreMaterialFileRequest $request, MaterialFolder $folder): RedirectResponse
    {
        $this->library->uploadFile(
            $request->user(),
            $folder,
            $request->file('file'),
        );

        return redirect()
            ->route('training-manager.library.index', ['folder' => $folder->id])
            ->with('success', 'File uploaded.');
    }

    public function storeYoutube(StoreMaterialYoutubeRequest $request, MaterialFolder $folder): RedirectResponse
    {
        $this->library->addYoutube(
            $request->user(),
            $folder,
            $request->validated('url'),
            $request->validated('title'),
        );

        return redirect()
            ->route('training-manager.library.index', ['folder' => $folder->id])
            ->with('success', 'YouTube video added.');
    }

    public function storeYoutubePlaylist(StoreMaterialYoutubePlaylistRequest $request, MaterialFolder $folder): RedirectResponse
    {
        $this->library->addYoutubePlaylist(
            $request->user(),
            $folder,
            $request->validated('url'),
            $request->validated('title'),
        );

        return redirect()
            ->route('training-manager.library.index', ['folder' => $folder->id])
            ->with('success', 'YouTube playlist added.');
    }

    public function storeLink(StoreMaterialLinkRequest $request, MaterialFolder $folder): RedirectResponse
    {
        $this->library->addLink(
            $request->user(),
            $folder,
            $request->validated('url'),
            $request->validated('title'),
        );

        return redirect()
            ->route('training-manager.library.index', ['folder' => $folder->id])
            ->with('success', 'Website link added.');
    }

    public function renameFile(RenameMaterialFileRequest $request, MaterialFile $file): RedirectResponse
    {
        $this->library->renameFile($file, $request->validated('display_name'));

        return redirect()
            ->route('training-manager.library.index', ['folder' => $file->folder_id])
            ->with('success', 'File renamed.');
    }

    public function moveFile(RelocateMaterialFileRequest $request, MaterialFile $file): RedirectResponse
    {
        $sourceFolderId = $file->folder_id;
        $destination = MaterialFolder::query()->findOrFail(
            $request->validated('folder_id'),
        );

        $this->library->moveFile($file, $destination);

        return redirect()
            ->route('training-manager.library.index', ['folder' => $sourceFolderId])
            ->with('success', 'Moved.');
    }

    public function copyFile(RelocateMaterialFileRequest $request, MaterialFile $file): RedirectResponse
    {
        $sourceFolderId = $file->folder_id;
        $destination = MaterialFolder::query()->findOrFail(
            $request->validated('folder_id'),
        );

        $this->library->copyFile($request->user(), $file, $destination);

        return redirect()
            ->route('training-manager.library.index', ['folder' => $sourceFolderId])
            ->with('success', 'Copied.');
    }

    public function moveFolder(RelocateMaterialFolderRequest $request, MaterialFolder $folder): RedirectResponse
    {
        $sourceParentId = $folder->parent_id;
        $parentId = $request->validated('parent_id');
        $destination = $parentId === null
            ? null
            : MaterialFolder::query()->findOrFail($parentId);

        $this->library->moveFolder($folder, $destination);

        return redirect()
            ->route('training-manager.library.index', ['folder' => $sourceParentId])
            ->with('success', 'Folder moved.');
    }

    public function copyFolder(RelocateMaterialFolderRequest $request, MaterialFolder $folder): RedirectResponse
    {
        $sourceParentId = $folder->parent_id;
        $parentId = $request->validated('parent_id');
        $destination = $parentId === null
            ? null
            : MaterialFolder::query()->findOrFail($parentId);

        $this->library->copyFolder($request->user(), $folder, $destination);

        return redirect()
            ->route('training-manager.library.index', ['folder' => $sourceParentId])
            ->with('success', 'Folder copied.');
    }

    public function destroyFolder(MaterialFolder $folder): RedirectResponse
    {
        $parentId = $folder->parent_id;
        $this->library->deleteFolder($folder);

        return redirect()
            ->route('training-manager.library.index', ['folder' => $parentId])
            ->with('success', 'Folder deleted.');
    }

    public function destroyFile(MaterialFile $file): RedirectResponse
    {
        $folderId = $file->folder_id;
        $this->library->deleteFile($file);

        return redirect()
            ->route('training-manager.library.index', ['folder' => $folderId])
            ->with('success', 'File deleted.');
    }

    public function assignFolder(AssignMaterialRequest $request, MaterialFolder $folder): RedirectResponse
    {
        $this->assignments->assignFolder(
            $request->user(),
            $folder,
            $request->validated('trainer_ids', []),
        );

        return redirect()
            ->route('training-manager.library.index', ['folder' => $folder->parent_id])
            ->with('success', 'Folder assignments updated.');
    }

    public function assignFile(AssignMaterialRequest $request, MaterialFile $file): RedirectResponse
    {
        $this->assignments->assignFile(
            $request->user(),
            $file,
            $request->validated('trainer_ids', []),
        );

        return redirect()
            ->route('training-manager.library.index', ['folder' => $file->folder_id])
            ->with('success', 'File assignments updated.');
    }

    public function shareFolder(
        ShareMaterialWithBatchesRequest $request,
        MaterialFolder $folder,
    ): RedirectResponse {
        $this->assignments->shareFolderWithBatches(
            $request->user(),
            $folder,
            $request->validated('batch_ids', []),
        );

        return redirect()
            ->route('training-manager.library.index', ['folder' => $folder->parent_id])
            ->with('success', 'Folder shared with selected batches.');
    }

    public function shareFile(
        ShareMaterialWithBatchesRequest $request,
        MaterialFile $file,
    ): RedirectResponse {
        $this->assignments->shareFileWithBatches(
            $request->user(),
            $file,
            $request->validated('batch_ids', []),
        );

        return redirect()
            ->route('training-manager.library.index', ['folder' => $file->folder_id])
            ->with('success', 'File shared with selected batches.');
    }

    public function download(MaterialFile $file): StreamedResponse
    {
        abort_unless($file->isFile(), 404);
        abort_unless(filled($file->disk) && filled($file->path), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $downloadName = $file->display_name.'.'.$file->extension;

        return Storage::disk($file->disk)->download($file->path, $downloadName);
    }

    public function playlistItems(MaterialFile $file, YoutubePlaylistService $youtubePlaylists): JsonResponse
    {
        abort_unless($file->isYoutubePlaylist(), 404);

        $items = $youtubePlaylists->resolveItemsFor($file);

        return response()->json([
            'items' => $items,
            'api_configured' => $youtubePlaylists->hasApiKey(),
        ]);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function breadcrumbTrail(MaterialFolder $folder): array
    {
        $trail = [];
        $current = $folder;

        while ($current !== null) {
            array_unshift($trail, [
                'id' => $current->id,
                'name' => $current->name,
            ]);
            $current = $current->parent;
        }

        return $trail;
    }
}
