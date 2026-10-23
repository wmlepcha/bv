import type { ScrollTrigger } from 'gsap/ScrollTrigger';
import type { MutableRefObject, Ref } from 'react';
import { resolveMarkers } from '@marketing/animations/gsap/defaults';
import type { ScrollAnimationProps } from '@marketing/animations/types/animation-props';

export function mergeBreakpoints(
    base: ScrollAnimationProps,
    breakpoint?: string,
): ScrollAnimationProps {
    if (!breakpoint || !base.breakpoints?.[breakpoint]) {
        return base;
    }

    return {
        ...base,
        ...base.breakpoints[breakpoint],
    };
}

export function resolveScrollVars(
    props: ScrollAnimationProps,
): ScrollTrigger.Vars {
    const {
        start = 'top 85%',
        end,
        scrub,
        pin,
        snap,
        toggleActions = 'play none none none',
        once = false,
        markers,
    } = props;

    return {
        start,
        ...(end ? { end } : {}),
        ...(scrub !== undefined ? { scrub } : {}),
        ...(pin !== undefined ? { pin } : {}),
        ...(snap !== undefined ? { snap } : {}),
        toggleActions: once ? toggleActions : 'play none none reverse',
        once,
        markers: resolveMarkers(markers),
    };
}

export function mergeRefs<T>(
    ...refs: Array<Ref<T> | undefined>
): (instance: T | null) => void {
    return (instance) => {
        for (const ref of refs) {
            if (!ref) {
                continue;
            }

            if (typeof ref === 'function') {
                ref(instance);
            } else {
                (ref as MutableRefObject<T | null>).current = instance;
            }
        }
    };
}
