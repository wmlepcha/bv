<?php

namespace Database\Seeders;

use App\Enums\AssignmentQuestionType;
use App\Enums\AssignmentStatus;
use App\Enums\AssignmentSubmissionStatus;
use App\Enums\AssignmentTargetScope;
use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\SubmissionAnnotationType;
use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSection;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentTarget;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\LearningItem;
use App\Models\LeaveRequest;
use App\Models\MaterialBatchAssignment;
use App\Models\MaterialFile;
use App\Models\MaterialFolder;
use App\Models\SubmissionAnnotation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Fills the "German B1 · Morning · Demo" batch (taught by Demo Teacher) with one of every
 * assignment question type, a study bank, attendance, leave requests and a graded submission
 * for the demo student, so every student screen has something to look at.
 *
 * Run: php artisan db:seed --class=StudentExperienceDemoSeeder
 * Re-running is safe; everything is keyed on titles/names.
 */
class StudentExperienceDemoSeeder extends Seeder
{
    private const STUDENT_EMAIL = 'wilburlepcha@outlook.com';

    private const BATCH_NAME = 'German B1 · Morning · Demo';

    private const TEACHER_EMAIL = 'teacher@demo.bilingualvitae.com';

    public function run(): void
    {
        $teacher = User::query()->where('email', self::TEACHER_EMAIL)->firstOrFail();
        $batch = Batch::query()->where('name', self::BATCH_NAME)->firstOrFail();
        $student = User::query()->where('email', self::STUDENT_EMAIL)->firstOrFail();

        $mixed = $this->seedMixedAssignment($teacher, $batch);
        $media = $this->seedMediaAssignment($teacher, $batch);
        $this->seedEveryTypeAssignment($teacher, $batch);
        $this->seedOverdueAssignment($teacher, $batch);

        $this->seedGradedSubmission($mixed, $student, $teacher);
        $this->seedSubmittedSubmission($media, $student);

        $this->seedStudyBank($teacher, $batch, $student);
        $this->seedAttendance($teacher, $batch, $student);
        $this->seedLeaveRequests($teacher, $batch, $student);
    }

    /**
     * @param  list<array{label: string, title: string, instructions?: string, questions: list<array<string, mixed>>}>  $sections
     */
    private function publish(User $teacher, Batch $batch, string $title, string $instructions, \DateTimeInterface $dueAt, array $sections): Assignment
    {
        $assignment = Assignment::query()->updateOrCreate(
            ['batch_id' => $batch->id, 'title' => $title],
            [
                'created_by' => $teacher->id,
                'instructions' => $instructions,
                'due_at' => $dueAt,
                'status' => AssignmentStatus::Published,
                'published_at' => now()->subDay(),
                'approved_by' => $teacher->id,
                'approved_at' => now()->subDay(),
            ],
        );

        AssignmentTarget::query()->where('assignment_id', $assignment->id)->delete();
        AssignmentTarget::query()->create([
            'assignment_id' => $assignment->id,
            'scope' => AssignmentTargetScope::Batch,
            'student_id' => null,
        ]);

        // Rebuilding the sections would orphan submitted answers, so only do it for a fresh assignment.
        if ($assignment->questions()->exists()) {
            return $assignment;
        }

        $maxScore = 0;

        foreach ($sections as $sectionIndex => $sectionData) {
            $section = AssignmentSection::query()->create([
                'assignment_id' => $assignment->id,
                'label' => $sectionData['label'],
                'title' => $sectionData['title'],
                'instructions' => $sectionData['instructions'] ?? null,
                'sort_order' => $sectionIndex,
            ]);

            foreach ($sectionData['questions'] as $questionIndex => $question) {
                $maxScore += (int) ($question['points'] ?? 0);

                AssignmentQuestion::query()->create([
                    'assignment_id' => $assignment->id,
                    'section_id' => $section->id,
                    'sort_order' => $questionIndex,
                    'type' => $question['type'],
                    'prompt' => $question['prompt'],
                    'points' => $question['points'] ?? 0,
                    'config' => $question['config'] ?? null,
                ]);
            }
        }

        $assignment->update(['max_score' => $maxScore]);

        return $assignment;
    }

