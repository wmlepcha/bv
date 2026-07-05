import type { ReactNode } from 'react';

/** Shared ScrollTrigger animation configuration passed to all animation components. */
export interface ScrollAnimationProps {
    duration?: number;
    delay?: number;
    ease?: string;
    start?: string;
    end?: string;
    scrub?: boolean | number;
    pin?: boolean;
    snap?: number | number[] | object;
    toggleActions?: string;
    once?: boolean;
    markers?: boolean;
    disabled?: boolean;
    breakpoints?: Record<string, Partial<ScrollAnimationProps>>;
}

export interface AnimationComponentProps extends ScrollAnimationProps {
    children?: ReactNode;
    className?: string;
    as?: keyof HTMLElementTagNameMap;
}

export type ScrollTriggerConfig = Pick<
    ScrollAnimationProps,
    | 'start'
    | 'end'
    | 'scrub'
    | 'pin'
    | 'snap'
    | 'toggleActions'
    | 'once'
    | 'markers'
>;
