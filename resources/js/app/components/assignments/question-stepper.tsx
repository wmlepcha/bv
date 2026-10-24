import { router } from '@inertiajs/react';
import {
    CalendarClock,
    Check,
    ChevronLeft,
    ChevronRight,
    Circle,
    ClipboardList,
    Lock,
    Sparkles,
    Trophy,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { AssignmentAudioPlayer } from '@app/components/assignments/assignment-audio-player';
import { validateStudentAnswer } from '@app/components/assignments/assignment-validation';
import type { StudentAnswerState } from '@app/components/assignments/assignment-validation';
import ConfirmDialog from '@app/components/confirm-dialog';
import type {
    AssignmentData,
    AssignmentQuestion,
} from '@app/types/assignments';
import InputError from '@shared/components/input-error';

type Step = {
    question: AssignmentQuestion;
    sectionLabel: string | null;
    sectionTitle: string | null;
    sectionInstructions: string | null;
};

type Props = {
    assignment: AssignmentData;
    answers: Record<number, StudentAnswerState>;
    setAnswer: (questionId: number, next: StudentAnswerState) => void;
    renderAnswer: (question: AssignmentQuestion) => ReactNode;
    /** Question to jump to, e.g. after a failed submit. */
    focusQuestionId: number | null;
    submitError: string | null;
    submitting: boolean;
    onSubmit: () => void;
};

const TYPE_LABELS: Record<string, string> = {
    mcq: 'Multiple choice',
    short_text: 'Written answers',
    fill_in_blank: 'Fill in the blanks',
    word_reorder: 'Sentence building',
    pdf: 'PDF reading',
    image: 'Picture task',
    audio: 'Listening',
    link: 'Online resource',
};

const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];

function buildSteps(assignment: AssignmentData): Step[] {
    const sections = assignment.sections ?? [];

    if (sections.length === 0) {
        return (assignment.questions ?? []).map((question) => ({
            question,
            sectionLabel: null,
            sectionTitle: null,
            sectionInstructions: null,
        }));
    }

    return sections.flatMap((section) =>
        (section.questions ?? []).map((question) => ({
            question,
            sectionLabel: section.label,
            sectionTitle: section.title ?? null,
            sectionInstructions: section.instructions ?? null,
        })),
    );
}

/** What the student picked or typed, for the review list. */
function answerSummary(
    question: AssignmentQuestion,
    answer: StudentAnswerState | undefined,
): string | null {
    if (!answer) {
        return null;
    }

    if (question.type === 'mcq') {
        return answer.selected_index === undefined
            ? null
            : (question.options?.[answer.selected_index] ?? null);
    }

    if (question.type === 'fill_in_blank') {
        const blanks = (answer.blanks ?? []).filter((b) => b.trim() !== '');

        return blanks.length > 0 ? blanks.join(' · ') : null;
    }

    if (question.type === 'word_reorder') {
        return answer.ordered_tokens?.length
            ? answer.ordered_tokens.join(' ')
            : null;
    }

    return answer.text?.trim() ? answer.text.trim() : null;
}

function encouragement(done: number, total: number): string {
    if (done === 0) {
        return "Let's get started";
    }

    if (done === total) {
        return 'All answered. Nice work!';
    }

    return done / total >= 0.5
        ? 'More than halfway there'
        : 'Good start, keep going';
}

