import { forwardRef, useRef } from 'react';
import { cn } from '@/lib/utils';
import { gsap } from '@marketing/animations/gsap/register-plugins';
import type { AnimationComponentProps } from '@marketing/animations/types/animation-props';
import { useScrollAnimation } from '@marketing/hooks/use-gsap-context';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';
import { resolveScrollVars, mergeRefs } from '@marketing/utils/animation-helpers';

type Direction = 'left' | 'right' | 'top' | 'bottom';

const clipPaths: Record<Direction, { from: string; to: string }> = {
    left: {
        from: 'inset(0 100% 0 0)',
        to: 'inset(0 0% 0 0)',
    },
    right: {
        from: 'inset(0 0 0 100%)',
        to: 'inset(0 0 0 0%)',
    },
    top: {
        from: 'inset(100% 0 0 0)',
        to: 'inset(0% 0 0 0)',
    },
    bottom: {
        from: 'inset(0 0 100% 0)',
        to: 'inset(0 0 0% 0)',
    },
};

interface RevealProps extends AnimationComponentProps {
    direction?: Direction;
}

export const ClipPathReveal = forwardRef<HTMLDivElement, RevealProps>(
    ({ children, className, direction = 'bottom', disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();
        const paths = clipPaths[direction];

        useScrollAnimation(
            containerRef,
            () => {
                if (!containerRef.current) {
                    return;
                }

                gsap.fromTo(
                    containerRef.current,
                    { clipPath: paths.from },
                    {
                        clipPath: paths.to,
                        duration: anim.duration ?? 1.2,
                        ease: anim.ease ?? 'power4.inOut',
                        scrollTrigger: {
                            trigger: containerRef.current,
                            ...resolveScrollVars(anim),
                        },
                    },
                );
            },
            [direction, anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={cn('overflow-hidden', className)}>
                {children}
            </div>
        );
    },
);
ClipPathReveal.displayName = 'ClipPathReveal';

export const MaskReveal = forwardRef<HTMLDivElement, RevealProps>(
    ({ children, className, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (!containerRef.current) {
                    return;
                }

                gsap.fromTo(
                    containerRef.current,
                    {
                        WebkitMaskSize: '100% 0%',
                        maskSize: '100% 0%',
                    },
                    {
                        WebkitMaskSize: '100% 100%',
                        maskSize: '100% 100%',
                        duration: anim.duration ?? 1.2,
                        ease: anim.ease ?? 'power3.inOut',
                        scrollTrigger: {
                            trigger: containerRef.current,
                            ...resolveScrollVars(anim),
                        },
                    },
                );
            },
            [anim, reduced],
            disabled || reduced,
        );

        return (
            <div
                ref={mergeRefs(ref, containerRef)}
                className={cn('[mask-image:linear-gradient(black,black)]', className)}
            >
                {children}
            </div>
        );
    },
);
MaskReveal.displayName = 'MaskReveal';

export const ScaleReveal = forwardRef<HTMLDivElement, RevealProps>(
    ({ children, className, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (!containerRef.current) {
                    return;
                }

                gsap.fromTo(
                    containerRef.current,
                    { scale: 1.2, opacity: 0 },
                    {
                        scale: 1,
                        opacity: 1,
                        duration: anim.duration ?? 1,
                        ease: anim.ease ?? 'power3.out',
                        scrollTrigger: {
                            trigger: containerRef.current,
                            ...resolveScrollVars(anim),
                        },
                    },
                );
            },
            [anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={cn('overflow-hidden', className)}>
                {children}
            </div>
        );
    },
);
ScaleReveal.displayName = 'ScaleReveal';

export const DirectionalReveal = forwardRef<HTMLDivElement, RevealProps>(
    (props, ref) => <ClipPathReveal ref={ref} {...props} />,
);
DirectionalReveal.displayName = 'DirectionalReveal';

export const DiagonalReveal = forwardRef<HTMLDivElement, RevealProps>(
    ({ children, className, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (!containerRef.current) {
                    return;
                }

                gsap.fromTo(
                    containerRef.current,
                    { clipPath: 'polygon(0 0, 0 0, 0 100%, 0 100%)' },
                    {
                        clipPath: 'polygon(0 0, 100% 0, 100% 100%, 0 100%)',
                        duration: anim.duration ?? 1.2,
                        ease: anim.ease ?? 'power4.inOut',
                        scrollTrigger: {
                            trigger: containerRef.current,
                            ...resolveScrollVars(anim),
                        },
                    },
                );
            },
            [anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={cn('overflow-hidden', className)}>
                {children}
            </div>
        );
    },
);
DiagonalReveal.displayName = 'DiagonalReveal';

export const AnimatedBorderReveal = forwardRef<HTMLDivElement, RevealProps>(
    ({ children, className, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const borderRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (!borderRef.current) {
                    return;
                }

                gsap.fromTo(
                    borderRef.current,
                    { scaleX: 0 },
                    {
                        scaleX: 1,
                        duration: anim.duration ?? 1,
                        ease: anim.ease ?? 'power3.inOut',
                        scrollTrigger: {
                            trigger: containerRef.current,
                            ...resolveScrollVars(anim),
                        },
                    },
                );
            },
            [anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={cn('relative', className)}>
                {children}
                <div
                    ref={borderRef}
                    className="absolute bottom-0 left-0 h-px w-full origin-left bg-gradient-to-r from-brand-warm to-brand-cool-light"
                />
            </div>
        );
    },
);
AnimatedBorderReveal.displayName = 'AnimatedBorderReveal';
