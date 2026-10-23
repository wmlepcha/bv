import { Link } from '@inertiajs/react';
import { Ellipsis } from 'lucide-react';
import { useState } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/responsive-sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

const MAX_TABS = 5;

function Tab({
    active,
    label,
    badge,
    children,
}: {
    active: boolean;
    label: string;
    badge?: string | number;
    children: React.ReactNode;
}) {
    return (
        <span
            className={cn(
                'flex min-w-0 flex-1 flex-col items-center gap-0.5 py-1.5 text-[10px] font-medium transition-transform active:scale-90',
                active ? 'text-primary' : 'text-muted-foreground',
            )}
        >
            <span
                className={cn(
                    'relative flex h-7 w-14 items-center justify-center rounded-full transition-colors',
                    active && 'bg-primary/15',
                )}
            >
                {children}
                {badge ? (
                    <span className="absolute top-0 right-2 flex min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4 font-bold text-white">
                        {badge}
                    </span>
                ) : null}
            </span>
            <span className="max-w-full truncate">{label}</span>
        </span>
    );
}

export function MobileBottomNav({ items }: { items: NavItem[] }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const [moreOpen, setMoreOpen] = useState(false);

    const needsMore = items.length > MAX_TABS;
    const tabs = needsMore ? items.slice(0, MAX_TABS - 1) : items;
    const overflow = needsMore ? items.slice(MAX_TABS - 1) : [];
    const overflowActive = overflow.some((item) =>
        isCurrentOrParentUrl(item.href),
    );
    const overflowBadge = overflow.reduce(
        (sum, item) => sum + (Number(item.badge) || 0),
        0,
    );

    return (
        <>
            <nav
                aria-label="Main"
                className="fixed inset-x-0 bottom-0 z-40 border-t border-border/60 bg-background/80 backdrop-blur-xl print:hidden"
                style={{ paddingBottom: 'env(safe-area-inset-bottom)' }}
            >
                <div className="mx-auto flex h-16 max-w-md items-stretch px-2">
                    {tabs.map((item) => {
                        const active = isCurrentOrParentUrl(item.href);
                        const Icon = item.icon;

                        return (
                            <Link
                                key={item.title}
                                href={item.href}
                                prefetch
                                aria-current={active ? 'page' : undefined}
                                className="flex min-w-0 flex-1 outline-none"
                            >
                                <Tab
                                    active={active}
                                    label={item.title}
                                    badge={item.badge}
                                >
                                    {Icon && (
                                        <Icon
                                            className={cn(
                                                'size-5',
                                                active && 'stroke-[2.4]',
                                            )}
                                        />
                                    )}
                                </Tab>
                            </Link>
                        );
                    })}
                    {needsMore && (
                        <button
                            type="button"
                            onClick={() => setMoreOpen(true)}
                            className="flex min-w-0 flex-1 outline-none"
                            aria-haspopup="dialog"
                        >
                            <Tab
                                active={overflowActive}
                                label="More"
                                badge={overflowBadge || undefined}
                            >
                                <Ellipsis className="size-5" />
                            </Tab>
                        </button>
                    )}
                </div>
            </nav>

            <Sheet open={moreOpen} onOpenChange={setMoreOpen}>
                <SheetContent
                    side="bottom"
                    className="rounded-t-3xl px-4 pt-3 pb-[calc(1.5rem+env(safe-area-inset-bottom))]"
                >
                    <SheetHeader className="p-0">
                        <SheetTitle>More</SheetTitle>
                        <SheetDescription className="sr-only">
                            Other sections
                        </SheetDescription>
                    </SheetHeader>
                    <ul className="grid gap-1">
                        {overflow.map((item) => {
                            const Icon = item.icon;
                            const active = isCurrentOrParentUrl(item.href);

                            return (
                                <li key={item.title}>
                                    <Link
                                        href={item.href}
                                        prefetch
                                        onClick={() => setMoreOpen(false)}
                                        className={cn(
                                            'flex items-center gap-3 rounded-2xl px-3 py-3.5 text-base font-medium transition-colors active:bg-muted',
                                            active &&
                                                'bg-primary/10 text-primary',
                                        )}
                                    >
                                        {Icon && <Icon className="size-5" />}
                                        <span className="flex-1">
                                            {item.title}
                                        </span>
                                        {item.badge ? (
                                            <span className="rounded-full bg-destructive px-2 text-xs font-bold text-white">
                                                {item.badge}
                                            </span>
                                        ) : null}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </SheetContent>
            </Sheet>
        </>
    );
}
