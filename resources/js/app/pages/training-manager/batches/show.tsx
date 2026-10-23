import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    CalendarRange,
    CheckCircle2,
    Circle,
    ClipboardList,
    MapPin,
    Pencil,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Panel = 'students' | 'assignments' | 'submissions';

type SetupItem = {
    key: string;
    label: string;
    complete: boolean;
};

type BatchAssignment = {
    id: number;
    title: string;
    status: string;
    status_label: string;
    submissions_count: number;
    graded_submissions_count: number;
    due_at: string | null;
};

type BatchStudent = {
    id: number;
    name: string;
    email: string;
    student_number: string | null;
    enrolled_at: string | null;
};

type BatchSubmission = {
    id: number;
    assignment_id: number;
    assignment_title: string;
    student_name: string;
    status: string;
    status_label: string;
    submitted_at: string | null;
    final_score: number | null;
};

type Props = {
    batch: {
        id: number;
        name: string;
        center_name: string | null;
        language_label: string;
        level: string;
        level_label: string;
        teacher_name: string | null;
        teacher_email: string | null;
        is_active: boolean;
        starts_on: string | null;
        ends_on: string | null;
        starts_on_label: string | null;
        ends_on_label: string | null;
    };
    stats: {
        enrollments_count: number;
        total_assignments: number;
        published_assignments: number;
        submissions_count: number;
        graded_submissions_count: number;
        shared_materials_count: number;
        setup_percent: number;
    };
    setup: {
        percent: number;
        completed: number;
        total: number;
        items: SetupItem[];
    };
    assignments: BatchAssignment[];
    students: BatchStudent[];
    submissions: BatchSubmission[];
};

function formatDateRange(
    startsLabel: string | null,
    endsLabel: string | null,
): string {
    if (startsLabel && endsLabel) {
        return `${startsLabel} – ${endsLabel}`;
    }

    if (startsLabel) {
        return `Starts ${startsLabel}`;
    }

    if (endsLabel) {
        return `Ends ${endsLabel}`;
    }

    return 'Dates not set';
}

function formatDueAt(iso: string | null): string {
    if (!iso) {
        return 'No due date';
    }

    try {
        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
        }).format(new Date(iso));
    } catch {
        return 'No due date';
    }
}

function formatWhen(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    try {
        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch {
        return '—';
    }
}

function StatCard({
    label,
    value,
    hint,
    active,
    onClick,
}: {
    label: string;
    value: string | number;
    hint?: string;
    active?: boolean;
    onClick?: () => void;
}) {
    const interactive = onClick !== undefined;

    return (
        <button
            type="button"
            onClick={onClick}
            disabled={!interactive}
            className={cn(
                'rounded-2xl border border-border/70 bg-card p-4 text-left transition',
                interactive &&
                    'cursor-pointer hover:border-primary/40 hover:shadow-sm',
                active && 'border-primary ring-2 ring-primary/20',
                !interactive && 'cursor-default',
            )}
        >
            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                {label}
            </p>
            <p className="mt-2 text-2xl font-semibold tabular-nums">{value}</p>
            {hint && (
                <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
            )}
        </button>
    );
}

function StudentsPanel({
    batchId,
    students,
}: {
    batchId: number;
    students: BatchStudent[];
}) {
    if (students.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No students enrolled yet.{' '}
                <Link
                    href={`/training-manager/batches/${batchId}/students`}
                    className="font-medium text-primary hover:underline"
                >
                    Enroll students
                </Link>
            </p>
        );
    }

    return (
        <ul className="divide-y divide-border">
            {students.map((student) => (
                <li
                    key={student.id}
                    className="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div className="min-w-0">
                        <p className="font-medium">{student.name}</p>
                        <p className="text-xs text-muted-foreground">
                            {student.email}
                            {student.student_number
                                ? ` · ${student.student_number}`
                                : ''}
                        </p>
                    </div>
                    <p className="shrink-0 text-xs text-muted-foreground">
                        Enrolled {formatWhen(student.enrolled_at)}
                    </p>
                </li>
            ))}
        </ul>
    );
}

