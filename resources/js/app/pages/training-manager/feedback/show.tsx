import { Link } from '@inertiajs/react';
import { ArrowLeft, Star } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    index as feedbackIndex,
    show as feedbackShow,
} from '@/routes/training-manager/feedback';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    feedback: {
        id: number;
        student_name: string | null;
        student_email: string | null;
        form_number: number;
        level: string;
        batch_name: string | null;
        trainer_name: string | null;
        center_name: string | null;
        average_rating: number;
        pace_label: string;
        submitted_at_label: string;
        ratings: Array<{ key: string; question: string; value: number }>;
        most_improved_skill_label: string;
        needs_improvement_skill_label: string;
        liked_most: string;
        improvement_suggestion: string;
    };
};

function Stars({ value }: { value: number }) {
    return (
        <span
            className="inline-flex items-center gap-0.5"
            aria-label={`${value} of 5 stars`}
        >
            {[1, 2, 3, 4, 5].map((star) => (
                <Star
                    key={star}
                    className={cn(
                        'size-4',
                        star <= value
                            ? 'fill-amber-400 text-amber-400'
                            : 'text-muted-foreground/40',
                    )}
                />
            ))}
        </span>
    );
}

export default function FeedbackShow({ feedback }: Props) {
    const details: Array<[string, string | null]> = [
        ['Student', feedback.student_name],
        ['Email', feedback.student_email],
        ['Feedback form', `${feedback.form_number} of 4`],
        ['Level completed', feedback.level],
        ['Batch', feedback.batch_name],
        ['Trainer', feedback.trainer_name],
        ['Centre', feedback.center_name],
        ['Submitted', feedback.submitted_at_label],
        ['Average rating', `${feedback.average_rating} / 5`],
    ];

    return (
        <>
            <PrivateSeoHead title="Course feedback" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-2 sm:px-4">
                <div className="flex items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Course feedback
                    </h1>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={feedbackIndex()}>
                            <ArrowLeft />
                            Back
                        </Link>
                    </Button>
                </div>

                <section className="grid gap-3 rounded-xl border border-border bg-card p-5 text-sm sm:grid-cols-2">
                    {details.map(([label, value]) => (
                        <p key={label}>
                            <span className="text-muted-foreground">
                                {label}:
                            </span>{' '}
                            <span className="font-medium">{value ?? '—'}</span>
                        </p>
                    ))}
                </section>

                <section className="rounded-xl border border-border bg-card p-5">
                    <h2 className="mb-4 font-semibold">Ratings</h2>
                    <ul className="flex flex-col gap-3 text-sm">
                        {feedback.ratings.map((rating) => (
                            <li
                                key={rating.key}
                                className="flex flex-col gap-1 border-b border-border pb-3 last:border-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <span>{rating.question}</span>
                                <Stars value={rating.value} />
                            </li>
                        ))}
                    </ul>
                </section>

                <section className="grid gap-3 rounded-xl border border-border bg-card p-5 text-sm sm:grid-cols-3">
                    <p>
                        <span className="block text-muted-foreground">
                            Improved the most
                        </span>
                        <span className="font-medium">
                            {feedback.most_improved_skill_label}
                        </span>
                    </p>
                    <p>
                        <span className="block text-muted-foreground">
                            Needs the most improvement
                        </span>
                        <span className="font-medium">
                            {feedback.needs_improvement_skill_label}
                        </span>
                    </p>
                    <p>
                        <span className="block text-muted-foreground">
                            Course pace
                        </span>
                        <span className="font-medium">
                            {feedback.pace_label}
                        </span>
                    </p>
                </section>

                <section className="flex flex-col gap-4 rounded-xl border border-border bg-card p-5 text-sm">
                    <div>
                        <p className="text-muted-foreground">
                            What did you like most about this course?
                        </p>
                        <p className="mt-1 whitespace-pre-line">
                            {feedback.liked_most}
                        </p>
                    </div>
                    <div>
                        <p className="text-muted-foreground">
                            What is the one thing we should improve for the next
                            level?
                        </p>
                        <p className="mt-1 whitespace-pre-line">
                            {feedback.improvement_suggestion}
                        </p>
                    </div>
                </section>
            </div>
        </>
    );
}

FeedbackShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Feedback', href: feedbackIndex() },
        {
            title: props.feedback.student_name ?? 'Details',
            href: feedbackShow(props.feedback.id),
        },
    ],
});
