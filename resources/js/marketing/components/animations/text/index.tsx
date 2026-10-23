import {
    forwardRef,
    useImperativeHandle,
    useRef
    
} from 'react';
import type {ReactNode} from 'react';
import { cn } from '@/lib/utils';
import { gsap } from '@marketing/animations/gsap/register-plugins';
import type { SplitMode } from '@marketing/animations/split/split-text';
import type { ScrollAnimationProps } from '@marketing/animations/types/animation-props';
import { useScrollAnimation } from '@marketing/hooks/use-gsap-context';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';
import { useSplitText } from '@marketing/hooks/use-split-text';
import { resolveScrollVars } from '@marketing/utils/animation-helpers';

export interface SplitTextHandle {
    replay: () => void;
}

interface SplitTextProps extends ScrollAnimationProps {
    children: ReactNode;
    className?: string;
    split?: SplitMode;
    effect?: SplitEffect;
    as?: 'h1' | 'h2' | 'h3' | 'p' | 'span' | 'div';
}

export type SplitEffect =
    | 'fadeUp'
    | 'fadeIn'
    | 'rotate'
    | 'blur'
    | 'stagger'
    | 'clip'
    | 'mask';

function getEffectVars(effect: SplitEffect): {
    from: gsap.TweenVars;
    to: gsap.TweenVars;
} {
    switch (effect) {
        case 'fadeIn':
            return { from: { opacity: 0 }, to: { opacity: 1 } };
        case 'rotate':
            return {
                from: { opacity: 0, rotationX: -90 },
                to: { opacity: 1, rotationX: 0 },
            };
        case 'blur':
            return {
                from: { opacity: 0, filter: 'blur(12px)' },
                to: { opacity: 1, filter: 'blur(0px)' },
            };
        case 'clip':
            return {
                from: { opacity: 0, clipPath: 'inset(100% 0% 0% 0%)' },
                to: { opacity: 1, clipPath: 'inset(0% 0% 0% 0%)' },
            };
        case 'mask':
            return {
                from: { opacity: 0, y: '100%' },
                to: { opacity: 1, y: '0%' },
            };
        case 'stagger':
        case 'fadeUp':
        default:
            return { from: { opacity: 0, y: 40 }, to: { opacity: 1, y: 0 } };
    }
}

export const SplitText = forwardRef<SplitTextHandle, SplitTextProps>(
    (
        {
            children,
            className,
            split = 'chars',
            effect = 'fadeUp',
            as: Tag = 'div',
            disabled,
            duration = 0.8,
            delay = 0,
            ease = 'power3.out',
            ...anim
        },
        ref,
    ) => {
        const containerRef = useRef<HTMLElement>(null);
        const reduced = useReducedMotion();
        const { split: doSplit, replay, replayKey, cleanup } = useSplitText(split);

        useImperativeHandle(ref, () => ({ replay }));

        useScrollAnimation(
            containerRef,
            () => {
                if (!containerRef.current) {
                    return;
                }

                const result = doSplit(containerRef.current);

                if (!result) {
                    return;
                }

                const targets =
                    split === 'chars'
                        ? result.chars
                        : split === 'words'
                          ? result.words
                          : result.lines;

                if (!targets?.length) {
                    return;
                }

                const { from, to } = getEffectVars(effect);

                gsap.fromTo(targets, from, {
                    ...to,
                    duration,
                    delay,
                    ease,
                    stagger: 0.03,
                    scrollTrigger: {
                        trigger: containerRef.current,
                        ...resolveScrollVars(anim),
                    },
                });

                return () => cleanup();
            },
            [split, effect, anim, reduced, replayKey, duration, delay, ease],
            disabled || reduced,
        );

        return (
            <Tag
                ref={containerRef as never}
                className={cn(className)}
            >
                {children}
            </Tag>
        );
    },
);
SplitText.displayName = 'SplitText';

export const SplitFadeUp = forwardRef<
    SplitTextHandle,
    Omit<SplitTextProps, 'effect'>
>((props, ref) => <SplitText ref={ref} effect="fadeUp" {...props} />);
SplitFadeUp.displayName = 'SplitFadeUp';

export const SplitFadeIn = forwardRef<
    SplitTextHandle,
    Omit<SplitTextProps, 'effect'>
>((props, ref) => <SplitText ref={ref} effect="fadeIn" {...props} />);
SplitFadeIn.displayName = 'SplitFadeIn';

export const SplitRotate = forwardRef<
    SplitTextHandle,
    Omit<SplitTextProps, 'effect'>
>((props, ref) => <SplitText ref={ref} effect="rotate" {...props} />);
SplitRotate.displayName = 'SplitRotate';

export const SplitBlurReveal = forwardRef<
    SplitTextHandle,
    Omit<SplitTextProps, 'effect'>
>((props, ref) => <SplitText ref={ref} effect="blur" {...props} />);
SplitBlurReveal.displayName = 'SplitBlurReveal';

export const SplitStaggerReveal = forwardRef<
    SplitTextHandle,
    Omit<SplitTextProps, 'effect'>
>((props, ref) => <SplitText ref={ref} effect="stagger" {...props} />);
SplitStaggerReveal.displayName = 'SplitStaggerReveal';

export const SplitClipReveal = forwardRef<
    SplitTextHandle,
    Omit<SplitTextProps, 'effect'>
>((props, ref) => <SplitText ref={ref} effect="clip" {...props} />);
SplitClipReveal.displayName = 'SplitClipReveal';

export const SplitMaskReveal = forwardRef<
    SplitTextHandle,
    Omit<SplitTextProps, 'effect'>
>((props, ref) => <SplitText ref={ref} effect="mask" {...props} />);
SplitMaskReveal.displayName = 'SplitMaskReveal';
