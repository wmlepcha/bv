import { AppContent } from '@app/components/app-content';
import { AppShell } from '@app/components/app-shell';
import { AppSidebar } from '@app/components/app-sidebar';
import { AppSidebarHeader } from '@app/components/app-sidebar-header';
import DashboardAccessGate from '@app/components/dashboard-access-gate';
import { BrandPageBackground } from '@shared/components/brand-page-background';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <div className="relative min-h-svh bg-nav-rail">
            <BrandPageBackground />
            <AppShell variant="sidebar">
                <AppSidebar />
                <AppContent variant="sidebar" className="min-w-0 overflow-x-hidden">
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
        </div>
    );
}
