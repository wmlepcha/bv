import { useRef  } from 'react';
import type {ReactNode} from 'react';
import { cn } from '@/lib/utils';
import { gsap, ScrollTrigger } from '@marketing/animations/gsap/register-plugins';
import { useScrollAnimation } from '@marketing/hooks/use-gsap-context';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';

interface ColumnConfig {
    content: ReactNode;
    className?: string;
}

interface SequentialColumnSectionProps {
    leading: ColumnConfig;
    trailing: ColumnConfig;
    className?: string;
    gridClassName?: string;
    background?: ReactNode;
}

function getMobileFirstColumn(
    leadingEl: HTMLElement,
    trailingEl: HTMLElement,
): 'leading' | 'trailing' {
    const leadingOrder = Number.parseInt(getComputedStyle(leadingEl).order, 10) || 0;
    const trailingOrder = Number.parseInt(getComputedStyle(trailingEl).order, 10) || 0;

    return leadingOrder <= trailingOrder ? 'leading' : 'trailing';
}

function buildRevealTimeline(
    firstEl: HTMLElement,
    secondEl: HTMLElement,
    trigger: HTMLElement,
) {
    const from = { opacity: 0, y: 48 };
    const to = { opacity: 1, y: 0, ease: 'power3.out', duration: 0.45 };

    gsap.set([firstEl, secondEl], from);

    return gsap.timeline({
        scrollTrigger: {
            trigger,
            start: 'top 80%',
            end: 'bottom 50%',
            scrub: 1,
            once: false,
        },
    })
        .fromTo(firstEl, from, { ...to }, 0)
        .fromTo(secondEl, from, { ...to }, 0.55);
}

/** Two-column section that reveals one column on scroll, then the other. */
export function SequentialColumnSection({
    leading,
    trailing,
    className,
    gridClassName,
    background,
}: SequentialColumnSectionProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const leadingRef = useRef<HTMLDivElement>(null);
    const trailingRef = useRef<HTMLDivElement>(null);
    const reduced = useReducedMotion();

    useScrollAnimation(
        containerRef,
        () => {
            const container = containerRef.current;
            const leadingEl = leadingRef.current;
            const trailingEl = trailingRef.current;

            if (!container || !leadingEl || !trailingEl) {
                return;
            }

            const mm = ScrollTrigger.matchMedia({
                '(min-width: 1024px)': () =>
                    buildRevealTimeline(leadingEl, trailingEl, container),
                '(max-width: 1023px)': () => {
                    const firstKey = getMobileFirstColumn(leadingEl, trailingEl);
                    const firstEl = firstKey === 'leading' ? leadingEl : trailingEl;
                    const secondEl = firstKey === 'leading' ? trailingEl : leadingEl;

                    return buildRevealTimeline(firstEl, secondEl, container);
                },
            });

            return () => mm.revert();
        },
        [reduced],
        reduced,
    );

    return (
        <section className={cn('relative', className)}>
            {background}
            <div
                ref={containerRef}
                className={cn(
                    'relative mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2 lg:gap-20',
                    gridClassName,
                )}
            >
                <div ref={leadingRef} className={leading.className}>
                    {leading.content}
                </div>
                <div ref={trailingRef} className={trailing.className}>
                    {trailing.content}
                </div>
            </div>
        </section>
    );
}
