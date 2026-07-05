import { forwardRef, useRef, type ReactNode } from 'react';
import type { AnimationComponentProps } from '@marketing/animations/types/animation-props';
import { useScrollAnimation } from '@marketing/hooks/use-gsap-context';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';
import { mergeRefs } from '@marketing/utils/animation-helpers';
import { presets } from '@marketing/animations/gsap/timelines/presets';
import { cn } from '@/lib/utils';

interface PrimitiveProps extends AnimationComponentProps {
    children?: ReactNode;
}

export const FadeIn = forwardRef<HTMLDivElement, PrimitiveProps>(
    ({ children, className, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (containerRef.current) {
                    presets.fadeIn(containerRef.current, anim);
                }
            },
            [anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={className}>
                {children}
            </div>
        );
    },
);
FadeIn.displayName = 'FadeIn';

export const FadeUp = forwardRef<HTMLDivElement, PrimitiveProps>(
    ({ children, className, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (containerRef.current) {
                    presets.fadeUp(containerRef.current, anim);
                }
            },
            [anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={className}>
                {children}
            </div>
        );
    },
);
FadeUp.displayName = 'FadeUp';

export const ScaleIn = forwardRef<HTMLDivElement, PrimitiveProps>(
    ({ children, className, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (containerRef.current) {
                    presets.scaleIn(containerRef.current, anim);
                }
            },
            [anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={className}>
                {children}
            </div>
        );
    },
);
ScaleIn.displayName = 'ScaleIn';

export const RotateIn = forwardRef<HTMLDivElement, PrimitiveProps>(
    ({ children, className, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (containerRef.current) {
                    presets.rotateIn(containerRef.current, anim);
                }
            },
            [anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={className}>
                {children}
            </div>
        );
    },
);
RotateIn.displayName = 'RotateIn';

export const StaggerChildren = forwardRef<HTMLDivElement, PrimitiveProps>(
    ({ children, className, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (containerRef.current) {
                    presets.staggerChildren(
                        containerRef.current.children,
                        {
                            ...anim,
                            trigger: containerRef.current,
                        },
                    );
                }
            },
            [anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={cn(className)}>
                {children}
            </div>
        );
    },
);
StaggerChildren.displayName = 'StaggerChildren';
