import { router } from '@inertiajs/react';
import { CalendarPlus, ScanLine } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { useMediaQuery } from '@/hooks/use-media-query';
import { cn } from '@/lib/utils';
import { index } from '@/routes/student/attendance';
import { destroy as cancelLeave } from '@/routes/student/leave-requests';
import {
    MonthCalendar,
    WeekStrip
    
} from '@app/components/attendance-calendar';
import type {StatusMap} from '@app/components/attendance-calendar';
import AttendanceScanner from '@app/components/attendance-scanner';
import ConfirmDialog from '@app/components/confirm-dialog';
import LeaveRequestSheet from '@app/components/leave-request-sheet';
import PhoneHandoffCard from '@app/components/phone-handoff-card';
import { PrivateSeoHead } from '@shared/components/seo-head';

type DayStatus = 'present' | 'excused' | 'absent';

type Summary = {
    total_sessions: number;
    attended: number;
    excused: number;
    absent: number;
    percentage: number | null;
    recent: { date: string; status: DayStatus }[];
    days: { date: string; status: DayStatus }[];
};

type Leave = {
    id: number;
    from_date: string;
    to_date: string;
    reason: string;
    status: 'pending' | 'approved' | 'rejected';
    decision_note: string | null;
};

const LEAVE_STYLES = {
    pending: 'bg-amber-500/15 text-amber-600',
    approved: 'bg-emerald-600/15 text-emerald-600',
    rejected: 'bg-destructive/15 text-destructive',
} as const;

function range(from: string, to: string): string {
    const format = (value: string) =>
        new Date(`${value}T00:00:00`).toLocaleDateString('en-GB', {
            day: 'numeric',
            month: 'short',
        });

    return from === to ? format(from) : `${format(from)} – ${format(to)}`;
}

function SummaryCard({ summary }: { summary: Summary }) {
    const percentage = summary.percentage;
    const circumference = 2 * Math.PI * 44;

    return (
        <section className="flex items-center gap-6 rounded-3xl border border-border bg-card/60 p-5 shadow-sm">
            <div className="relative size-28 shrink-0">
                <svg viewBox="0 0 100 100" className="size-full -rotate-90">
                    <circle
                        cx="50"
                        cy="50"
                        r="44"
                        fill="none"
                        strokeWidth="10"
                        className="stroke-muted"
                    />
                    <circle
                        cx="50"
                        cy="50"
                        r="44"
                        fill="none"
                        strokeWidth="10"
                        strokeLinecap="round"
                        className={cn(
                            percentage !== null && percentage < 75
                                ? 'stroke-destructive'
                                : 'stroke-emerald-500',
                        )}
                        strokeDasharray={circumference}
                        strokeDashoffset={
                            circumference * (1 - (percentage ?? 0) / 100)
                        }
                    />
                </svg>
                <div className="absolute inset-0 flex items-center justify-center text-2xl font-bold tabular-nums">
                    {percentage === null ? '—' : `${percentage}%`}
                </div>
            </div>
            <dl className="grid flex-1 grid-cols-3 gap-3 text-center">
                {(
                    [
                        ['Present', summary.attended],
                        ['Leave', summary.excused],
                        ['Absent', summary.absent],
                    ] as const
                ).map(([label, value]) => (
                    <div key={label}>
                        <dd className="text-2xl font-semibold tabular-nums">
                            {value}
                        </dd>
                        <dt className="text-xs text-muted-foreground">
                            {label}
                        </dt>
                    </div>
                ))}
            </dl>
        </section>
    );
}

