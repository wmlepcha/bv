import Lenis from 'lenis';

export interface LenisOptions {
    isMobile?: boolean;
    reducedMotion?: boolean;
}

/** Factory for Lenis instances with mobile and accessibility defaults. */
export function createLenis({
    isMobile = false,
    reducedMotion = false,
}: LenisOptions = {}): Lenis | null {
    if (reducedMotion) {
        return null;
    }

    return new Lenis({
        lerp: isMobile ? 0.08 : 0.1,
        smoothWheel: true,
        syncTouch: false,
        touchMultiplier: isMobile ? 1 : 1.5,
        infinite: false,
    });
}
