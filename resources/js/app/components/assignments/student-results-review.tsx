import { Link } from '@inertiajs/react';
import {
    ArrowLeft,
    BadgeCheck,
    Check,
    CircleHelp,
    MessageSquareText,
    PencilLine,
    Sparkles,
    X,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { AssignmentAudioPlayer } from '@app/components/assignments/assignment-audio-player';
import { formatAnswerDisplayText } from '@app/components/assignments/gradable-answer-card';
import { AnnotationLayer } from '@app/components/assignments/grading/annotation-layer';
import { ensureClientIds } from '@app/components/assignments/grading/grading-types';
import { HighlightedAnswerText } from '@app/components/assignments/grading/highlight-text';
import { MediaQuestionResource } from '@app/components/assignments/media-question-resource';
import { isMediaItemType } from '@app/types/assignments';
import type {
    AssignmentData,
    AssignmentQuestion,
    AssignmentSubmissionData,
    SubmissionAnnotation,
} from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Entry = AssignmentSubmissionData['answers'][number];

type Tier = { title: string; message: string; gradient: string };

function tierFor(percent: number): Tier {
    if (percent >= 90) {
        return {
            title: 'Excellent!',
            message: 'Outstanding work. You really know this topic.',
            gradient: 'from-emerald-500 via-emerald-600 to-teal-700',
        };
    }

    if (percent >= 75) {
        return {
            title: 'Great job!',
            message: 'Solid result. A little polish and it is perfect.',
            gradient: 'from-teal-500 via-emerald-600 to-sky-700',
        };
    }

    if (percent >= 50) {
        return {
            title: 'Good effort',
            message: 'You are getting there. Check the notes below.',
            gradient: 'from-amber-500 via-orange-500 to-orange-700',
        };
    }

    return {
        title: 'Keep practising',
        message: 'Read the corrections below. You will improve quickly.',
        gradient: 'from-rose-500 via-orange-600 to-rose-800',
    };
}

/** The answer-key version of the student's answer, shown only when they got it wrong. */
function correctAnswerText(question: AssignmentQuestion): string | null {
    if (question.type === 'mcq') {
        return question.correct_index !== undefined
            ? (question.options?.[question.correct_index] ?? null)
            : null;
    }

    if (question.type === 'fill_in_blank' && question.model_answers) {
        const answers = question.model_answers.map((options) => options[0]);
        let blank = 0;

        return (question.template ?? question.prompt).replace(
            /_{3,}/g,
            () => answers[blank++] ?? '___',
        );
    }

    if (question.type === 'word_reorder') {
        return question.model_sentence ?? null;
    }

    return null;
}

function Status({ correct }: { correct: boolean | null }) {
    if (correct === true) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                <Check className="size-3.5" />
                Correct
            </span>
        );
    }

    if (correct === false) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-rose-500/15 px-2.5 py-1 text-xs font-bold text-rose-700 dark:text-rose-300">
                <X className="size-3.5" />
                Not quite
            </span>
        );
    }

    return (
        <span className="inline-flex items-center gap-1 rounded-full bg-sky-500/15 px-2.5 py-1 text-xs font-bold text-sky-700 dark:text-sky-300">
            <PencilLine className="size-3.5" />
            Checked by trainer
        </span>
    );
}

function TrainerNote({ annotation }: { annotation: SubmissionAnnotation }) {
    const text = String(annotation.payload.text ?? '').trim();

    if (!text) {
        return null;
    }

    return (
        <div className="flex gap-3 rounded-2xl border-2 border-amber-400 bg-amber-400/12 p-3.5">
            <MessageSquareText className="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-300" />
            <div className="min-w-0">
                <p className="text-xs font-bold tracking-wide text-amber-700 uppercase dark:text-amber-300">
                    Trainer note
                </p>
                <p className="mt-0.5 text-base leading-snug font-medium whitespace-pre-wrap">
                    {text}
                </p>
            </div>
        </div>
    );
}

