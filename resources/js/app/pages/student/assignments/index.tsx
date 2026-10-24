import { Link } from '@inertiajs/react';
import { CalendarClock, CheckCircle2, ChevronRight, Clock } from 'lucide-react';
import { useMemo, useState } from 'react';
import { cn } from '@/lib/utils';
import type { AssignmentData } from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = { assignments: AssignmentData[] };

type Tab = 'open' | 'submitted' | 'graded';

const TABS: { id: Tab; label: string }[] = [
    { id: 'open', label: 'Open' },
    { id: 'submitted', label: 'Submitted' },
    { id: 'graded', label: 'Graded' },
];

const EMPTY_COPY: Record<Tab, string> = {
    open: 'You are all caught up. New assignments will show up here.',
    submitted: 'Nothing waiting for review.',
    graded: 'Graded work will appear here once your trainer reviews it.',
};

function tabOf(assignment: AssignmentData): Tab {
    if (assignment.submission_status === 'graded') {
        return 'graded';
    }

    return assignment.submission_status === 'submitted' ? 'submitted' : 'open';
}

function formatDue(value: string): string {
    const date = new Date(value);

    return `${date.toLocaleDateString('en-IN', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    })}, ${date.toLocaleTimeString('en-IN', {
        hour: 'numeric',
        minute: '2-digit',
    })}`;
}

/** "Due today", "Due tomorrow", "Due in 5 days", "Overdue by 3 days". */
function dueHint(value: string): { text: string; overdue: boolean } {
    const days = Math.ceil(
        (new Date(value).getTime() - Date.now()) / 86_400_000,
    );

    if (days < 0) {
        const late = Math.abs(days);

        return {
            text: `Overdue by ${late} day${late === 1 ? '' : 's'}`,
            overdue: true,
        };
    }

    if (days === 0) {
        return { text: 'Due today', overdue: false };
    }

    return {
        text: days === 1 ? 'Due tomorrow' : `Due in ${days} days`,
        overdue: false,
    };
}

function AssignmentCard({ assignment }: { assignment: AssignmentData }) {
    const tab = tabOf(assignment);
    const due = assignment.due_at ? dueHint(assignment.due_at) : null;
    const overdue = tab === 'open' && due?.overdue === true;

    return (
        <Link
            href={`/student/assignments/${assignment.id}`}
            className="flex items-center gap-3 rounded-2xl border border-border/70 bg-card p-4 shadow-sm transition-transform active:scale-[0.98]"
        >
            <div className="min-w-0 flex-1 space-y-2.5">
                <div className="flex flex-wrap items-center gap-2">
                    {tab === 'graded' && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                            <CheckCircle2 className="size-3.5" />
                            Graded
                            {assignment.final_score != null &&
                                ` · ${assignment.final_score}/${assignment.max_score}`}
                        </span>
                    )}
                    {tab === 'submitted' && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-900 dark:bg-sky-950 dark:text-sky-100">
                            <Clock className="size-3.5" />
                            Awaiting review
                        </span>
                    )}
                    {tab === 'open' && (
                        <span
                            className={cn(
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold',
                                overdue
                                    ? 'bg-red-100 text-red-900 dark:bg-red-950 dark:text-red-100'
                                    : 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-100',
                            )}
                        >
                            <CalendarClock className="size-3.5" />
                            {due?.text ?? 'To do'}
                        </span>
                    )}
                </div>

                <p className="text-base leading-snug font-semibold text-foreground">
                    {assignment.title}
                </p>

                <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 text-sm text-muted-foreground">
                    <p className="min-w-0">{assignment.batch.name}</p>
                    {assignment.due_at && (
                        <p className="ml-auto shrink-0 text-right font-medium text-foreground/80">
                            Due {formatDue(assignment.due_at)}
                        </p>
                    )}
                </div>
            </div>
            <ChevronRight className="size-5 shrink-0 text-muted-foreground/60" />
        </Link>
    );
}

export default function StudentAssignmentsIndex({ assignments }: Props) {
    const [tab, setTab] = useState<Tab>('open');

    const grouped = useMemo(() => {
        const byDue = (a: AssignmentData, b: AssignmentData) =>
            new Date(a.due_at ?? '9999-12-31').getTime() -
            new Date(b.due_at ?? '9999-12-31').getTime();

        const groups: Record<Tab, AssignmentData[]> = {
            open: [],
            submitted: [],
            graded: [],
        };

        for (const assignment of assignments) {
            groups[tabOf(assignment)].push(assignment);
        }

        groups.open.sort(byDue);
        groups.submitted.sort((a, b) => byDue(b, a));
        groups.graded.sort((a, b) => byDue(b, a));

        return groups;
    }, [assignments]);

    const activeIndex = TABS.findIndex((item) => item.id === tab);
    const visible = grouped[tab];

    return (
        <>
            <PrivateSeoHead title="Assignments" />
            <div className="flex flex-col gap-4 p-2 sm:px-4">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                        Assignments
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Complete your batch assignments here.
                    </p>
                </div>

                <div className="sticky top-[var(--app-top-h,0px)] z-20 -mx-2 border-b border-border/60 bg-background/90 px-2 py-2.5 backdrop-blur-xl sm:-mx-4 sm:px-4">
                    <div
                        role="tablist"
                        aria-label="Filter assignments"
                        className="relative grid grid-cols-3 rounded-xl bg-muted p-1"
                    >
                        <span
                            aria-hidden
                            className="absolute inset-y-1 left-1 w-[calc((100%-0.5rem)/3)] rounded-lg bg-card shadow-sm transition-transform duration-300 ease-out"
                            style={{
                                transform: `translateX(${activeIndex * 100}%)`,
                            }}
                        />
                        {TABS.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                role="tab"
                                aria-selected={tab === item.id}
                                onClick={() => setTab(item.id)}
                                className={cn(
                                    'relative z-10 flex h-10 items-center justify-center gap-1.5 rounded-lg text-sm font-semibold transition-colors active:scale-[0.97]',
                                    tab === item.id
                                        ? 'text-foreground'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {item.label}
                                <span
                                    className={cn(
                                        'min-w-5 rounded-full px-1.5 text-xs leading-5 tabular-nums',
                                        tab === item.id
                                            ? 'bg-primary/15 text-primary'
                                            : 'bg-foreground/10',
                                    )}
                                >
                                    {grouped[item.id].length}
                                </span>
                            </button>
                        ))}
                    </div>
                </div>

                {visible.length === 0 ? (
                    <p className="rounded-2xl border border-dashed border-border/70 px-4 py-10 text-center text-sm text-muted-foreground">
                        {EMPTY_COPY[tab]}
                    </p>
                ) : (
                    <div className="grid gap-3">
                        {visible.map((assignment) => (
                            <AssignmentCard
                                key={assignment.id}
                                assignment={assignment}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

StudentAssignmentsIndex.layout = {
    breadcrumbs: [{ title: 'Assignments', href: '/student/assignments' }],
};
