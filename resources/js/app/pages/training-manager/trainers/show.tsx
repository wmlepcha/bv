import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    BookOpen,
    CalendarRange,
    ClipboardList,
    Layers,
    Mail,
    MessageSquareQuote,
    Pencil,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Panel = 'batches' | 'students' | 'assignments' | 'remarks';

type TrainerBatch = {
    id: number;
    name: string;
    center_name: string | null;
    language_label: string;
    level_label: string;
    is_active: boolean;
    starts_on_label: string | null;
    ends_on_label: string | null;
    enrollments_count: number;
    assignments_count: number;
};

type TrainerAssignment = {
    id: number;
    title: string;
    batch_id: number;
    batch_name: string;
    status: string;
    status_label: string;
    submissions_count: number;
    graded_submissions_count: number;
    due_at: string | null;
};

type TrainerStudent = {
    id: number;
    name: string;
    email: string;
    student_number: string | null;
    batch_name: string;
    enrolled_at: string | null;
};

type Props = {
    trainer: {
        id: number;
        name: string;
        email: string;
        photo_url: string | null;
        created_at: string | null;
        created_at_label: string | null;
    };
    stats: {
        batches_count: number;
        students_count: number;
        total_assignments: number;
        published_assignments: number;
        submissions_count: number;
        graded_submissions_count: number;
        shared_materials_count: number;
    };
    batches: TrainerBatch[];
    assignments: TrainerAssignment[];
    students: TrainerStudent[];
    remarks: unknown[];
};

const AVATAR_PALETTE = [
    'from-[#2A80CC] to-[#0959B5]',
    'from-[#FC5C2E] to-[#EE3D27]',
    'from-[#FBA214] to-[#FC5C2E]',
    'from-[#04A8C8] to-[#2A80CC]',
    'from-[#0959B5] to-[#031835]',
] as const;

function avatarGradient(id: number): string {
    return AVATAR_PALETTE[Math.abs(id) % AVATAR_PALETTE.length];
}

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

function BatchesPanel({ batches }: { batches: TrainerBatch[] }) {
    if (batches.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No batches assigned to this trainer yet.
            </p>
        );
    }

    return (
        <ul className="divide-y divide-border">
            {batches.map((batch) => (
                <li
                    key={batch.id}
                    className="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div className="min-w-0">
                        <Link
                            href={`/training-manager/batches/${batch.id}`}
                            className="font-medium hover:underline"
                        >
                            {batch.name}
                        </Link>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {batch.language_label} {batch.level_label}
                            {batch.center_name ? ` · ${batch.center_name}` : ''}
                        </p>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {formatDateRange(
                                batch.starts_on_label,
                                batch.ends_on_label,
                            )}
                        </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-3">
                        <span
                            className={cn(
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                batch.is_active
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-muted text-muted-foreground',
                            )}
                        >
                            {batch.is_active ? 'Active' : 'Inactive'}
                        </span>
                        <p className="text-xs text-muted-foreground">
                            {batch.enrollments_count} students ·{' '}
                            {batch.assignments_count} assignments
                        </p>
                    </div>
                </li>
            ))}
        </ul>
    );
}