export default function Attendance({
    summary,
    leaves,
}: {
    summary: Summary;
    leaves: Leave[];
}) {
    const isDesktop = useMediaQuery('(min-width: 1024px)');
    const [scannerOpen, setScannerOpen] = useState(false);
    const [leaveOpen, setLeaveOpen] = useState(false);
    const [cancelling, setCancelling] = useState<Leave | null>(null);
    const [selected, setSelected] = useState(() => new Date());
    const statuses = useMemo<StatusMap>(
        () =>
            Object.fromEntries(
                summary.days.map((day) => [day.date, day.status]),
            ),
        [summary.days],
    );

    const handleMarked = useCallback(() => {
        window.setTimeout(() => {
            setScannerOpen(false);
            router.reload({ only: ['summary'] });
        }, 1400);
    }, []);

    const leaveButton = (
        <Button
            variant="outline"
            className="h-12 rounded-full"
            onClick={() => setLeaveOpen(true)}
        >
            <CalendarPlus className="size-4" />
            Request leave
        </Button>
    );

    return (
        <>
            <PrivateSeoHead title="Attendance" />
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                        Attendance
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground md:mt-2">
                        Track your classes and request leave when you cannot
                        attend.
                    </p>
                </div>

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
                    <div className="flex flex-col gap-6">
                        {!isDesktop && (
                            <Button
                                className="h-14 rounded-full text-base"
                                onClick={() => setScannerOpen(true)}
                            >
                                <ScanLine className="size-5" />
                                Scan attendance QR
                            </Button>
                        )}
                        <WeekStrip
                            selected={selected}
                            onSelect={setSelected}
                            statuses={statuses}
                        />
                        <MonthCalendar
                            selected={selected}
                            onSelect={setSelected}
                            statuses={statuses}
                        />
                    </div>

                    <div className="flex flex-col gap-6">
                        {isDesktop && <PhoneHandoffCard />}
                        <SummaryCard summary={summary} />

                        <section className="flex flex-col gap-3">
                            <div className="flex items-center justify-between gap-3">
                                <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                    Leave requests
                                </h2>
                            </div>
                            {leaveButton}
                            {leaves.length === 0 ? (
                                <p className="rounded-3xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                                    You have not requested any leave.
                                </p>
                            ) : (
                                <ul className="overflow-hidden rounded-3xl border border-border bg-card/40">
                                    {leaves.map((leave) => (
                                        <li
                                            key={leave.id}
                                            className="flex flex-col gap-1.5 border-b border-border/60 px-4 py-3 last:border-b-0"
                                        >
                                            <div className="flex items-center justify-between gap-3">
                                                <span className="text-sm font-semibold">
                                                    {range(
                                                        leave.from_date,
                                                        leave.to_date,
                                                    )}
                                                </span>
                                                <span
                                                    className={cn(
                                                        'rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                                        LEAVE_STYLES[
                                                            leave.status
                                                        ],
                                                    )}
                                                >
                                                    {leave.status}
                                                </span>
                                            </div>
                                            <p className="text-sm text-muted-foreground">
                                                {leave.reason}
                                            </p>
                                            {leave.decision_note && (
                                                <p className="text-xs text-muted-foreground italic">
                                                    Trainer:{' '}
                                                    {leave.decision_note}
                                                </p>
                                            )}
                                            {leave.status === 'pending' && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setCancelling(leave)
                                                    }
                                                    className="w-fit text-xs font-semibold text-destructive underline"
                                                >
                                                    Cancel request
                                                </button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>
                </div>
            </div>

            <AttendanceScanner
                open={scannerOpen}
                onOpenChange={setScannerOpen}
                onMarked={handleMarked}
            />
            <LeaveRequestSheet open={leaveOpen} onOpenChange={setLeaveOpen} />
            <ConfirmDialog
                open={cancelling !== null}
                onOpenChange={(open) => !open && setCancelling(null)}
                title="Cancel this leave request?"
                description="The request will be withdrawn and your trainer will no longer see it."
                confirmLabel="Yes, cancel it"
                cancelLabel="No, keep it"
                destructive
                onConfirm={() =>
                    cancelling &&
                    router.delete(cancelLeave.url(cancelling.id), {
                        preserveScroll: true,
                    })
                }
            />
        </>
    );
}

Attendance.layout = {
    breadcrumbs: [{ title: 'Attendance', href: index() }],
};
