import type Lenis from 'lenis';
import { gsap, ScrollTrigger } from '@marketing/animations/gsap/register-plugins';

let syncedLenis: Lenis | null = null;
let tickerCallback: ((time: number) => void) | null = null;
let scrollUpdateHandler: (() => void) | null = null;
let refreshHandler: (() => void) | null = null;

/** Sync Lenis scroll position with GSAP ScrollTrigger. */
export function syncLenisWithScrollTrigger(lenis: Lenis): void {
    syncedLenis = lenis;

    scrollUpdateHandler = () => ScrollTrigger.update();
    lenis.on('scroll', scrollUpdateHandler);

    tickerCallback = (time: number) => {
        lenis.raf(time * 1000);
    };
    gsap.ticker.add(tickerCallback);
    gsap.ticker.lagSmoothing(0);

    ScrollTrigger.scrollerProxy(document.documentElement, {
        scrollTop(value) {
            if (arguments.length && value !== undefined) {
                lenis.scrollTo(value, { immediate: true });
            }

            return lenis.scroll;
        },
        getBoundingClientRect() {
            return {
                top: 0,
                left: 0,
                width: window.innerWidth,
                height: window.innerHeight,
            };
        },
    });

    refreshHandler = () => {
        lenis.resize();
    };
    ScrollTrigger.addEventListener('refresh', refreshHandler);

    ScrollTrigger.refresh();
}

/** Tear down Lenis ↔ ScrollTrigger sync. */
export function unsyncLenisFromScrollTrigger(lenis: Lenis): void {
    if (syncedLenis === lenis) {
        syncedLenis = null;
    }

    if (scrollUpdateHandler) {
        lenis.off('scroll', scrollUpdateHandler);
        scrollUpdateHandler = null;
    }

    if (tickerCallback) {
        gsap.ticker.remove(tickerCallback);
        tickerCallback = null;
    }

    if (refreshHandler) {
        ScrollTrigger.removeEventListener('refresh', refreshHandler);
        refreshHandler = null;
    }

    ScrollTrigger.scrollerProxy(document.documentElement, {});
    ScrollTrigger.clearScrollMemory();
}

export function getSyncedLenis(): Lenis | null {
    return syncedLenis;
}
