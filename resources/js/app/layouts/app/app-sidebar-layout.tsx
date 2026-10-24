import { usePage } from '@inertiajs/react';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import type { AppLayoutProps } from '@/types';
import type { Auth } from '@/types/auth';
import { AppContent } from '@app/components/app-content';
import { AppShell } from '@app/components/app-shell';
import { AppSidebar } from '@app/components/app-sidebar';
import { AppSidebarHeader } from '@app/components/app-sidebar-header';
import DashboardAccessGate from '@app/components/dashboard-access-gate';
import { MobileBottomNav } from '@app/components/mobile-bottom-nav';
import { MobileTopBar } from '@app/components/mobile-top-bar';
import { PullToRefreshIndicator } from '@app/components/native/pull-to-refresh-indicator';
import RegistrationRenewalBanner from '@app/components/registration-renewal-banner';
import { StudentChatWidget } from '@app/components/student-chat-widget';
import { getMainNavItems } from '@app/config/navigation';
import {
    useCollapsedPageTitle,
    useKeyboardInset,
    usePullToRefresh,
} from '@app/lib/native';
import { BrandPageBackground } from '@shared/components/brand-page-background';

function MobileAppLayout({ children }: { children: React.ReactNode }) {
    const page = usePage<{
        auth: Auth;
        unread_messages_count: number;
        unread_notifications_count: number;
    }>();
    const {
        auth,
        unread_messages_count: unreadMessages,
        unread_notifications_count: unreadNotifications,
    } = page.props;
    const { url } = page;
    const role = auth.user?.role ?? 'student';
    const locked =
        role === 'student' && !(auth.user?.can_access_dashboard ?? false);
    const pathname = url.split('?')[0];
    // The account/renewal banner only shows on the main dashboard on phones.
    const isDashboard = pathname === '/dashboard';
    const keyboardOpen = useKeyboardInset();
    const pull = usePullToRefresh(!keyboardOpen);
    const collapsedTitle = useCollapsedPageTitle(pathname);

    return (
        <div
            className="mobile-app relative min-h-svh bg-background print:min-h-0 print:bg-white"
            style={
                {
                    '--app-top-h': 'calc(3.5rem + env(safe-area-inset-top))',
                    '--app-bottom-h': keyboardOpen
                        ? '0px'
                        : 'calc(4rem + env(safe-area-inset-bottom))',
                } as React.CSSProperties
            }
        >
            <MobileTopBar locked={locked} title={collapsedTitle} />
            <main
                className={cn(
                    'min-w-0 overflow-x-clip px-1 pt-[var(--app-top-h)] print:overflow-visible print:p-0',
                    !locked && !keyboardOpen && 'pb-[var(--app-bottom-h)]',
                )}
            >
                <DashboardAccessGate>
                    {isDashboard && <RegistrationRenewalBanner />}
                    <div key={pathname} className="mobile-page-enter min-w-0">
                        {children}
                    </div>
                </DashboardAccessGate>
            </main>
            <PullToRefreshIndicator
                distance={pull.distance}
                refreshing={pull.refreshing}
            />
            {!locked && !keyboardOpen && (
                <MobileBottomNav
                    items={getMainNavItems(
                        role,
                        unreadMessages,
                        unreadNotifications,
                    ).filter(
                        // Students get attendance and notifications as header icons instead.
                        (item) =>
                            role !== 'student' ||
                            !['Attendance', 'Notifications'].includes(
                                item.title,
                            ),
                    )}
                />
            )}
            <StudentChatWidget />
        </div>
    );
}

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const isMobile = useIsMobile();

    if (isMobile) {
        return <MobileAppLayout>{children}</MobileAppLayout>;
    }

    return (
        <div className="relative min-h-svh bg-nav-rail">
            <BrandPageBackground />
            <AppShell variant="sidebar">
                <AppSidebar />
                <AppContent
                    variant="sidebar"
                    className="min-w-0 overflow-x-clip"
                >
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    <DashboardAccessGate>
                        {/*
                          Keep inset background flush with the rail for the cut-out join.
                          Pad page bodies so cards/UI clear the fixed rail + indicator bleed
                          (see .nav-rail-indicator right: -1.5rem). z-index cannot fix this.
                        */}
                        <div className="min-w-0 pl-4 sm:pl-6">{children}</div>
                    </DashboardAccessGate>
                </AppContent>
            </AppShell>
            <StudentChatWidget />
        </div>
    );
}
