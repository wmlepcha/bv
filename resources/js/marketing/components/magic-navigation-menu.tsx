import { Link, usePage } from '@inertiajs/react';
import {
    getMarketingNavActiveIndex,
} from '@marketing/components/nav-links';
import { resolveNavIcon } from '@marketing/lib/nav-icons';
import { cn } from '@/lib/utils';

const ITEM_SIZE = 70;

/** Animated bottom navigation bar for mobile marketing pages. */
export function MagicNavigationMenu() {
    const page = usePage();
    const { siteLayout } = page.props;
    const url = page.url;
    const navigation = siteLayout.header.navigation;
    const activeIndex = getMarketingNavActiveIndex(url, navigation);

    return (
        <nav
            className="magic-nav fixed inset-x-0 bottom-0 z-50 pb-[env(safe-area-inset-bottom)] lg:hidden"
            aria-label="Mobile navigation"
        >
            <div className="magic-nav__bar mx-auto flex max-w-[400px] justify-center rounded-t-2xl border-t border-border/60 bg-background/95 shadow-[0_-4px_24px_rgba(0,0,0,0.08)] backdrop-blur-xl">
                <div
                    className="magic-nav__inner relative flex h-[70px]"
                    style={{ width: ITEM_SIZE * navigation.length }}
                >
                    <div
                        className="indicator"
                        style={{
                            transform: `translateX(calc(${activeIndex} * ${ITEM_SIZE}px))`,
                        }}
                        aria-hidden
                    />
                    <ul className="relative z-[1] flex">
                        {navigation.map((link, index) => {
                            const Icon = resolveNavIcon(link.icon);
                            const isActive = index === activeIndex;

                            return (
                                <li
                                    key={link.href}
                                    className={cn('list', isActive && 'active')}
                                >
                                    <Link
                                        href={link.href}
                                        className="flex h-[70px] w-[70px] items-center justify-center"
                                        aria-label={link.label}
                                        aria-current={isActive ? 'page' : undefined}
                                    >
                                        <span className="icon">
                                            <Icon
                                                className={cn(
                                                    'size-[22px]',
                                                    isActive
                                                        ? 'text-brand-warm'
                                                        : 'text-muted-foreground',
                                                )}
                                                strokeWidth={isActive ? 2.25 : 1.75}
                                            />
                                        </span>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </div>
        </nav>
    );
}