    /** Auto-gradable types in one worksheet; graded for the demo student. */
    private function seedMixedAssignment(User $teacher, Batch $batch): Assignment
    {
        return $this->publish($teacher, $batch, 'Demo 1 · Grammatik-Mix (MCQ, Text, Lücken, Satzbau)', 'Bearbeiten Sie alle vier Abschnitte.', now()->addDays(5), [
            [
                'label' => 'A', 'title' => 'Multiple Choice', 'instructions' => 'Wählen Sie die richtige Antwort.',
                'questions' => [
                    ['type' => AssignmentQuestionType::Mcq, 'prompt' => 'Welcher Artikel passt: ___ Tisch?', 'points' => 1, 'config' => ['options' => ['der', 'die', 'das'], 'correct_index' => 0]],
                    ['type' => AssignmentQuestionType::Mcq, 'prompt' => 'Wie lautet der Plural von „das Kind“?', 'points' => 1, 'config' => ['options' => ['die Kinds', 'die Kinder', 'die Kindern', 'die Kinde'], 'correct_index' => 1]],
                ],
            ],
            [
                'label' => 'B', 'title' => 'Kurzantwort', 'instructions' => 'Antworten Sie in ein bis zwei Sätzen.',
                'questions' => [
                    ['type' => AssignmentQuestionType::ShortText, 'prompt' => 'Beschreiben Sie Ihren Tagesablauf.', 'points' => 2, 'config' => null],
                ],
            ],
            [
                'label' => 'C', 'title' => 'Lückentext', 'instructions' => 'Ergänzen Sie die fehlenden Wörter.',
                'questions' => [
                    ['type' => AssignmentQuestionType::FillInBlank, 'prompt' => 'Ich ___ jeden Tag um sieben Uhr ___.', 'points' => 2, 'config' => ['template' => 'Ich ___ jeden Tag um sieben Uhr ___.', 'hints' => ['aufstehen', ''], 'blank_count' => 2, 'model_answers' => [['stehe'], ['auf']]]],
                ],
            ],
            [
                'label' => 'D', 'title' => 'Satzbau', 'instructions' => 'Ordnen Sie die Wörter.',
                'questions' => [
                    ['type' => AssignmentQuestionType::WordReorder, 'prompt' => 'Bringen Sie die Wörter in die richtige Reihenfolge.', 'points' => 2, 'config' => ['tokens' => ['arbeite', 'ich', 'im', 'Krankenhaus'], 'model_sentence' => 'Ich arbeite im Krankenhaus.']],
                ],
            ],
        ]);
    }

    /** Media resources (PDF, image, audio, link), each followed by a question about it. */
    private function seedMediaAssignment(User $teacher, Batch $batch): Assignment
    {
        $files = $this->demoMediaFiles();

        return $this->publish($teacher, $batch, 'Demo 2 · Medien: PDF, Bild, Audio & Link', 'Lesen, ansehen, hören – und beantworten Sie die Fragen.', now()->addDays(2), [
            [
                'label' => 'A', 'title' => 'PDF lesen',
                'questions' => [
                    ['type' => AssignmentQuestionType::Pdf, 'prompt' => 'Lesen Sie das Dokument.', 'config' => $files['pdf']],
                    ['type' => AssignmentQuestionType::ShortText, 'prompt' => 'Was ist das Hauptthema des Dokuments?', 'points' => 2, 'config' => null],
                ],
            ],
            [
                'label' => 'B', 'title' => 'Bild beschreiben',
                'questions' => [
                    ['type' => AssignmentQuestionType::Image, 'prompt' => 'Sehen Sie sich das Bild an.', 'config' => $files['image']],
                    ['type' => AssignmentQuestionType::Mcq, 'prompt' => 'Welche Farbe dominiert das Bild?', 'points' => 1, 'config' => ['options' => ['Blau', 'Rot', 'Grün'], 'correct_index' => 0]],
                ],
            ],
            [
                'label' => 'C', 'title' => 'Hörverstehen',
                'questions' => [
                    ['type' => AssignmentQuestionType::Audio, 'prompt' => 'Hören Sie den Ton.', 'config' => $files['audio']],
                    ['type' => AssignmentQuestionType::Mcq, 'prompt' => 'Wie viele Töne haben Sie gehört?', 'points' => 1, 'config' => ['options' => ['Einen', 'Zwei', 'Drei'], 'correct_index' => 0]],
                ],
            ],
            [
                'label' => 'D', 'title' => 'Online-Ressource',
                'questions' => [
                    ['type' => AssignmentQuestionType::Link, 'prompt' => 'Öffnen Sie die Seite und lesen Sie den Artikel.', 'config' => ['url' => 'https://www.dw.com/de/deutsch-lernen/s-2055']],
                    ['type' => AssignmentQuestionType::ShortText, 'prompt' => 'Nennen Sie ein neues Wort, das Sie gelernt haben.', 'points' => 1, 'config' => null],
                ],
            ],
        ]);
    }

