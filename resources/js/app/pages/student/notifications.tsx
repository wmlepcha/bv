import { router } from '@inertiajs/react';
import { ClipboardList, PartyPopper } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    index as notificationsIndex,
    read as markAsRead,
    readAll as markAllAsRead,
} from '@/routes/student/notifications';
import { ConfettiBurst } from '@app/components/confetti-burst';
import Heading from '@shared/components/heading';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { BRAND_NAME_TM } from '@shared/lib/brand';

type StudentNotification = {
    id: string;
    title: string;
    body: string;
    url: string | null;
    kind?: 'info' | 'celebration' | 'requirements' | string;
    read_at: string | null;
    created_at: string | null;
};

type Props = {
    notifications: StudentNotification[];
    unread_count: number;
};

function formatWhen(iso: string | null): string {
    if (!iso) {
        return '';
    }

    try {
        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

export default function Notifications({ notifications, unread_count }: Props) {
    const [filter, setFilter] = useState<'all' | 'unread'>('all');
    const visible =
        filter === 'unread'
            ? notifications.filter((item) => item.read_at === null)
            : notifications;

    return (
        <>
            <PrivateSeoHead title="Notifications" />

            <div className="mx-auto w-full max-w-3xl space-y-6 px-2 pt-5 md:pt-0">
                <Heading
                    title="Notifications"
                    description={`Announcements and updates from ${BRAND_NAME_TM}.`}
                />

                <div className="sticky top-[var(--app-top-h,0px)] z-20 -mx-2 flex items-center justify-between gap-3 border-b border-border/60 bg-background/95 px-2 py-2.5 backdrop-blur">
                    <div
                        role="tablist"
                        aria-label="Filter notifications"
                        className="inline-flex rounded-full border border-border bg-card p-1 shadow-sm"
                    >
                        {(
                            [
                                ['all', `All (${notifications.length})`],
                                ['unread', `Unread (${unread_count})`],
                            ] as const
                        ).map(([value, label]) => (
                            <button
                                key={value}
                                type="button"
                                role="tab"
                                aria-selected={filter === value}
                                onClick={() => setFilter(value)}
                                className={cn(
                                    'h-9 rounded-full px-4 text-sm whitespace-nowrap transition-colors',
                                    filter === value
                                        ? 'bg-primary font-medium text-primary-foreground shadow-sm'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {label}
                            </button>
                        ))}
                    </div>

                    {unread_count > 0 && (
                        <button
                            type="button"
                            className="shrink-0 text-sm font-medium text-primary active:opacity-60"
                            onClick={() =>
                                router.post(
                                    markAllAsRead.url(),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            Mark all read
                        </button>
                    )}
                </div>

                {visible.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {filter === 'unread'
                            ? 'You are all caught up.'
                            : 'No notifications yet.'}
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {visible.map((notification) => {
                            const unread = notification.read_at === null;

                            if (notification.kind === 'celebration') {
                                return (
                                    <li key={notification.id}>
                                        <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#FFE3D1] via-[#FFF1C9] to-[#D8ECFA] p-6 text-[#031835] shadow-sm sm:p-8">
                                            <ConfettiBurst />
                                            <div className="relative flex flex-col items-center gap-3 text-center">
                                                <span className="flex size-14 items-center justify-center rounded-full bg-white/80 shadow">
                                                    <PartyPopper className="size-7 text-[#E8704A]" />
                                                </span>
                                                <h2 className="text-2xl font-bold tracking-tight">
                                                    You've been shortlisted!
                                                </h2>
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
                                        'flex flex-col gap-3 rounded-3xl border border-border/70 bg-card p-4 shadow-sm sm:flex-row sm:items-start sm:justify-between',
                                        unread &&
                                            'border-primary/30 bg-primary/5',
                                    )}
                                >
                                    <div className="min-w-0 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="flex items-center gap-1.5 text-sm font-medium text-foreground">
                                                {notification.kind ===
                                                    'requirements' && (
                                                    <ClipboardList className="size-4 text-brand-cool" />
                                                )}
                                                {notification.title}
                                            </p>
                                            {unread && (
                                                <span
                                                    className="size-2 rounded-full bg-primary"
                                                    aria-label="New"
                                                />
                                            )}
                                        </div>
                                        {notification.body !== '' && (
                                            <p className="text-sm whitespace-pre-line text-muted-foreground">
                                                {notification.body}
                                            </p>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            {formatWhen(
                                                notification.created_at,
                                            )}
                                        </p>
                                    </div>

                                    <div className="flex shrink-0 flex-wrap gap-2">
                                        {notification.url && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    const unread =
                                                        notification.read_at ===
                                                        null;

                                                    const navigate = () => {
                                                        if (
                                                            notification.url!.startsWith(
                                                                '/',
                                                            )
                                                        ) {
                                                            router.visit(
                                                                notification.url!,
                                                            );

                                                            return;
                                                        }

                                                        window.location.assign(
                                                            notification.url!,
                                                        );
                                                    };

                                                    if (unread) {
                                                        router.post(
                                                            markAsRead.url(
                                                                notification.id,
                                                            ),
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                                onSuccess:
                                                                    navigate,
                                                            },
                                                        );
                                                    } else {
                                                        navigate();
                                                    }
                                                }}
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
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </>
    );
}

Notifications.layout = {
    breadcrumbs: [
        {
            title: 'Notifications',
            href: notificationsIndex(),
        },
    ],
};
