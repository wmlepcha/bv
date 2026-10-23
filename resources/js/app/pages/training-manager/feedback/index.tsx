import { Link } from '@inertiajs/react';
import { Star } from 'lucide-react';
import {
    index as feedbackIndex,
    show as feedbackShow,
} from '@/routes/training-manager/feedback';
import { PrivateSeoHead } from '@shared/components/seo-head';

type FeedbackRow = {
    id: number;
    student_name: string | null;
    form_number: number;
    level: string;
    batch_name: string | null;
    trainer_name: string | null;
    center_name: string | null;
    overall_satisfaction: number;
    average_rating: number;
    pace_label: string;
    submitted_at_label: string;
};

type Props = {
    feedback: FeedbackRow[];
};

export default function FeedbackIndex({ feedback }: Props) {
    return (
        <>
            <PrivateSeoHead title="Course feedback" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Course feedback
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Course completion feedback submitted by students.
                    </p>
                </div>

                {feedback.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        No feedback has been submitted yet.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-border bg-card">
                        <table className="w-full min-w-[760px] text-left text-sm">
                            <thead className="border-b border-border bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Student
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Form
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Level
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Batch
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Trainer
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Centre
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Overall
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Average
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Submitted
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {feedback.map((item) => (
                                    <tr
                                        key={item.id}
                                        className="border-b border-border last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            <Link
                                                href={feedbackShow(item.id)}
                                                className="hover:underline"
                                            >
                                                {item.student_name ?? '—'}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {item.form_number} of 4
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.level}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {item.batch_name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {item.trainer_name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {item.center_name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="inline-flex items-center gap-1">
                                                <Star className="size-4 fill-amber-400 text-amber-400" />
                                                {item.overall_satisfaction}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.average_rating} / 5
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {item.submitted_at_label}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

FeedbackIndex.layout = {
    breadcrumbs: [
        {
            title: 'Feedback',
            href: feedbackIndex(),
        },
    ],
};