    /** A long worksheet with several of every auto-gradable type, to test scrolling and progress. */
    private function seedEveryTypeAssignment(User $teacher, Batch $batch): void
    {
        $mcq = fn (string $prompt, array $options, int $correct): array => [
            'type' => AssignmentQuestionType::Mcq, 'prompt' => $prompt, 'points' => 1,
            'config' => ['options' => $options, 'correct_index' => $correct],
        ];

        $this->publish($teacher, $batch, 'Demo 3 · Langes Arbeitsblatt (10 Aufgaben)', 'Testen Sie Scrollen, Fortschritt und Speichern auf dem Handy.', now()->addDays(10), [
            [
                'label' => 'A', 'title' => 'Wortschatz',
                'questions' => [
                    $mcq('„Der Arzt“ ist auf Englisch…', ['doctor', 'teacher', 'driver'], 0),
                    $mcq('„Die Schwester“ im Krankenhaus ist…', ['sister only', 'nurse', 'cook'], 1),
                    $mcq('Was bedeutet „Termin“?', ['appointment', 'train', 'price'], 0),
                    $mcq('„Gesund“ ist das Gegenteil von…', ['krank', 'müde', 'schnell'], 0),
                ],
            ],
            [
                'label' => 'B', 'title' => 'Verben',
                'questions' => [
                    ['type' => AssignmentQuestionType::FillInBlank, 'prompt' => 'Du ___ sehr gut Deutsch.', 'points' => 1, 'config' => ['template' => 'Du ___ sehr gut Deutsch.', 'hints' => ['sprechen'], 'blank_count' => 1, 'model_answers' => [['sprichst']]]],
                    ['type' => AssignmentQuestionType::FillInBlank, 'prompt' => 'Wir ___ morgen nach Berlin.', 'points' => 1, 'config' => ['template' => 'Wir ___ morgen nach Berlin.', 'hints' => ['fahren'], 'blank_count' => 1, 'model_answers' => [['fahren']]]],
                    ['type' => AssignmentQuestionType::FillInBlank, 'prompt' => 'Er ___ das Buch und ___ es nach Hause.', 'points' => 2, 'config' => ['template' => 'Er ___ das Buch und ___ es nach Hause.', 'hints' => ['nehmen', 'bringen'], 'blank_count' => 2, 'model_answers' => [['nimmt'], ['bringt']]]],
                ],
            ],
            [
                'label' => 'C', 'title' => 'Sätze bilden',
                'questions' => [
                    ['type' => AssignmentQuestionType::WordReorder, 'prompt' => 'Ordnen Sie den Satz.', 'points' => 1, 'config' => ['tokens' => ['wohne', 'ich', 'in', 'Gangtok'], 'model_sentence' => 'Ich wohne in Gangtok.']],
                    ['type' => AssignmentQuestionType::WordReorder, 'prompt' => 'Ordnen Sie den Satz.', 'points' => 1, 'config' => ['tokens' => ['hat', 'Anna', 'einen', 'Termin', 'heute'], 'model_sentence' => 'Anna hat heute einen Termin.']],
                ],
            ],
            [
                'label' => 'D', 'title' => 'Schreiben',
                'questions' => [
                    ['type' => AssignmentQuestionType::ShortText, 'prompt' => 'Schreiben Sie eine kurze E-Mail an Ihren Chef: Sie sind krank.', 'points' => 3, 'config' => null],
                ],
            ],
        ]);
    }

