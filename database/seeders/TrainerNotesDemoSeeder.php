<?php

namespace Database\Seeders;

use App\Enums\AssignmentQuestionType;
use App\Enums\AssignmentStatus;
use App\Enums\AssignmentSubmissionStatus;
use App\Enums\AssignmentTargetScope;
use App\Enums\SubmissionAnnotationType;
use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSection;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentTarget;
use App\Models\Batch;
use App\Models\SubmissionAnnotation;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One graded submission carrying every kind of trainer note the grading tool can leave:
 * highlights in all five colours, sticky memos in all six colours, pen strokes, a note on
 * a wrong auto-graded answer, a general note, and overall feedback.
 *
 * Run: php artisan db:seed --class=TrainerNotesDemoSeeder
 */
class TrainerNotesDemoSeeder extends Seeder
{
    private const STUDENT_EMAIL = 'wilburlepcha@outlook.com';

    private const BATCH_NAME = 'German B1 · Morning · Demo';

    private const TEACHER_EMAIL = 'teacher@demo.bilingualvitae.com';

    private const TITLE = 'Demo 5 · Schreiben mit Trainer-Notizen';

    private const FAMILY = 'Meine Familie ist klein. Ich habe zwei Bruder und eine Schwester. Mein Vater arbeitet als Arzt und meine Mutter ist Lehrerin. Wir wohnen in Gangtok.';

    private const EMAIL = 'Sehr geehrter Herr Müller, ich bin heute krank und kann nicht kommen. Ich gehe zum Arzt. Mit freundlichen Grüßen, Rapgyal';

    private const WEEKEND = 'Am Samstag ich habe Freunde besucht. Wir haben zusammen gekocht und Fußball gespielt.';

    public function run(): void
    {
        $teacher = User::query()->where('email', self::TEACHER_EMAIL)->firstOrFail();
        $batch = Batch::query()->where('name', self::BATCH_NAME)->firstOrFail();
        $student = User::query()->where('email', self::STUDENT_EMAIL)->firstOrFail();

        $assignment = $this->assignment($teacher, $batch);
        $questions = $assignment->questions()->orderBy('section_id')->orderBy('sort_order')->get()->values();

        $submission = AssignmentSubmission::query()->updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            [
                'status' => AssignmentSubmissionStatus::Graded,
                'submitted_at' => now()->subDays(3),
                'auto_score' => 0,
                'final_score' => 9,
                'score_overridden' => true,
                'feedback' => "Gute Fortschritte, Rapgyal! Ihre Sätze sind verständlich und die E-Mail ist höflich geschrieben.\n\nBitte wiederholen Sie bis nächste Woche: (1) den Plural der Nomen, (2) die Verbposition im Hauptsatz. Ich habe Ihnen dazu Notizen an die einzelnen Antworten geschrieben.",
                'graded_by' => $teacher->id,
                'graded_at' => now()->subDay(),
            ],
        );

        AssignmentAnswer::query()->where('submission_id', $submission->id)->delete();
        SubmissionAnnotation::query()->where('submission_id', $submission->id)->delete();

        [$family, $email, $weekend, $article, $verb, $order] = $questions->all();

        $answers = [
            [$family, ['text' => self::FAMILY], null],
            [$email, ['text' => self::EMAIL], null],
            [$weekend, ['text' => self::WEEKEND], null],
            [$article, ['selected_index' => 1], false],
            [$verb, ['blanks' => ['trinke']], false],
            [$order, ['ordered_tokens' => ['zur', 'Schule', 'ich', 'gehe']], false],
        ];

        foreach ($answers as [$question, $answer, $correct]) {
            AssignmentAnswer::query()->create([
                'submission_id' => $submission->id,
                'question_id' => $question->id,
                'answer' => $answer,
                'is_correct' => $correct,
            ]);
        }

