import { useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, Send } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { store as storeFeedback } from '@/routes/student/help-center/feedback';
import { ActionBar } from '@app/components/native/form';
import { StarRating } from '@app/components/star-rating';
import { haptic } from '@app/lib/native';
import InputError from '@shared/components/input-error';

export type FeedbackOption = { value: string; label: string };

type Step =
    | { key: string; title: string; type: 'choice'; options: FeedbackOption[] }
    | { key: string; title: string; type: 'rating' }
    | { key: string; title: string; type: 'text' };

type Props = {
    ratingQuestions: Array<{ key: string; question: string }>;
    levelOptions: FeedbackOption[];
    skillOptions: FeedbackOption[];
    paceOptions: FeedbackOption[];
    defaultLevel: string;
    maxAnswerLength: number;
    onSubmitted?: () => void;
};

const FORBIDDEN_CHARACTERS = /[<>]/;

function buildSteps({
    ratingQuestions,
    levelOptions,
    skillOptions,
    paceOptions,
}: Pick<
    Props,
    'ratingQuestions' | 'levelOptions' | 'skillOptions' | 'paceOptions'
>): Step[] {
    const rating = (key: string): Step | null => {
        const question = ratingQuestions.find((item) => item.key === key);

        return question
            ? { key, title: question.question, type: 'rating' }
            : null;
    };

    return [
        {
            key: 'level',
            title: 'Which level have you completed?',
            type: 'choice',
            options: levelOptions,
        },
        rating('overall_satisfaction'),
        rating('trainer_quality'),
        rating('explanation_clarity'),
        rating('class_engagement'),
        rating('materials_usefulness'),
        rating('skills_improvement'),
        rating('confidence'),
        {
            key: 'most_improved_skill',
            title: 'Which skill improved the most?',
            type: 'choice',
            options: skillOptions,
        },
        {
            key: 'needs_improvement_skill',
            title: 'Which skill needs the most improvement?',
            type: 'choice',
            options: skillOptions,
        },
        {
            key: 'pace',
            title: 'Was the course pace appropriate for you?',
            type: 'choice',
            options: paceOptions,
        },
        rating('support_satisfaction'),
        {
            key: 'liked_most',
            title: 'What did you like most about this course?',
            type: 'text',
        },
        {
            key: 'improvement_suggestion',
            title: 'What is the one thing we should improve for the next level?',
            type: 'text',
        },
    ].filter((step): step is Step => step !== null);
}