function AssignmentsPanel({ assignments }: { assignments: BatchAssignment[] }) {
    if (assignments.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No assignments for this batch yet.
            </p>
        );
    }

    return (
        <ul className="divide-y divide-border">
            {assignments.map((assignment) => (
                <li
                    key={assignment.id}
                    className="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div className="min-w-0">
                        <Link
                            href={`/training-manager/assignments/${assignment.id}`}
                            className="font-medium hover:underline"
                        >
                            {assignment.title}
                        </Link>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {assignment.status_label} · Due{' '}
                            {formatDueAt(assignment.due_at)}
                        </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-3">
                        <p className="text-xs text-muted-foreground">
                            {assignment.graded_submissions_count}/
                            {assignment.submissions_count} graded
                        </p>
                        <Link
                            href={`/training-manager/assignments/${assignment.id}/submissions`}
                            className="text-xs font-medium text-primary hover:underline"
                        >
                            View work
                        </Link>
                    </div>
                </li>
            ))}
        </ul>
    );
}

function SubmissionsPanel({ submissions }: { submissions: BatchSubmission[] }) {
    if (submissions.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No submissions yet for this batch.
            </p>
        );
    }

    return (
        <ul className="divide-y divide-border">
            {submissions.map((submission) => (
                <li
                    key={submission.id}
                    className="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div className="min-w-0">
                        <Link
                            href={`/training-manager/assignments/${submission.assignment_id}`}
                            className="font-medium hover:underline"
                        >
                            {submission.assignment_title}
                        </Link>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {submission.student_name} ·{' '}
                            {formatWhen(submission.submitted_at)}
                        </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-3">
                        <span
                            className={cn(
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                submission.status === 'graded'
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
                            )}
                        >
                            {submission.status_label}
                            {submission.final_score !== null
                                ? ` · ${submission.final_score}`
                                : ''}
                        </span>
                        <Link
                            href={`/training-manager/assignments/${submission.assignment_id}/submissions/${submission.id}/grade`}
                            className="text-xs font-medium text-primary hover:underline"
                        >
                            {submission.status === 'graded'
                                ? 'Review'
                                : 'Grade'}
                        </Link>
                    </div>
                </li>
            ))}
        </ul>
    );
}

const PANEL_META: Record<
    Panel,
    { title: string; description: string; icon: typeof Users }
> = {
    students: {
        title: 'Students',
        description: 'Enrolled candidates in this batch.',
        icon: Users,
    },
    assignments: {
        title: 'Assignments',
        description: 'All worksheets and tasks for this batch.',
        icon: ClipboardList,
    },
    submissions: {
        title: 'Submissions',
        description: 'Student work turned in across batch assignments.',
        icon: ClipboardList,
    },
};

