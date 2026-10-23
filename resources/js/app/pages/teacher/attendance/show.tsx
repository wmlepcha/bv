import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Check, Clock, X } from 'lucide-react';
import QRCode from 'qrcode';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { close, index, live } from '@/routes/teacher/attendance';
import { update as updateStudent } from '@/routes/teacher/attendance/students';
import ConfirmDialog from '@app/components/confirm-dialog';
import { PrivateSeoHead } from '@shared/components/seo-head';

type RosterRow = {
    student_id: number;
    name: string;
    status: 'present' | 'excused' | null;
    marked_at: string | null;
    method: string | null;
};

type Live = {
    open: boolean;
    token: { token: string; expires_in: number } | null;
    roster: RosterRow[];
    present_count: number;
    excused_count: number;
    total: number;
};

type Props = {
    session: { id: number; batch: string; started_at: string; open: boolean };
    live: Live;
};

const POLL_MS = 4000;

export default function AttendanceShow({ session, live: initial }: Props) {
    const [data, setData] = useState<Live>(initial);
    const [endOpen, setEndOpen] = useState(false);
    const canvasRef = useRef<HTMLCanvasElement>(null);

    const refresh = useCallback(async () => {
        const response = await fetch(live.url(session.id), {
            headers: { Accept: 'application/json' },
        });

        if (response.ok) {
            setData((await response.json()) as Live);
        }
    }, [session.id]);

    useEffect(() => {
        if (!data.open) {
            return;
        }

        const timer = window.setInterval(() => void refresh(), POLL_MS);

        return () => window.clearInterval(timer);
    }, [data.open, refresh]);

    const token = data.token?.token;

    useEffect(() => {
        if (token && canvasRef.current) {
            void QRCode.toCanvas(canvasRef.current, token, {
                width: 320,
                margin: 1,
                errorCorrectionLevel: 'M',
            });
        }
    }, [token]);

    const setStatus = (
        row: RosterRow,
        status: 'present' | 'excused' | 'absent',
    ) =>
        router.put(
            updateStudent.url({ session: session.id, student: row.student_id }),
            { status },
            { preserveScroll: true, onSuccess: () => void refresh() },
        );

    return (
        <>
            <PrivateSeoHead title={`Attendance · ${session.batch}`} />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-col gap-3">
                    <Link
                        href={index()}
                        className="inline-flex w-fit items-center gap-2 text-sm text-muted-foreground hover:underline"
                    >
                        <ArrowLeft className="size-4" />
                        All sessions
                    </Link>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                                {session.batch}
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {data.open ? 'Session open' : 'Session closed'}{' '}
                                · {data.present_count}/{data.total} present
                                {data.excused_count > 0 &&
                                    ` · ${data.excused_count} on leave`}
                            </p>
                        </div>
                        {data.open && (
                            <Button
                                variant="outline"
                                className="rounded-full"
                                onClick={() => setEndOpen(true)}
                            >
                                End session
                            </Button>
                        )}
                    </div>
                </div>

                {data.open && (
                    <div className="flex flex-col items-center gap-3 rounded-3xl border border-border bg-white p-6 text-center text-neutral-900 shadow-sm">
                        <canvas ref={canvasRef} className="max-w-full" />
                        <p className="flex items-center gap-1.5 text-sm text-neutral-600">
                            <Clock className="size-4" />
                            The code refreshes every few seconds. Students scan
                            it from Attendance in their dashboard.
                        </p>
                    </div>
                )}

                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Roll · {data.present_count}/{data.total}
                    </h2>
                    <ul className="overflow-hidden rounded-3xl border border-border bg-card/40">
                        {data.roster.map((row) => (
                            <li
                                key={row.student_id}
                                className="flex flex-wrap items-center gap-3 border-b border-border/60 px-4 py-3 last:border-b-0"
                            >
                                <span
                                    className={cn(
                                        'flex size-8 items-center justify-center rounded-full',
                                        row.status === 'present' &&
                                            'bg-emerald-600/15 text-emerald-600',
                                        row.status === 'excused' &&
                                            'bg-sky-600/15 text-sky-600',
                                        row.status === null &&
                                            'bg-muted text-muted-foreground',
                                    )}
                                >
                                    {row.status === 'present' ? (
                                        <Check className="size-4" />
                                    ) : row.status === 'excused' ? (
                                        <Clock className="size-4" />
                                    ) : (
                                        <X className="size-4" />
                                    )}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">
                                        {row.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {row.status === 'present'
                                            ? `Present${row.method === 'manual' ? ' (manual)' : ''}`
                                            : row.status === 'excused'
                                              ? row.method === 'leave'
                                                  ? 'On approved leave'
                                                  : 'On leave'
                                              : 'Absent'}
                                    </p>
                                </div>
                                <div className="flex gap-1.5">
                                    {(
                                        [
                                            ['present', 'Present'],
                                            ['excused', 'Leave'],
                                            ['absent', 'Absent'],
                                        ] as const
                                    ).map(([value, label]) => {
                                        const current = row.status ?? 'absent';

                                        return (
                                            <Button
                                                key={value}
                                                type="button"
                                                size="sm"
                                                variant={
                                                    current === value
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                disabled={
                                                    value === 'absent' &&
                                                    row.method === 'leave'
                                                }
                                                className="h-8 rounded-full px-3 text-xs"
                                                onClick={() =>
                                                    current !== value &&
                                                    setStatus(row, value)
                                                }
                                            >
                                                {label}
                                            </Button>
                                        );
                                    })}
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>

            <ConfirmDialog
                open={endOpen}
                onOpenChange={setEndOpen}
                title="End this session?"
                description={`${data.present_count} of ${data.total} students are marked present. Anyone not marked will count as absent, and the QR stops working. You can still correct the roll afterwards.`}
                confirmLabel="Yes, end session"
                cancelLabel="No, keep open"
                onConfirm={() => router.post(close.url(session.id))}
            />
        </>
    );
}

AttendanceShow.layout = {
    breadcrumbs: [{ title: 'Attendance', href: index() }],
};
