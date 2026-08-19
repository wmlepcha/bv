import { FadeUp } from '@marketing/components/animations/primitives';
import { cn } from '@/lib/utils';

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
        'rounded-2xl border border-white/20 bg-white/10 p-6 backdrop-blur-xl shadow-xl',
        inline
            ? 'relative w-full max-w-none'
            : 'absolute bottom-6 left-6 right-6 max-w-xs md:left-auto md:right-8',
        className,
    );

    // Always light text — card sits on a dark glass overlay over photos.
    const content = (
        <>
            <p className="text-sm leading-relaxed text-white/95">&ldquo;{quote}&rdquo;</p>
            <div className="mt-4">
                <p className="text-sm font-semibold text-white">{author}</p>
                {role && (
                    <p className="text-xs text-white/70">{role}</p>
                )}
            </div>
        </>
    );

    if (disableAnimation) {
        return <div className={cardClassName}>{content}</div>;
    }

    return <FadeUp className={cardClassName}>{content}</FadeUp>;
}