function StudentsPanel({ students }: { students: TrainerStudent[] }) {
    if (students.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No students across this trainer&apos;s batches yet.
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
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            Batch: {student.batch_name}
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

function AssignmentsPanel({ assignments }: { assignments: TrainerAssignment[] }) {
    if (assignments.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No assignments created by this trainer yet.
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
                            {assignment.batch_name} · {assignment.status_label}{' '}
                            · Due {formatDueAt(assignment.due_at)}
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

function RemarksPanel() {
    return (
        <div className="rounded-xl border border-dashed border-border/80 bg-muted/20 px-4 py-8 text-center">
            <MessageSquareQuote className="mx-auto size-8 text-muted-foreground/70" />
            <p className="mt-3 text-sm font-medium">Student remarks coming soon</p>
            <p className="mt-1 text-sm text-muted-foreground">
                Students will be able to leave feedback and suggestions for their
                trainer here.
            </p>
        </div>
    );
}

const PANEL_META: Record<
    Panel,
    { title: string; description: string; icon: typeof Users }
> = {
    batches: {
        title: 'Batches',
        description: 'All batches assigned to this trainer.',
        icon: Layers,
    },
    students: {
        title: 'Students',
        description: 'Candidates enrolled across this trainer’s batches.',
        icon: Users,
    },
    assignments: {
        title: 'Assignments',
        description: 'Worksheets and tasks created by this trainer.',
        icon: ClipboardList,
    },
    remarks: {
        title: 'Student remarks',
        description: 'Feedback and suggestions from students.',
        icon: MessageSquareQuote,
    },
};

export default function TrainerShow({
    trainer,
    stats,
    batches,
    assignments,
    students,
}: Props) {
    const [panel, setPanel] = useState<Panel>('batches');
    const getInitials = useInitials();
    const panelMeta = PANEL_META[panel];
    const PanelIcon = panelMeta.icon;

    return (
        <>
            <PrivateSeoHead title={trainer.name} />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="flex min-w-0 gap-4">
                        <div
                            className={cn(
                                'relative size-20 shrink-0 overflow-hidden rounded-2xl sm:size-24',
                                trainer.photo_url
                                    ? 'bg-muted'
                                    : cn(
                                          'bg-gradient-to-br',
                                          avatarGradient(trainer.id),
                                      ),
                            )}
                        >
                            {trainer.photo_url ? (
                                <img
                                    src={trainer.photo_url}
                                    alt=""
                                    className="size-full object-cover"
                                />
                            ) : (
                                <div className="flex size-full items-center justify-center text-2xl font-semibold text-white/95">
                                    {getInitials(trainer.name)}
                                </div>
                            )}
                        </div>

                        <div className="min-w-0 space-y-2">
                            <div className="flex flex-wrap items-center gap-2">
                                <h1 className="text-2xl font-semibold tracking-tight">
                                    {trainer.name}
                                </h1>
                                <BadgeCheck
                                    className="size-5 shrink-0 fill-emerald-500 text-white dark:text-[#0f1419]"
                                    aria-label="Verified trainer"
                                />
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                <span className="inline-flex items-center gap-1.5">
                                    <Mail className="size-3.5" />
                                    {trainer.email}
                                </span>
                                {trainer.created_at_label && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <CalendarRange className="size-3.5" />
                                        Trainer since {trainer.created_at_label}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link
                                href={`/training-manager/trainers/${trainer.id}/edit`}
                            >
                                <Pencil className="size-4" />
                                Edit trainer
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="Batches"
                        value={stats.batches_count}
                        hint={
                            stats.batches_count === 1
                                ? 'batch assigned'
                                : 'batches assigned'
                        }
                        active={panel === 'batches'}
                        onClick={() => setPanel('batches')}
                    />
                    <StatCard
                        label="Students"
                        value={stats.students_count}
                        hint={
                            stats.students_count === 1
                                ? 'student managed'
                                : 'students managed'
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
                            {panel === 'batches' && (
                                <Button asChild variant="ghost" size="sm">
                                    <Link href="/training-manager/batches">
                                        View all
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
                            {panel === 'batches' && (
                                <BatchesPanel batches={batches} />
                            )}
                            {panel === 'students' && (
                                <StudentsPanel students={students} />
                            )}
                            {panel === 'assignments' && (
                                <AssignmentsPanel assignments={assignments} />
                            )}
                            {panel === 'remarks' && <RemarksPanel />}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border/70 bg-card p-5">
                        <h2 className="text-base font-semibold">
                            Trainer overview
                        </h2>

                        <dl className="mt-4 space-y-3 text-sm">
                            <div className="flex items-center justify-between gap-3">
                                <dt className="text-muted-foreground">
                                    Batches
                                </dt>
                                <dd className="font-medium tabular-nums">
                                    {stats.batches_count}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <dt className="text-muted-foreground">
                                    Students
                                </dt>
                                <dd className="font-medium tabular-nums">
                                    {stats.students_count}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <dt className="text-muted-foreground">
                                    Assignments created
                                </dt>
                                <dd className="font-medium tabular-nums">
                                    {stats.total_assignments}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <dt className="text-muted-foreground">
                                    Shared materials
                                </dt>
                                <dd className="font-medium tabular-nums">
                                    {stats.shared_materials_count}
                                </dd>
                            </div>
                        </dl>

                        <div className="mt-5 space-y-2 border-t border-border/70 pt-4 text-sm">
                            <p className="flex items-center gap-2 text-muted-foreground">
                                <BookOpen className="size-4" />
                                {stats.published_assignments} published
                                assignments
                            </p>
                            <p className="flex items-center gap-2 text-muted-foreground">
                                <ClipboardList className="size-4" />
                                {stats.graded_submissions_count}/
                                {stats.submissions_count} submissions graded
                            </p>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="mt-5 w-full"
                            onClick={() => setPanel('remarks')}
                        >
                            <MessageSquareQuote className="size-4" />
                            Student remarks
                        </Button>
                    </section>
                </div>
            </div>
        </>
    );
}

TrainerShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Trainers', href: '/training-manager/trainers' },
        {
            title: props.trainer.name,
            href: `/training-manager/trainers/${props.trainer.id}`,
        },
    ],
});
