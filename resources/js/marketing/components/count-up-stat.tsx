import { useEffect, useRef } from 'react';
import { presets } from '@marketing/animations/gsap/timelines/presets';
import type { ScrollAnimationProps } from '@marketing/animations/types/animation-props';
import { useScrollAnimation } from '@marketing/hooks/use-gsap-context';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';
import { cn } from '@/lib/utils';

interface CountUpStatProps extends ScrollAnimationProps {
    value: number;
    label: string;
    suffix?: string;
    prefix?: string;
    className?: string;
}

/** Animate a statistic from 0 on scroll into view. */
export function CountUpStat({
    value,
    label,
    suffix = '',
    prefix = '',
    className,
    disabled,
    ...anim
}: CountUpStatProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const valueRef = useRef<HTMLSpanElement>(null);
    const reduced = useReducedMotion();

    useScrollAnimation(
        containerRef,
        () => {
            if (!valueRef.current) {
                return;
            }

            presets.countUp(valueRef.current, value, {
                ...anim,
                suffix,
                prefix,
            });
        },
        [value, suffix, prefix, anim, reduced],
        disabled || reduced,
    );

    useEffect(() => {
        if (reduced && valueRef.current) {
            valueRef.current.textContent = `${prefix}${value}${suffix}`;
        }
    }, [reduced, value, prefix, suffix]);

    return (
        <div ref={containerRef} className={cn('text-center', className)}>
            <span
                ref={valueRef}
                className="block text-3xl font-bold text-primary md:text-4xl"
            >
                {reduced ? `${prefix}${value}${suffix}` : `${prefix}0${suffix}`}
            </span>
            <span className="mt-2 block text-xs leading-snug text-muted-foreground md:text-sm">
                {label}
            </span>
        </div>
    );
}
