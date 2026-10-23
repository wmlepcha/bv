import { forwardRef, useRef } from 'react';
import { cn } from '@/lib/utils';
import { gsap } from '@marketing/animations/gsap/register-plugins';
import type { AnimationComponentProps } from '@marketing/animations/types/animation-props';
import { useScrollAnimation } from '@marketing/hooks/use-gsap-context';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';
import { resolveScrollVars, mergeRefs } from '@marketing/utils/animation-helpers';

interface ProgressLineProps extends AnimationComponentProps {
    orientation?: 'horizontal' | 'vertical';
}

export const ProgressLine = forwardRef<HTMLDivElement, ProgressLineProps>(
    ({ className, orientation = 'horizontal', disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const lineRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (!lineRef.current || !containerRef.current) {
                    return;
                }

                const prop = orientation === 'horizontal' ? 'scaleX' : 'scaleY';

                gsap.fromTo(
                    lineRef.current,
                    { [prop]: 0 },
                    {
                        [prop]: 1,
                        ease: 'none',
                        scrollTrigger: {
                            trigger: containerRef.current,
                            scrub: anim.scrub ?? true,
                            start: anim.start ?? 'top center',
                            end: anim.end ?? 'bottom center',
                            ...resolveScrollVars({ ...anim, once: false }),
                        },
                    },
                );
            },
            [orientation, anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={cn('relative', className)}>
                <div
                    ref={lineRef}
                    className={cn(
                        'bg-gradient-to-r from-brand-warm to-brand-cool-light',
                        orientation === 'horizontal'
                            ? 'h-0.5 w-full origin-left'
                            : 'h-full w-0.5 origin-top',
                    )}
                />
            </div>
        );
    },
);
ProgressLine.displayName = 'ProgressLine';

interface ScrollProgressIndicatorProps {
    className?: string;
    position?: 'top' | 'bottom';
}

export const ScrollProgressIndicator = forwardRef<
    HTMLDivElement,
    ScrollProgressIndicatorProps
>(({ className, position = 'top' }, ref) => {
    const barRef = useRef<HTMLDivElement>(null);
    const reduced = useReducedMotion();

    useScrollAnimation(
        barRef,
        () => {
            if (!barRef.current || reduced) {
                return;
            }

            gsap.fromTo(
                barRef.current,
                { scaleX: 0 },
                {
                    scaleX: 1,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: document.documentElement,
                        start: 'top top',
                        end: 'bottom bottom',
                        scrub: 0,
                    },
                },
            );
        },
        [reduced],
        reduced,
    );

    return (
        <div
            ref={mergeRefs(ref, barRef)}
            className={cn(
                'fixed left-0 z-[100] h-0.5 w-full origin-left bg-gradient-to-r from-brand-warm to-brand-cool-light',
                position === 'top' ? 'top-0' : 'bottom-0',
                className,
            )}
        />
    );
});
ScrollProgressIndicator.displayName = 'ScrollProgressIndicator';

interface ScrollScrubProps extends AnimationComponentProps {
    children: React.ReactNode;
    from?: gsap.TweenVars;
    to?: gsap.TweenVars;
}

export const ScrollScrub = forwardRef<HTMLDivElement, ScrollScrubProps>(
    ({ children, className, from = { opacity: 0.3 }, to = { opacity: 1 }, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (!containerRef.current) {
                    return;
                }

                gsap.fromTo(containerRef.current, from, {
                    ...to,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: containerRef.current,
                        scrub: anim.scrub ?? 1,
                        ...resolveScrollVars({ ...anim, once: false }),
                    },
                });
            },
            [from, to, anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={className}>
                {children}
            </div>
        );
    },
);
ScrollScrub.displayName = 'ScrollScrub';