/** One question per screen, with a progress bar and a final review step (phones). */
export function QuestionStepper({
    assignment,
    answers,
    setAnswer,
    renderAnswer,
    focusQuestionId,
    submitError,
    submitting,
    onSubmit,
}: Props) {
    const steps = useMemo(() => buildSteps(assignment), [assignment]);
    const total = steps.length;
    const hasMultipleSections = (assignment.sections?.length ?? 0) > 1;
    const [index, setIndex] = useState(-1);
    const [direction, setDirection] = useState<'forward' | 'back'>('forward');
    const topRef = useRef<HTMLDivElement>(null);

    const isAnswered = (question: AssignmentQuestion) =>
        question.id !== undefined &&
        validateStudentAnswer(question, answers[question.id] ?? {}) === null;

    const answeredCount = steps.filter((step) =>
        isAnswered(step.question),
    ).length;
    const typeLabels = useMemo(
        () => [
            ...new Set(
                steps.map(
                    (s) => TYPE_LABELS[s.question.type] ?? s.question.type,
                ),
            ),
        ],
        [steps],
    );
    const isIntro = index < 0;
    const started = !isIntro;

    // Once the test has started, leaving the page (menus, logo, logout, tab close) asks first.
    const allowLeave = useRef(false);
    const [pendingVisit, setPendingVisit] = useState<{
        url: string;
        method: 'get' | 'post';
    } | null>(null);

    useEffect(() => {
        if (!started) {
            return;
        }

        const stopBefore = router.on('before', (event) => {
            const visit = event.detail.visit;
            const target = new URL(String(visit.url), window.location.href);

            if (
                allowLeave.current ||
                visit.prefetch ||
                target.pathname === window.location.pathname
            ) {
                return;
            }

            setPendingVisit({
                url: target.pathname + target.search,
                method: visit.method === 'get' ? 'get' : 'post',
            });

            return false;
        });

        const warnOnUnload = (event: BeforeUnloadEvent) =>
            event.preventDefault();

        if (!submitting) {
            window.addEventListener('beforeunload', warnOnUnload);
        }

        return () => {
            stopBefore();
            window.removeEventListener('beforeunload', warnOnUnload);
        };
    }, [started, submitting]);
    const isReview = index >= total;
    const step = steps[index];
    const canAdvance = step === undefined || isAnswered(step.question);

    const goTo = (next: number) => {
        setDirection(next >= index ? 'forward' : 'back');
        setIndex(Math.min(Math.max(next, -1), total));
        topRef.current?.scrollIntoView({ block: 'start' });
    };

    // Jump to the offending question after a failed submit (adjust state during render, not in an effect).
    const [seenFocusId, setSeenFocusId] = useState<number | null>(null);

    if (focusQuestionId !== seenFocusId) {
        setSeenFocusId(focusQuestionId);

        const target = steps.findIndex(
            (s) => s.question.id === focusQuestionId,
        );

        if (focusQuestionId !== null && target >= 0) {
            setIndex(target);
        }
    }

    return (
        <div
            ref={topRef}
            className="flex scroll-mt-[var(--app-top-h,0px)] flex-col gap-4"
        >
            {!isIntro && (
                <div className="sticky top-[var(--app-top-h,0px)] z-20 -mx-2 space-y-3 border-b border-border/60 bg-background/90 px-2 py-3 backdrop-blur-xl">
                    <div className="flex items-end justify-between gap-3">
                        <p className="min-w-0 truncate text-xs font-semibold text-muted-foreground">
                            {assignment.title}
                        </p>
                        <p className="shrink-0 text-xs font-semibold text-primary tabular-nums">
                            {isReview ? 'Review' : `${index + 1} / ${total}`}
                        </p>
                    </div>

                    <div className="flex gap-1" aria-hidden>
                        {steps.map((s, i) => (
                            <span
                                key={s.question.id ?? i}
                                className={cn(
                                    'h-1.5 flex-1 rounded-full transition-colors duration-300',
                                    i === index && !isReview
                                        ? 'bg-primary'
                                        : isAnswered(s.question)
                                          ? 'bg-primary/45'
                                          : 'bg-foreground/15',
                                )}
                            />
                        ))}
                    </div>
                    <p className="text-xs text-muted-foreground">
                        {encouragement(answeredCount, total)} · {answeredCount}{' '}
                        of {total} answered
                    </p>
                </div>
            )}

            {assignment.has_audio && !isIntro && !isReview && (
                <AssignmentAudioPlayer
                    assignmentId={assignment.id}
                    label={assignment.audio_original_name}
                    portal="student"
                />
            )}

            <div
                key={index}
                className={cn(
                    'rounded-3xl border border-border/70 bg-card p-5 shadow-sm',
                    direction === 'forward'
                        ? 'step-enter-forward'
                        : 'step-enter-back',
                )}
            >
                {isIntro ? (
                    <div className="space-y-5">
                        <div className="flex items-center gap-3">
                            <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                                <ClipboardList className="size-6" />
                            </span>
                            <h2 className="text-2xl leading-tight font-bold tracking-tight">
                                {assignment.title}
                            </h2>
                        </div>

                        {assignment.instructions && (
                            <p className="text-base leading-relaxed text-muted-foreground">
                                {assignment.instructions}
                            </p>
                        )}

                        <dl className="grid grid-cols-2 gap-2.5">
                            <div className="rounded-2xl bg-muted/60 p-3">
                                <dt className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <ClipboardList className="size-3.5" />
                                    Questions
                                </dt>
                                <dd className="mt-1 text-xl font-bold tabular-nums">
                                    {total}
                                </dd>
                            </div>
                            <div className="rounded-2xl bg-muted/60 p-3">
                                <dt className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Trophy className="size-3.5" />
                                    Total points
                                </dt>
                                <dd className="mt-1 text-xl font-bold tabular-nums">
                                    {assignment.max_score}
                                </dd>
                            </div>
                            {assignment.due_at && (
                                <div className="col-span-2 rounded-2xl bg-muted/60 p-3">
                                    <dt className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <CalendarClock className="size-3.5" />
                                        Due
                                    </dt>
                                    <dd className="mt-1 text-base font-semibold">
                                        {new Date(
                                            assignment.due_at,
                                        ).toLocaleString('en-IN', {
                                            weekday: 'short',
                                            day: 'numeric',
                                            month: 'short',
                                            hour: 'numeric',
                                            minute: '2-digit',
                                        })}
                                    </dd>
                                </div>
                            )}
                        </dl>

                        <div className="space-y-2">
                            <p className="text-sm font-semibold">
                                What&apos;s inside
                            </p>
                            <div className="flex flex-wrap gap-2">
                                {typeLabels.map((label) => (
                                    <span
                                        key={label}
                                        className="rounded-full border border-border/70 px-3 py-1 text-xs font-medium"
                                    >
                                        {label}
                                    </span>
                                ))}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <p className="text-sm font-semibold">
                                Before you start
                            </p>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li className="flex gap-2">
                                    <Lock className="mt-0.5 size-4 shrink-0 text-primary" />
                                    Answer each question to unlock the next one.
                                </li>
                                <li className="flex gap-2">
                                    <Check className="mt-0.5 size-4 shrink-0 text-primary" />
                                    You can go back and change answers before
                                    you submit.
                                </li>
                                <li className="flex gap-2">
                                    <Check className="mt-0.5 size-4 shrink-0 text-primary" />
                                    You can only submit once, so review at the
                                    end.
                                </li>
                            </ul>
                        </div>
                    </div>
                ) : isReview ? (
                    <div className="space-y-4">
                        <div className="flex items-center gap-3">
                            <span className="flex size-11 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                                <Sparkles className="size-5" />
                            </span>
                            <div>
                                <h2 className="text-xl font-bold tracking-tight">
                                    Ready to submit?
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {answeredCount} of {total} answered. Tap a
                                    question to change it.
                                </p>
                            </div>
                        </div>
                        <ul className="divide-y divide-border/60 overflow-hidden rounded-2xl border border-border/60">
                            {steps.map((s, i) => {
                                const done = isAnswered(s.question);
                                const summary = answerSummary(
                                    s.question,
                                    answers[s.question.id ?? -1],
                                );

                                return (
                                    <li key={s.question.id ?? i}>
                                        <button
                                            type="button"
                                            onClick={() => goTo(i)}
                                            className="flex min-h-12 w-full items-center gap-3 px-3 py-2.5 text-left active:bg-foreground/5"
                                        >
                                            {done ? (
                                                <Check className="size-5 shrink-0 text-emerald-500" />
                                            ) : (
                                                <Circle className="size-5 shrink-0 text-amber-500" />
                                            )}
                                            <span className="min-w-0 flex-1 text-sm">
                                                <span className="line-clamp-2 text-muted-foreground">
                                                    {i + 1}. {s.question.prompt}
                                                </span>
                                                <span
                                                    className={cn(
                                                        'mt-0.5 line-clamp-2 font-semibold',
                                                        summary
                                                            ? 'text-foreground'
                                                            : 'text-amber-500',
                                                    )}
                                                >
                                                    {summary ?? 'Not answered'}
                                                </span>
                                            </span>
                                            <ChevronRight className="size-4 shrink-0 text-muted-foreground/60" />
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                ) : (
                    step && (
                        <div
                            id={`question-${step.question.id}`}
                            className="space-y-5"
                        >
                            {hasMultipleSections &&
                                (step.sectionTitle ||
                                    step.sectionInstructions) && (
                                    <div className="space-y-1">
                                        {step.sectionTitle && (
                                            <span className="inline-flex items-center gap-2 rounded-full bg-primary/12 px-3 py-1 text-xs font-semibold text-primary">
                                                {step.sectionLabel &&
                                                    step.sectionLabel.length <=
                                                        2 && (
                                                        <span className="flex size-5 items-center justify-center rounded-full bg-primary text-[11px] text-primary-foreground">
                                                            {step.sectionLabel}
                                                        </span>
                                                    )}
                                                {step.sectionTitle}
                                            </span>
                                        )}
                                        {step.sectionInstructions && (
                                            <p className="text-sm text-muted-foreground">
                                                {step.sectionInstructions}
                                            </p>
                                        )}
                                    </div>
                                )}

                            {step.question.type !== 'fill_in_blank' && (
                                <h2 className="text-2xl leading-snug font-bold tracking-tight">
                                    {step.question.type === 'word_reorder'
                                        ? (step.question.prompt ??
                                          'Put the words in order')
                                        : step.question.prompt}
                                </h2>
                            )}

                            {step.question.type === 'mcq' ? (
                                <div
                                    className="grid gap-2.5"
                                    role="radiogroup"
                                    aria-label="Answer options"
                                >
                                    {step.question.options?.map(
                                        (option, optionIndex) => {
                                            const selected =
                                                answers[step.question.id!]
                                                    ?.selected_index ===
                                                optionIndex;

                                            return (
                                                <button
                                                    key={optionIndex}
                                                    type="button"
                                                    role="radio"
                                                    aria-checked={selected}
                                                    onClick={() =>
                                                        setAnswer(
                                                            step.question.id!,
                                                            {
                                                                selected_index:
                                                                    optionIndex,
                                                            },
                                                        )
                                                    }
                                                    className={cn(
                                                        'flex min-h-14 items-center gap-3 rounded-2xl border-2 px-3.5 py-3 text-left text-base font-medium transition-all active:scale-[0.98]',
                                                        selected
                                                            ? 'border-primary bg-primary/10 shadow-sm'
                                                            : 'border-border/70 bg-background',
                                                    )}
                                                >
                                                    <span
                                                        className={cn(
                                                            'flex size-8 shrink-0 items-center justify-center rounded-xl text-sm font-bold',
                                                            selected
                                                                ? 'bg-primary text-primary-foreground'
                                                                : 'bg-muted text-muted-foreground',
                                                        )}
                                                    >
                                                        {selected ? (
                                                            <Check className="size-4" />
                                                        ) : (
                                                            LETTERS[optionIndex]
                                                        )}
                                                    </span>
                                                    {option}
                                                </button>
                                            );
                                        },
                                    )}
                                </div>
                            ) : (
                                renderAnswer(step.question)
                            )}
                        </div>
                    )
                )}
            </div>

            <ConfirmDialog
                open={pendingVisit !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingVisit(null);
                    }
                }}
                title="Leave this test?"
                description="Your answers so far will be lost and you will need to start again."
                confirmLabel="Leave test"
                cancelLabel="Keep going"
                destructive
                onConfirm={() => {
                    allowLeave.current = true;

                    if (pendingVisit) {
                        router.visit(pendingVisit.url, {
                            method: pendingVisit.method,
                        });
                    }
                }}
            />

            <div className="sticky bottom-[var(--app-bottom-h,0px)] z-10 -mx-2 border-t border-border/70 bg-background/90 px-4 py-3 backdrop-blur-xl">
                <InputError
                    message={submitError ?? undefined}
                    className="mb-3"
                />
                {!isIntro && !isReview && !canAdvance && (
                    <p className="mb-2 flex items-center justify-center gap-1.5 text-xs text-muted-foreground">
                        <Lock className="size-3.5" />
                        Answer this question to continue
                    </p>
                )}
                <div className="flex gap-3">
                    {index > 0 && (
                        <Button
                            type="button"
                            variant="outline"
                            className="h-12 shrink-0 rounded-full px-5 text-base"
                            disabled={submitting}
                            onClick={() => goTo(index - 1)}
                        >
                            <ChevronLeft className="mr-1 size-5" />
                            Back
                        </Button>
                    )}
                    {isReview ? (
                        <Button
                            type="button"
                            className="h-12 flex-1 rounded-full text-base"
                            disabled={submitting}
                            onClick={onSubmit}
                        >
                            {submitting && <Spinner className="mr-2" />}
                            Submit assignment
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            className="h-12 flex-1 rounded-full text-base"
                            disabled={!canAdvance}
                            onClick={() => goTo(index + 1)}
                        >
                            {isIntro
                                ? 'Start assignment'
                                : index === total - 1
                                  ? 'Review answers'
                                  : 'Next'}
                            {!isIntro && (
                                <ChevronRight className="ml-1 size-5" />
                            )}
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}
