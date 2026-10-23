<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_unenrolled_student_sees_chat_as_unavailable(): void
    {
        $student = User::factory()->student()->paid()->create();

        $this->actingAs($student)
            ->getJson(route('student.messages.show'))
            ->assertOk()
            ->assertJson(['available' => false, 'messages' => []]);
    }

    public function test_unenrolled_student_cannot_send_a_message(): void
    {
        $student = User::factory()->student()->paid()->create();

        $this->actingAs($student)
            ->postJson(route('student.messages.store'), ['body' => 'Hello?'])
            ->assertStatus(422);

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_student_can_send_a_text_message_to_their_teacher(): void
    {
        [$student, $teacher] = $this->enrolledStudentWithTeacher();

        $this->actingAs($student)
            ->postJson(route('student.messages.store'), ['body' => 'Hi, quick question!'])
            ->assertOk()
            ->assertJsonPath('message.body', 'Hi, quick question!')
            ->assertJsonPath('message.from_me', true);

        $conversation = Conversation::query()->sole();

        $this->assertSame($student->id, $conversation->student_id);
        $this->assertSame($teacher->id, $conversation->teacher_id);
        $this->assertSame(1, $conversation->messages()->count());
    }

    public function test_message_requires_body_or_attachment(): void
    {
        [$student] = $this->enrolledStudentWithTeacher();

        $this->actingAs($student)
            ->postJson(route('student.messages.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_student_can_send_an_image_attachment(): void
    {
        Storage::fake('local');
        [$student] = $this->enrolledStudentWithTeacher();

        $response = $this->actingAs($student)
            ->post(route('student.messages.store'), [
                'attachment' => UploadedFile::fake()->image('photo.jpg', 100, 100),
            ]);

        $response->assertOk();

        $message = Message::query()->sole();
        $this->assertNotNull($message->attachment_path);
        Storage::disk('local')->assertExists($message->attachment_path);
    }

    public function test_non_image_attachment_is_rejected(): void
    {
        [$student] = $this->enrolledStudentWithTeacher();

        $this->actingAs($student)
            ->post(route('student.messages.store'), [
                'attachment' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('attachment');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_teacher_sees_conversation_and_can_reply(): void
    {
        [$student, $teacher] = $this->enrolledStudentWithTeacher();
        $conversation = app(ChatService::class)->getOrCreateConversationForStudent($student);
        $conversation->messages()->create(['sender_id' => $student->id, 'body' => 'Hello trainer']);
        $conversation->forceFill(['last_message_at' => now()])->save();

        $this->actingAs($teacher)
            ->get(route('teacher.messages.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/teacher/messages/index')
                ->has('conversations', 1)
                ->where('conversations.0.student_name', $student->name)
                ->where('selectedConversationId', $conversation->id)
                ->has('messages', 1));

        $this->actingAs($teacher)
            ->post(route('teacher.messages.store', $conversation), ['body' => 'Sure, go ahead!'])
            ->assertRedirect();

        $this->assertSame(2, $conversation->messages()->count());
    }

    public function test_teacher_cannot_access_another_teachers_conversation(): void
    {
        [$student] = $this->enrolledStudentWithTeacher();
        $conversation = app(ChatService::class)->getOrCreateConversationForStudent($student);
        $otherTeacher = User::factory()->teacher()->create();

        $this->actingAs($otherTeacher)
            ->post(route('teacher.messages.store', $conversation), ['body' => 'Sneaky'])
            ->assertForbidden();

        $this->actingAs($otherTeacher)
            ->post(route('teacher.messages.read', $conversation))
            ->assertForbidden();
    }

    public function test_marking_read_clears_unread_count(): void
    {
        [$student, $teacher] = $this->enrolledStudentWithTeacher();

        $this->actingAs($student)
            ->postJson(route('student.messages.store'), ['body' => 'Question one']);

        $this->assertSame(1, app(ChatService::class)->unreadCountForTeacher($teacher));

        $conversation = Conversation::query()->sole();
        $this->actingAs($teacher)
            ->get(route('teacher.messages.index', ['conversation' => $conversation->id]));

        $this->assertSame(0, app(ChatService::class)->unreadCountForTeacher($teacher));
    }

    public function test_only_conversation_participants_can_view_attachment(): void
    {
        Storage::fake('local');
        [$student, $teacher] = $this->enrolledStudentWithTeacher();

        $this->actingAs($student)->post(route('student.messages.store'), [
            'attachment' => UploadedFile::fake()->image('photo.jpg', 100, 100),
        ]);

        $message = Message::query()->sole();

        $this->actingAs($teacher)
            ->get(route('chat.attachments.show', $message))
            ->assertOk();

        $stranger = User::factory()->teacher()->create();
        $this->actingAs($stranger)
            ->get(route('chat.attachments.show', $message))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function enrolledStudentWithTeacher(): array
    {
        $teacher = User::factory()->teacher()->create();
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id, 'is_active' => true]);
        $student = User::factory()->student()->paid()->create();

        BatchEnrollment::query()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        return [$student, $teacher];
    }
}
