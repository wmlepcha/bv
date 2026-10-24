import { AssignmentAudioPlayer } from '@app/components/assignments/assignment-audio-player';
import { GradableAnswerCard } from '@app/components/assignments/gradable-answer-card';
import StudentResultsReview from '@app/components/assignments/student-results-review';
import type {
    AssignmentData,
    AssignmentSubmissionData,
} from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    assignment: AssignmentData;
    submission: AssignmentSubmissionData;
    portal?: 'student' | 'teacher' | 'training-manager';
};

export default function SubmissionReview({
    assignment,
    submission,
    portal = 'student',
}: Props) {
    if (portal === 'student') {
        return (
            <StudentResultsReview
                assignment={assignment}
                submission={submission}
            />
        );
    }

    return (
        <>
            <PrivateSeoHead title={`Results: ${assignment.title}`} />
            <div className="mx-auto flex max-w-3xl flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {assignment.title}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Score:{' '}
                        {submission.final_score ?? submission.auto_score ?? 0} /{' '}
                        {assignment.max_score}
                        {submission.score_overridden && ' (adjusted by grader)'}
                    </p>
                    {submission.feedback && (
                        <p className="mt-3 rounded-lg border bg-muted/40 p-3 text-sm">
                            {submission.feedback}
                        </p>
                    )}
                </div>

                {assignment.has_audio && (
                    <AssignmentAudioPlayer
                        assignmentId={assignment.id}
                        label={assignment.audio_original_name}
                        portal={portal}
                    />
                )}

                {submission.answers.map((entry) => (
                    <GradableAnswerCard
                        key={entry.question_id}
                        questionId={entry.question_id}
                        question={entry.question}
                        answer={entry.answer}
                        isCorrect={entry.is_correct}
                        tool="select"
                        annotations={submission.annotations ?? []}
                        readOnly
                        assignmentId={assignment.id}
                        portal={portal}
                    />
                ))}
            </div>
        </>
    );
}
