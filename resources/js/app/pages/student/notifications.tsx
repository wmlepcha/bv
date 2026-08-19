import { router } from '@inertiajs/react';
import Heading from '@shared/components/heading';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { BRAND_NAME_TM } from '@shared/lib/brand';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    index as notificationsIndex,
    read as markAsRead,
    readAll as markAllAsRead,
} from '@/routes/student/notifications';

type StudentNotification = {
    id: string;
    title: string;
    body: string;
    url: string | null;
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

export default function Notifications({
    notifications,
    unread_count,
}: Props) {
    return (
        <>
            <PrivateSeoHead title="Notifications" />

            <div className="mx-auto w-full max-w-3xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Notifications"
                        description={`Announcements and updates from ${BRAND_NAME_TM}.`}
                    />

                    {unread_count > 0 && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                router.post(markAllAsRead.url(), {}, {
                                    preserveScroll: true,
                                })
                            }
                        >
                            Mark all as read
                        </Button>
                    )}
                </div>

                {notifications.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No notifications yet.
                    </p>
                ) : (
                    <ul className="divide-y divide-border border-y border-border">
                        {notifications.map((notification) => {
                            const unread = notification.read_at === null;

                            return (
                                <li
                                    key={notification.id}
                                    className={cn(
                                        'flex flex-col gap-2 py-4 sm:flex-row sm:items-start sm:justify-between',
                                        unread && 'bg-muted/30',
                                    )}
                                >
                                    <div className="min-w-0 space-y-1 px-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-medium text-foreground">
                                                {notification.title}
                                            </p>
                                            {unread && (
                                                <span className="text-xs text-muted-foreground">
                                                    New
                                                </span>
                                            )}
                                        </div>
                                        {notification.body !== '' && (
                                            <p className="text-sm text-muted-foreground">
                                                {notification.body}
                                            </p>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            {formatWhen(notification.created_at)}
                                        </p>
                                    </div>

                                    <div className="flex shrink-0 flex-wrap gap-2 px-1">
                                        {notification.url && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    if (unread) {
                                                        router.post(
                                                            markAsRead.url(
                                                                notification.id,
                                                            ),
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                                onFinish: () => {
                                                                    window.location.href =
                                                                        notification.url!;
                                                                },
                                                            },
                                                        );
                                                    } else {
                                                        window.location.href =
                                                            notification.url!;
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
