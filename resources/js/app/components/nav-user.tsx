import { usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { UserInfo } from '@app/components/user-info';
import { UserMenuContent } from '@app/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

export function NavUser() {
    const { auth } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    if (!auth.user) {
        return null;
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className={cn(
                                'nav-rail-footer-pill group',
                                'text-nav-rail-foreground hover:text-nav-rail-foreground',
                                'data-[state=open]:text-nav-rail-foreground',
                                '[&_span]:text-nav-rail-foreground',
                            )}
                            data-test="sidebar-menu-button"
                        >
                            <UserInfo user={auth.user} />
                            <ChevronsUpDown className="ml-auto size-4 opacity-80" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'left'
                                  : 'bottom'
                        }
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
