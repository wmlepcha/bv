import { Link, router } from '@inertiajs/react';
import {
    Bell,
    BookOpen,
    CalendarRange,
    ClipboardList,
    FolderOpen,
    GraduationCap,
    Mail,
    MapPin,
    PartyPopper,
    ScanLine,
    UserRound,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as attendanceIndex } from '@/routes/student/attendance';
import { edit as cvEdit } from '@/routes/student/cv';
import {
    index as notificationsIndex,
    read as markAsRead,
    readAll as markAllAsRead,
} from '@/routes/student/notifications';
import { index as studyBankIndex } from '@/routes/student/study-bank';
import { ConfettiBurst } from '@app/components/confetti-burst';
import { PrivateSeoHead } from '@shared/components/seo-head';

type StudentNotification = {
    id: string;
    title: string;
    body: string;
    url: string | null;
    kind?: 'info' | 'celebration' | 'requirements' | string;
    read_at: string | null;
    created_at: string | null;
};

type RecentAssignment = {
    id: number;
    title: string;
    status: string;
    status_label: string;
    due_at: string | null;
    submission_status: string | null;
    submission_id: number | null;
};

type Props = {
    profile: {
        name: string;
        email: string;
        student_number: string | null;
        headline: string | null;
        photo_url: string | null;
        completion_percent: number;
    };
    batch: {
        id: number;
        name: string;
        center_name: string | null;
        language_label: string;
        level_label: string;
        is_active: boolean;
        starts_on_label: string | null;
        ends_on_label: string | null;
    } | null;
    trainer: {
        id: number;
        name: string;
        email: string;
        photo_url: string | null;
    } | null;
    registration: {
        expires_at: string | null;
        expires_at_label: string | null;
        plan_name: string | null;
        days_remaining: number | null;
    };
    stats: {
        pending_assignments: number;
        completed_assignments: number;
        total_assignments: number;
        study_materials_count: number;
        unread_notifications: number;
    };
    recent_assignments: RecentAssignment[];
    notifications: StudentNotification[];
    attendance: {
        total_sessions: number;
        percentage: number | null;
    };
};

function formatWhen(iso: string | null): string {
    if (!iso) {
        return '';
    }

    try {
        const date = new Date(iso);
        const now = new Date();
        const diffMs = now.getTime() - date.getTime();
        const diffMins = Math.floor(diffMs / 60000);

        if (diffMins < 1) {
            return 'Just now';
        }

        if (diffMins < 60) {
            return `${diffMins}m ago`;
        }

        const diffHours = Math.floor(diffMins / 60);

        if (diffHours < 24) {
            return `${diffHours}h ago`;
        }

        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
        }).format(date);
    } catch {
        return '';
    }
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

function StatCard({
    label,
    value,
    hint,
    href,
    icon: Icon,
    body,
    soft,
    accent,
}: {
    label: string;
    value: number;
    hint: string;
    href?: string;
    icon: typeof ClipboardList;
    body: string;
    soft: string;
    accent: number;
}) {
    const content = (
        <>
            <div className="flex items-start justify-between gap-3">
                <p className="text-xs font-medium text-white/85 sm:text-sm">
                    {label}
                </p>
                <span
                    className={cn(
                        'flex size-9 shrink-0 items-center justify-center rounded-2xl sm:size-10',
                        soft,
                    )}
                >
                    <Icon className="size-5 text-white" />
                </span>
            </div>
            <p className="mt-2 text-3xl font-semibold tracking-tight text-white tabular-nums sm:mt-4 sm:text-4xl">
                {value}
            </p>
            <p className="mt-0.5 text-xs text-white/75 sm:mt-1 sm:text-sm">
                {hint}
            </p>
            <div className="mt-auto pt-3 sm:pt-5">
                <div className="h-1.5 overflow-hidden rounded-full bg-white/25">
                    <div
                        className="h-full rounded-full bg-white transition-[width] duration-300"
                        style={{ width: `${accent}%` }}
                    />
                </div>
            </div>
        </>
    );

    const className = cn(
        'group relative flex min-h-28 flex-col overflow-hidden rounded-3xl p-4 text-white shadow-[0_18px_40px_-24px_rgba(3,24,53,0.55)] transition-transform duration-200 active:scale-[0.97] sm:min-h-[160px] sm:rounded-[1.75rem] sm:p-5 sm:hover:-translate-y-0.5 sm:active:scale-100',
        body,
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {content}
            </Link>
        );
    }

    return <div className={className}>{content}</div>;
}

