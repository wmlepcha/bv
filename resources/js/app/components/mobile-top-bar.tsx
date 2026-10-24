import { Link, usePage } from '@inertiajs/react';
import { Bell, CalendarCheck, ChevronLeft, MessageCircle } from 'lucide-react';
import { dashboard } from '@/routes';
import { index as attendanceIndex } from '@/routes/student/attendance';
import { index as notificationsIndex } from '@/routes/student/notifications';
import type { Auth } from '@/types/auth';
import AppLogo from '@app/components/app-logo';
import { MobileUserMenu } from '@app/components/mobile-user-menu';
import { OPEN_CHAT_EVENT } from '@app/components/student-chat-widget';

const iconButton =
    'relative flex size-10 items-center justify-center rounded-full text-foreground/80 transition-transform active:scale-90 active:bg-foreground/10';

export function MobileTopBar({
    locked,
    title = null,
}: {
    locked: boolean;
    title?: string | null;
}) {
    const {
        auth,
        unread_notifications_count: unread,
        unread_messages_count: unreadMessages,
    } = usePage<{
        auth: Auth;
        unread_notifications_count: number;
        unread_messages_count: number;
    }>().props;
    const isStudent = auth.user?.role === 'student';
    const { url } = usePage();
    const path = url.split('?')[0];
    // Pages reached from elsewhere (settings, a test) get a back arrow instead of the logo.
    const backTarget = /^\/student\/assignments\/\d+$/.test(path)
        ? '/student/assignments'
        : path.startsWith('/settings')
          ? '/dashboard'
          : null;

    return (
        <header
            className="fixed inset-x-0 top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur-xl print:hidden"
            style={{ paddingTop: 'env(safe-area-inset-top)' }}
        >
            <div className="flex h-14 items-center justify-between gap-3 px-4">
                {backTarget && (
                    <Link
                        href={backTarget}
                        className="-ml-2 flex h-10 shrink-0 items-center gap-0.5 rounded-full pr-3 pl-1 text-base font-medium text-primary transition-transform active:scale-95"
                        aria-label="Back"
                    >
                        <ChevronLeft className="size-6" />
                        Back
                    </Link>
                )}
                {title ? (
                    <h2 className="min-w-0 flex-1 truncate text-[17px] font-semibold tracking-tight">
                        {title}
                    </h2>
                ) : backTarget ? (
                    <span className="flex-1" />
                ) : locked ? (
                    <div className="h-8 w-32 opacity-50">
                        <AppLogo />
                    </div>
                ) : (
                    <Link
                        href={dashboard()}
                        prefetch
                        className="block h-8 w-32"
                        aria-label="Home"
                    >
                        <AppLogo />
                    </Link>
                )}

                <div className="flex items-center gap-1">
                    {isStudent && !locked && (
                        <Link
                            href={attendanceIndex()}
                            prefetch
                            className={iconButton}
                            aria-label="Attendance"
                        >
                            <CalendarCheck className="size-5" />
                        </Link>
                    )}
                    {isStudent && !locked && (
                        <button
                            type="button"
                            onClick={() =>
                                window.dispatchEvent(new Event(OPEN_CHAT_EVENT))
                            }
                            className={iconButton}
                            aria-label="Messages"
                        >
                            <MessageCircle className="size-5" />
                            {unreadMessages > 0 && (
                                <span className="absolute top-1.5 right-1.5 flex min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4 font-bold text-white">
                                    {unreadMessages > 9 ? '9+' : unreadMessages}
                                </span>
                            )}
                        </button>
                    )}
                    {isStudent && !locked && (
                        <Link
                            href={notificationsIndex()}
                            prefetch
                            className={iconButton}
                            aria-label="Notifications"
                        >
                            <Bell className="size-5" />
                            {unread > 0 && (
                                <span className="absolute top-1.5 right-1.5 flex min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4 font-bold text-white">
                                    {unread > 9 ? '9+' : unread}
                                </span>
                            )}
                        </Link>
                    )}
                    {auth.user && <MobileUserMenu user={auth.user} />}
                </div>
            </div>
        </header>
    );
}
