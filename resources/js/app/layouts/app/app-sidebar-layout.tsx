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
        <div className="relative min-h-svh">
            <BrandPageBackground />
            <AppShell variant="sidebar">
                <AppSidebar />
                <AppContent variant="sidebar" className="min-w-0 overflow-x-hidden">
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    <DashboardAccessGate>{children}</DashboardAccessGate>
                </AppContent>
            </AppShell>
        </div>
    );
}
