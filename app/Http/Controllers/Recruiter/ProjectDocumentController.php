<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\ProjectRequirement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectDocumentController extends Controller
{
    /**
     * Only these types are ever shown inline in the preview dialog; everything else is download-only.
     */
    private const PREVIEWABLE = ['application/pdf', 'image/jpeg', 'image/png'];

    public function download(ProjectRequirement $requirement): StreamedResponse
    {
        $path = $this->documentPath($requirement);

        return Storage::disk(ProjectRequirement::DISK)->download($path, $requirement->original_name ?? basename($path));
    }

    public function view(ProjectRequirement $requirement): StreamedResponse
    {
        return $this->inline($this->documentPath($requirement), $requirement->original_name);
    }

    public function sample(ProjectRequirement $requirement): StreamedResponse
    {
        $path = $this->samplePath($requirement);

        return Storage::disk(ProjectRequirement::DISK)->download($path, $requirement->sample_name ?? basename($path));
    }

    public function sampleView(ProjectRequirement $requirement): StreamedResponse
    {
        return $this->inline($this->samplePath($requirement), $requirement->sample_name);
    }

    private function documentPath(ProjectRequirement $requirement): string
    {
        /** @var User $user */
        $user = Auth::user();
        $entry = $requirement->entry()->with('project')->firstOrFail();

        $allowed = $user->isAdmin() || $user->isRecruitmentManager()
            || ($entry->project->user_id === $user->id && $entry->isSentToRecruiter());

        abort_unless($allowed && $requirement->isUploaded(), 404);
        abort_unless(Storage::disk(ProjectRequirement::DISK)->exists($requirement->file_path), 404);

        return $requirement->file_path;
    }

    private function samplePath(ProjectRequirement $requirement): string
    {
        /** @var User $user */
        $user = Auth::user();
        $entry = $requirement->entry()->with('project')->firstOrFail();

        abort_unless($user->isAdmin() || $user->isRecruitmentManager() || $entry->project->user_id === $user->id, 404);
        abort_unless($requirement->hasSample() && Storage::disk(ProjectRequirement::DISK)->exists($requirement->sample_path), 404);

        return $requirement->sample_path;
    }

    private function inline(string $path, ?string $name): StreamedResponse
    {
        $disk = Storage::disk(ProjectRequirement::DISK);
        $mime = $disk->mimeType($path);

        abort_unless(in_array($mime, self::PREVIEWABLE, true), 415, 'This file type cannot be previewed. Download it instead.');

        return $disk->response($path, $name ?? basename($path), [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ], 'inline');
    }
}
