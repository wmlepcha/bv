<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Materials\ShareMaterialWithBatchesRequest;
use App\Models\MaterialFile;
use App\Models\MaterialFolder;
use App\Services\MaterialAssignmentService;
use App\Services\YoutubePlaylistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function __construct(
        private readonly MaterialAssignmentService $assignments,
    ) {}

    public function index(Request $request): Response
    {
        $materials = $this->assignments->materialsForTrainer($request->user());

        return Inertia::render('app/teacher/materials/index', [
            'materials' => $materials,
            'batches' => $this->assignments->shareableBatchesFor($request->user()),
        ]);
    }

    public function showFolder(Request $request, MaterialFolder $folder): Response
    {
        abort_unless(
            $this->assignments->trainerCanAccessFolder($request->user(), $folder),
            403,
        );

        $folders = $folder->children()
            ->orderBy('name')
            ->get()
            ->map(fn (MaterialFolder $child): array => [
                'id' => $child->id,
                'name' => $child->name,
                'shared_batch_ids' => $this->assignments->sharedBatchIds($child),
                'type' => 'folder',
            ]);

        $files = $folder->files()
            ->orderBy('display_name')
            ->get()
            ->map(fn (MaterialFile $file): array => [
                'id' => $file->id,
                'name' => $file->display_name,
                'extension' => $file->extension,
                'kind' => $file->kind ?? MaterialFile::KIND_FILE,
                'url' => $file->url,
                'embed_url' => $file->embedUrl(),
                'playlist_items' => $file->isYoutubePlaylist()
                    ? ($file->playlist_items ?? [])
                    : null,
                'created_at' => $file->created_at?->toIso8601String(),
                'shared_batch_ids' => $this->assignments->sharedBatchIds($file),
                'type' => 'file',
            ]);

        return Inertia::render('app/teacher/materials/folder', [
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
                'shared_batch_ids' => $this->assignments->sharedBatchIds($folder),
            ],
            'breadcrumbs' => $this->breadcrumbTrail($folder),
            'folders' => $folders,
            'files' => $files,
            'batches' => $this->assignments->shareableBatchesFor($request->user()),
        ]);
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

        return back()->with('success', 'Folder shared with selected batches.');
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

        return back()->with('success', 'File shared with selected batches.');
    }

    public function download(Request $request, MaterialFile $file): StreamedResponse
    {
        abort_unless(
            $this->assignments->trainerCanAccessFile($request->user(), $file),
            403,
        );

        abort_unless($file->isFile(), 404);
        abort_unless(filled($file->disk) && filled($file->path), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download(
            $file->path,
            $file->display_name.'.'.$file->extension,
        );
    }

    public function playlistItems(
        Request $request,
        MaterialFile $file,
        YoutubePlaylistService $youtubePlaylists,
    ): JsonResponse {
        abort_unless(
            $this->assignments->trainerCanAccessFile($request->user(), $file),
            403,
        );
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
        $current = $folder->loadMissing('parent');

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
