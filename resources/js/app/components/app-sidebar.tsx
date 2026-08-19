import { Link, usePage } from '@inertiajs/react';
import AppLogo from '@app/components/app-logo';
import { getMainNavItems } from '@app/config/navigation';
import { NavMain } from '@app/components/nav-main';
import { NavUser } from '@app/components/nav-user';
import { AppearanceToggle } from '@shared/components/appearance-toggle';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, UserRole } from '@/types/auth';

const ROLE_LABELS: Record<UserRole, string> = {
    student: 'Student',
    teacher: 'Faculty',
    training_manager: 'Training Manager',
    recruiter: 'Recruiter',
    employee: 'Employee',
    admin: 'Admin',
};

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const role = auth.user?.role ?? 'student';
    const mainNavItems = getMainNavItems(role);
    const navLocked =
        role === 'student' && !(auth.user?.can_access_dashboard ?? false);
    const roleLabel = ROLE_LABELS[role] ?? role;

    return (
        <Sidebar
            collapsible="offcanvas"
            variant="inset"
            className="border-0 border-r-0 py-3 pl-2 pr-0 shadow-none"
        >
            <SidebarHeader className="px-3 pt-3 pb-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        {navLocked ? (
                            <SidebarMenuButton
                                size="lg"
                                disabled
                                aria-disabled
                                className="h-14 pointer-events-none opacity-50 hover:bg-transparent"
                            >
                                <AppLogo onBrandRail />
                            </SidebarMenuButton>
                        ) : (
                            <SidebarMenuButton
                                size="lg"
                                asChild
                                className="h-14 hover:bg-transparent data-[active=true]:bg-transparent"
                            >
                                <Link href={dashboard()} prefetch>
                                    <AppLogo onBrandRail />
                                </Link>
                            </SidebarMenuButton>
                        )}
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="overflow-visible pl-2 pr-0">
                <NavMain items={mainNavItems} locked={navLocked} />
            </SidebarContent>

            <SidebarFooter className="gap-3 px-3 pb-4">
                <div className="flex justify-center px-1">
                    <AppearanceToggle variant="onLightRail" />
                </div>
                {auth.user && <NavUser />}
                <p className="px-2 text-center text-xs font-medium tracking-wide text-nav-rail-muted">
                    {roleLabel}
                </p>
            </SidebarFooter>
        </Sidebar>
    );
}
