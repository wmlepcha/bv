<?php

namespace App\Support;

use App\Enums\CourseLevel;
use App\Enums\CoursePace;
use App\Enums\FeedbackSkill;
use App\Http\Requests\Student\StoreCourseFeedbackRequest;
use App\Models\CourseFeedback;
use App\Models\User;

class CourseFeedbackPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forStudent(User $student): array
    {
        $student->loadMissing(['batchEnrollment.batch.center', 'batchEnrollment.batch.teacher']);
        $batch = $student->batchEnrollment?->batch;
        $languageLabel = $batch?->language->label() ?? 'the language';

        $openFormNumber = $student->openCourseFeedbackFormNumber();

        return [
            'feedback' => [
                'open_form_number' => $openFormNumber,
                'forms' => self::formSchedule($student, $openFormNumber),
                'candidate' => [
                    'name' => $student->name,
                    'batch_name' => $batch?->name,
                    'trainer_name' => $batch?->teacher?->name,
                    'center_name' => $batch?->center?->name,
                ],
                'default_level' => $batch?->level->value,
                'rating_questions' => self::ratingQuestions($languageLabel),
                'level_options' => self::options(CourseLevel::options()),
                'skill_options' => self::options(FeedbackSkill::options()),
                'pace_options' => self::options(CoursePace::options()),
                'max_answer_length' => StoreCourseFeedbackRequest::MAX_ANSWER_LENGTH,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forIndex(CourseFeedback $feedback): array
    {
        return [
            'id' => $feedback->id,
            'student_name' => $feedback->student?->name,
            'form_number' => $feedback->form_number,
            'level' => $feedback->level->value,
            'batch_name' => $feedback->batch?->name,
            'trainer_name' => $feedback->teacher?->name,
            'center_name' => $feedback->center?->name,
            'overall_satisfaction' => $feedback->overall_satisfaction,
            'average_rating' => $feedback->averageRating(),
            'pace_label' => $feedback->pace->label(),
            'submitted_at_label' => $feedback->created_at->format('j M Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function show(CourseFeedback $feedback): array
    {
        $feedback->loadMissing(['student:id,name,email', 'batch:id,name,language', 'teacher:id,name', 'center:id,name']);
        $languageLabel = $feedback->batch?->language->label() ?? 'the language';

        return [
            'feedback' => [
                ...self::forIndex($feedback),
                'student_email' => $feedback->student?->email,
                'ratings' => array_map(
                    fn (array $question): array => [
                        ...$question,
                        'value' => (int) $feedback->getAttribute($question['key']),
                    ],
                    self::ratingQuestions($languageLabel),
                ),
                'most_improved_skill_label' => $feedback->most_improved_skill->label(),
                'needs_improvement_skill_label' => $feedback->needs_improvement_skill->label(),
                'liked_most' => $feedback->liked_most,
                'improvement_suggestion' => $feedback->improvement_suggestion,
            ],
        ];
    }

    /**
     * @return list<array{number: int, status: string, available_at_label: string|null, level: string|null, submitted_at_label: string|null}>
     */
    private static function formSchedule(User $student, ?int $openFormNumber): array
    {
        $submitted = $student->courseFeedback()->get()->keyBy('form_number');
        $forms = [];

        foreach (range(1, User::COURSE_FEEDBACK_FORM_COUNT) as $formNumber) {
            /** @var CourseFeedback|null $feedback */
            $feedback = $submitted->get($formNumber);

            $forms[] = [
                'number' => $formNumber,
                'status' => match (true) {
                    $feedback !== null => 'submitted',
                    $formNumber === $openFormNumber => 'open',
                    default => 'locked',
                },
                'available_at_label' => $student->courseFeedbackFormAvailableAt($formNumber)?->format('j M Y'),
                'level' => $feedback?->level->value,
                'submitted_at_label' => $feedback?->created_at->format('j M Y'),
            ];
        }

        return $forms;
    }

    /**
     * @return list<array{key: string, question: string}>
     */
    public static function ratingQuestions(string $languageLabel): array
    {
        $questions = [];

        foreach (CourseFeedback::RATING_QUESTIONS as $key => $question) {
            $questions[] = [
                'key' => $key,
                'question' => str_replace('the language', $languageLabel, $question),
            ];
        }

        return $questions;
    }

    /**
     * @param  array<string, string>  $options
     * @return list<array{value: string, label: string}>
     */
    private static function options(array $options): array
    {
        $formatted = [];

        foreach ($options as $value => $label) {
            $formatted[] = ['value' => (string) $value, 'label' => $label];
        }

        return $formatted;
    }
}
