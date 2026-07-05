import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { gsap } from './register-plugins';

const isDev = import.meta.env.DEV;

/** Apply global GSAP and ScrollTrigger defaults. */
export function applyGsapDefaults(): void {
    gsap.defaults({
        ease: 'power3.out',
        duration: 1,
    });

    ScrollTrigger.defaults({
        markers: isDev,
    });
}

export function resolveMarkers(markers?: boolean): boolean {
    return Boolean(markers && isDev);
}