export function CourseFeedbackWizard({
    defaultLevel,
    maxAnswerLength,
    onSubmitted,
    ...options
}: Props) {
    const [steps] = useState(() => buildSteps(options));
    const [stepIndex, setStepIndex] = useState(0);

    const form = useForm<Record<string, string | number>>(
        Object.fromEntries(
            steps.map((step) => [
                step.key,
                step.key === 'level'
                    ? defaultLevel
                    : step.type === 'rating'
                      ? 0
                      : '',
            ]),
        ),
    );

    const step = steps[stepIndex];
    const value = form.data[step.key];
    const isLastStep = stepIndex === steps.length - 1;
    const progress = Math.round((stepIndex / steps.length) * 100);

    const textError = (answer: string): string | undefined => {
        if (FORBIDDEN_CHARACTERS.test(answer)) {
            return 'Please remove the < and > characters from your answer.';
        }

        if (answer.length > maxAnswerLength) {
            return `Your answer may not be longer than ${maxAnswerLength} characters.`;
        }

        return undefined;
    };

    const isAnswered = (current: Step): boolean => {
        const answer = form.data[current.key];

        if (current.type === 'rating') {
            return Number(answer) >= 1;
        }

        if (current.type === 'text') {
            const text = String(answer).trim();

            return text.length > 0 && textError(text) === undefined;
        }

        return String(answer) !== '';
    };

    const goNext = () => {
        if (!isAnswered(step)) {
            return;
        }

        if (isLastStep) {
            submit();

            return;
        }

        setStepIndex((index) => index + 1);
    };

    const submit = () => {
        const firstUnanswered = steps.findIndex((item) => !isAnswered(item));

        if (firstUnanswered !== -1) {
            setStepIndex(firstUnanswered);

            return;
        }

        form.post(storeFeedback.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setStepIndex(0);
                onSubmitted?.();
            },
            onError: (errors) => {
                const firstError = steps.findIndex(
                    (item) => errors[item.key] !== undefined,
                );

                if (firstError !== -1) {
                    setStepIndex(firstError);
                }
            },
        });
    };

    const setAnswer = (answer: string | number) => {
        form.setData(step.key, answer);
        form.clearErrors(step.key);
    };

    const liveTextError =
        step.type === 'text' ? textError(String(value)) : undefined;

    return (
        <form
            className="flex flex-col gap-6"
            onSubmit={(event) => {
                event.preventDefault();
                goNext();
            }}
        >
            <div className="flex flex-col gap-2">
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span>
                        Question {stepIndex + 1} of {steps.length}
                    </span>
                    <span>{progress}% complete</span>
                </div>
                <div
                    className="h-2 overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={progress}
                >
                    <div
                        className="h-full rounded-full bg-primary transition-all duration-300"
                        style={{ width: `${progress}%` }}
                    />
                </div>
            </div>

            <div
                key={step.key}
                className="flex min-h-[200px] animate-in flex-col gap-5 duration-300 fade-in slide-in-from-right-4"
            >
                <h3 className="text-lg leading-snug font-semibold">
                    {stepIndex + 1}) {step.title}
                </h3>

                {step.type === 'choice' && (
                    <div className="divide-y divide-border/70 overflow-hidden rounded-2xl border border-border bg-card/70 sm:grid sm:grid-cols-2 sm:gap-2 sm:divide-y-0 sm:overflow-visible sm:rounded-none sm:border-0 sm:bg-transparent">
                        {step.options.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                role="radio"
                                aria-checked={value === option.value}
                                onClick={() => {
                                    haptic();
                                    setAnswer(option.value);
                                }}
                                className={cn(
                                    'flex min-h-12 w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-base font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none active:bg-muted sm:rounded-lg sm:border sm:text-sm',
                                    value === option.value
                                        ? 'bg-primary/10 text-foreground sm:border-primary sm:bg-primary sm:text-primary-foreground'
                                        : 'sm:border-border sm:bg-background sm:hover:bg-muted',
                                )}
                            >
                                {option.label}
                                {value === option.value && (
                                    <Check className="size-5 shrink-0 text-primary sm:text-primary-foreground" />
                                )}
                            </button>
                        ))}
                    </div>
                )}

                {step.type === 'rating' && (
                    <StarRating
                        label={step.title}
                        value={Number(value)}
                        onChange={setAnswer}
                    />
                )}

                {step.type === 'text' && (
                    <div className="flex flex-col gap-1.5">
                        <Textarea
                            value={String(value)}
                            onChange={(event) =>
                                setAnswer(
                                    event.target.value.slice(
                                        0,
                                        maxAnswerLength,
                                    ),
                                )
                            }
                            rows={5}
                            maxLength={maxAnswerLength}
                            autoFocus
                            placeholder="Type your answer here…"
                            aria-invalid={liveTextError !== undefined}
                        />
                        <div className="flex justify-between gap-3 text-xs text-muted-foreground">
                            <span>
                                {liveTextError && (
                                    <span className="text-red-600 dark:text-red-400">
                                        {liveTextError}
                                    </span>
                                )}
                            </span>
                            <span
                                className={cn(
                                    'shrink-0 tabular-nums',
                                    String(value).length >= maxAnswerLength &&
                                        'text-red-600 dark:text-red-400',
                                )}
                            >
                                {String(value).length} / {maxAnswerLength}
                            </span>
                        </div>
                    </div>
                )}

                <InputError message={form.errors[step.key]} />
            </div>

            <ActionBar>
                <Button
                    type="button"
                    variant="outline"
                    className="h-12 rounded-full px-5"
                    disabled={stepIndex === 0 || form.processing}
                    onClick={() => setStepIndex((index) => index - 1)}
                >
                    <ArrowLeft />
                    Back
                </Button>

                <Button
                    type="submit"
                    className="h-12 flex-1 rounded-full text-base"
                    disabled={!isAnswered(step) || form.processing}
                >
                    {form.processing && <Spinner />}
                    {isLastStep ? (
                        <>
                            Submit feedback
                            {!form.processing && <Send />}
                        </>
                    ) : (
                        <>
                            Next
                            <ArrowRight />
                        </>
                    )}
                </Button>
            </ActionBar>
        </form>
    );
}
