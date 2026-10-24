import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/** Light tap feedback where the browser supports it (Android Chrome; iOS ignores it). */
export function haptic(duration = 10): void {
    try {
        navigator.vibrate?.(duration);
    } catch {
        // vibration is optional
    }
}

/**
 * Tracks the on-screen keyboard via the visual viewport. Sets `--keyboard-h` on the root
 * and returns whether the keyboard is open, so bars can step aside while typing.
 */
export function useKeyboardInset(): boolean {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        const viewport = window.visualViewport;

        if (!viewport) {
            return;
        }

        const update = () => {
            const inset = Math.max(
                0,
                window.innerHeight - viewport.height - viewport.offsetTop,
            );

            document.documentElement.style.setProperty(
                '--keyboard-h',
                `${Math.round(inset)}px`,
            );
            setOpen(inset > 120);
        };

        update();
        viewport.addEventListener('resize', update);
        viewport.addEventListener('scroll', update);

        return () => {
            viewport.removeEventListener('resize', update);
            viewport.removeEventListener('scroll', update);
            document.documentElement.style.removeProperty('--keyboard-h');
        };
    }, []);

    return open;
}

const PULL_THRESHOLD = 72;

/**
 * Pull down from the top of the page to reload the current Inertia page.
 * Returns the pull distance (0 when idle) and whether a refresh is running.
 */
export function usePullToRefresh(enabled: boolean): {
    distance: number;
    refreshing: boolean;
} {
    const [distance, setDistance] = useState(0);
    const [refreshing, setRefreshing] = useState(false);
    const start = useRef<number | null>(null);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        const insideOverlay = (target: EventTarget | null) =>
            target instanceof Element &&
            target.closest(
                '[role="dialog"], [data-vaul-drawer], [data-slot="drawer-content"], input, textarea, select',
            ) !== null;

        const onStart = (event: TouchEvent) => {
            start.current =
                window.scrollY <= 0 && !insideOverlay(event.target)
                    ? event.touches[0].clientY
                    : null;
        };

        const onMove = (event: TouchEvent) => {
            if (start.current === null || refreshing) {
                return;
            }

            const delta = event.touches[0].clientY - start.current;

            if (delta > 0 && window.scrollY <= 0) {
                setDistance(Math.min(delta * 0.5, PULL_THRESHOLD + 24));
            } else {
                setDistance(0);
            }
        };

        const onEnd = () => {
            if (start.current !== null && distance >= PULL_THRESHOLD) {
                setRefreshing(true);
                haptic(15);
                router.reload({ onFinish: () => setRefreshing(false) });
            }

            start.current = null;
            setDistance(0);
        };

        window.addEventListener('touchstart', onStart, { passive: true });
        window.addEventListener('touchmove', onMove, { passive: true });
        window.addEventListener('touchend', onEnd);

        return () => {
            window.removeEventListener('touchstart', onStart);
            window.removeEventListener('touchmove', onMove);
            window.removeEventListener('touchend', onEnd);
        };
    }, [enabled, distance, refreshing]);

    return { distance, refreshing };
}

/**
 * iOS large-title behaviour: returns the page's first `<h1>` text once it has scrolled
 * under the top bar, so the bar can show it as a compact title.
 */
export function useCollapsedPageTitle(pathname: string): string | null {
    const [title, setTitle] = useState<string | null>(null);

    useEffect(() => {
        let observer: IntersectionObserver | undefined;
        const frame = requestAnimationFrame(() => {
            setTitle(null);

            const heading = document.querySelector<HTMLElement>(
                '.mobile-app main h1',
            );

            if (!heading) {
                return;
            }

            const text = heading.textContent?.trim() ?? '';

            observer = new IntersectionObserver(
                ([entry]) =>
                    setTitle(!entry.isIntersecting && text ? text : null),
                { rootMargin: '-64px 0px 0px 0px' },
            );
            observer.observe(heading);
        });

        return () => {
            cancelAnimationFrame(frame);
            observer?.disconnect();
        };
    }, [pathname]);

    return title;
}
