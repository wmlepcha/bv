<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentDocumentController extends Controller
{
    public function show(Request $request, StudentProfile $studentProfile, string $kind): StreamedResponse
    {
        /** @var User $viewer */
        $viewer = $request->user();

        $column = StudentProfile::PRIVATE_DOCUMENTS[$kind] ?? null;
        $path = $column === null ? null : $studentProfile->{$column};

        abort_if(blank($path) || ! $studentProfile->documentsViewableBy($viewer), 404);

        // Files uploaded before the move to private storage are still on the public disk until the command runs.
        $disk = Storage::disk(StudentProfile::PRIVATE_DISK)->exists($path)
            ? Storage::disk(StudentProfile::PRIVATE_DISK)
            : Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, basename($path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
