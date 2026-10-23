<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController extends Controller
{
    public function __invoke(Request $request, Message $message): StreamedResponse
    {
        $user = $request->user();

        abort_if($user === null || ! $message->hasAttachment(), 404);

        $conversation = $message->conversation;

        abort_unless(
            $conversation->student_id === $user->id || $conversation->teacher_id === $user->id,
            403,
        );

        $disk = Storage::disk($message->attachment_disk);

        abort_unless($disk->exists($message->attachment_path), 404);

        return $disk->response(
            $message->attachment_path,
            $message->attachment_original_name ?? 'attachment',
        );
    }
}
