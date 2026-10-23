import { Link, router } from '@inertiajs/react';
import { CalendarCheck, Check, Play, QrCode, Users, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { index, show, store } from '@/routes/teacher/attendance';
import LeaveDecisionDialog from '@app/components/leave-decision-dialog';
import type {LeaveRow} from '@app/components/leave-decision-dialog';
import { PrivateSeoHead } from '@shared/components/seo-head';

type BatchRow = {
    id: number;
    name: string;
    level: string;
    students: number;
    open_session_id: number | null;
};

type RecentSession = {
    id: number;
    batch: string;
    started_at: string;
    open: boolean;
    present_count: number;
};

function formatWhen(iso: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}

const LEAVE_STYLES = {
    pending: 'bg-amber-500/15 text-amber-600',
    approved: 'bg-emerald-600/15 text-emerald-600',
    rejected: 'bg-destructive/15 text-destructive',
} as const;

function leaveRange(from: string, to: string): string {
    const format = (value: string) =>
        new Date(`${value}T00:00:00`).toLocaleDateString('en-GB', {
            day: 'numeric',
            month: 'short',
        });

    return from === to ? format(from) : `${format(from)} – ${format(to)}`;
}

export default function AttendanceIndex({
    batches,
    recent,
    leaveRequests,
}: {
    batches: BatchRow[];
    recent: RecentSession[];
    leaveRequests: LeaveRow[];
}) {
    const [deciding, setDeciding] = useState<{
        leave: LeaveRow;
        decision: 'approve' | 'reject';
    } | null>(null);
    const pending = leaveRequests.filter((leave) => leave.status === 'pending');

    return (
        <>
            <PrivateSeoHead title="Attendance" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                        Attendance
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground md:mt-2">
                        Start a session, show the QR and students scan it from
                        their dashboard.
                    </p>
                </div>

                {batches.length === 0 ? (
                    <div className="rounded-3xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        You have no active batches yet.
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {batches.map((batch) => (
                            <div
                                key={batch.id}
                                className="flex flex-col gap-4 rounded-3xl border border-border bg-card/60 p-5 shadow-sm"
                            >
                                <div className="flex items-center gap-3">
                                    <span className="flex size-11 items-center justify-center rounded-2xl bg-brand-cool/15 text-brand-cool">
                                        <QrCode className="size-5" />
                                    </span>
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold">
                                            {batch.name}
                                        </p>
                                        <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <Users className="size-3.5" />
                                            {batch.students} students ·{' '}
                                            {batch.level}
                                        </p>
                                    </div>
                                </div>
                                {batch.open_session_id ? (
                                    <Button
                                        className="h-11 rounded-full"
                                        onClick={() =>
                                            router.visit(
                                                show.url(
                                                    batch.open_session_id!,
                                                ),
                                            )
                                        }
                                    >
                                        <Play className="size-4" />
                                        Resume session
                                    </Button>
                                ) : (
                                    <Button
                                        className="h-11 rounded-full"
                                        onClick={() =>
                                            router.post(store.url(batch.id))
                                        }
                                    >
                                        <CalendarCheck className="size-4" />
                                        Start attendance
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                <section className="flex flex-col gap-3">
                    <h2 className="flex items-center gap-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Leave requests
                        {pending.length > 0 && (
                            <span className="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white">
                                {pending.length} pending
                            </span>
                        )}
                    </h2>
                    {leaveRequests.length === 0 ? (
                        <p className="rounded-3xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                            No leave requests from your students.
                        </p>
                    ) : (
                        <ul className="overflow-hidden rounded-3xl border border-border bg-card/40">
                            {leaveRequests.map((leave) => (
                                <li
                                    key={leave.id}
                                    className="flex flex-col gap-2 border-b border-border/60 px-4 py-3 last:border-b-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0 space-y-0.5">
                                        <p className="text-sm font-semibold">
                                            {leave.student}{' '}
                                            <span className="font-normal text-muted-foreground">
                                                · {leave.batch}
                                            </span>
                                        </p>
                                        <p className="text-sm">
                                            {leaveRange(
                                                leave.from_date,
                                                leave.to_date,
                                            )}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {leave.reason}
                                        </p>
                                        {leave.decision_note && (
                                            <p className="text-xs text-muted-foreground italic">
                                                Note: {leave.decision_note}
                                            </p>
                                        )}
                                    </div>
                                    {leave.status === 'pending' ? (
                                        <div className="flex shrink-0 gap-2">
                                            <Button
                                                size="sm"
                                                className="rounded-full"
                                                onClick={() =>
                                                    setDeciding({
                                                        leave,
                                                        decision: 'approve',
                                                    })
                                                }
                                            >
                                                <Check className="size-4" />
                                                Approve
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="rounded-full"
                                                onClick={() =>
                                                    setDeciding({
                                                        leave,
                                                        decision: 'reject',
                                                    })
                                                }
                                            >
                                                <X className="size-4" />
                                                Reject
                                            </Button>
                                        </div>
                                    ) : (
                                        <span
                                            className={cn(
                                                'w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                                LEAVE_STYLES[leave.status],
                                            )}
                                        >
                                            {leave.status}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {recent.length > 0 && (
                    <section className="flex flex-col gap-3">
                        <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                            Recent sessions
                        </h2>
                        <ul className="overflow-hidden rounded-3xl border border-border bg-card/40">
                            {recent.map((session) => (
                                <li key={session.id}>
                                    <Link
                                        href={show(session.id)}
                                        className="flex items-center gap-3 border-b border-border/60 px-4 py-3 last:border-b-0 active:bg-muted"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {session.batch}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatWhen(session.started_at)}
                                            </p>
                                        </div>
                                        <span className="text-sm font-semibold tabular-nums">
                                            {session.present_count} present
                                        </span>
                                        {session.open && (
                                            <span className="rounded-full bg-emerald-600/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-600">
                                                Open
                                            </span>
                                        )}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
            <LeaveDecisionDialog
                key={`${deciding?.leave.id}-${deciding?.decision}`}
                leave={deciding?.leave ?? null}
                decision={deciding?.decision ?? 'approve'}
                onClose={() => setDeciding(null)}
            />
        </>
    );
}

AttendanceIndex.layout = {
    breadcrumbs: [{ title: 'Attendance', href: index() }],
};
