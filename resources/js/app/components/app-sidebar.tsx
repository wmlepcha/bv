import { Link, usePage } from '@inertiajs/react';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, UserRole } from '@/types/auth';
import AppLogo from '@app/components/app-logo';
import { NavMain } from '@app/components/nav-main';
import { NavUser } from '@app/components/nav-user';
import { SidebarSearch } from '@app/components/sidebar-search';
import { getMainNavItems } from '@app/config/navigation';
import { AppearanceToggle } from '@shared/components/appearance-toggle';

const ROLE_LABELS: Record<UserRole, string> = {
    student: 'Student',
    teacher: 'Faculty',
    training_manager: 'Training Manager',
    recruitment_manager: 'Recruitment Manager',
    recruiter: 'Recruiter',
    employee: 'Employee',
    admin: 'Admin',
};

export function AppSidebar() {
    const {
        auth,
        unread_messages_count: unreadMessagesCount,
        unread_notifications_count: unreadNotificationsCount,
    } = usePage<{
        auth: Auth;
        unread_notifications_count: number;
        unread_messages_count: number;
    }>().props;
    const { isMobile, setOpenMobile } = useSidebar();
    const role = auth.user?.role ?? 'student';
    const mainNavItems = getMainNavItems(
        role,
        unreadMessagesCount,
        unreadNotificationsCount,
    );
    const navLocked =
        role === 'student' && !(auth.user?.can_access_dashboard ?? false);
    const roleLabel = ROLE_LABELS[role] ?? role;

    return (
        <Sidebar
            collapsible="offcanvas"
            variant="inset"
            className="border-0 border-r-0 py-3 pr-0 pl-2 shadow-none"
        >
            <SidebarHeader className="px-3 pt-3 pb-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        {navLocked ? (
                            <SidebarMenuButton
                                size="lg"
                                disabled
                                aria-disabled
                                className="pointer-events-none h-14 opacity-50 hover:bg-transparent"
                            >
                                <AppLogo onBrandRail />
                            </SidebarMenuButton>
                        ) : (
                            <SidebarMenuButton
                                size="lg"
                                asChild
                                className="h-14 hover:bg-transparent data-[active=true]:bg-transparent"
                            >
                                <Link
                                    href={dashboard()}
                                    prefetch
                                    onClick={() => {
                                        if (isMobile) {
                                            setOpenMobile(false);
                                        }
                                    }}
                                >
                                    <AppLogo onBrandRail />
                                </Link>
                            </SidebarMenuButton>
                        )}
                    </SidebarMenuItem>
                </SidebarMenu>

                {!navLocked && (
                    <div className="pt-1">
                        <SidebarSearch navItems={mainNavItems} />
                    </div>
                )}
            </SidebarHeader>

            <SidebarContent className="overflow-visible pr-0 pl-2">
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