export default function BatchShow({
    batch,
    stats,
    setup,
    assignments,
    students,
    submissions,
}: Props) {
    const [panel, setPanel] = useState<Panel>('assignments');
    const dateRange = formatDateRange(
        batch.starts_on_label,
        batch.ends_on_label,
    );
    const panelMeta = PANEL_META[panel];
    const PanelIcon = panelMeta.icon;

    return (
        <>
            <PrivateSeoHead title={batch.name} />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="min-w-0 space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {batch.name}
                            </h1>
                            <span
                                className={cn(
                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide',
                                    batch.is_active
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-muted text-muted-foreground',
                                )}
                            >
                                {batch.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {batch.language_label} {batch.level_label}
                        </p>
                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                            <span className="inline-flex items-center gap-1.5">
                                <MapPin className="size-3.5" />
                                {batch.center_name ?? 'No center'}
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <Users className="size-3.5" />
                                {batch.teacher_name ?? 'No trainer assigned'}
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <CalendarRange className="size-3.5" />
                                {dateRange}
                            </span>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link
                                href={`/training-manager/batches/${batch.id}/students`}
                            >
                                Manage students
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={`/training-manager/batches/${batch.id}/edit`}>
                                <Pencil className="size-4" />
                                Edit batch
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="Class strength"
                        value={stats.enrollments_count}
                        hint={
                            stats.enrollments_count === 1
                                ? 'student enrolled'
                                : 'students enrolled'
                        }
                        active={panel === 'students'}
                        onClick={() => setPanel('students')}
                    />
                    <StatCard
                        label="Assignments"
                        value={stats.total_assignments}
                        hint={`${stats.published_assignments} published`}
                        active={panel === 'assignments'}
                        onClick={() => setPanel('assignments')}
                    />
                    <StatCard
                        label="Submissions"
                        value={stats.submissions_count}
                        hint={`${stats.graded_submissions_count} graded`}
                        active={panel === 'submissions'}
                        onClick={() => setPanel('submissions')}
                    />
                    <StatCard
                        label="Shared materials"
                        value={stats.shared_materials_count}
                        hint="in study bank"
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <section className="rounded-2xl border border-border/70 bg-card p-5">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <div className="flex items-center gap-2">
                                    <PanelIcon className="size-4 text-muted-foreground" />
                                    <h2 className="text-base font-semibold">
                                        {panelMeta.title}
                                    </h2>
                                </div>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {panelMeta.description}
                                </p>
                            </div>
                            {panel === 'students' && (
                                <Button asChild variant="ghost" size="sm">
                                    <Link
                                        href={`/training-manager/batches/${batch.id}/students`}
                                    >
                                        Manage
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            )}
                            {panel === 'assignments' && (
                                <Button asChild variant="ghost" size="sm">
                                    <Link href="/training-manager/assignments">
                                        View all
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            )}
                        </div>

                        <div className="mt-4">
                            {panel === 'students' && (
                                <StudentsPanel
                                    batchId={batch.id}
                                    students={students}
                                />
                            )}
                            {panel === 'assignments' && (
                                <AssignmentsPanel assignments={assignments} />
                            )}
                            {panel === 'submissions' && (
                                <SubmissionsPanel submissions={submissions} />
                            )}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border/70 bg-card p-5">
                        <div className="flex items-center justify-between gap-2">
                            <h2 className="text-base font-semibold">
                                Batch details
                            </h2>
                            <span className="text-sm font-semibold tabular-nums text-primary">
                                {setup.percent}%
                            </span>
                        </div>
                        <div className="mt-3 h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                className="h-full rounded-full bg-primary transition-all"
                                style={{ width: `${setup.percent}%` }}
                            />
                        </div>
                        <p className="mt-2 text-xs text-muted-foreground">
                            {setup.completed} of {setup.total} setup items
                            complete
                        </p>

                        <ul className="mt-4 space-y-2">
                            {setup.items.map((item) => (
                                <li
                                    key={item.key}
                                    className="flex items-start gap-2 text-sm"
                                >
                                    {item.complete ? (
                                        <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-emerald-600" />
                                    ) : (
                                        <Circle className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                    )}
                                    <span
                                        className={
                                            item.complete
                                                ? 'text-foreground'
                                                : 'text-muted-foreground'
                                        }
                                    >
                                        {item.label}
                                    </span>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-5 space-y-2 border-t border-border/70 pt-4 text-sm">
                            <p className="flex items-center gap-2 text-muted-foreground">
                                <BookOpen className="size-4" />
                                {batch.language_label} · {batch.level}
                            </p>
                            {batch.teacher_email && (
                                <p className="truncate text-muted-foreground">
                                    {batch.teacher_email}
                                </p>
                            )}
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}

BatchShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Batches', href: '/training-manager/batches' },
        {
            title: props.batch.name,
            href: `/training-manager/batches/${props.batch.id}`,
        },
    ],
});
