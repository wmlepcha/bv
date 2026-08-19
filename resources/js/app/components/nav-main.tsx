import { Link } from '@inertiajs/react';
import {
    useCallback,
    useLayoutEffect,
    useRef,
    useState,
} from 'react';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

type IndicatorState = {
    top: number;
    height: number;
    visible: boolean;
};

const itemButtonClass = (active: boolean, locked = false) =>
    cn(
        'nav-rail-item',
        // Beat shadcn SidebarMenuButton accent utilities
        'bg-transparent! shadow-none!',
        'hover:bg-foreground/5! hover:text-nav-rail-foreground!',
        'data-[active=true]:bg-transparent! data-[active=true]:text-nav-active-fg!',
        'data-[active=true]:hover:bg-transparent! data-[active=true]:hover:text-nav-active-fg!',
        'data-[active=true]:font-semibold',
        active && 'nav-rail-item-active text-nav-active-fg!',
        locked && 'pointer-events-none opacity-50',
    );

export function NavMain({
    items = [],
    locked = false,
}: {
    items: NavItem[];
    locked?: boolean;
}) {
    const { isCurrentOrParentUrl, currentUrl } = useCurrentUrl();
    const listRef = useRef<HTMLDivElement>(null);
    const itemRefs = useRef(new Map<string, HTMLElement>());
    const [indicator, setIndicator] = useState<IndicatorState>({
        top: 0,
        height: 0,
        visible: false,
    });

    const activeItem = items.find((item) => isCurrentOrParentUrl(item.href));

    const updateIndicator = useCallback(() => {
        const list = listRef.current;

        if (!list || !activeItem) {
            setIndicator((prev) => ({ ...prev, visible: false }));
            return;
        }

        const el = itemRefs.current.get(activeItem.title);

        if (!el) {
            setIndicator((prev) => ({ ...prev, visible: false }));
            return;
        }

        const listRect = list.getBoundingClientRect();
        const elRect = el.getBoundingClientRect();

        setIndicator({
            top: elRect.top - listRect.top + list.scrollTop,
            height: elRect.height,
            visible: true,
        });
    }, [activeItem]);

    useLayoutEffect(() => {
        updateIndicator();

        const list = listRef.current;
        if (!list) {
            return;
        }

        const observer = new ResizeObserver(() => updateIndicator());
        observer.observe(list);
        itemRefs.current.forEach((node) => observer.observe(node));

        window.addEventListener('resize', updateIndicator);

        // Re-measure after fonts/layout settle
        const frame = window.requestAnimationFrame(() => updateIndicator());

        return () => {
            observer.disconnect();
            window.removeEventListener('resize', updateIndicator);
            window.cancelAnimationFrame(frame);
        };
    }, [updateIndicator, currentUrl, items]);

    return (
        <SidebarGroup className="p-0">
                    <div ref={listRef} className="nav-rail-track relative">
                <div
                    className={cn(
                        'nav-rail-indicator',
                        indicator.visible && 'nav-rail-indicator-visible',
                    )}
                    style={{
                        transform: `translate3d(0, ${indicator.top}px, 0)`,
                        height: Math.max(indicator.height, 0),
                    }}
                    aria-hidden
                />
                <SidebarMenu className="nav-rail-menu relative z-10 gap-1.5 px-2">
                    {items.map((item) => {
                        const active = isCurrentOrParentUrl(item.href);

                        return (
                            <SidebarMenuItem
                                key={item.title}
                                ref={(node) => {
                                    if (node) {
                                        itemRefs.current.set(item.title, node);
                                    } else {
                                        itemRefs.current.delete(item.title);
                                    }
                                }}
                            >
                                {locked ? (
                                    <SidebarMenuButton
                                        disabled
                                        aria-disabled
                                        isActive={active}
                                        tooltip={{
                                            children:
                                                'Unlock your dashboard to continue',
                                        }}
                                        className={itemButtonClass(active, true)}
                                    >
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </SidebarMenuButton>
                                ) : (
                                    <SidebarMenuButton
                                        asChild
                                        isActive={active}
                                        tooltip={{ children: item.title }}
                                        className={itemButtonClass(active)}
                                    >
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                )}
                            </SidebarMenuItem>
                        );
                    })}
                </SidebarMenu>
            </div>
        </SidebarGroup>
    );
}
