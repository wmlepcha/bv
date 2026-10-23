import { router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import {
    questionIdFromServerErrors,
    validateStudentAnswers
    
} from '@app/components/assignments/assignment-validation';
import type {StudentAnswerState} from '@app/components/assignments/assignment-validation';
import { FillInBlankAnswer } from '@app/components/assignments/fill-in-blank-answer';
import { GermanTextInput } from '@app/components/assignments/german-text-input';
import { MediaQuestionResource } from '@app/components/assignments/media-question-resource';
import { WordReorderAnswer } from '@app/components/assignments/word-reorder-answer';
import {
    WorksheetItem,
    WorksheetLayout,
    WorksheetSection,
} from '@app/components/assignments/worksheet-layout';
import { isMediaItemType } from '@app/types/assignments';
import type {
    AssignmentData,
    AssignmentQuestion,
    AssignmentSubmissionData,
} from '@app/types/assignments';
import InputError from '@shared/components/input-error';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    assignment: AssignmentData;
    hasSubmitted: boolean;
    submission: AssignmentSubmissionData | null;
};

function QuestionAnswer({
    assignmentId,
    question,
    answer,
    onChange,
}: {
    assignmentId: number;
    question: AssignmentQuestion;
    answer: StudentAnswerState;
    onChange: (next: StudentAnswerState) => void;
}) {
    if (question.type === 'mcq') {
        return (
            <div className="grid gap-2">
                {question.options?.map((option, optionIndex) => (
                    <label
                        key={optionIndex}
                        className="flex items-center gap-2 text-sm"
                    >
                        <input
                            type="radio"
                            name={`q-${question.id}`}
                            checked={answer.selected_index === optionIndex}
                            onChange={() =>
                                onChange({ selected_index: optionIndex })
                            }
                        />
                        {option}
                    </label>
                ))}
            </div>
        );
    }

    if (question.type === 'fill_in_blank') {
        const blankCount = question.blank_count ?? 1;

        return (
            <FillInBlankAnswer
                question={question}
                value={
                    answer.blanks ??
                    Array.from({ length: blankCount }, () => '')
                }
                onChange={(blanks) => onChange({ blanks })}
            />
        );
    }

    if (question.type === 'word_reorder') {
        return (
            <WordReorderAnswer
                question={question}
                value={answer.ordered_tokens ?? []}
                onChange={(ordered_tokens) => onChange({ ordered_tokens })}
            />
        );
    }

    if (isMediaItemType(question.type)) {
        return (
            <div className="grid gap-4">
                <MediaQuestionResource
                    assignmentId={assignmentId}
                    question={question}
                    portal="student"
                />
                <div className="grid gap-2">
                    <Label>Your answer</Label>
                    <GermanTextInput
                        multiline
                        rows={3}
                        value={answer.text ?? ''}
                        onChange={(text) => onChange({ text })}
                    />
                </div>
            </div>
        );
    }

    return (
        <div className="grid gap-2">
            <Label>Your answer</Label>
            <GermanTextInput
                multiline
                rows={3}
                value={answer.text ?? ''}
                onChange={(text) => onChange({ text })}
            />
        </div>
    );
}

function renderSections(
    assignment: AssignmentData,
    answers: Record<number, StudentAnswerState>,
    setAnswer: (questionId: number, next: StudentAnswerState) => void,
    errorQuestionId: number | null,
) {
    const sections = assignment.sections ?? [];

    if (sections.length > 0) {
        return sections.map((section) => (
            <WorksheetSection
                key={section.id ?? section.label}
                label={section.label}
                title={section.title}
                instructions={section.instructions}
            >
                {(section.questions ?? []).map((question, index) => (
                    <WorksheetItem key={question.id} number={index + 1}>
                        <div
                            id={
                                question.id !== undefined
                                    ? `question-${question.id}`
                                    : undefined
                            }
                            className={cn(
                                errorQuestionId === question.id &&
                                    'rounded-lg ring-2 ring-destructive/60 ring-offset-2 ring-offset-background',
                            )}
                        >
                            {question.type !== 'fill_in_blank' &&
                                question.type !== 'word_reorder' && (
                                    <p className="mb-2 text-sm">
                                        {question.prompt}
                                    </p>
                                )}
                            {question.type === 'word_reorder' && (
                                <p className="mb-2 text-sm text-muted-foreground">
                                    {question.tokens?.join(' / ')}
                                </p>
                            )}
                            <QuestionAnswer
                                assignmentId={assignment.id}
                                question={question}
                                answer={answers[question.id!] ?? {}}
                                onChange={(next) =>
                                    setAnswer(question.id!, next)
                                }
                            />
                        </div>
                    </WorksheetItem>
                ))}
            </WorksheetSection>
        ));
    }

    return (
        <div className="grid gap-4">
            {assignment.questions?.map((question, index) => (
                <div
                    key={question.id}
                    id={
                        question.id !== undefined
                            ? `question-${question.id}`
                            : undefined
                    }
                    className={cn(
                        'rounded-xl border border-border/70 p-4',
                        errorQuestionId === question.id &&
                            'ring-2 ring-destructive/60',
                    )}
                >
                    <p className="mb-3 text-sm font-medium">
                        Q{index + 1}. {question.prompt}
                    </p>
                    <QuestionAnswer
                        assignmentId={assignment.id}
                        question={question}
                        answer={answers[question.id!] ?? {}}
                        onChange={(next) => setAnswer(question.id!, next)}
                    />
                </div>
            ))}
        </div>
    );
}

