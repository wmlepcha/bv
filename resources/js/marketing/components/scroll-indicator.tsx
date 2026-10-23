import { cn } from '@/lib/utils';
import { FadeIn } from '@marketing/components/animations/primitives';

interface ScrollIndicatorProps {
    className?: string;
}

/** Animated scroll cue for hero sections. */
export function ScrollIndicator({ className }: ScrollIndicatorProps) {
    return (
        <FadeIn className={cn('flex flex-col items-center gap-2', className)} delay={1.2}>
            <span className="text-xs uppercase tracking-widest text-muted-foreground">
                Scroll
            </span>
            <div className="h-10 w-px animate-pulse bg-gradient-to-b from-primary to-transparent" />
        </FadeIn>
    );
}
