import { usePage } from '@inertiajs/react';
import { CheckCircle2, Lock, MessageSquareHeart } from 'lucide-react';
import { cn } from '@/lib/utils';
import { index as helpCenterIndex } from '@/routes/student/help-center';
import { CourseFeedbackWizard } from '@app/components/course-feedback-wizard';
import type { FeedbackOption } from '@app/components/course-feedback-wizard';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Option = FeedbackOption;

type FeedbackForm = {
    number: number;
    status: 'submitted' | 'open' | 'locked';
    available_at_label: string | null;
    level: string | null;
    submitted_at_label: string | null;
};

type Props = {
    feedback: {
        open_form_number: number | null;
        forms: FeedbackForm[];
        candidate: {
            name: string;
            batch_name: string | null;
            trainer_name: string | null;
            center_name: string | null;
        };
        default_level: string | null;
        rating_questions: Array<{ key: string; question: string }>;
        level_options: Option[];
        skill_options: Option[];
        pace_options: Option[];
        max_answer_length: number;
    };
};

function formStatusLabel(form: FeedbackForm): string {
    if (form.status === 'submitted') {
        return `Submitted${form.level ? ` (${form.level})` : ''} on ${form.submitted_at_label}`;
    }

    if (form.status === 'open') {
        return 'Open now';
    }

    if (form.available_at_label) {
        return `Opens on ${form.available_at_label}`;
    }

    return form.number === 1
        ? 'Opens after your dashboard is activated'
        : 'Opens 5 weeks after you submit feedback form 1';
}

export default function HelpCenter({ feedback }: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };

    const formVisible = feedback.open_form_number !== null;
    const allSubmitted = feedback.forms.every(
        (form) => form.status === 'submitted',
    );
    const nextLockedForm = feedback.forms.find(
        (form) => form.status === 'locked',
    );

    const detailRows: Array<[string, string | null]> = [
        ['Name', feedback.candidate.name],
        ['Batch name', feedback.candidate.batch_name],
        ["Trainer's name", feedback.candidate.trainer_name],
        ['Centre', feedback.candidate.center_name],
    ];

    return (
        <>
            <PrivateSeoHead title="Help Center" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Help Center
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Share your feedback about your course with Bilingual
                        Vitae.
                    </p>
                </div>

                {flash?.success && (
                    <p className="text-sm font-medium text-green-600 dark:text-emerald-400">
                        {flash.success}
                    </p>
                )}

                <section className="rounded-xl border border-border bg-card p-5">
                    <div className="flex items-start gap-3">
                        <MessageSquareHeart className="mt-0.5 size-5 shrink-0 text-primary" />
                        <div>
                            <h2 className="text-lg font-semibold">
                                Course completion feedback form
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Dear students, congratulations on achieving
                                another milestone in your language proficiency
                                journey. Please take a few minutes to share your
                                honest feedback.
                            </p>
                        </div>
                    </div>

                    <ol className="mt-4 grid gap-2 sm:grid-cols-2">
                        {feedback.forms.map((form) => (
                            <li
                                key={form.number}
                                className={cn(
                                    'flex items-start gap-2 rounded-lg border px-3 py-2 text-sm',
                                    form.status === 'open'
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border bg-muted/40',
                                )}
                            >
                                {form.status === 'submitted' ? (
                                    <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-green-600" />
                                ) : form.status === 'open' ? (
                                    <MessageSquareHeart className="mt-0.5 size-4 shrink-0 text-primary" />
                                ) : (
                                    <Lock className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                )}
                                <div>
                                    <p className="font-medium">
                                        Feedback form {form.number}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {formStatusLabel(form)}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ol>
                </section>

                {!formVisible && allSubmitted && (
                    <div className="flex items-start gap-3 rounded-xl border border-border bg-card p-5">
                        <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-green-600" />
                        <div>
                            <p className="font-medium">
                                Thank you for your feedback!
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                You have submitted all {feedback.forms.length}{' '}
                                feedback forms.
                            </p>
                        </div>
                    </div>
                )}

                {!formVisible && !allSubmitted && (
                    <div className="flex items-start gap-3 rounded-xl border border-dashed border-border bg-muted/30 p-5">
                        <Lock className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                        <div>
                            <p className="font-medium">
                                Your next feedback form is not available yet
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {nextLockedForm?.available_at_label
                                    ? `Feedback form ${nextLockedForm.number} opens on ${nextLockedForm.available_at_label}.`
                                    : nextLockedForm?.number === 1
                                      ? 'Your first feedback form opens 4 weeks after your dashboard is activated.'
                                      : 'Your next feedback form opens 5 weeks after you submit the previous one.'}
                            </p>
                        </div>
                    </div>
                )}

                {formVisible && (
                    <section className="flex flex-col gap-6 rounded-xl border border-border bg-card p-5">
                        <div className="grid gap-2 rounded-lg bg-muted/40 p-4 text-sm sm:grid-cols-2">
                            <p className="font-medium sm:col-span-2">
                                Feedback form {feedback.open_form_number} of{' '}
                                {feedback.forms.length} · Candidate details
                            </p>
                            {detailRows.map(([label, value]) => (
                                <p key={label}>
                                    <span className="text-muted-foreground">
                                        {label}:
                                    </span>{' '}
                                    {value ?? '—'}
                                </p>
                            ))}
                        </div>

                        <CourseFeedbackWizard
                            key={feedback.open_form_number}
                            ratingQuestions={feedback.rating_questions}
                            levelOptions={feedback.level_options}
                            skillOptions={feedback.skill_options}
                            paceOptions={feedback.pace_options}
                            defaultLevel={feedback.default_level ?? ''}
                            maxAnswerLength={feedback.max_answer_length}
                        />
                    </section>
                )}
            </div>
        </>
    );
}

HelpCenter.layout = {
    breadcrumbs: [
        {
            title: 'Help Center',
            href: helpCenterIndex(),
        },
    ],
};
