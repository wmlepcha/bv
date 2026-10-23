<?php

namespace App\Services;

use App\Enums\AssignmentQuestionType;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentQuestionMediaService
{
    /**
     * @var list<string>
     */
    public const PDF_EXTENSIONS = ['pdf'];

    /**
     * @var list<string>
     */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * @var list<string>
     */
    public const AUDIO_EXTENSIONS = ['mp3', 'wav', 'm4a', 'ogg'];

    /**
     * @param  array<string, mixed>  $questionData
     * @return array<string, mixed>
     */
    public function resolveConfig(Assignment $assignment, AssignmentQuestionType $type, array $questionData): array
    {
        if ($type === AssignmentQuestionType::Link) {
            $url = trim((string) ($questionData['url'] ?? ''));

            if ($url === '') {
                throw ValidationException::withMessages([
                    'url' => 'A link URL is required.',
                ]);
            }

            return ['url' => $url];
        }

        if (! $type->isMediaFile()) {
            throw ValidationException::withMessages([
                'type' => 'Unsupported media question type.',
            ]);
        }

        $upload = $questionData['media'] ?? null;

        if ($upload instanceof UploadedFile) {
            return $this->store($assignment, $type, $upload);
        }

        $existingPath = $questionData['existing_media_path'] ?? null;

        if (is_string($existingPath) && $existingPath !== '') {
            return $this->keepOrCopy($assignment, $type, $existingPath, $questionData);
        }

        throw ValidationException::withMessages([
            'media' => 'Upload a file for this item.',
        ]);
    }

    /**
     * @return array{disk: string, path: string, original_name: string}
     */
    public function store(Assignment $assignment, AssignmentQuestionType $type, UploadedFile $upload): array
    {
        $extension = strtolower($upload->getClientOriginalExtension());
        $allowed = $this->allowedExtensions($type);

        if (! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'media' => $this->invalidExtensionMessage($type),
            ]);
        }

        $storedName = Str::uuid()->toString().'.'.$extension;
        $directory = $this->directoryFor($assignment);
        $path = $upload->storeAs($directory, $storedName, 'local');

        return [
            'disk' => 'local',
            'path' => $path,
            'original_name' => $upload->getClientOriginalName(),
        ];
    }

    /**
     * @param  array<string, mixed>  $questionData
     * @return array{disk: string, path: string, original_name: string}
     */
    public function keepOrCopy(
        Assignment $assignment,
        AssignmentQuestionType $type,
        string $existingPath,
        array $questionData,
    ): array {
        if (! $this->isAllowedQuestionMediaPath($existingPath)) {
            throw ValidationException::withMessages([
                'existing_media_path' => 'Invalid media path.',
            ]);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($existingPath)) {
            throw ValidationException::withMessages([
                'existing_media_path' => 'The previous media file is missing. Upload it again.',
            ]);
        }

        $originalName = is_string($questionData['media_original_name'] ?? null)
            ? (string) $questionData['media_original_name']
            : basename($existingPath);

        $assignmentPrefix = $this->directoryFor($assignment).'/';

        if (str_starts_with($existingPath, $assignmentPrefix)) {
            return [
                'disk' => 'local',
                'path' => $existingPath,
                'original_name' => $originalName,
            ];
        }

        $extension = pathinfo($existingPath, PATHINFO_EXTENSION);
        $storedName = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $this->directoryFor($assignment).'/'.$storedName;

        $disk->makeDirectory($this->directoryFor($assignment));
        $disk->copy($existingPath, $path);

        return [
            'disk' => 'local',
            'path' => $path,
            'original_name' => $originalName,
        ];
    }

    /**
     * @param  list<string>  $usedPaths
     */
    public function pruneOrphans(Assignment $assignment, array $usedPaths): void
    {
        $disk = Storage::disk('local');
        $directory = $this->directoryFor($assignment);

        if (! $disk->exists($directory)) {
            return;
        }

        $used = array_flip($usedPaths);

        foreach ($disk->files($directory) as $path) {
            if (! isset($used[$path])) {
                $disk->delete($path);
            }
        }
    }

    public function stream(AssignmentQuestion $question): StreamedResponse
    {
        if (! $question->isMediaFile() || ! $question->hasMedia()) {
            abort(404);
        }

        $disk = Storage::disk($question->mediaDisk() ?? 'local');
        $path = $question->mediaPath();

        if ($path === null || ! $disk->exists($path)) {
            abort(404);
        }

        return $disk->response(
            $path,
            $question->mediaOriginalName() ?? 'media',
        );
    }

    public function deleteAssignmentMedia(Assignment $assignment): void
    {
        Storage::disk('local')->deleteDirectory($this->directoryFor($assignment));
    }

    public function directoryFor(Assignment $assignment): string
    {
        return 'assignments/'.$assignment->id.'/questions';
    }

    public function isAllowedQuestionMediaPath(string $path): bool
    {
        if (str_contains($path, '..')) {
            return false;
        }

        return (bool) preg_match('#^assignments/\d+/questions/[^/]+$#', $path);
    }

    /**
     * @return list<string>
     */
    public function allowedExtensions(AssignmentQuestionType $type): array
    {
        return match ($type) {
            AssignmentQuestionType::Pdf => self::PDF_EXTENSIONS,
            AssignmentQuestionType::Image => self::IMAGE_EXTENSIONS,
            AssignmentQuestionType::Audio => self::AUDIO_EXTENSIONS,
            default => [],
        };
    }

    private function invalidExtensionMessage(AssignmentQuestionType $type): string
    {
        return match ($type) {
            AssignmentQuestionType::Pdf => 'Only PDF files are allowed.',
            AssignmentQuestionType::Image => 'Only JPG, PNG, and WEBP images are allowed.',
            AssignmentQuestionType::Audio => 'Only MP3, WAV, M4A, and OGG audio files are allowed.',
            default => 'Invalid media file type.',
        };
    }
}
