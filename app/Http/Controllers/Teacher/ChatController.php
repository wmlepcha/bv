<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chat,
    ) {}

    public function index(Request $request): Response
    {
        $teacher = $request->user();
        $conversations = $this->chat->conversationsForTeacher($teacher);

        $selectedId = $request->integer('conversation') ?: $conversations->first()?->id;
        $selected = $selectedId !== null
            ? $conversations->firstWhere('id', $selectedId)
            : null;

        if ($selected !== null) {
            $this->chat->markRead($teacher, $selected);
        }

        return Inertia::render('app/teacher/messages/index', [
            'conversations' => $conversations
                ->map(fn (Conversation $conversation): array => $this->presentConversation($conversation, $teacher))
                ->values(),
            'selectedConversationId' => $selected?->id,
            'messages' => $selected !== null
                ? $this->chat->messagesFor($selected)
                    ->map(fn (Message $message): array => $this->presentMessage($message, $teacher))
                    ->values()
                : [],
        ]);
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $teacher = $request->user();
        abort_unless($this->chat->teacherCanAccess($teacher, $conversation), 403);

        $this->chat->sendMessage(
            $teacher,
            $conversation,
            $request->validated('body'),
            $request->file('attachment'),
        );

        return back();
    }

    public function markRead(Request $request, Conversation $conversation): RedirectResponse
    {
        $teacher = $request->user();
        abort_unless($this->chat->teacherCanAccess($teacher, $conversation), 403);

        $this->chat->markRead($teacher, $conversation);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentConversation(Conversation $conversation, User $teacher): array
    {
        $lastMessage = $conversation->messages->first();

        return [
            'id' => $conversation->id,
            'student_name' => $conversation->student->name,
            'unread_count' => $conversation->unreadCountFor($teacher),
            'last_message_preview' => $lastMessage?->body
                ?? ($lastMessage?->hasAttachment() ? 'Sent an image' : null),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMessage(Message $message, User $viewer): array
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
