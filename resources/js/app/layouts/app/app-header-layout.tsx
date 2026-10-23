import type { AppLayoutProps } from '@/types';
import { AppContent } from '@app/components/app-content';
import { AppHeader } from '@app/components/app-header';
import { AppShell } from '@app/components/app-shell';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: AppLayoutProps) {
    return (
        <AppShell variant="header">
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent variant="header">{children}</AppContent>
        </AppShell>
    );
}
