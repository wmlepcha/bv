<?php

namespace App\Services;

use App\Models\Assignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentAudioService
{
    /**
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = ['mp3', 'wav', 'm4a', 'ogg'];

    public function store(Assignment $assignment, UploadedFile $upload): Assignment
    {
        $extension = strtolower($upload->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'audio' => 'Only MP3, WAV, M4A, and OGG audio files are allowed.',
            ]);
        }

        $this->delete($assignment);

        $storedName = Str::uuid()->toString().'.'.$extension;
        $path = $upload->storeAs(
            'assignments/'.$assignment->id.'/audio',
            $storedName,
            'local',
        );

        $assignment->update([
            'audio_disk' => 'local',
            'audio_path' => $path,
            'audio_original_name' => $upload->getClientOriginalName(),
        ]);

        return $assignment->refresh();
    }

    public function copyFrom(Assignment $source, Assignment $target): void
    {
        if (! $source->hasAudio()) {
            return;
        }

        $sourceDisk = Storage::disk($source->audio_disk);

        if (! $sourceDisk->exists($source->audio_path)) {
            throw ValidationException::withMessages([
                'audio' => 'Source audio file is missing and cannot be copied.',
            ]);
        }

        $extension = pathinfo($source->audio_path, PATHINFO_EXTENSION);
        $storedName = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = 'assignments/'.$target->id.'/audio/'.$storedName;

        $sourceDisk->makeDirectory('assignments/'.$target->id.'/audio');
        $sourceDisk->copy($source->audio_path, $path);

        $target->update([
            'audio_disk' => $source->audio_disk,
            'audio_path' => $path,
            'audio_original_name' => $source->audio_original_name,
        ]);
    }

    public function delete(Assignment $assignment): void
    {
        if ($assignment->hasAudio()) {
            Storage::disk($assignment->audio_disk)->delete($assignment->audio_path);
        }

        $assignment->update([
            'audio_disk' => null,
            'audio_path' => null,
            'audio_original_name' => null,
        ]);
    }

    public function stream(Assignment $assignment): StreamedResponse
    {
        if (! $assignment->hasAudio()) {
            abort(404);
        }

        $disk = Storage::disk($assignment->audio_disk);

        if (! $disk->exists($assignment->audio_path)) {
            abort(404);
        }

        return $disk->response(
            $assignment->audio_path,
            $assignment->audio_original_name ?? 'audio',
        );
    }
}
