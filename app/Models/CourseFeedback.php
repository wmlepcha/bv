<?php

namespace App\Models;

use App\Enums\CourseLevel;
use App\Enums\CoursePace;
use App\Enums\FeedbackSkill;
use Database\Factories\CourseFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $student_id
 * @property int|null $batch_id
 * @property int|null $teacher_id
 * @property int|null $center_id
 * @property int $form_number
 * @property CourseLevel $level
 * @property int $overall_satisfaction
 * @property int $trainer_quality
 * @property int $explanation_clarity
 * @property int $class_engagement
 * @property int $materials_usefulness
 * @property int $skills_improvement
 * @property int $confidence
 * @property FeedbackSkill $most_improved_skill
 * @property FeedbackSkill $needs_improvement_skill
 * @property CoursePace $pace
 * @property int $support_satisfaction
 * @property string $liked_most
 * @property string $improvement_suggestion
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CourseFeedback extends Model
{
    /** @use HasFactory<CourseFeedbackFactory> */
    use HasFactory;

    /**
     * Star-rated questions, in the order they appear on the form.
     *
     * @var array<string, string>
     */
    public const RATING_QUESTIONS = [
        'overall_satisfaction' => 'How satisfied are you with the overall course?',
        'trainer_quality' => "How would you rate your trainer's teaching quality?",
        'explanation_clarity' => 'How clearly did the trainer explain concepts?',
        'class_engagement' => 'How interactive and engaging were the classes?',
        'materials_usefulness' => 'How useful were the study materials and practice activities?',
        'skills_improvement' => 'How well did the course improve your language skills?',
        'confidence' => 'How confident are you in using the language after completing this level?',
        'support_satisfaction' => 'How satisfied are you with the support provided by Bilingual Vitae outside regular classes?',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'batch_id',
        'teacher_id',
        'center_id',
        'form_number',
        'level',
        'overall_satisfaction',
        'trainer_quality',
        'explanation_clarity',
        'class_engagement',
        'materials_usefulness',
        'skills_improvement',
        'confidence',
        'most_improved_skill',
        'needs_improvement_skill',
        'pace',
        'support_satisfaction',
        'liked_most',
        'improvement_suggestion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'form_number' => 'integer',
            'level' => CourseLevel::class,
            'overall_satisfaction' => 'integer',
            'trainer_quality' => 'integer',
            'explanation_clarity' => 'integer',
            'class_engagement' => 'integer',
            'materials_usefulness' => 'integer',
            'skills_improvement' => 'integer',
            'confidence' => 'integer',
            'most_improved_skill' => FeedbackSkill::class,
            'needs_improvement_skill' => FeedbackSkill::class,
            'pace' => CoursePace::class,
            'support_satisfaction' => 'integer',
        ];
    }

    public function averageRating(): float
    {
        $ratings = array_map(fn (string $field): int => (int) $this->getAttribute($field), array_keys(self::RATING_QUESTIONS));

        return round(array_sum($ratings) / count($ratings), 1);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return BelongsTo<Center, $this>
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