function QuestionResult({
    entry,
    number,
    assignmentId,
    annotations,
}: {
    entry: Entry;
    number: number;
    assignmentId: number;
    annotations: SubmissionAnnotation[];
}) {
    const { question } = entry;
    const mine = annotations.filter((a) => a.question_id === entry.question_id);
    const notes = mine.filter((a) => a.type === 'memo');
    const marks = ensureClientIds(mine.filter((a) => a.type !== 'memo'));
    const answerText = formatAnswerDisplayText(question, entry.answer);
    const correctText =
        entry.is_correct === false ? correctAnswerText(question) : null;

    return (
        <article
            className={cn(
                'space-y-3 rounded-3xl border-2 bg-card p-4 shadow-sm',
                entry.is_correct === true && 'border-emerald-500/40',
                entry.is_correct === false && 'border-rose-500/40',
                entry.is_correct === null && 'border-sky-500/30',
            )}
        >
            <div className="flex items-center justify-between gap-3">
                <span className="flex size-8 items-center justify-center rounded-full bg-foreground/10 text-sm font-bold tabular-nums">
                    {number}
                </span>
                <Status correct={entry.is_correct} />
            </div>

            <p className="text-lg leading-snug font-semibold">
                {question.prompt}
            </p>

            {isMediaItemType(question.type) && (
                <MediaQuestionResource
                    assignmentId={assignmentId}
                    question={question}
                    portal="student"
                />
            )}

            <div className="space-y-1.5">
                <p className="text-xs font-bold tracking-wide text-muted-foreground uppercase">
                    Your answer
                </p>
                <div
                    className={cn(
                        'relative min-h-12 rounded-2xl p-3.5 text-base font-medium',
                        entry.is_correct === true && 'bg-emerald-500/10',
                        entry.is_correct === false && 'bg-rose-500/10',
                        entry.is_correct === null && 'bg-muted/60',
                    )}
                >
                    <div className="relative z-0 whitespace-pre-wrap">
                        {answerText ? (
                            <HighlightedAnswerText
                                text={answerText}
                                annotations={marks}
                            />
                        ) : (
                            <span className="text-muted-foreground">
                                No answer
                            </span>
                        )}
                    </div>
                    <AnnotationLayer
                        annotations={marks}
                        tool="select"
                        selectedClientId={null}
                        autoFocusClientId={null}
                        readOnly
                        onSelect={() => undefined}
                        onUpdate={() => undefined}
                        onAutoFocusComplete={() => undefined}
                    />
                </div>
            </div>

            {correctText && (
                <div className="space-y-1.5">
                    <p className="text-xs font-bold tracking-wide text-emerald-700 uppercase dark:text-emerald-300">
                        Correct answer
                    </p>
                    <p className="rounded-2xl bg-emerald-500/15 p-3.5 text-base font-semibold text-emerald-800 dark:text-emerald-200">
                        {correctText}
                    </p>
                </div>
            )}

            {notes.map((note, index) => (
                <TrainerNote key={note.id ?? index} annotation={note} />
            ))}
        </article>
    );
}

