import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type {
    AssignmentData,
    StudentWorkEntry,
    StudentWorkSummary,
} from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    assignment: AssignmentData;
    roster: StudentWorkEntry[];
    summary: StudentWorkSummary;
    portal: 'teacher' | 'training-manager';
    canGrade?: boolean;
    embedded?: boolean;
};

function StatusBadge({
    status,
    label,
}: {
    status: StudentWorkEntry['status'];
    label: string;
}) {
    return (
        <span
            className={cn(
                'rounded-full px-2 py-0.5 text-xs font-medium',
                status === 'graded' && 'bg-emerald-100 text-emerald-800',
                status === 'submitted' && 'bg-blue-100 text-blue-800',
                status === 'assigned' && 'bg-muted text-muted-foreground',
            )}
        >
            {label}
        </span>
    );
}

function StudentRow({
    entry,
    assignment,
    basePath,
    canGrade,
}: {
    entry: StudentWorkEntry;
    assignment: AssignmentData;
    basePath: string;
    canGrade: boolean;
}) {
    const hasSubmission =
        entry.submission_id !== null &&
        (entry.status === 'submitted' || entry.status === 'graded');

    const href = hasSubmission
        ? `${basePath}/${entry.submission_id}${canGrade ? '/grade' : ''}`
        : undefined;

    const scoreLabel =
        entry.status === 'graded' && entry.final_score !== null
            ? `${entry.final_score}/${assignment.max_score}`
            : null;

    const content = (
        <div className="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p className="font-medium">{entry.student.name}</p>
                <p className="text-xs text-muted-foreground">
                    {entry.student.email}
                </p>
                {entry.is_late && (
                    <p className="text-xs text-amber-700">Done late</p>
                )}
            </div>
            <div className="flex flex-col items-end gap-1">
                <StatusBadge status={entry.status} label={entry.status_label} />
                {scoreLabel && (
                    <span className="text-xs text-muted-foreground">
                        Score {scoreLabel}
                    </span>
                )}
            </div>
        </div>
    );

    if (!href) {
        return (
            <div className="rounded-xl border border-border/70 bg-card p-4 opacity-70">
                {content}
            </div>
        );
    }

    return (
        <Link
            href={href}
            className="block rounded-xl border border-border/70 bg-card p-4 transition hover:border-primary/30"
        >
            {content}
        </Link>
    );
}

export function StudentWorkPage({
    assignment,
    roster,
    summary,
    portal,
    canGrade = false,
    embedded = false,
}: Props) {
    const basePath = `/${portal}/assignments/${assignment.id}/submissions`;

    const turnedIn = roster.filter(
        (entry) => entry.status === 'submitted' || entry.status === 'graded',
    );
    const notSubmitted = roster.filter((entry) => entry.status === 'assigned');

    const body = (
        <div className="flex flex-col gap-6">
            {!embedded && (
                <div>
                    <h1 className="text-2xl font-semibold">Student work</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {assignment.title}
                    </p>
                </div>
            )}

            <div className="flex items-center gap-4 rounded-xl border border-border/70 bg-muted/30 px-4 py-3 text-sm">
                <span>
                    <strong>{summary.turned_in_count}</strong> turned in
                </span>
                <span className="text-muted-foreground">·</span>
                <span>
                    <strong>{summary.assigned_count}</strong> assigned
                </span>
                {summary.graded_count > 0 && (
                    <>
                        <span className="text-muted-foreground">·</span>
                        <span>
                            <strong>{summary.graded_count}</strong> graded
                        </span>
                    </>
                )}
            </div>

            {roster.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No students are assigned to this assignment yet.
                </p>
            ) : (
                <div className="grid gap-6">
                    {turnedIn.length > 0 && (
                        <section className="grid gap-3">
                            <h2 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Turned in
                            </h2>
                            {turnedIn.map((entry) => (
                                <StudentRow
                                    key={entry.student.id}
                                    entry={entry}
                                    assignment={assignment}
                                    basePath={basePath}
                                    canGrade={canGrade}
                                />
                            ))}
                        </section>
                    )}

                    {notSubmitted.length > 0 && (
                        <section className="grid gap-3">
                            <h2 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Assigned
                            </h2>
                            {notSubmitted.map((entry) => (
                                <StudentRow
                                    key={entry.student.id}
                                    entry={entry}
                                    assignment={assignment}
                                    basePath={basePath}
                                    canGrade={canGrade}
                                />
                            ))}
                        </section>
                    )}
                </div>
            )}
        </div>
    );

    if (embedded) {
        return body;
    }

    return (
        <>
            <PrivateSeoHead title="Student work" />
            <div className="mx-auto max-w-3xl p-2 sm:px-4">{body}</div>
        </>
    );
}
