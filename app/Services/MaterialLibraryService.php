<?php

namespace App\Services;

use App\Models\MaterialFile;
use App\Models\MaterialFolder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MaterialLibraryService
{
    /**
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'mp3', 'mp4'];

    public function __construct(private YoutubePlaylistService $youtubePlaylists) {}

    public function createFolder(User $actor, string $name, ?int $parentId = null): MaterialFolder
    {
        $name = trim($name);

        if ($parentId !== null) {
            MaterialFolder::query()->findOrFail($parentId);
        }

        $exists = MaterialFolder::query()
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'A folder with this name already exists here.',
            ]);
        }

        return MaterialFolder::query()->create([
            'parent_id' => $parentId,
            'name' => $name,
            'created_by' => $actor->id,
        ]);
    }

    public function renameFolder(MaterialFolder $folder, string $name): MaterialFolder
    {
        $name = trim($name);

        $exists = MaterialFolder::query()
            ->where('parent_id', $folder->parent_id)
            ->where('name', $name)
            ->whereKeyNot($folder->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'A folder with this name already exists here.',
            ]);
        }

        $folder->update(['name' => $name]);

        return $folder->refresh();
    }

    public function deleteFolder(MaterialFolder $folder): void
    {
        DB::transaction(function () use ($folder): void {
            $this->deleteFolderRecursive($folder);
        });
    }

    public function uploadFile(User $actor, MaterialFolder $folder, UploadedFile $upload): MaterialFile
    {
        $extension = strtolower($upload->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => 'Only PDF, Word, Excel, MP3, and MP4 files are allowed.',
            ]);
        }

        $originalName = $upload->getClientOriginalName();
        $storedName = Str::uuid()->toString().'.'.$extension;
        $path = $upload->storeAs(
            'materials/'.$folder->id,
            $storedName,
            'local',
        );

        return MaterialFile::query()->create([
            'folder_id' => $folder->id,
            'kind' => MaterialFile::KIND_FILE,
            'original_name' => $originalName,
            'display_name' => pathinfo($originalName, PATHINFO_FILENAME) ?: $originalName,
            'disk' => 'local',
            'path' => $path,
            'url' => null,
            'mime_type' => $upload->getClientMimeType(),
            'extension' => $extension,
            'size' => $upload->getSize() ?: 0,
            'created_by' => $actor->id,
        ]);
    }

    public function addYoutube(User $actor, MaterialFolder $folder, string $url, ?string $title = null): MaterialFile
    {
        $videoId = MaterialFile::extractYoutubeVideoId($url);

        if ($videoId === null) {
            throw ValidationException::withMessages([
                'url' => 'Enter a valid YouTube video URL.',
            ]);
        }

        $canonical = 'https://www.youtube.com/watch?v='.$videoId;
        $displayName = trim((string) $title);

        if ($displayName === '') {
            $displayName = 'YouTube video';
        }

        return MaterialFile::query()->create([
            'folder_id' => $folder->id,
            'kind' => MaterialFile::KIND_YOUTUBE,
            'original_name' => $canonical,
            'display_name' => $displayName,
            'disk' => null,
            'path' => null,
            'url' => $canonical,
            'mime_type' => null,
            'extension' => 'youtube',
            'size' => 0,
            'created_by' => $actor->id,
        ]);
    }

    public function addYoutubePlaylist(User $actor, MaterialFolder $folder, string $url, ?string $title = null): MaterialFile
    {
        $playlistId = MaterialFile::extractYoutubePlaylistId($url);

        if ($playlistId === null || ! MaterialFile::isShareableYoutubePlaylistId($playlistId)) {
            throw ValidationException::withMessages([
                'url' => 'Enter a public YouTube playlist URL (not Watch Later, Liked, or Mix).',
            ]);
        }

        $canonical = 'https://www.youtube.com/playlist?list='.$playlistId;
        $displayName = trim((string) $title);

        if ($displayName === '') {
            $displayName = 'YouTube playlist';
        }

        $playlistItems = [];

        if ($this->youtubePlaylists->hasApiKey()) {
            try {
                $playlistItems = $this->youtubePlaylists->fetchItems($playlistId);
            } catch (\RuntimeException $exception) {
                Log::warning('YouTube playlist fetch failed', [
                    'playlist_id' => $playlistId,
                    'message' => $exception->getMessage(),
                ]);
                $playlistItems = [];
            }
        }

        return MaterialFile::query()->create([
            'folder_id' => $folder->id,
            'kind' => MaterialFile::KIND_YOUTUBE_PLAYLIST,
            'original_name' => $canonical,
            'display_name' => $displayName,
            'disk' => null,
            'path' => null,
            'url' => $canonical,
            'playlist_items' => $playlistItems === [] ? null : $playlistItems,
            'mime_type' => null,
            'extension' => 'youtube_playlist',
            'size' => 0,
            'created_by' => $actor->id,
        ]);
    }

    public function addLink(User $actor, MaterialFolder $folder, string $url, ?string $title = null): MaterialFile
    {
        $url = trim($url);
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw ValidationException::withMessages([
                'url' => 'Enter a valid website URL.',
            ]);
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw ValidationException::withMessages([
                'url' => 'Website links must use http or https.',
            ]);
        }

        $host = strtolower($parts['host']);
        $host = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

        if (in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be'], true)) {
            throw ValidationException::withMessages([
                'url' => 'Use the YouTube option to add YouTube videos.',
            ]);
        }

        $displayName = trim((string) $title);

        if ($displayName === '') {
            $displayName = $parts['host'];
        }

        return MaterialFile::query()->create([
            'folder_id' => $folder->id,
            'kind' => MaterialFile::KIND_LINK,
            'original_name' => $url,
            'display_name' => $displayName,
            'disk' => null,
            'path' => null,
            'url' => $url,
            'mime_type' => null,
            'extension' => 'link',
            'size' => 0,
            'created_by' => $actor->id,
        ]);
    }

    public function renameFile(MaterialFile $file, string $displayName): MaterialFile
    {
        $displayName = trim($displayName);

        if ($displayName === '') {
            throw ValidationException::withMessages([
                'display_name' => 'File name is required.',
            ]);
        }

        $file->update(['display_name' => $displayName]);

        return $file->refresh();
    }

    public function moveFile(MaterialFile $file, MaterialFolder $destination): MaterialFile
    {
        if ($destination->id === $file->folder_id) {
            throw ValidationException::withMessages([
                'folder_id' => 'Choose a different folder than the current location.',
            ]);
        }

        return DB::transaction(function () use ($file, $destination): MaterialFile {
            $updates = ['folder_id' => $destination->id];

            if ($file->isFile() && filled($file->path) && filled($file->disk)) {
                $disk = Storage::disk($file->disk);

                if (! $disk->exists($file->path)) {
                    throw ValidationException::withMessages([
                        'folder_id' => 'The file is missing from storage and cannot be moved.',
                    ]);
                }

                $basename = basename($file->path);
                $newPath = 'materials/'.$destination->id.'/'.$basename;

                $disk->makeDirectory('materials/'.$destination->id);
                $disk->move($file->path, $newPath);
                $updates['path'] = $newPath;
            }

            $file->update($updates);

            return $file->refresh();
        });
    }

    public function copyFile(User $actor, MaterialFile $file, MaterialFolder $destination): MaterialFile
    {
        if ($destination->id === $file->folder_id) {
            throw ValidationException::withMessages([
                'folder_id' => 'Choose a different folder than the current location.',
            ]);
        }

        return DB::transaction(function () use ($actor, $file, $destination): MaterialFile {
            return $this->cloneFileIntoFolder($actor, $file, $destination);
        });
    }

    public function moveFolder(MaterialFolder $folder, ?MaterialFolder $destination): MaterialFolder
    {
        $newParentId = $destination?->id;

        if ($newParentId === $folder->parent_id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Choose a different location than the current parent.',
            ]);
        }

        if ($destination !== null) {
            if ($destination->id === $folder->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A folder cannot be moved into itself.',
                ]);
            }

            if (in_array($destination->id, $this->descendantIds($folder), true)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A folder cannot be moved into one of its subfolders.',
                ]);
            }
        }

        $this->assertUniqueFolderName($folder->name, $newParentId);

        $folder->update(['parent_id' => $newParentId]);

        return $folder->refresh();
    }

    public function copyFolder(User $actor, MaterialFolder $folder, ?MaterialFolder $destination): MaterialFolder
    {
        $newParentId = $destination?->id;

        if ($newParentId === $folder->parent_id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Choose a different location than the current parent.',
            ]);
        }

        if ($destination !== null) {
            if ($destination->id === $folder->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A folder cannot be copied into itself.',
                ]);
            }

            if (in_array($destination->id, $this->descendantIds($folder), true)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A folder cannot be copied into one of its subfolders.',
                ]);
            }
        }

        $this->assertUniqueFolderName($folder->name, $newParentId);

        return DB::transaction(function () use ($actor, $folder, $newParentId): MaterialFolder {
            return $this->copyFolderRecursive($actor, $folder, $newParentId);
        });
    }

    public function deleteFile(MaterialFile $file): void
    {
        if ($file->isFile() && filled($file->path) && filled($file->disk)) {
            Storage::disk($file->disk)->delete($file->path);
        }

        $file->delete();
    }

    /**
     * @return list<array{id: int, name: string, parent_id: int|null, depth: int}>
     */
    public function folderOptionsTree(): array
    {
        $folders = MaterialFolder::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $byParent = $folders->groupBy(
            fn (MaterialFolder $folder): string => (string) ($folder->parent_id ?? 'root'),
        );

        $options = [];

        $walk = function (?int $parentId, int $depth) use (&$walk, &$options, $byParent): void {
            $key = (string) ($parentId ?? 'root');
            $children = $byParent->get($key, collect());

            foreach ($children as $folder) {
                /** @var MaterialFolder $folder */
                $options[] = [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'parent_id' => $folder->parent_id,
                    'depth' => $depth,
                ];
                $walk($folder->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $options;
    }

    /**
     * @return list<int>
     */
    public function descendantIds(MaterialFolder $folder): array
    {
        $ids = [];
        $folder->loadMissing('children');

        foreach ($folder->children as $child) {
            $ids[] = $child->id;
            $ids = [...$ids, ...$this->descendantIds($child)];
        }

        return $ids;
    }

    private function assertUniqueFolderName(string $name, ?int $parentId): void
    {
        $exists = MaterialFolder::query()
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'parent_id' => 'A folder with this name already exists in the destination.',
            ]);
        }
    }

    private function copyFolderRecursive(User $actor, MaterialFolder $source, ?int $newParentId): MaterialFolder
    {
        $source->load(['children', 'files']);

        $copy = MaterialFolder::query()->create([
            'parent_id' => $newParentId,
            'name' => $source->name,
            'created_by' => $actor->id,
        ]);

        foreach ($source->files as $file) {
            $this->cloneFileIntoFolder($actor, $file, $copy);
        }

        foreach ($source->children as $child) {
            $this->copyFolderRecursive($actor, $child, $copy->id);
        }

        return $copy;
    }

    private function cloneFileIntoFolder(
        User $actor,
        MaterialFile $file,
        MaterialFolder $destination,
    ): MaterialFile {
        $path = null;
        $disk = $file->disk;

        if ($file->isFile() && filled($file->path) && filled($file->disk)) {
            $storage = Storage::disk($file->disk);

            if (! $storage->exists($file->path)) {
                throw ValidationException::withMessages([
                    'folder_id' => 'A file is missing from storage and cannot be copied.',
                ]);
            }

            $extension = $file->extension ?: pathinfo($file->path, PATHINFO_EXTENSION);
            $newName = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
            $path = 'materials/'.$destination->id.'/'.$newName;

            $storage->makeDirectory('materials/'.$destination->id);
            $storage->copy($file->path, $path);
        }

        return MaterialFile::query()->create([
            'folder_id' => $destination->id,
            'kind' => $file->kind ?? MaterialFile::KIND_FILE,
            'original_name' => $file->original_name,
            'display_name' => $file->display_name,
            'disk' => $file->isFile() ? $disk : null,
            'path' => $path,
            'url' => $file->url,
            'playlist_items' => $file->playlist_items,
            'mime_type' => $file->mime_type,
            'extension' => $file->extension,
            'size' => $file->size,
            'created_by' => $actor->id,
        ]);
    }

    private function deleteFolderRecursive(MaterialFolder $folder): void
    {
        $folder->load(['children', 'files']);

        foreach ($folder->children as $child) {
            $this->deleteFolderRecursive($child);
        }

        foreach ($folder->files as $file) {
            $this->deleteFile($file);
        }

        $folder->delete();
    }
}
