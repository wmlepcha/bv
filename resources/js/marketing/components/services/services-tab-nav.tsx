import { useEffect, useRef, useState } from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import { FadeUp } from '@marketing/components/animations/primitives';
import { useLenis } from '@marketing/providers/lenis-provider';
import type { ServiceCategory } from '@marketing/types/services-page';

interface ServicesTabNavProps {
    categories: ServiceCategory[];
    activeId: string;
    onChange: (id: string) => void;
    onPinChange?: (pinned: boolean, navHeight: number) => void;
}

function readHeaderBottom(): number {
    if (typeof document === 'undefined') {
        return 80;
    }

    const header = document.querySelector('header');

    if (!header) {
        return 80;
    }

    return header.getBoundingClientRect().bottom;
}

export function ServicesTabNav({ categories, activeId, onChange, onPinChange }: ServicesTabNavProps) {
    const sentinelRef = useRef<HTMLDivElement>(null);
    const navRef = useRef<HTMLElement>(null);
    const [isPinned, setIsPinned] = useState(false);
    const [navHeight, setNavHeight] = useState(0);
    const [headerBottom, setHeaderBottom] = useState(readHeaderBottom);
    const isMobile = useIsMobile();
    const { lenis } = useLenis();

    useEffect(() => {
        const header = document.querySelector('header');

        if (!header) {
            return;
        }

        const updateHeaderBottom = () => {
            setHeaderBottom(header.getBoundingClientRect().bottom);
        };

        updateHeaderBottom();

        const observer = new ResizeObserver(updateHeaderBottom);
        observer.observe(header);

        window.addEventListener('scroll', updateHeaderBottom, { passive: true });
        lenis?.on('scroll', updateHeaderBottom);

        return () => {
            observer.disconnect();
            window.removeEventListener('scroll', updateHeaderBottom);
            lenis?.off('scroll', updateHeaderBottom);
        };
    }, [lenis]);

    useEffect(() => {
        const sentinel = sentinelRef.current;

        if (!sentinel) {
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => setIsPinned(!entry.isIntersecting),
            {
                root: null,
                threshold: 0,
                rootMargin: `-${headerBottom}px 0px 0px 0px`,
            },
        );

        observer.observe(sentinel);

        return () => observer.disconnect();
    }, [headerBottom]);

    useEffect(() => {
        const nav = navRef.current;

        if (!nav) {
            return;
        }

        const updateHeight = () => {
            const height = nav.offsetHeight;
            setNavHeight(height);
            onPinChange?.(isPinned, height);
        };

        updateHeight();

        const observer = new ResizeObserver(updateHeight);
        observer.observe(nav);

        return () => observer.disconnect();
    }, [isPinned, onPinChange]);

    useEffect(() => {
        onPinChange?.(isPinned, navHeight);
    }, [isPinned, navHeight, onPinChange]);

    return (
        <>
            <div ref={sentinelRef} className="h-px" aria-hidden />

            <nav
                ref={navRef}
                aria-label="Services sections"
                style={isPinned ? { top: `${headerBottom}px` } : undefined}
                className={cn(
                    'z-40 px-4 sm:px-6',
                    isPinned
                        ? 'fixed right-0 left-0 border-b border-border bg-background/90 py-3 shadow-lg shadow-black/10 backdrop-blur-md'
                        : 'relative py-4',
                )}
            >
                <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                    <FadeUp disabled={isMobile}>
                        <div
                            className={cn(
                                'inline-flex flex-wrap gap-2 rounded-full border marketing-hairline',
                                'bg-background/80 p-1.5 shadow-lg shadow-black/20 backdrop-blur-md',
                                isPinned && 'shadow-none',
                            )}
                            role="tablist"
                        >
                            {categories.map((category) => {
                                const isActive = category.id === activeId;

                                return (
                                    <button
                                        key={category.id}
                                        type="button"
                                        role="tab"
                                        aria-selected={isActive}
                                        aria-controls={`services-panel-${category.id}`}
                                        id={`services-tab-${category.id}`}
                                        onClick={() => onChange(category.id)}
                                        className={cn(
                                            'rounded-full px-4 py-2 text-sm font-medium transition-all duration-300',
                                            isActive
                                                ? 'bg-primary/15 text-primary shadow-sm'
                                                : 'text-muted-foreground hover:bg-foreground/[0.05] hover:text-foreground',
                                        )}
                                    >
                                        {category.title}
                                    </button>
                                );
                            })}
                        </div>
                    </FadeUp>
                </div>
            </nav>

            {isPinned && <div style={{ height: navHeight }} aria-hidden />}
        </>
    );
}
