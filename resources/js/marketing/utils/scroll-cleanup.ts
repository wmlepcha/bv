import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { getSyncedLenis } from '@marketing/animations/lenis/sync-scroll-trigger';

export function refreshScrollAnimations(): void {
    requestAnimationFrame(() => {
        ScrollTrigger.refresh();
    });
}

export function resetScrollPosition(immediate = true): void {
    const lenis = getSyncedLenis();

    if (lenis) {
        lenis.scrollTo(0, { immediate });

        return;
    }

    window.scrollTo({ top: 0, behavior: immediate ? 'auto' : 'smooth' });
}
