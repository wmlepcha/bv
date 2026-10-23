<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatService
{
    /**
     * @var list<string>
     */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function findConversationForStudent(User $student): ?Conversation
    {
        $teacherId = $student->batchEnrollment?->batch?->teacher_id;

        if ($teacherId === null) {
            return null;
        }

        return Conversation::query()
            ->where('student_id', $student->id)
            ->where('teacher_id', $teacherId)
            ->first();
    }

    public function getOrCreateConversationForStudent(User $student): ?Conversation
    {
        $teacherId = $student->batchEnrollment?->batch?->teacher_id;

        if ($teacherId === null) {
            return null;
        }

        return Conversation::query()->firstOrCreate([
            'student_id' => $student->id,
            'teacher_id' => $teacherId,
        ]);
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function conversationsForTeacher(User $teacher): Collection
    {
        return Conversation::query()
            ->where('teacher_id', $teacher->id)
            ->with([
                'student',
                'messages' => fn ($query) => $query->latest()->limit(1),
            ])
            ->orderByDesc('last_message_at')
            ->get();
    }

    public function teacherCanAccess(User $teacher, Conversation $conversation): bool
    {
        return $conversation->teacher_id === $teacher->id;
    }

    /**
     * @return Collection<int, Message>
     */
    public function messagesFor(Conversation $conversation): Collection
    {
        return $conversation->messages()->with('sender')->orderBy('created_at')->get();
    }

    public function sendMessage(User $sender, Conversation $conversation, ?string $body, ?UploadedFile $attachment): Message
    {
        $body = filled($body) ? trim($body) : null;

        if ($body === null && $attachment === null) {
            throw ValidationException::withMessages([
                'body' => 'Write a message or attach an image.',
            ]);
        }

        $attachmentData = $attachment !== null ? $this->storeAttachment($conversation, $attachment) : [];

        return DB::transaction(function () use ($sender, $conversation, $body, $attachmentData) {
            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'body' => $body,
                ...$attachmentData,
            ]);

            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            return $message->load('sender');
        });
    }

    public function markRead(User $user, Conversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCountForStudent(User $student): int
    {
        return $this->findConversationForStudent($student)?->unreadCountFor($student) ?? 0;
    }

    public function unreadCountForTeacher(User $teacher): int
    {
        return Message::query()
            ->whereHas('conversation', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->where('sender_id', '!=', $teacher->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function storeAttachment(Conversation $conversation, UploadedFile $upload): array
    {
        $extension = strtolower($upload->getClientOriginalExtension());

        if (! in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'attachment' => 'Attachments must be JPG, PNG, or WEBP images.',
            ]);
        }

        $storedName = Str::uuid()->toString().'.'.$extension;
        $directory = 'chat/'.$conversation->id;
        $path = $upload->storeAs($directory, $storedName, 'local');

        return [
            'attachment_disk' => 'local',
            'attachment_path' => $path,
            'attachment_original_name' => $upload->getClientOriginalName(),
            'attachment_mime_type' => $upload->getClientMimeType(),
            'attachment_size' => $upload->getSize() ?: 0,
        ];
    }
}
