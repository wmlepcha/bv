import { Link } from '@inertiajs/react';
import {
    BellRing,
    BriefcaseBusiness,
    ChevronRight,
    Clock,
    Inbox,
    UsersRound,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { show as studentShow } from '@/routes/recruiter/students';
import { index as candidatesIndex } from '@/routes/recruitment-manager/candidates';
import { index as inquiriesIndex } from '@/routes/recruitment-manager/inquiries';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Row = {
    id: number;
    name: string;
    student_number: string | null;
    batch: string | null;
    placement_status: 'open' | 'hired';
    profile: number;
    assignments: number | null;
    submission_rate: number | null;
    attendance: number | null;
};

type Props = {
    newInquiries: number;
    counts: {
        onboarded: number;
        placed: number;
        pending: number;
        requests: number;
    };
    heatmap: {
        rows: Row[];
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
};

function tone(value: number | null): string {
    if (value === null) {
        return 'bg-muted text-muted-foreground';
    }

    if (value < 40) {
        return 'bg-red-500/20 text-red-600 dark:text-red-400';
    }

    if (value < 70) {
        return 'bg-amber-500/20 text-amber-600 dark:text-amber-400';
    }

    return 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400';
}

function Cell({ value }: { value: number | null }) {
    return (
        <td className="p-1">
            <div
                className={cn(
                    'flex h-9 min-w-16 items-center justify-center rounded-lg text-xs font-semibold tabular-nums',
                    tone(value),
                )}
            >
                {value === null ? '—' : `${value}%`}
            </div>
        </td>
    );
}

export default function RecruitmentManagerDashboard({
    counts,
    newInquiries,
    heatmap,
}: Props) {
    const cards: {
        label: string;
        value: number;
        icon: LucideIcon;
        query: Record<string, string | number>;
        accent?: boolean;
    }[] = [
        {
            label: 'Onboarded',
            value: counts.onboarded,
            icon: UsersRound,
            query: { purpose: 'training_and_placement' },
        },
        {
            label: 'Placed',
            value: counts.placed,
            icon: BriefcaseBusiness,
            query: { status: 'hired' },
        },
        {
            label: 'Pending',
            value: counts.pending,
            icon: Clock,
            query: { status: 'open' },
        },
        {
            label: 'Requests',
            value: counts.requests,
            icon: BellRing,
            query: { requested: 1 },
            accent: true,
        },
    ];

    return (
        <>
            <PrivateSeoHead title="Recruitment Manager" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Recruitment Manager
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Placement progress and how ready each student is.
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    {cards.map((card) => (
                        <Link
                            key={card.label}
                            href={candidatesIndex({ query: card.query })}
                            className={cn(
                                'flex flex-col gap-3 rounded-3xl border p-4 shadow-sm transition-transform active:scale-[0.97]',
                                card.accent
                                    ? 'border-brand-warm/40 bg-brand-warm/10'
                                    : 'border-border bg-card',
                            )}
                        >
                            <span
                                className={cn(
                                    'flex size-9 items-center justify-center rounded-full',
                                    card.accent
                                        ? 'bg-brand-warm/20 text-brand-warm'
                                        : 'bg-muted text-muted-foreground',
                                )}
                            >
                                <card.icon className="size-4.5" />
                            </span>
                            <span>
                                <span className="block text-3xl leading-none font-bold tabular-nums">
                                    {card.value}
                                </span>
                                <span className="mt-1 block text-xs text-muted-foreground">
                                    {card.label}
                                </span>
                            </span>
                        </Link>
                    ))}
                </div>

                <Link
                    href={inquiriesIndex()}
                    className="flex items-center gap-3 rounded-3xl border border-border bg-card p-4 shadow-sm transition-transform active:scale-[0.98]"
                >
                    <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-warm/15 text-brand-warm">
                        <Inbox className="size-5" />
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="block text-base font-semibold">
                            Website inquiries
                        </span>
                        <span className="block text-xs text-muted-foreground">
                            {newInquiries > 0
                                ? `${newInquiries} new in the last 7 days`
                                : 'No new inquiries this week'}
                        </span>
                    </span>
                    <ChevronRight className="size-5 shrink-0 text-muted-foreground" />
                </Link>

                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Student readiness · {heatmap.total}
                    </h2>

                    {heatmap.rows.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                            No students are registered for placement yet.
                        </div>
                    ) : (
                        <>
                            <div className="flex flex-col gap-3 md:hidden">
                                {heatmap.rows.map((row) => (
                                    <Link
                                        key={row.id}
                                        href={studentShow(row.id)}
                                        className="overflow-hidden rounded-3xl border border-border bg-card shadow-sm transition-transform active:scale-[0.98]"
                                    >
                                        <span className="flex items-center gap-3 p-4">
                                            <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-warm/15 text-sm font-semibold text-brand-warm">
                                                {row.name
                                                    .split(' ')
                                                    .slice(0, 2)
                                                    .map((word) =>
                                                        word[0]?.toUpperCase(),
                                                    )
                                                    .join('')}
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-base font-semibold capitalize">
                                                    {row.name}
                                                </span>
                                                <span className="block truncate text-xs text-muted-foreground">
                                                    {[
                                                        row.student_number,
                                                        row.batch,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </span>
                                            </span>
                                            {row.placement_status ===
                                                'hired' && (
                                                <span className="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-medium text-emerald-600">
                                                    Placed
                                                </span>
                                            )}
                                            <ChevronRight className="size-5 shrink-0 text-muted-foreground" />
                                        </span>
                                        <span className="grid grid-cols-4 gap-2 border-t border-border/70 bg-muted/30 p-3">
                                            {[
                                                ['Profile', row.profile],
                                                ['Score', row.assignments],
                                                [
                                                    'Submitted',
                                                    row.submission_rate,
                                                ],
                                                ['Attend.', row.attendance],
                                            ].map(([label, value]) => (
                                                <span
                                                    key={label as string}
                                                    className="flex flex-col items-center gap-1"
                                                >
                                                    <span
                                                        className={cn(
                                                            'flex h-8 w-full items-center justify-center rounded-lg text-xs font-semibold tabular-nums',
                                                            tone(
                                                                value as
                                                                    | number
                                                                    | null,
                                                            ),
                                                        )}
                                                    >
                                                        {value === null
                                                            ? '—'
                                                            : `${value}%`}
                                                    </span>
                                                    <span className="text-[10px] text-muted-foreground">
                                                        {label}
                                                    </span>
                                                </span>
                                            ))}
                                        </span>
                                    </Link>
                                ))}
                            </div>

                            <div className="hidden overflow-x-auto rounded-2xl border border-border bg-card/70 p-2 md:block">
                                <table className="w-full min-w-[640px] text-left text-sm">
                                    <thead className="text-xs text-muted-foreground">
                                        <tr>
                                            <th className="p-2 font-medium">
                                                Student
                                            </th>
                                            <th className="p-2 text-center font-medium">
                                                Profile
                                            </th>
                                            <th className="p-2 text-center font-medium">
                                                Assignment score
                                            </th>
                                            <th className="p-2 text-center font-medium">
                                                Submitted
                                            </th>
                                            <th className="p-2 text-center font-medium">
                                                Attendance
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {heatmap.rows.map((row) => (
                                            <tr key={row.id}>
                                                <td className="p-2">
                                                    <Link
                                                        href={studentShow(
                                                            row.id,
                                                        )}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {row.name}
                                                    </Link>
                                                    <p className="text-xs text-muted-foreground">
                                                        {[
                                                            row.student_number,
                                                            row.batch,
                                                            row.placement_status ===
                                                            'hired'
                                                                ? 'Placed'
                                                                : null,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                    </p>
                                                </td>
                                                <Cell value={row.profile} />
                                                <Cell value={row.assignments} />
                                                <Cell
                                                    value={row.submission_rate}
                                                />
                                                <Cell value={row.attendance} />
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    )}

                    {heatmap.links.length > 3 && (
                        <nav className="flex flex-wrap gap-1">
                            {heatmap.links.map((link, index) =>
                                link.url ? (
                                    <Link
                                        key={index}
                                        href={link.url}
                                        preserveScroll
                                        className={cn(
                                            'rounded-full px-3 py-1 text-xs',
                                            link.active
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted hover:bg-muted/70',
                                        )}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : null,
                            )}
                        </nav>
                    )}
                </section>
            </div>
        </>
    );
}
