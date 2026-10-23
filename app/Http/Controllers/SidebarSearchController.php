<?php

namespace App\Http\Controllers;

use App\Models\MaterialFile;
use App\Models\MaterialFolder;
use App\Models\User;
use App\Services\MaterialAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarSearchController extends Controller
{
    public function __construct(
        private readonly MaterialAssignmentService $assignments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = trim((string) $request->string('q'));

        if ($user === null || mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        return response()->json([
            'results' => $user->isStudent() ? $this->searchForStudent($user, $query) : [],
        ]);
    }

    /**
     * @return list<array{group: string, label: string, sublabel: string, href: string}>
     */
    private function searchForStudent(User $user, string $query): array
    {
        $results = [];

        $folders = MaterialFolder::query()
            ->where('name', 'like', '%'.$query.'%')
            ->limit(25)
            ->get()
            ->filter(fn (MaterialFolder $folder): bool => $this->assignments->studentCanAccessFolder($user, $folder))
            ->take(5);

        foreach ($folders as $folder) {
            $results[] = [
                'group' => 'Study Bank',
                'label' => $folder->name,
                'sublabel' => 'Folder',
                'href' => route('student.study-bank.folders.show', $folder),
            ];
        }

        $files = MaterialFile::query()
            ->where('display_name', 'like', '%'.$query.'%')
            ->limit(25)
            ->get()
            ->filter(fn (MaterialFile $file): bool => $this->assignments->studentCanAccessFile($user, $file))
            ->take(5);

        foreach ($files as $file) {
            $results[] = [
                'group' => 'Study Bank',
                'label' => $file->display_name,
                'sublabel' => $file->folder->name,
                'href' => route('student.study-bank.folders.show', $file->folder_id),
            ];
        }

        $learningItems = $user->learningItems()
            ->with('materialFile')
            ->whereHas('materialFile', fn ($fileQuery) => $fileQuery->where('display_name', 'like', '%'.$query.'%'))
            ->limit(5)
            ->get();

        foreach ($learningItems as $item) {
            if ($item->materialFile === null) {
                continue;
            }

            $results[] = [
                'group' => 'My Learning',
                'label' => $item->materialFile->display_name,
                'sublabel' => 'Saved with your notes',
                'href' => route('student.my-learning.index', ['item' => $item->id]),
            ];
        }

        return $results;
    }
}