    /** Past-due so the "overdue" styling shows. */
    private function seedOverdueAssignment(User $teacher, Batch $batch): void
    {
        $this->publish($teacher, $batch, 'Demo 4 · Überfällig: Vokabeltest', 'Diese Aufgabe ist absichtlich überfällig.', now()->subDays(3), [
            [
                'label' => 'A', 'title' => 'Vokabeln',
                'questions' => [
                    ['type' => AssignmentQuestionType::Mcq, 'prompt' => '„Danke“ bedeutet…', 'points' => 1, 'config' => ['options' => ['thanks', 'sorry', 'please'], 'correct_index' => 0]],
                ],
            ],
        ]);
    }

    private function seedGradedSubmission(Assignment $assignment, User $student, User $teacher): void
    {
        $questions = $assignment->questions()->orderBy('section_id')->orderBy('sort_order')->get();

        $submission = AssignmentSubmission::query()->updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            [
                'status' => AssignmentSubmissionStatus::Graded,
                'submitted_at' => now()->subDays(2),
                'auto_score' => 5,
                'final_score' => 7,
                'score_overridden' => true,
                'feedback' => 'Sehr gut! Achten Sie auf die Verbposition im Nebensatz. Keep it up.',
                'graded_by' => $teacher->id,
                'graded_at' => now()->subDay(),
            ],
        );

        AssignmentAnswer::query()->where('submission_id', $submission->id)->delete();
        SubmissionAnnotation::query()->where('submission_id', $submission->id)->delete();

        foreach ($questions as $question) {
            [$answer, $correct] = match ($question->type) {
                AssignmentQuestionType::Mcq => [['selected_index' => $question->config['correct_index']], true],
                AssignmentQuestionType::ShortText => [['text' => 'Ich stehe um sechs Uhr auf, frühstücke und gehe dann zur Arbeit.'], null],
                AssignmentQuestionType::FillInBlank => [['blanks' => ['stehe', 'aus']], false],
                AssignmentQuestionType::WordReorder => [['ordered_tokens' => ['ich', 'arbeite', 'im', 'Krankenhaus']], true],
                default => [[], null],
            };

            AssignmentAnswer::query()->create([
                'submission_id' => $submission->id,
                'question_id' => $question->id,
                'answer' => $answer,
                'is_correct' => $correct,
            ]);
        }