export default function StudentResultsReview({
    assignment,
    submission,
}: {
    assignment: AssignmentData;
    submission: AssignmentSubmissionData;
}) {
    const score = submission.final_score ?? submission.auto_score ?? 0;
    const max = Math.max(assignment.max_score, 1);
    const percent = Math.round((score / max) * 100);
    const tier = tierFor(percent);
    const correct = submission.answers.filter(
        (a) => a.is_correct === true,
    ).length;
    const wrong = submission.answers.filter(
        (a) => a.is_correct === false,
    ).length;
    const manual = submission.answers.filter(
        (a) => a.is_correct === null,
    ).length;
    const annotations = submission.annotations ?? [];
    const generalNotes = annotations.filter(
        (a) => a.type === 'memo' && a.question_id === null,
    );
    const grader = submission.graded_by?.name ?? null;
    const gradedOn = submission.graded_at
        ? new Date(submission.graded_at).toLocaleDateString('en-IN', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : null;

    return (
        <>
            <PrivateSeoHead title={`Results: ${assignment.title}`} />
            <div className="mx-auto flex max-w-3xl flex-col gap-5 p-2 sm:px-4">
                <div>
                    <Link
                        href="/student/assignments"
                        className="mb-2 inline-flex items-center gap-1 text-sm font-medium text-muted-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Assignments
                    </Link>
                    <h1 className="text-lg leading-snug font-semibold md:text-lg">
                        {assignment.title}
                    </h1>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        {grader ? `Graded by ${grader}` : 'Graded'}
                        {gradedOn && ` · ${gradedOn}`}
                    </p>
                </div>

                <section
                    className={cn(
                        'relative overflow-hidden rounded-3xl bg-gradient-to-br p-5 text-white shadow-lg',
                        tier.gradient,
                    )}
                >
                    <Sparkles className="absolute -top-3 -right-3 size-28 text-white/15" />
                    <p className="text-sm font-bold tracking-wide uppercase opacity-90">
                        {tier.title}
                    </p>
                    <div className="mt-1 flex items-end gap-2">
                        <span className="text-6xl leading-none font-extrabold tabular-nums">
                            {score}
                        </span>
                        <span className="pb-1 text-2xl font-semibold opacity-80">
                            / {assignment.max_score}
                        </span>
                        <span className="mb-1 ml-auto rounded-full bg-white/20 px-3 py-1 text-lg font-bold tabular-nums">
                            {percent}%
                        </span>
                    </div>
                    <div className="mt-4 h-2.5 overflow-hidden rounded-full bg-white/25">
                        <div
                            className="h-full rounded-full bg-white transition-all duration-700"
                            style={{ width: `${Math.min(percent, 100)}%` }}
                        />
                    </div>
                    <p className="mt-3 text-sm opacity-95">{tier.message}</p>
                    {submission.score_overridden && (
                        <p className="mt-3 inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">
                            <BadgeCheck className="size-4" />
                            Score adjusted by your trainer
                        </p>
                    )}
                </section>

                <div className="grid grid-cols-3 gap-2.5">
                    <div className="rounded-2xl bg-emerald-500/12 p-3 text-center">
                        <p className="text-2xl font-extrabold text-emerald-700 tabular-nums dark:text-emerald-300">
                            {correct}
                        </p>
                        <p className="text-xs font-semibold text-emerald-800/80 dark:text-emerald-200/80">
                            Correct
                        </p>
                    </div>
                    <div className="rounded-2xl bg-rose-500/12 p-3 text-center">
                        <p className="text-2xl font-extrabold text-rose-700 tabular-nums dark:text-rose-300">
                            {wrong}
                        </p>
                        <p className="text-xs font-semibold text-rose-800/80 dark:text-rose-200/80">
                            To review
                        </p>
                    </div>
                    <div className="rounded-2xl bg-sky-500/12 p-3 text-center">
                        <p className="text-2xl font-extrabold text-sky-700 tabular-nums dark:text-sky-300">
                            {manual}
                        </p>
                        <p className="text-xs font-semibold text-sky-800/80 dark:text-sky-200/80">
                            Trainer checked
                        </p>
                    </div>
                </div>

                {submission.feedback && (
                    <section className="space-y-2 rounded-3xl border border-primary/30 bg-primary/10 p-4">
                        <div className="flex items-center gap-3">
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                                {(grader ?? 'T')
                                    .split(' ')
                                    .map((part) => part[0])
                                    .slice(0, 2)
                                    .join('')
                                    .toUpperCase()}
                            </span>
                            <div>
                                <p className="text-sm font-bold">
                                    {grader ?? 'Your trainer'}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Overall feedback
                                </p>
                            </div>
                        </div>
                        <p className="text-base leading-relaxed whitespace-pre-wrap">
                            {submission.feedback}
                        </p>
                    </section>
                )}

                {generalNotes.map((note, index) => (
                    <TrainerNote key={note.id ?? index} annotation={note} />
                ))}

                {assignment.has_audio && (
                    <AssignmentAudioPlayer
                        assignmentId={assignment.id}
                        label={assignment.audio_original_name}
                        portal="student"
                    />
                )}

                <div className="flex items-center gap-2 pt-1">
                    <CircleHelp className="size-4 text-muted-foreground" />
                    <h2 className="text-sm font-bold tracking-wide text-muted-foreground uppercase">
                        Question by question
                    </h2>
                </div>

                {submission.answers.map((entry, index) => (
                    <QuestionResult
                        key={entry.question_id}
                        entry={entry}
                        number={index + 1}
                        assignmentId={assignment.id}
                        annotations={annotations}
                    />
                ))}

                <Button
                    asChild
                    variant="outline"
                    className="h-12 rounded-full text-base"
                >
                    <Link href="/student/assignments">Back to assignments</Link>
                </Button>
            </div>
        </>
    );
}
