import { cn } from '@/lib/utils';
import { FadeUp } from '@marketing/components/animations/primitives';

interface FloatingReviewCardProps {
    quote: string;
    author: string;
    role?: string;
    className?: string;
    disableAnimation?: boolean;
    /** When true, renders as a static block (for mobile below-image layout). */
    inline?: boolean;
}

/** Floating review overlay for image sections. */
export function FloatingReviewCard({
    quote,
    author,
    role,
    className,
    disableAnimation = false,
    inline = false,
}: FloatingReviewCardProps) {
    const cardClassName = cn(
        'rounded-2xl border p-6 shadow-xl',
        inline
            ? 'relative w-full max-w-none border-border bg-card'
            : 'absolute bottom-6 left-6 right-6 max-w-xs border-white/20 bg-white/10 backdrop-blur-xl md:left-auto md:right-8',
        className,
    );

    // Overlay: light text on dark glass over the photo. Inline: theme colors, since it sits on the page background.
    const text = inline ? 'text-foreground' : 'text-white';
    const mutedText = inline ? 'text-muted-foreground' : 'text-white/70';
    const content = (
        <>
            <p className={cn('text-sm leading-relaxed', text)}>&ldquo;{quote}&rdquo;</p>
            <div className="mt-4">
                <p className={cn('text-sm font-semibold', text)}>{author}</p>
                {role && (
                    <p className={cn('text-xs', mutedText)}>{role}</p>
                )}
            </div>
        </>
    );

    if (disableAnimation) {
        return <div className={cardClassName}>{content}</div>;
    }

    return <FadeUp className={cardClassName}>{content}</FadeUp>;
}