function InfoCard({
    title,
    icon: Icon,
    body,
    className,
    children,
}: {
    title: string;
    icon: typeof GraduationCap;
    body: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <section
            className={cn(
                'relative flex min-h-[220px] flex-col overflow-hidden rounded-[1.75rem] p-5 text-white shadow-[0_18px_40px_-24px_rgba(3,24,53,0.55)] max-lg:w-[88%] max-lg:shrink-0 max-lg:snap-center',
                body,
                className,
            )}
        >
            <div
                className="pointer-events-none absolute -top-10 -right-10 size-32 rounded-full bg-white/10 blur-2xl"
                aria-hidden
            />
            <div className="relative flex items-center gap-2">
                <span className="flex size-9 items-center justify-center rounded-xl bg-white/15">
                    <Icon className="size-4" />
                </span>
                <h2 className="text-base font-semibold">{title}</h2>
            </div>
            <div className="relative mt-4 flex flex-1 flex-col">{children}</div>
        </section>
    );
}

function NotificationsPanel({
    notifications,
    unreadCount,
}: {
    notifications: StudentNotification[];
    unreadCount: number;
}) {
    function openNotification(notification: StudentNotification) {
        const unread = notification.read_at === null;

        const navigate = () => {
            if (!notification.url) {
                return;
            }

            if (notification.url.startsWith('/')) {
                router.visit(notification.url);

                return;
            }

            window.location.assign(notification.url);
        };

        if (unread) {
            router.post(
                markAsRead.url(notification.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: navigate,
                },
            );

            return;
        }

        navigate();
    }

    return (
        <section className="overflow-hidden rounded-[1.75rem] border border-border/70 bg-card shadow-sm">
            <div className="flex items-center justify-between gap-3 border-b border-border/70 bg-gradient-to-r from-brand-cool/10 via-brand-warm/5 to-transparent px-5 py-4">
                <div className="flex items-center gap-2">
                    <span className="flex size-9 items-center justify-center rounded-xl bg-brand-cool/15 text-brand-cool">
                        <Bell className="size-4" />
                    </span>
                    <h2 className="text-base font-semibold">Notifications</h2>
                    {unreadCount > 0 && (
                        <span className="rounded-full bg-brand-warm px-2 py-0.5 text-xs font-semibold text-white">
                            {unreadCount} new
                        </span>
                    )}
                </div>
                <div className="flex items-center gap-1">
                    <Link
                        href={notificationsIndex()}
                        className="rounded-md px-2 py-1 text-sm font-medium text-primary hover:underline"
                    >
                        View all
                    </Link>
                    {unreadCount > 0 && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                router.post(
                                    markAllAsRead.url(),
                                    {},
                                    {
                                        preserveScroll: true,
                                    },
                                )
                            }
                        >
                            Mark all read
                        </Button>
                    )}
                </div>
            </div>

            {notifications.length === 0 ? (
                <p className="px-5 py-8 text-sm text-muted-foreground">
                    No notifications yet. Updates about assignments, your batch,
                    and account will appear here.
                </p>
            ) : (
                <ul className="max-h-[520px] divide-y divide-border overflow-y-auto">
                    {notifications.map((notification) => {
                        const unread = notification.read_at === null;

                        if (notification.kind === 'celebration') {
                            return (
                                <li key={notification.id} className="p-4">
                                    <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#FFE3D1] via-[#FFF1C9] to-[#D8ECFA] p-6 text-[#031835] shadow-sm">
                                        <ConfettiBurst />
                                        <div className="relative flex flex-col items-center gap-3 text-center">
                                            <span className="flex size-14 items-center justify-center rounded-full bg-white/80 shadow">
                                                <PartyPopper className="size-7 text-[#E8704A]" />
                                            </span>
                                            <h3 className="text-2xl font-bold tracking-tight">
                                                You've been shortlisted!
                                            </h3>
                                            <p className="text-base font-medium">
                                                We will call you soon.
                                            </p>
                                            {notification.body !== '' && (
                                                <p className="max-w-md text-sm whitespace-pre-line text-[#031835]/80">
                                                    {notification.body}
                                                </p>
                                            )}
                                            <p className="text-xs text-[#031835]/60">
                                                {formatWhen(
                                                    notification.created_at,
                                                )}
                                            </p>
                                            {unread && (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    className="rounded-full"
                                                    onClick={() =>
                                                        router.post(
                                                            markAsRead.url(
                                                                notification.id,
                                                            ),
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    Mark read
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </li>
                            );
                        }

                        return (
                            <li
                                key={notification.id}
                                className={cn(
                                    'px-5 py-4 transition',
                                    unread && 'bg-brand-cool/5',
                                )}
                            >
                                <div className="flex items-start gap-3">
                                    <span
                                        className={cn(
                                            'mt-1.5 size-2 shrink-0 rounded-full',
                                            unread
                                                ? 'bg-brand-warm'
                                                : 'bg-transparent',
                                        )}
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="flex items-center gap-1.5 text-sm font-medium">
                                                {notification.kind ===
                                                    'requirements' && (
                                                    <ClipboardList className="size-4 text-brand-cool" />
                                                )}
                                                {notification.title}
                                            </p>
                                            <span className="shrink-0 text-xs text-muted-foreground">
                                                {formatWhen(
                                                    notification.created_at,
                                                )}
                                            </span>
                                        </div>
                                        {notification.body !== '' && (
                                            <p className="mt-1 text-sm whitespace-pre-line text-muted-foreground">
                                                {notification.body}
                                            </p>
                                        )}
                                        <div className="mt-2 flex flex-wrap gap-2">
                                            {notification.url && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        openNotification(
                                                            notification,
                                                        )
                                                    }
                                                >
                                                    Open
                                                </Button>
                                            )}
                                            {unread && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.post(
                                                            markAsRead.url(
                                                                notification.id,
                                                            ),
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    Mark read
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}

export default function StudentDashboard({
    profile,
    batch,
    trainer,
    registration,
    stats,
    recent_assignments,
    notifications,
    attendance,
}: Props) {
    const getInitials = useInitials();
    const firstName = profile.name.split(' ')[0] ?? profile.name;
    const pendingAccent =
        stats.total_assignments > 0
            ? Math.max(
                  12,
                  Math.round(
                      (stats.pending_assignments / stats.total_assignments) *
                          100,
                  ),
              )
            : 12;
    const gradedAccent =
        stats.total_assignments > 0
            ? Math.max(
                  12,
                  Math.round(
                      (stats.completed_assignments / stats.total_assignments) *
                          100,
                  ),
              )
            : 12;
    const materialsAccent = Math.min(
        100,
        Math.max(12, stats.study_materials_count * 15),
    );
    const notificationAccent = Math.min(
        100,
        Math.max(8, stats.unread_notifications * 20),
    );

    return (
        <>
            <PrivateSeoHead title="Dashboard" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                        Welcome back, {firstName}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Your classes, assignments, and updates in one place.
                    </p>
                </div>

                <div className="-mx-2 flex snap-x snap-mandatory scrollbar-none gap-3 overflow-x-auto px-2 pb-1 lg:mx-0 lg:grid lg:grid-cols-3 lg:gap-4 lg:overflow-visible lg:px-0 lg:pb-0">
                    <InfoCard
                        title="Your profile"
                        icon={UserRound}
                        body="bg-gradient-to-br from-brand-cool-dark via-brand-cool to-[#042a5c]"
                    >
                        <div className="flex items-start gap-4">
                            <div className="relative size-16 shrink-0 overflow-hidden rounded-2xl ring-2 ring-white/20">
                                {profile.photo_url ? (
                                    <img
                                        src={profile.photo_url}
                                        alt=""
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    <div className="flex size-full items-center justify-center bg-white/15 text-lg font-semibold">
                                        {getInitials(profile.name)}
                                    </div>
                                )}
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-lg font-semibold">
                                    {profile.name}
                                </p>
                                {profile.headline && (
                                    <p className="mt-0.5 line-clamp-2 text-sm text-white/75">
                                        {profile.headline}
                                    </p>
                                )}
                                <p className="mt-1 text-xs text-white/70">
                                    {profile.student_number
                                        ? `#${profile.student_number}`
                                        : profile.email}
                                </p>
                            </div>
                        </div>

                        <div className="mt-4">
                            <div className="flex items-center justify-between text-xs text-white/75">
                                <span>CV completion</span>
                                <span className="font-semibold text-white">
                                    {profile.completion_percent}%
                                </span>
                            </div>
                            <div className="mt-2 h-2 overflow-hidden rounded-full bg-white/20">
                                <div
                                    className="h-full rounded-full bg-white transition-all"
                                    style={{
                                        width: `${profile.completion_percent}%`,
                                    }}
                                />
                            </div>
                        </div>

                        {registration.expires_at_label && (
                            <p className="mt-3 text-xs text-white/70">
                                {registration.plan_name
                                    ? `${registration.plan_name} · `
                                    : ''}
                                Valid until {registration.expires_at_label}
                                {registration.days_remaining !== null &&
                                    ` (${registration.days_remaining} days left)`}
                            </p>
                        )}

                        <Button
                            asChild
                            size="sm"
                            className="mt-auto w-full border-white/25 bg-white/15 text-white hover:bg-white/25 hover:text-white"
                        >
                            <Link href={cvEdit()}>
                                <UserRound className="size-4" />
                                Edit your CV
                            </Link>
                        </Button>
                    </InfoCard>

                    <section className="relative flex flex-col gap-4 overflow-hidden rounded-[1.75rem] bg-brand-warm p-5 text-white shadow-[0_18px_40px_-24px_rgba(3,24,53,0.55)] max-lg:w-[88%] max-lg:shrink-0 max-lg:snap-center lg:hidden">
                        <div className="flex items-center gap-2">
                            <span className="flex size-9 items-center justify-center rounded-xl bg-white/15">
                                <GraduationCap className="size-4" />
                            </span>
                            <h2 className="text-base font-semibold">
                                Your batch
                            </h2>
                        </div>
                        {batch ? (
                            <div className="space-y-1.5 text-sm">
                                <p className="text-lg font-semibold">
                                    {batch.name}
                                </p>
                                <p className="text-white/80">
                                    {batch.language_label} {batch.level_label}
                                </p>
                                <p className="flex items-center gap-1.5 text-white/75">
                                    <MapPin className="size-3.5 shrink-0" />
                                    {batch.center_name ?? 'No center assigned'}
                                </p>
                                <p className="flex items-center gap-1.5 text-white/75">
                                    <CalendarRange className="size-3.5 shrink-0" />
                                    {formatDateRange(
                                        batch.starts_on_label,
                                        batch.ends_on_label,
                                    )}
                                </p>
                            </div>
                        ) : (
                            <p className="text-sm text-white/80">
                                You are not enrolled in a batch yet.
                            </p>
                        )}
                        <div className="flex items-center gap-3 rounded-2xl bg-black/15 p-3">
                            <div className="size-11 shrink-0 overflow-hidden rounded-xl ring-2 ring-white/25">
                                {trainer?.photo_url ? (
                                    <img
                                        src={trainer.photo_url}
                                        alt=""
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    <div className="flex size-full items-center justify-center bg-white/20 text-sm font-semibold">
                                        {trainer
                                            ? getInitials(trainer.name)
                                            : '?'}
                                    </div>
                                )}
                            </div>
                            <div className="min-w-0 text-sm">
                                <p className="text-xs text-white/70">
                                    Your trainer
                                </p>
                                <p className="truncate font-semibold">
                                    {trainer?.name ?? 'Not assigned yet'}
                                </p>
                                {trainer && (
                                    <p className="truncate text-white/80">
                                        {trainer.email}
                                    </p>
                                )}
                            </div>
                        </div>
                    </section>

                    <InfoCard
                        title="Your batch"
                        icon={GraduationCap}
                        className="max-lg:hidden"
                        body="bg-brand-warm"
                    >
                        {batch ? (
                            <div className="space-y-3 text-sm">
                                <div>
                                    <p className="text-lg font-semibold">
                                        {batch.name}
                                    </p>
                                    <p className="mt-1 text-white/80">
                                        {batch.language_label}{' '}
                                        {batch.level_label}
                                    </p>
                                </div>
                                <p className="flex items-center gap-1.5 text-white/75">
                                    <MapPin className="size-3.5 shrink-0" />
                                    {batch.center_name ?? 'No center assigned'}
                                </p>
                                <p className="flex items-center gap-1.5 text-white/75">
                                    <CalendarRange className="size-3.5 shrink-0" />
                                    {formatDateRange(
                                        batch.starts_on_label,
                                        batch.ends_on_label,
                                    )}
                                </p>
                                <span
                                    className={cn(
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        batch.is_active
                                            ? 'bg-white/20 text-white'
                                            : 'bg-black/20 text-white/70',
                                    )}
                                >
                                    {batch.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                        ) : (
                            <p className="text-sm text-white/80">
                                You are not enrolled in a batch yet. Your
                                training manager will assign you soon.
                            </p>
                        )}
                    </InfoCard>

                    <InfoCard
                        title="Your trainer"
                        icon={BookOpen}
                        className="max-lg:hidden"
                        body="bg-brand-warm-light"
                    >
                        {trainer ? (
                            <div className="flex items-start gap-3">
                                <div className="size-14 shrink-0 overflow-hidden rounded-xl ring-2 ring-white/25">
                                    {trainer.photo_url ? (
                                        <img
                                            src={trainer.photo_url}
                                            alt=""
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex size-full items-center justify-center bg-white/20 text-sm font-semibold">
                                            {getInitials(trainer.name)}
                                        </div>
                                    )}
                                </div>
                                <div className="min-w-0">
                                    <p className="text-lg font-semibold">
                                        {trainer.name}
                                    </p>
                                    <p className="mt-1 flex items-center gap-1.5 text-sm text-white/85">
                                        <Mail className="size-3.5 shrink-0" />
                                        <span className="truncate">
                                            {trainer.email}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <p className="text-sm text-white/85">
                                No trainer assigned yet. This will appear once
                                you are placed in a batch.
                            </p>
                        )}
                    </InfoCard>
                </div>

                <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                    <StatCard
                        label="Pending tasks"
                        value={stats.pending_assignments}
                        hint="assignments to complete"
                        href="/student/assignments"
                        icon={ClipboardList}
                        body="bg-brand-cool"
                        soft="bg-white/15"
                        accent={pendingAccent}
                    />
                    <StatCard
                        label="Graded"
                        value={stats.completed_assignments}
                        hint="assignments reviewed"
                        href="/student/assignments"
                        icon={ClipboardList}
                        body="bg-brand-warm"
                        soft="bg-white/15"
                        accent={gradedAccent}
                    />
                    <StatCard
                        label="Study materials"
                        value={stats.study_materials_count}
                        hint="files and folders shared"
                        href={studyBankIndex()}
                        icon={FolderOpen}
                        body="bg-brand-cool-dark"
                        soft="bg-white/15"
                        accent={materialsAccent}
                    />
                    <StatCard
                        label="Notifications"
                        value={stats.unread_notifications}
                        hint="unread updates"
                        icon={Bell}
                        body="bg-brand-warm-light"
                        soft="bg-white/20"
                        accent={notificationAccent}
                    />
                </div>

                <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
                    <div className="max-lg:hidden">
                        <NotificationsPanel
                            notifications={notifications}
                            unreadCount={stats.unread_notifications}
                        />
                    </div>

                    <section className="overflow-hidden rounded-[1.75rem] border border-border/70 bg-card shadow-sm max-lg:hidden">
                        <div className="flex items-center justify-between gap-2 border-b border-border/70 bg-gradient-to-r from-brand-warm/10 to-transparent px-5 py-4">
                            <div className="flex items-center gap-2">
                                <span className="flex size-9 items-center justify-center rounded-xl bg-brand-warm/15 text-brand-warm">
                                    <ClipboardList className="size-4" />
                                </span>
                                <h2 className="text-base font-semibold">
                                    Recent assignments
                                </h2>
                            </div>
                            <Link
                                href="/student/assignments"
                                className="text-xs font-semibold text-brand-cool hover:underline"
                            >
                                View all
                            </Link>
                        </div>

                        <div className="p-5">
                            {recent_assignments.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No assignments yet. New tasks from your
                                    trainer will show up here.
                                </p>
                            ) : (
                                <ul className="divide-y divide-border">
                                    {recent_assignments.map((assignment) => (
                                        <li
                                            key={assignment.id}
                                            className="py-3"
                                        >
                                            <Link
                                                href={`/student/assignments/${assignment.id}`}
                                                className="font-medium hover:text-brand-cool hover:underline"
                                            >
                                                {assignment.title}
                                            </Link>
                                            <p className="mt-0.5">
                                                <span
                                                    className={cn(
                                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                        assignment.submission_status ===
                                                            'graded'
                                                            ? 'bg-brand-cool/15 text-brand-cool'
                                                            : assignment.submission_status ===
                                                                'submitted'
                                                              ? 'bg-brand-warm/15 text-brand-warm'
                                                              : 'bg-muted text-muted-foreground',
                                                    )}
                                                >
                                                    {assignment.submission_status ===
                                                    'graded'
                                                        ? 'Graded'
                                                        : assignment.submission_status ===
                                                            'submitted'
                                                          ? 'Submitted'
                                                          : 'Not submitted'}
                                                </span>
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </section>

                    <Link
                        href={attendanceIndex()}
                        className="flex items-center justify-between gap-4 rounded-[1.75rem] border border-border/70 bg-card p-5 shadow-sm transition active:scale-[0.99] max-lg:hidden"
                    >
                        <div>
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                Attendance
                            </p>
                            <p className="mt-1 text-3xl font-semibold tabular-nums">
                                {attendance.percentage === null
                                    ? '—'
                                    : `${attendance.percentage}%`}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {attendance.total_sessions === 0
                                    ? 'No classes recorded yet'
                                    : `${attendance.total_sessions} classes recorded`}
                            </p>
                        </div>
                        <span className="flex size-12 items-center justify-center rounded-2xl bg-brand-cool/15 text-brand-cool">
                            <ScanLine className="size-6" />
                        </span>
                    </Link>
                </div>
            </div>
        </>
    );
}

StudentDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
