import { Link, usePage } from '@inertiajs/react';
import { useId } from 'react';
import {
    getMarketingNavActiveIndex,
} from '@marketing/components/nav-links';
import { resolveNavIcon } from '@marketing/lib/nav-icons';
import { cn } from '@/lib/utils';

const ITEM_SIZE = 84;
const BAR_HEIGHT = 64;
const BAR_RADIUS = 32;
/** Floating active disc diameter. */
const DISC_SIZE = 60;
/** Empty ring (page showing through) between disc and orange bar. */
const GAP = 8;
const HOLE_RADIUS = DISC_SIZE / 2 + GAP;
const DISC_LEFT = (ITEM_SIZE - DISC_SIZE) / 2;

/** Animated bottom navigation bar for mobile marketing pages. */
export function MagicNavigationMenu() {
    const page = usePage();
    const maskId = useId().replace(/:/g, '');
    const { siteLayout } = page.props;
    const url = page.url;
    const navigation = siteLayout.header.navigation;
    const activeIndex = getMarketingNavActiveIndex(url, navigation);
    const barWidth = ITEM_SIZE * Math.max(navigation.length, 1);
    const slideX = activeIndex * ITEM_SIZE;

    return (
        <nav
            className="magic-nav fixed inset-x-0 bottom-0 z-50 px-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:px-5 lg:hidden"
            aria-label="Mobile navigation"
        >
            <div
                className="magic-nav__bar mx-auto"
                style={{ width: barWidth, height: BAR_HEIGHT }}
            >
                {/*
                  Pill + circular hole punched from the top edge.
                  Hole radius = disc + gap → consistent air gap like the reference.
                */}
                <svg
                    className="magic-nav__shape"
                    width={barWidth}
                    height={BAR_HEIGHT}
                    viewBox={`0 0 ${barWidth} ${BAR_HEIGHT}`}
                    aria-hidden
                >
                    <defs>
                        <mask
                            id={maskId}
                            maskUnits="userSpaceOnUse"
                            x={0}
                            y={0}
                            width={barWidth}
                            height={BAR_HEIGHT}
                        >
                            <rect
                                width={barWidth}
                                height={BAR_HEIGHT}
                                rx={BAR_RADIUS}
                                fill="#fff"
                            />
                            <circle
                                className="magic-nav__hole"
                                cx={ITEM_SIZE / 2}
                                cy={0}
                                r={HOLE_RADIUS}
                                fill="#000"
                                style={{
                                    transform: `translateX(${slideX}px)`,
                                }}
                            />
                        </mask>
                    </defs>
                    <rect
                        width={barWidth}
                        height={BAR_HEIGHT}
                        rx={BAR_RADIUS}
                        className="magic-nav__shape-path"
                        mask={`url(#${maskId})`}
                    />
                </svg>

                <div
                    className="indicator"
                    style={{
                        left: DISC_LEFT,
                        transform: `translateX(${slideX}px)`,
                    }}
                    aria-hidden
                />

                <ul className="magic-nav__items relative z-[1] flex h-full">
                    {navigation.map((link, index) => {
                        const Icon = resolveNavIcon(link.icon);
                        const isActive = index === activeIndex;

                        return (
                            <li
                                key={link.href}
                                className={cn('list', isActive && 'active')}
                                style={{ width: ITEM_SIZE }}
                            >
                                <Link
                                    href={link.href}
                                    className="flex h-full items-center justify-center"
                                    style={{ width: ITEM_SIZE }}
                                    aria-label={link.label}
                                    aria-current={isActive ? 'page' : undefined}
                                >
                                    <span className="icon">
                                        <Icon
                                            className={cn(
                                                'size-[22px]',
                                                isActive
                                                    ? 'text-[#031835]'
                                                    : 'text-[#fff8e7]/85',
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
        </nav>
    );
}
