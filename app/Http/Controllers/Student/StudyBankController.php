<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningItem;
use App\Models\MaterialFile;
use App\Models\MaterialFolder;
use App\Models\User;
use App\Services\MaterialAssignmentService;
use App\Services\YoutubePlaylistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudyBankController extends Controller
{
    public function __construct(
        private readonly MaterialAssignmentService $assignments,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isStudent(), 403);

        $materials = $this->assignments->materialsForStudent($user);

        return Inertia::render('app/student/study-bank/index', [
            'enrolled' => $materials['enrolled'],
            'folders' => $materials['folders'],
            'files' => $this->markLearningStatus($user, $materials['files']),
        ]);
    }

    public function showFolder(Request $request, MaterialFolder $folder): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $this->assignments->studentCanAccessFolder($user, $folder), 403);

        $folders = $folder->children()
            ->orderBy('name')
            ->get()
            ->map(fn (MaterialFolder $child): array => [
                'id' => $child->id,
                'name' => $child->name,
                'type' => 'folder',
            ]);

        $files = $folder->files()
            ->orderBy('display_name')
            ->get()
            ->map(fn (MaterialFile $file): array => $this->assignments->presentStudentFile($file))
            ->values()
            ->all();

        return Inertia::render('app/student/study-bank/folder', [
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
            ],
            'breadcrumbs' => $this->breadcrumbTrail($user, $folder),
            'folders' => $folders,
            'files' => $this->markLearningStatus($user, $files),
        ]);
    }

    public function download(Request $request, MaterialFile $file): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $this->assignments->studentCanAccessFile($user, $file), 403);

        abort_unless($file->isFile(), 404);
        abort_unless(filled($file->disk) && filled($file->path), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download(
            $file->path,
            $file->display_name.'.'.$file->extension,
        );
    }

    public function preview(Request $request, MaterialFile $file): StreamedResponse|BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $this->assignments->studentCanAccessFile($user, $file), 403);

        abort_unless($file->isFile(), 404);
        abort_unless(filled($file->disk) && filled($file->path), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        // Audio/video needs HTTP Range support (iOS Safari, seeking); BinaryFileResponse provides it.
        if (in_array($file->extension, ['mp3', 'mp4', 'wav', 'm4a'], true) && $file->disk === 'local') {
            return response()->file(Storage::disk($file->disk)->path($file->path), [
                'Content-Type' => $file->mime_type ?? 'application/octet-stream',
            ]);
        }

        return Storage::disk($file->disk)->response(
            $file->path,
            $file->display_name.'.'.$file->extension,
        );
    }

    public function playlistItems(
        Request $request,
        MaterialFile $file,
        YoutubePlaylistService $youtubePlaylists,
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user !== null && $this->assignments->studentCanAccessFile($user, $file), 403);
        abort_unless($file->isYoutubePlaylist(), 404);

        $items = $youtubePlaylists->resolveItemsFor($file);

        return response()->json([
            'items' => $items,
            'api_configured' => $youtubePlaylists->hasApiKey(),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $files
     * @return list<array<string, mixed>>
     */
    private function markLearningStatus(User $user, array $files): array
    {
        $learningItemIdsByFileId = LearningItem::query()
            ->where('user_id', $user->id)
            ->pluck('id', 'material_file_id');

        return array_map(
            fn (array $file): array => [
                ...$file,
                'learning_item_id' => $learningItemIdsByFileId->get($file['id']),
            ],
            $files,
        );
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function breadcrumbTrail(User $user, MaterialFolder $folder): array
    {
        $trail = [];
        $current = $folder->loadMissing('parent');

        while ($current !== null && $this->assignments->studentCanAccessFolder($user, $current)) {
            array_unshift($trail, [
                'id' => $current->id,
                'name' => $current->name,
            ]);
            $current = $current->parent;
        }

        return $trail;
    }
}
