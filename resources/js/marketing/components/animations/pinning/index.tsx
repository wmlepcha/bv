import { forwardRef, useRef } from 'react';
import { gsap } from '@marketing/animations/gsap/register-plugins';
import type { AnimationComponentProps } from '@marketing/animations/types/animation-props';
import { useScrollAnimation } from '@marketing/hooks/use-gsap-context';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';
import { useScrollEffectsEnabled } from '@marketing/hooks/use-scroll-effects-enabled';
import { resolveScrollVars, mergeRefs } from '@marketing/utils/animation-helpers';
import { cn } from '@/lib/utils';

interface PinProps extends AnimationComponentProps {
    pinSpacing?: boolean;
    end?: string;
}

export const SectionPin = forwardRef<HTMLDivElement, PinProps>(
    ({ children, className, pinSpacing = true, disabled, end, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();
        const scrollEffects = useScrollEffectsEnabled();

        useScrollAnimation(
            containerRef,
            () => {
                if (!containerRef.current) {
                    return;
                }

                gsap.timeline({
                    scrollTrigger: {
                        trigger: containerRef.current,
                        pin: true,
                        pinSpacing,
                        end: end ?? '+=100%',
                        ...resolveScrollVars(anim),
                    },
                });
            },
            [anim, end, pinSpacing, reduced, scrollEffects],
            disabled || reduced || !scrollEffects,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={className}>
                {children}
            </div>
        );
    },
);
SectionPin.displayName = 'SectionPin';

interface ImagePinProps extends AnimationComponentProps {
    image: React.ReactNode;
    content: React.ReactNode;
    end?: string;
}

export const ImagePin = forwardRef<HTMLDivElement, ImagePinProps>(
    ({ image, content, className, end, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const imageRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();
        const scrollEffects = useScrollEffectsEnabled();

        useScrollAnimation(
            containerRef,
            () => {
                if (!containerRef.current || !imageRef.current) {
                    return;
                }

                gsap.timeline({
                    scrollTrigger: {
                        trigger: containerRef.current,
                        pin: imageRef.current,
                        pinSpacing: true,
                        end: end ?? '+=150%',
                        ...resolveScrollVars(anim),
                    },
                });
            },
            [anim, end, reduced, scrollEffects],
            disabled || reduced || !scrollEffects,
        );

        return (
            <div
                ref={mergeRefs(ref, containerRef)}
                className={cn('grid gap-8 lg:grid-cols-2', className)}
            >
                <div ref={imageRef}>{image}</div>
                <div>{content}</div>
            </div>
        );
    },
);
ImagePin.displayName = 'ImagePin';

interface StickyStep {
    id: string;
    content: React.ReactNode;
}

interface StickySectionProps extends AnimationComponentProps {
    fixed: React.ReactNode;
    steps: StickyStep[];
    end?: string;
}

export const StickySection = forwardRef<HTMLDivElement, StickySectionProps>(
    ({ fixed, steps, className, end, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const fixedRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();
        const scrollEffects = useScrollEffectsEnabled();

        useScrollAnimation(
            containerRef,
            () => {
                if (!containerRef.current || !fixedRef.current) {
                    return;
                }

                const tl = gsap.timeline({
                    scrollTrigger: {
                        trigger: containerRef.current,
                        pin: fixedRef.current,
                        pinSpacing: true,
                        end: end ?? `+=${steps.length * 100}%`,
                        scrub: anim.scrub ?? 1,
                        ...resolveScrollVars({ ...anim, scrub: anim.scrub ?? 1 }),
                    },
                });

                steps.forEach((step, index) => {
                    const el = containerRef.current?.querySelector(
                        `[data-step="${step.id}"]`,
                    );
                    if (el) {
                        tl.fromTo(
                            el,
                            { opacity: index === 0 ? 1 : 0 },
                            { opacity: 1, duration: 0.5 },
                            index,
                        );
                    }
                });
            },
            [steps, anim, end, reduced, scrollEffects],
            disabled || reduced || !scrollEffects,
        );

        return (
            <div
                ref={mergeRefs(ref, containerRef)}
                className={cn('relative', className)}
            >
                <div
                    ref={fixedRef}
                    className={cn(
                        scrollEffects ? 'sticky top-0 h-screen' : 'relative',
                    )}
                >
                    {fixed}
                </div>
                <div className="relative z-10">
                    {steps.map((step) => (
                        <div
                            key={step.id}
                            data-step={step.id}
                            className="min-h-screen"
                        >
                            {step.content}
                        </div>
                    ))}
                </div>
            </div>
        );
    },
);
StickySection.displayName = 'StickySection';

interface HorizontalScrollSectionProps extends Omit<AnimationComponentProps, 'snap'> {
    children: React.ReactNode;
    enableSnap?: boolean;
}

export const HorizontalScrollSection = forwardRef<
    HTMLDivElement,
    HorizontalScrollSectionProps
>(({ children, className, enableSnap, disabled, ...anim }, ref) => {
    const containerRef = useRef<HTMLDivElement>(null);
    const trackRef = useRef<HTMLDivElement>(null);
    const reduced = useReducedMotion();
    const scrollEffects = useScrollEffectsEnabled();

    useScrollAnimation(
        containerRef,
        () => {
            if (!containerRef.current || !trackRef.current) {
                return;
            }

            const track = trackRef.current;
            const container = containerRef.current;
            const scrollDistance = track.scrollWidth - container.offsetWidth;

            if (scrollDistance <= 0) {
                return;
            }

            gsap.to(track, {
                x: () => -scrollDistance,
                ease: 'none',
                scrollTrigger: {
                    trigger: container,
                    pin: true,
                    scrub: anim.scrub ?? 1,
                    end: () => `+=${scrollDistance}`,
                    snap: enableSnap ? 1 / (track.children.length - 1) : undefined,
                    ...resolveScrollVars({ ...anim, once: false }),
                },
            });
        },
        [anim, enableSnap, reduced, scrollEffects],
        disabled || reduced || !scrollEffects,
    );

    return (
        <div
            ref={mergeRefs(ref, containerRef)}
            className={cn(scrollEffects ? 'overflow-hidden' : 'overflow-visible', className)}
        >
            <div
                ref={trackRef}
                className={cn(
                    'gap-6',
                    scrollEffects ? 'flex w-max' : 'flex flex-col',
                )}
            >
                {children}
            </div>
        </div>
    );
});
HorizontalScrollSection.displayName = 'HorizontalScrollSection';
