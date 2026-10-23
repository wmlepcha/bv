import { Link, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import type { CSSProperties } from 'react';
import { cn } from '@/lib/utils';
import { getMarketingNavActiveIndex } from '@marketing/components/nav-links';
import { resolveNavIcon } from '@marketing/lib/nav-icons';

const MAX_ITEM_SIZE = 84;
const MIN_ITEM_SIZE = 56;
const BASE_BAR_HEIGHT = 64;
const BASE_DISC_SIZE = 60;
/** Empty ring (page showing through) between disc and orange bar. */
const BASE_GAP = 8;
const BASE_ICON_SIZE = 22;

/** Animated bottom navigation bar for mobile marketing pages. */
export function MagicNavigationMenu() {
    const page = usePage();
    const maskId = useId().replace(/:/g, '');
    const { siteLayout } = page.props;
    const url = page.url;
    /** Login/account lives next to the logo on mobile, not in this bar. */
    const navigation = siteLayout.header.navigation.filter(
        (link) => link.href !== '/login',
    );
    const activeIndex = getMarketingNavActiveIndex(url, navigation);

    const navRef = useRef<HTMLElement>(null);
    const [availableWidth, setAvailableWidth] = useState<number | null>(null);

    useEffect(() => {
        const node = navRef.current;

        if (!node) {
            return;
        }

        const updateWidth = () => setAvailableWidth(node.clientWidth);

        updateWidth();

        const observer = new ResizeObserver(updateWidth);
        observer.observe(node);

        return () => observer.disconnect();
    }, []);

    const count = Math.max(navigation.length, 1);
    /** Shrink items uniformly so the bar never overflows the viewport. */
    const fittedItemSize = availableWidth
        ? availableWidth / count
        : MAX_ITEM_SIZE;
    const itemSize = Math.min(
        MAX_ITEM_SIZE,
        Math.max(MIN_ITEM_SIZE, fittedItemSize),
    );
    const scale = itemSize / MAX_ITEM_SIZE;

    const barHeight = BASE_BAR_HEIGHT * scale;
    const barRadius = barHeight / 2;
    const discSize = BASE_DISC_SIZE * scale;
    const gap = BASE_GAP * scale;
    const holeRadius = discSize / 2 + gap;
    const discLeft = (itemSize - discSize) / 2;
    const iconSize = Math.round(BASE_ICON_SIZE * scale);
    const barWidth = itemSize * count;
    const slideX = activeIndex * itemSize;

    return (
        <nav
            ref={navRef}
            className="magic-nav fixed inset-x-0 bottom-0 z-50 px-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:px-5 lg:hidden"
            aria-label="Mobile navigation"
            style={
                { '--magic-nav-lift': `${-(discSize / 2)}px` } as CSSProperties
            }
        >
            <div
                className="magic-nav__bar mx-auto"
                style={{ width: barWidth, height: barHeight }}
            >
                {/*
                  Pill + circular hole punched from the top edge.
                  Hole radius = disc + gap → consistent air gap like the reference.
                */}
                <svg
                    className="magic-nav__shape"
                    width={barWidth}
                    height={barHeight}
                    viewBox={`0 0 ${barWidth} ${barHeight}`}
                    aria-hidden
                >
                    <defs>
                        <mask
                            id={maskId}
                            maskUnits="userSpaceOnUse"
                            x={0}
                            y={0}
                            width={barWidth}
                            height={barHeight}
                        >
                            <rect
                                width={barWidth}
                                height={barHeight}
                                rx={barRadius}
                                fill="#fff"
                            />
                            <circle
                                className="magic-nav__hole"
                                cx={itemSize / 2}
                                cy={0}
                                r={holeRadius}
                                fill="#000"
                                style={{
                                    transform: `translateX(${slideX}px)`,
                                }}
                            />
                        </mask>
                    </defs>
                    <rect
                        width={barWidth}
                        height={barHeight}
                        rx={barRadius}
                        className="magic-nav__shape-path"
                        mask={`url(#${maskId})`}
                    />
                </svg>

                <div
                    className="indicator"
                    style={{
                        left: discLeft,
                        width: discSize,
                        height: discSize,
                        top: -(discSize / 2),
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
                                style={{ width: itemSize }}
                            >
                                <Link
                                    href={link.href}
                                    className="flex h-full items-center justify-center"
                                    style={{ width: itemSize }}
                                    aria-label={link.label}
                                    aria-current={isActive ? 'page' : undefined}
                                >
                                    <span className="icon">
                                        <Icon
                                            style={{
                                                width: iconSize,
                                                height: iconSize,
                                            }}
                                            className={cn(
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
