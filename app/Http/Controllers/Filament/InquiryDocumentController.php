<?php

namespace App\Http\Controllers\Filament;

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
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_unless($inquiry->type === 'candidate', 404);
        abort_unless(in_array($field, self::ALLOWED_FIELDS, true), 404);

        $path = $inquiry->data[$field] ?? null;

        abort_unless(filled($path) && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path, basename($path));
    }
}
