<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chat,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $student = $request->user();
        $conversation = $this->chat->getOrCreateConversationForStudent($student);

        if ($conversation === null) {
            return response()->json([
                'available' => false,
                'teacher_name' => null,
                'messages' => [],
            ]);
        }

        $this->chat->markRead($student, $conversation);

        return response()->json([
            'available' => true,
            'teacher_name' => $conversation->teacher->name,
            'messages' => $this->chat->messagesFor($conversation)
                ->map(fn (Message $message): array => $this->present($message, $student))
                ->values(),
        ]);
    }

    /**
     * Validated manually (rather than via a FormRequest injection) so validation
     * failures return JSON here — this app renders JSON errors only for `/api/*`
     * paths (see bootstrap/app.php), and this endpoint is called via fetch(),
     * not an Inertia visit, so it always needs a JSON response.
     */
    public function store(Request $request): JsonResponse
    {
        $student = $request->user();
        $conversation = $this->chat->getOrCreateConversationForStudent($student);

        abort_if($conversation === null, 422, 'You are not enrolled in a batch yet.');

        $validator = Validator::make($request->all(), (new StoreMessageRequest)->rules());

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $message = $this->chat->sendMessage(
            $student,
            $conversation,
            $validated['body'] ?? null,
            $request->file('attachment'),
        );

        return response()->json(['message' => $this->present($message, $student)]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $student = $request->user();
        $conversation = $this->chat->findConversationForStudent($student);

        if ($conversation !== null) {
            $this->chat->markRead($student, $conversation);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Message $message, User $viewer): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'from_me' => $message->sender_id === $viewer->id,
            'sender_name' => $message->sender->name,
            'attachment_url' => $message->hasAttachment()
                ? route('chat.attachments.show', $message)
                : null,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
