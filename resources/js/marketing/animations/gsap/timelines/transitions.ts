import { gsap } from '@marketing/animations/gsap/register-plugins';

export type TransitionType = 'fade' | 'slide' | 'scale' | 'clip';

export interface TransitionOptions {
    duration?: number;
    ease?: string;
}

export function resetPageTransition(element: HTMLElement): void {
    gsap.set(element, {
        opacity: 1,
        xPercent: 0,
        scale: 1,
        clipPath: 'inset(0 0 0 0)',
    });
}

export function createExitTransition(
    element: HTMLElement,
    type: TransitionType = 'fade',
    options: TransitionOptions = {},
): gsap.core.Tween {
    const { duration = 0.5, ease = 'power2.inOut' } = options;

    const variants: Record<TransitionType, gsap.TweenVars> = {
        fade: { opacity: 0 },
        slide: { xPercent: -5, opacity: 0 },
        scale: { scale: 0.95, opacity: 0 },
        clip: { clipPath: 'inset(0 0 100% 0)' },
    };

    return gsap.to(element, {
        ...variants[type],
        duration,
        ease,
    });
}

export function createEnterTransition(
    element: HTMLElement,
    type: TransitionType = 'fade',
    options: TransitionOptions = {},
): gsap.core.Tween {
    const { duration = 0.6, ease = 'power2.out' } = options;

    const fromVars: Record<TransitionType, gsap.TweenVars> = {
        fade: { opacity: 0 },
        slide: { xPercent: 5, opacity: 0 },
        scale: { scale: 1.05, opacity: 0 },
        clip: { clipPath: 'inset(100% 0 0 0)' },
    };

    const toVars: Record<TransitionType, gsap.TweenVars> = {
        fade: { opacity: 1 },
        slide: { xPercent: 0, opacity: 1 },
        scale: { scale: 1, opacity: 1 },
        clip: { clipPath: 'inset(0 0 0 0)' },
    };

    return gsap.fromTo(element, fromVars[type], {
        ...toVars[type],
        duration,
        ease,
    });
}
