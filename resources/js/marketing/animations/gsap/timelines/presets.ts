import type { ScrollAnimationProps } from '@marketing/animations/types/animation-props';
import { gsap } from '@marketing/animations/gsap/register-plugins';
import { resolveScrollVars } from '@marketing/utils/animation-helpers';

export interface PresetOptions extends ScrollAnimationProps {
    y?: number;
    x?: number;
    scale?: number;
    rotation?: number;
    from?: gsap.TweenVars;
    to?: gsap.TweenVars;
}

function baseTween(
    target: gsap.TweenTarget,
    from: gsap.TweenVars,
    to: gsap.TweenVars,
    options: PresetOptions,
) {
    const { duration = 1, delay = 0, ease = 'power3.out' } = options;
    const scrollTrigger = resolveScrollVars(options);

    return gsap.fromTo(target, from, {
        ...to,
        duration,
        delay,
        ease,
        scrollTrigger: {
            trigger: target as Element,
            ...scrollTrigger,
        },
    });
}

export const presets = {
    fadeIn(target: gsap.TweenTarget, options: PresetOptions = {}) {
        return baseTween(target, { opacity: 0 }, { opacity: 1 }, options);
    },

    fadeUp(target: gsap.TweenTarget, options: PresetOptions = {}) {
        const y = options.y ?? 60;
        return baseTween(
            target,
            { opacity: 0, y },
            { opacity: 1, y: 0 },
            options,
        );
    },

    scaleIn(target: gsap.TweenTarget, options: PresetOptions = {}) {
        const scale = options.scale ?? 0.85;
        return baseTween(
            target,
            { opacity: 0, scale },
            { opacity: 1, scale: 1 },
            options,
        );
    },

    rotateIn(target: gsap.TweenTarget, options: PresetOptions = {}) {
        const rotation = options.rotation ?? 12;
        return baseTween(
            target,
            { opacity: 0, rotation },
            { opacity: 1, rotation: 0 },
            options,
        );
    },

    staggerChildren(
        targets: gsap.TweenTarget,
        options: PresetOptions & { stagger?: number; trigger?: Element } = {},
    ) {
        const stagger = options.stagger ?? 0.12;
        const { duration = 0.8, delay = 0, ease = 'power3.out' } = options;
        const scrollTrigger = resolveScrollVars(options);
        const trigger =
            options.trigger ??
            (targets instanceof Element
                ? targets
                : (targets as Element[])[0]?.parentElement);

        return gsap.fromTo(
            targets,
            { opacity: 0, y: options.y ?? 40 },
            {
                opacity: 1,
                y: 0,
                duration,
                delay,
                ease,
                stagger,
                scrollTrigger: {
                    trigger: trigger ?? undefined,
                    ...scrollTrigger,
                },
            },
        );
    },

    countUp(
        target: HTMLElement,
        endValue: number,
        options: PresetOptions & { suffix?: string; prefix?: string } = {},
    ) {
        const obj = { value: 0 };
        const { duration = 2, delay = 0, ease = 'power2.out', suffix = '', prefix = '' } = options;
        const scrollTrigger = resolveScrollVars(options);

        return gsap.to(obj, {
            value: endValue,
            duration,
            delay,
            ease,
            scrollTrigger: {
                trigger: target,
                ...scrollTrigger,
            },
            onUpdate: () => {
                target.textContent = `${prefix}${Math.round(obj.value)}${suffix}`;
            },
        });
    },

    parallax(
        target: gsap.TweenTarget,
        speed = 0.5,
        options: PresetOptions = {},
    ) {
        const yPercent = (speed - 1) * 100;
        const scrollTrigger = resolveScrollVars({
            ...options,
            scrub: options.scrub ?? true,
            start: options.start ?? 'top bottom',
            end: options.end ?? 'bottom top',
            once: false,
        });

        return gsap.to(target, {
            yPercent,
            ease: 'none',
            scrollTrigger: {
                trigger: target as Element,
                ...scrollTrigger,
            },
        });
    },
};