export default function StudentAssignmentShow({
    assignment,
    hasSubmitted,
}: Props) {
    const { errors } = usePage().props as { errors?: Record<string, string> };

    const allQuestions = useMemo(() => {
        if (assignment.sections && assignment.sections.length > 0) {
            return assignment.sections.flatMap((s) => s.questions ?? []);
        }

        return assignment.questions ?? [];
    }, [assignment]);

    const [answers, setAnswers] = useState<Record<number, StudentAnswerState>>(
        {},
    );
    const [submitting, setSubmitting] = useState(false);
    const [errorQuestionId, setErrorQuestionId] = useState<number | null>(null);
    const [submitError, setSubmitError] = useState<string | null>(null);

    const setAnswer = (questionId: number, next: StudentAnswerState) => {
        setAnswers((prev) => ({ ...prev, [questionId]: next }));
        setSubmitError(null);

        if (errorQuestionId === questionId) {
            setErrorQuestionId(null);
        }
    };

    useEffect(() => {
        if (!errors || Object.keys(errors).length === 0) {
            return;
        }

        const message =
            Object.values(errors)[0] ??
            'Please complete all questions before submitting.';
        const questionId = questionIdFromServerErrors(errors);

        setSubmitError(message);
        setErrorQuestionId(questionId);
        toast.error(message);

        if (questionId !== null) {
            document
                .getElementById(`question-${questionId}`)
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, [errors]);

    const submit = () => {
        const issue = validateStudentAnswers(assignment, answers);

        if (issue) {
            setSubmitError(issue.message);
            setErrorQuestionId(issue.questionId);
            toast.error(issue.message);
            document
                .getElementById(`question-${issue.questionId}`)
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });

            return;
        }

        setSubmitError(null);
        setErrorQuestionId(null);

        router.post(
            `/student/assignments/${assignment.id}`,
            {
                answers: allQuestions.map((question) => ({
                    question_id: question.id,
                    selected_index: answers[question.id!]?.selected_index,
                    text: answers[question.id!]?.text,
                    blanks: answers[question.id!]?.blanks,
                    ordered_tokens: answers[question.id!]?.ordered_tokens,
                })),
            },
            {
                preserveScroll: true,
                onStart: () => setSubmitting(true),
                onFinish: () => setSubmitting(false),
                onError: (serverErrors) => {
                    const message =
                        Object.values(serverErrors)[0] ??
                        'Please complete all questions before submitting.';
                    const questionId = questionIdFromServerErrors(serverErrors);

                    setSubmitError(message);
                    setErrorQuestionId(questionId);
                    toast.error(message);

                    if (questionId !== null) {
                        document
                            .getElementById(`question-${questionId}`)
                            ?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center',
                            });
                    }
                },
            },
        );
    };

    return (
        <>
            <PrivateSeoHead title={assignment.title} />
            <div className="p-2 sm:px-4">
                <WorksheetLayout assignment={assignment}>
                    {hasSubmitted ? (
                        <p className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                            You have submitted this assignment. Awaiting grade
                            from your training manager.
                        </p>
                    ) : (
                        <>
                            <div className="grid gap-6">
                                {renderSections(
                                    assignment,
                                    answers,
                                    setAnswer,
                                    errorQuestionId,
                                )}
                            </div>

                            <div className="sticky bottom-[var(--app-bottom-h,0px)] z-10 -mx-2 border-t border-border/70 bg-background/90 px-4 py-3 backdrop-blur-xl sm:-mx-4 md:bottom-0 md:py-4">
                                <InputError
                                    message={submitError}
                                    className="mb-3"
                                />
                                <Button
                                    type="button"
                                    className="h-12 w-full rounded-full text-base sm:h-9 sm:w-auto sm:rounded-md sm:text-sm"
                                    disabled={submitting}
                                    onClick={submit}
                                >
                                    {submitting && <Spinner className="mr-2" />}
                                    Submit assignment
                                </Button>
                            </div>
                        </>
                    )}
                </WorksheetLayout>
            </div>
        </>
    );
}
