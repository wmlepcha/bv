import { forwardRef  } from 'react';
import type {ReactNode} from 'react';
import { cn } from '@/lib/utils';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';

interface HorizontalSnapScrollProps {
    children: ReactNode;
    className?: string;
    trackClassName?: string;
    fadeEdges?: boolean;
    ariaLabel?: string;
}

export const HorizontalSnapScroll = forwardRef<HTMLDivElement, HorizontalSnapScrollProps>(
    function HorizontalSnapScroll(
        { children, className, trackClassName, fadeEdges = false, ariaLabel },
        ref,
    ) {
        const reducedMotion = useReducedMotion();

        return (
            <div className="relative">
                <div
                    ref={ref}
                    role="region"
                    aria-label={ariaLabel}
                    className={cn(
                        'overflow-x-auto overscroll-x-contain scrollbar-none',
                        'snap-x snap-mandatory',
                        !reducedMotion && 'scroll-smooth',
                        className,
                    )}
                >
                    <div className={cn('flex w-max', trackClassName)}>{children}</div>
                </div>
                {fadeEdges && (
                    <>
                        <div
                            aria-hidden
                            className="pointer-events-none absolute inset-y-0 left-0 z-10 w-6 bg-gradient-to-r from-background to-transparent"
                        />
                        <div
                            aria-hidden
                            className="pointer-events-none absolute inset-y-0 right-0 z-10 w-6 bg-gradient-to-l from-background to-transparent"
                        />
                    </>
                )}
            </div>
        );
    },
);
