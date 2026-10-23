<?php

namespace App\Http\Controllers\Filament;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiryDocumentController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_FIELDS = [
        'qualification_certificate',
        'resume',
        'id_document',
    ];

    public function download(Inquiry $inquiry, string $field): StreamedResponse
    {
        abort_unless(in_array(auth()->user()?->role, UserRole::placementStaff(), true), 403);
        abort_unless($inquiry->type === 'candidate', 404);
        abort_unless(in_array($field, self::ALLOWED_FIELDS, true), 404);

        $path = data_get($inquiry->data, $field);

        abort_unless(is_string($path) && $path !== '', 404);

        // New uploads are private; older ones were stored on the public disk.
        $disk = Storage::disk('local')->exists($path) ? 'local' : 'public';

        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download($path, basename($path));
    }
}