        SubmissionAnnotation::query()->create([
            'submission_id' => $submission->id,
            'question_id' => $questions->firstWhere('type', AssignmentQuestionType::ShortText)?->id,
            'created_by' => $teacher->id,
            'type' => SubmissionAnnotationType::Memo,
            'payload' => ['x' => 10, 'y' => 12, 'text' => 'Schöner Satz – „dann“ steht hier richtig.'],
        ]);
    }

    private function seedSubmittedSubmission(Assignment $assignment, User $student): void
    {
        $submission = AssignmentSubmission::query()->updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            ['status' => AssignmentSubmissionStatus::Submitted, 'submitted_at' => now()->subHours(5), 'auto_score' => 1],
        );

        AssignmentAnswer::query()->where('submission_id', $submission->id)->delete();

        foreach ($assignment->questions()->whereIn('type', [AssignmentQuestionType::Mcq, AssignmentQuestionType::ShortText])->get() as $question) {
            AssignmentAnswer::query()->create([
                'submission_id' => $submission->id,
                'question_id' => $question->id,
                'answer' => $question->type === AssignmentQuestionType::Mcq ? ['selected_index' => 0] : ['text' => 'Es geht um das Thema Gesundheit.'],
                'is_correct' => $question->type === AssignmentQuestionType::Mcq ? true : null,
            ]);
        }
    }

    /**
     * Study Bank: nested folders with a PDF, an image, audio, a link, a YouTube video and a playlist.
     */
    private function seedStudyBank(User $teacher, Batch $batch, User $student): void
    {
        $root = MaterialFolder::query()->firstOrCreate(
            ['parent_id' => null, 'name' => 'Demo · Deutsch B1'],
            ['created_by' => $teacher->id],
        );
        $grammar = MaterialFolder::query()->firstOrCreate(
            ['parent_id' => $root->id, 'name' => 'Grammatik'],
            ['created_by' => $teacher->id],
        );
        $listening = MaterialFolder::query()->firstOrCreate(
            ['parent_id' => $root->id, 'name' => 'Hörübungen'],
            ['created_by' => $teacher->id],
        );
        MaterialFolder::query()->firstOrCreate(
            ['parent_id' => $root->id, 'name' => 'Leer (Empty folder state)'],
            ['created_by' => $teacher->id],
        );

        $files = $this->demoMediaFiles();

        $pdf = $this->materialFile($teacher, $grammar, 'Grammatik-Übersicht', 'pdf', $files['pdf']);
        $this->materialFile($teacher, $grammar, 'Bild: Krankenhaus-Flur', 'png', $files['image']);
        $audio = $this->materialFile($teacher, $listening, 'Hörübung: Testton', 'wav', $files['audio']);

        $this->materialLink($teacher, $grammar, 'Deutsche Welle: Deutsch lernen', 'https://www.dw.com/de/deutsch-lernen/s-2055', MaterialFile::KIND_LINK, 'link');
        $this->materialLink($teacher, $listening, 'Nicos Weg A1 – Folge 1', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', MaterialFile::KIND_YOUTUBE, 'youtube');
        $this->materialLink($teacher, $listening, 'Playlist: Alltag & Beruf', 'https://www.youtube.com/playlist?list=PLDemoGermanB1', MaterialFile::KIND_YOUTUBE_PLAYLIST, 'youtube_playlist');

        MaterialBatchAssignment::query()->firstOrCreate([
            'assignable_type' => MaterialFolder::class,
            'assignable_id' => $root->id,
            'batch_id' => $batch->id,
        ], ['assigned_by' => $teacher->id]);

        LearningItem::query()->updateOrCreate(
            ['user_id' => $student->id, 'material_file_id' => $pdf->id],
            ['note' => 'Kapitel 2 nochmal lesen – Nebensätze!'],
        );
        LearningItem::query()->updateOrCreate(
            ['user_id' => $student->id, 'material_file_id' => $audio->id],
            ['note' => null],
        );
    }

    /**
     * @param  array{disk: string, path: string, original_name: string}  $file
     */
    private function materialFile(User $teacher, MaterialFolder $folder, string $name, string $extension, array $file): MaterialFile
    {
        $mime = ['pdf' => 'application/pdf', 'png' => 'image/png', 'wav' => 'audio/wav'][$extension];

        return MaterialFile::query()->updateOrCreate(
            ['folder_id' => $folder->id, 'display_name' => $name],
            [
                'kind' => MaterialFile::KIND_FILE,
                'original_name' => $file['original_name'],
                'disk' => $file['disk'],
                'path' => $file['path'],
                'url' => null,
                'mime_type' => $mime,
                'extension' => $extension,
                'size' => Storage::disk($file['disk'])->size($file['path']),
                'created_by' => $teacher->id,
            ],
        );
    }

    private function materialLink(User $teacher, MaterialFolder $folder, string $name, string $url, string $kind, string $extension): void
    {
        MaterialFile::query()->updateOrCreate(
            ['folder_id' => $folder->id, 'display_name' => $name],
            [
                'kind' => $kind,
                'original_name' => $url,
                'disk' => null,
                'path' => null,
                'url' => $url,
                'mime_type' => null,
                'extension' => $extension,
                'size' => 0,
                'created_by' => $teacher->id,
            ],
        );
    }

    private function seedAttendance(User $teacher, Batch $batch, User $student): void
    {
        foreach (range(1, 8) as $daysAgo) {
            $startedAt = now()->subDays($daysAgo)->setTime(9, 30);

            $session = AttendanceSession::query()->firstOrCreate(
                ['batch_id' => $batch->id, 'started_at' => $startedAt],
                ['opened_by' => $teacher->id, 'secret' => 'demo-'.$daysAgo, 'ttl_seconds' => 30, 'closed_at' => $startedAt->copy()->addHour()],
            );

            // Most days present; one excused; day 6 left unmarked so the calendar shows an absence.
            if ($daysAgo === 6) {
                continue;
            }

            AttendanceRecord::query()->updateOrCreate(
                ['attendance_session_id' => $session->id, 'student_id' => $student->id],
                [
                    'status' => $daysAgo === 4 ? AttendanceStatus::Excused : AttendanceStatus::Present,
                    'method' => $daysAgo === 4 ? 'manual' : 'qr',
                    'marked_at' => $startedAt->copy()->addMinutes(3),
                    'marked_by' => $daysAgo === 4 ? $teacher->id : null,
                    'note' => $daysAgo === 4 ? 'Doctor appointment' : null,
                ],
            );
        }
    }

    private function seedLeaveRequests(User $teacher, Batch $batch, User $student): void
    {
        $requests = [
            ['from' => now()->addDays(3), 'to' => now()->addDays(4), 'reason' => 'Family function in Siliguri.', 'status' => LeaveStatus::Pending, 'note' => null],
            ['from' => now()->subDays(12), 'to' => now()->subDays(11), 'reason' => 'Fever, doctor advised rest.', 'status' => LeaveStatus::Approved, 'note' => 'Get well soon.'],
            ['from' => now()->subDays(20), 'to' => now()->subDays(20), 'reason' => 'Personal work.', 'status' => LeaveStatus::Rejected, 'note' => 'Exam week – please attend.'],
        ];

        foreach ($requests as $request) {
            LeaveRequest::query()->updateOrCreate(
                ['student_id' => $student->id, 'batch_id' => $batch->id, 'reason' => $request['reason']],
                [
                    'from_date' => $request['from']->toDateString(),
                    'to_date' => $request['to']->toDateString(),
                    'status' => $request['status'],
                    'decided_by' => $request['status'] === LeaveStatus::Pending ? null : $teacher->id,
                    'decided_at' => $request['status'] === LeaveStatus::Pending ? null : now()->subDays(1),
                    'decision_note' => $request['note'],
                ],
            );
        }
    }

    /**
     * Writes small real PDF / PNG / WAV files once and returns the `config` shape media questions expect.
     *
     * @return array{pdf: array{disk: string, path: string, original_name: string}, image: array{disk: string, path: string, original_name: string}, audio: array{disk: string, path: string, original_name: string}}
     */
    private function demoMediaFiles(): array
    {
        $disk = Storage::disk('local');
        $dir = 'demo/student-experience';

        if (! $disk->exists("{$dir}/uebersicht.pdf")) {
            $disk->put("{$dir}/uebersicht.pdf", $this->minimalPdf('Grammatik-Übersicht (Demo)'));
        }

        if (! $disk->exists("{$dir}/krankenhaus.png")) {
            $image = imagecreatetruecolor(800, 500);
            imagefill($image, 0, 0, imagecolorallocate($image, 30, 90, 200));
            imagefilledellipse($image, 400, 250, 300, 300, imagecolorallocate($image, 255, 255, 255));
            imagestring($image, 5, 330, 245, 'Demo image', imagecolorallocate($image, 3, 24, 53));
            ob_start();
            imagepng($image);
            $disk->put("{$dir}/krankenhaus.png", (string) ob_get_clean());
        }

        if (! $disk->exists("{$dir}/testton.wav")) {
            $disk->put("{$dir}/testton.wav", $this->sineWav(440, 3));
        }

        return [
            'pdf' => ['disk' => 'local', 'path' => "{$dir}/uebersicht.pdf", 'original_name' => 'uebersicht.pdf'],
            'image' => ['disk' => 'local', 'path' => "{$dir}/krankenhaus.png", 'original_name' => 'krankenhaus.png'],
            'audio' => ['disk' => 'local', 'path' => "{$dir}/testton.wav", 'original_name' => 'testton.wav'],
        ];
    }

    private function minimalPdf(string $title): string
    {
        $stream = "BT /F1 24 Tf 72 720 Td ({$title}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function sineWav(int $frequency, int $seconds): string
    {
        $rate = 8000;
        $samples = '';

        for ($i = 0; $i < $rate * $seconds; $i++) {
            $samples .= pack('v', (int) (sin(2 * M_PI * $frequency * $i / $rate) * 12000) & 0xFFFF);
        }

        $size = strlen($samples);

        return 'RIFF'.pack('V', 36 + $size).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16).'data'.pack('V', $size).$samples;
    }
}
