import { forwardRef, useRef } from 'react';
import { gsap } from '@marketing/animations/gsap/register-plugins';
import type { AnimationComponentProps } from '@marketing/animations/types/animation-props';
import { useScrollAnimation } from '@marketing/hooks/use-gsap-context';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';
import { useScrollEffectsEnabled } from '@marketing/hooks/use-scroll-effects-enabled';
import { resolveScrollVars, mergeRefs } from '@marketing/utils/animation-helpers';
import { cn } from '@/lib/utils';

export interface StoryChapterData {
    id: string;
    title?: string;
    content: React.ReactNode;
    image?: React.ReactNode;
    background?: string;
}

interface StoryChapterProps {
    chapter: StoryChapterData;
    className?: string;
}

export const StoryChapter = forwardRef<HTMLDivElement, StoryChapterProps>(
    ({ chapter, className }, ref) => {
        return (
            <div
                ref={ref}
                data-chapter={chapter.id}
                className={cn('min-h-screen flex flex-col justify-center px-6 py-24', className)}
                style={{ background: chapter.background }}
            >
                {chapter.image && <div className="mb-8">{chapter.image}</div>}
                {chapter.title && (
                    <h3 className="mb-4 text-3xl font-semibold">{chapter.title}</h3>
                )}
                <div>{chapter.content}</div>
            </div>
        );
    },
);
StoryChapter.displayName = 'StoryChapter';

interface ScrollytellingSectionProps extends AnimationComponentProps {
    chapters: StoryChapterData[];
    showProgress?: boolean;
    pinDuration?: string;
}

export const ScrollytellingSection = forwardRef<
    HTMLDivElement,
    ScrollytellingSectionProps
>(
    (
        {
            chapters,
            showProgress = true,
            pinDuration = '200%',
            className,
            disabled,
            ...anim
        },
        ref,
    ) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const progressRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();
        const scrollEffects = useScrollEffectsEnabled();

        useScrollAnimation(
            containerRef,
            () => {
                if (!containerRef.current) {
                    return;
                }

                const tl = gsap.timeline({
                    scrollTrigger: {
                        trigger: containerRef.current,
                        pin: true,
                        end: `+=${chapters.length * 100}%`,
                        scrub: anim.scrub ?? 1,
                        ...resolveScrollVars({ ...anim, once: false }),
                    },
                });

                chapters.forEach((chapter, index) => {
                    const el = containerRef.current?.querySelector(
                        `[data-chapter="${chapter.id}"]`,
                    );
                    if (el) {
                        tl.fromTo(
                            el,
                            { opacity: index === 0 ? 1 : 0, y: index === 0 ? 0 : 40 },
                            { opacity: 1, y: 0, duration: 1 },
                            index,
                        );
                    }
                });

                if (showProgress && progressRef.current) {
                    gsap.to(progressRef.current, {
                        scaleX: 1,
                        ease: 'none',
                        scrollTrigger: {
                            trigger: containerRef.current,
                            start: 'top top',
                            end: pinDuration,
                            scrub: true,
                        },
                    });
                }
            },
            [chapters, anim, pinDuration, showProgress, reduced, scrollEffects],
            disabled || reduced || !scrollEffects,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={cn('relative', className)}>
                {showProgress && scrollEffects && (
                    <div className="fixed top-0 left-0 z-50 h-0.5 w-full bg-foreground/10">
                        <div
                            ref={progressRef}
                            className="h-full w-full origin-left scale-x-0 bg-gradient-to-r from-brand-warm to-brand-cool-light"
                        />
                    </div>
                )}
                {chapters.map((chapter) => (
                    <StoryChapter
                        key={chapter.id}
                        chapter={chapter}
                        className={scrollEffects ? undefined : 'min-h-0 py-16'}
                    />
                ))}
            </div>
        );
    },
);
ScrollytellingSection.displayName = 'ScrollytellingSection';