        $this->annotate($submission, $teacher, $family, $email, $weekend, $article, $verb, $order);
    }

    private function assignment(User $teacher, Batch $batch): Assignment
    {
        $assignment = Assignment::query()->updateOrCreate(
            ['batch_id' => $batch->id, 'title' => self::TITLE],
            [
                'created_by' => $teacher->id,
                'instructions' => 'Schreiben Sie kurze Texte und lösen Sie die Grammatikaufgaben. Ihr Trainer korrigiert alles mit Notizen.',
                'due_at' => now()->subDays(4),
                'status' => AssignmentStatus::Published,
                'published_at' => now()->subWeek(),
                'approved_by' => $teacher->id,
                'approved_at' => now()->subWeek(),
                'max_score' => 14,
            ],
        );

        AssignmentTarget::query()->where('assignment_id', $assignment->id)->delete();
        AssignmentTarget::query()->create([
            'assignment_id' => $assignment->id,
            'scope' => AssignmentTargetScope::Batch,
            'student_id' => null,
        ]);

        if ($assignment->questions()->exists()) {
            return $assignment;
        }

        $sections = [
            ['A', 'Schreiben', [
                [AssignmentQuestionType::ShortText, 'Beschreiben Sie Ihre Familie (3–4 Sätze).', 3, null],
                [AssignmentQuestionType::ShortText, 'Schreiben Sie eine kurze E-Mail an Ihren Chef: Sie sind krank.', 3, null],
                [AssignmentQuestionType::ShortText, 'Was haben Sie am Wochenende gemacht?', 3, null],
            ]],
            ['B', 'Grammatik', [
                [AssignmentQuestionType::Mcq, 'Welcher Artikel passt: ___ Mädchen?', 1, ['options' => ['der', 'die', 'das'], 'correct_index' => 2]],
                [AssignmentQuestionType::FillInBlank, 'Er ___ jeden Morgen Kaffee.', 2, ['template' => 'Er ___ jeden Morgen Kaffee.', 'hints' => ['trinken'], 'blank_count' => 1, 'model_answers' => [['trinkt']]]],
                [AssignmentQuestionType::WordReorder, 'Bringen Sie die Wörter in die richtige Reihenfolge.', 2, ['tokens' => ['gehe', 'ich', 'zur', 'Schule'], 'model_sentence' => 'Ich gehe zur Schule.']],
            ]],
        ];

        foreach ($sections as $sectionIndex => [$label, $title, $questions]) {
            $section = AssignmentSection::query()->create([
                'assignment_id' => $assignment->id,
                'label' => $label,
                'title' => $title,
                'sort_order' => $sectionIndex,
            ]);

            foreach ($questions as $index => [$type, $prompt, $points, $config]) {
                AssignmentQuestion::query()->create([
                    'assignment_id' => $assignment->id,
                    'section_id' => $section->id,
                    'sort_order' => $index,
                    'type' => $type,
                    'prompt' => $prompt,
                    'points' => $points,
                    'config' => $config,
                ]);
            }
        }

        return $assignment;
    }

    private function annotate(
        AssignmentSubmission $submission,
        User $teacher,
        AssignmentQuestion $family,
        AssignmentQuestion $email,
        AssignmentQuestion $weekend,
        AssignmentQuestion $article,
        AssignmentQuestion $verb,
        AssignmentQuestion $order,
    ): void {
        // Highlights: all five colours across the three written answers.
        $this->highlight($submission, $teacher, $family, self::FAMILY, 'Meine Familie ist klein.', '#FDBA74');
        $this->highlight($submission, $teacher, $family, self::FAMILY, 'zwei Bruder', '#F9A8D4');
        $this->highlight($submission, $teacher, $family, self::FAMILY, 'Mein Vater arbeitet als Arzt', '#86EFAC');
        $this->highlight($submission, $teacher, $family, self::FAMILY, 'Lehrerin', '#FFE066');
        $this->highlight($submission, $teacher, $family, self::FAMILY, 'Wir wohnen in Gangtok.', '#93C5FD');
        $this->highlight($submission, $teacher, $weekend, self::WEEKEND, 'Am Samstag ich habe', '#F9A8D4');
        $this->highlight($submission, $teacher, $weekend, self::WEEKEND, 'gekocht und Fußball gespielt', '#86EFAC');

        // Memos: all six colours, short and long.
        $this->memo($submission, $teacher, $family, 'yellow', 'Plural: zwei Brüder, nicht „Bruder“. Merken: der Bruder → die Brüder.');
        $this->memo($submission, $teacher, $family, 'green', 'Schön erzählt! Der Satz über Ihre Eltern ist perfekt.');
        $this->memo($submission, $teacher, $email, 'blue', 'Gute Anrede! Nach dem Komma schreibt man klein weiter.');
        $this->memo($submission, $teacher, $email, 'orange', 'Es fehlt: Wann kommen Sie wieder zur Arbeit? Zum Beispiel: „Ich komme am Montag wieder.“');
        $this->memo($submission, $teacher, $weekend, 'pink', 'Verbposition! Richtig: „Am Samstag habe ich Freunde besucht.“ Das Verb steht an Position 2.');
        $this->memo($submission, $teacher, $weekend, 'lavender', 'Sehr gut: „haben gekocht“ und „gespielt“ sind im Perfekt richtig.');
        $this->memo($submission, $teacher, $article, 'orange', 'Wörter auf -chen sind immer sächlich: das Mädchen, das Brötchen.');
        $this->memo($submission, $teacher, $verb, 'yellow', 'er/sie/es → Endung -t: er trinkt.');
        $this->memo($submission, $teacher, $order, 'blue', 'Das Subjekt „ich“ steht vor dem Verb: Ich gehe zur Schule.');

        // Pen strokes: an underline under the greeting and a wavy line under a weak phrase.
        $this->pen($submission, $teacher, $email, 'M 14 40 L 190 38 L 190 41 L 14 43 Z', '#E53935');
        $this->pen($submission, $teacher, $weekend, 'M 14 40 Q 30 34 46 40 T 78 40 T 110 40 T 142 40 L 142 43 Q 126 49 110 43 T 78 43 T 46 43 T 14 43 Z', '#9333EA');

        // A general note not tied to any question.
        SubmissionAnnotation::query()->create([
            'submission_id' => $submission->id,
            'question_id' => null,
            'created_by' => $teacher->id,
            'type' => SubmissionAnnotationType::Memo,
            'payload' => ['x' => 12, 'y' => 12, 'width' => 200, 'color' => 'green', 'text' => 'Insgesamt: sehr gute Entwicklung seit Woche 1. Weiter so!'],
        ]);
    }

    private function highlight(AssignmentSubmission $submission, User $teacher, AssignmentQuestion $question, string $text, string $needle, string $color): void
    {
        $start = mb_strpos($text, $needle);

        if ($start === false) {
            return;
        }

        SubmissionAnnotation::query()->create([
            'submission_id' => $submission->id,
            'question_id' => $question->id,
            'created_by' => $teacher->id,
            'type' => SubmissionAnnotationType::Highlight,
            'payload' => [
                'start_offset' => $start,
                'end_offset' => $start + mb_strlen($needle),
                'color' => $color,
            ],
        ]);
    }

    private function memo(AssignmentSubmission $submission, User $teacher, AssignmentQuestion $question, string $color, string $text): void
    {
        SubmissionAnnotation::query()->create([
            'submission_id' => $submission->id,
            'question_id' => $question->id,
            'created_by' => $teacher->id,
            'type' => SubmissionAnnotationType::Memo,
            'payload' => ['x' => 40, 'y' => 70, 'width' => 200, 'color' => $color, 'text' => $text],
        ]);
    }

    private function pen(AssignmentSubmission $submission, User $teacher, AssignmentQuestion $question, string $path, string $color): void
    {
        SubmissionAnnotation::query()->create([
            'submission_id' => $submission->id,
            'question_id' => $question->id,
            'created_by' => $teacher->id,
            'type' => SubmissionAnnotationType::Pen,
            'payload' => ['path' => $path, 'color' => $color, 'strokeWidth' => 3],
        ]);
    }
}
