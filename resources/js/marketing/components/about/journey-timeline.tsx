import { FadeUp } from '@marketing/components/animations/primitives';
import type { JourneyMilestone } from '@marketing/types/about-page';
import { cn } from '@/lib/utils';

function TimelineLoop() {
    return (
        <svg
            aria-hidden
            viewBox="0 0 24 40"
            className="absolute -top-9 left-1/2 h-10 w-6 -translate-x-1/2 text-foreground/60"
            fill="none"
            stroke="currentColor"
            strokeWidth="1"
        >
            <path d="M12 40 L12 18 C12 10 20 6 20 12 C20 18 12 14 12 8 C12 2 4 0 4 6" />
        </svg>
    );
}

function TimelineMarker({ period }: { period: string }) {
    return (
        <div className="flex flex-col items-center">
            <span className="whitespace-nowrap rounded-full border border-border bg-background/80 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.2em] text-muted-foreground">
                {period}
            </span>
            <div
                aria-hidden
                className="mt-3 h-3 w-3 shrink-0 rounded-full bg-foreground ring-4 ring-background"
            />
        </div>
    );
}

function MilestoneText({
    milestone,
    align,
}: {
    milestone: JourneyMilestone;
    align: 'left' | 'right' | 'center';
}) {
    return (
        <div
            className={cn(
                align === 'center' && 'text-center',
                align === 'left' && 'text-left lg:text-right lg:pr-10',
                align === 'right' && 'text-left lg:pl-10',
            )}
        >
            <h3 className="changa-one-regular text-2xl tracking-tight text-foreground md:text-3xl">
                {milestone.title}
            </h3>
            {milestone.bullets.length > 0 && (
                <ul
                    className={cn(
                        'mt-4 space-y-3',
                        align === 'center' && 'mx-auto max-w-lg text-left',
                    )}
                >
                    {milestone.bullets.map((bullet, index) => (
                        <li
                            key={index}
                            className="flex gap-3 text-sm leading-relaxed text-muted-foreground md:text-base"
                        >
                            <span className="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-foreground/40" />
                            <span>{bullet}</span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function MilestoneImage({ milestone }: { milestone: JourneyMilestone }) {
    if (!milestone.imageUrl) {
        return null;
    }

    return (
        <div className="overflow-hidden rounded-sm">
            <img
                src={milestone.imageUrl}
                alt={milestone.imageAlt ?? milestone.title}
                className="max-h-80 w-full object-cover"
                loading="lazy"
            />
        </div>
    );
}

function CenterMilestoneRow({
    milestone,
    index,
}: {
    milestone: JourneyMilestone;
    index: number;
}) {
    return (
        <FadeUp delay={index * 0.1} className="relative pb-20 lg:pb-28">
            {/* Mobile */}
            <div className="relative pl-10 lg:hidden">
                <div className="absolute left-4 top-0 flex -translate-x-1/2 flex-col items-center">
                    <TimelineMarker period={milestone.period} />
                </div>
                <div className="pt-16">
                    <MilestoneText milestone={milestone} align="left" />
                </div>
            </div>

            {/* Desktop */}
            <div className="hidden lg:block">
                <div className="mx-auto max-w-lg text-center">
                    <span className="inline-block rounded-full border border-border bg-background/80 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.2em] text-muted-foreground">
                        {milestone.period}
                    </span>
                    <div className="mt-8">
                        <MilestoneText milestone={milestone} align="center" />
                    </div>
                </div>
                <div className="relative mt-10 flex justify-center">
                    <div
                        aria-hidden
                        className="h-3 w-3 rounded-full bg-foreground ring-4 ring-background"
                    />
                </div>
            </div>
        </FadeUp>
    );
}

function AlternatingMilestoneRow({
    milestone,
    index,
}: {
    milestone: JourneyMilestone;
    index: number;
}) {
    const isTextLeft = milestone.layout === 'text-left';

    return (
        <FadeUp delay={index * 0.1} className="relative pb-20 lg:pb-28">
            {/* Mobile */}
            <div className="relative pl-10 lg:hidden">
                <div className="absolute left-4 top-6 flex -translate-x-1/2 flex-col items-center">
                    <TimelineMarker period={milestone.period} />
                </div>
                <div className="space-y-6 pt-20">
                    <MilestoneText milestone={milestone} align="left" />
                    <MilestoneImage milestone={milestone} />
                </div>
            </div>

            {/* Desktop */}
            <div
                className={cn(
                    'hidden lg:grid lg:grid-cols-[1fr_48px_1fr] lg:items-center lg:gap-8',
                )}
            >
                {isTextLeft ? (
                    <>
                        <MilestoneText milestone={milestone} align="left" />
                        <div className="flex justify-center">
                            <TimelineMarker period={milestone.period} />
                        </div>
                        <MilestoneImage milestone={milestone} />
                    </>
                ) : (
                    <>
                        <MilestoneImage milestone={milestone} />
                        <div className="flex justify-center">
                            <TimelineMarker period={milestone.period} />
                        </div>
                        <MilestoneText milestone={milestone} align="right" />
                    </>
                )}
            </div>
        </FadeUp>
    );
}

interface JourneyTimelineProps {
    milestones: JourneyMilestone[];
}

export function JourneyTimeline({ milestones }: JourneyTimelineProps) {
    return (
        <section className="border-y border-border bg-muted/20 px-4 py-16 sm:px-6 sm:py-24 md:py-32">
            <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                <FadeUp>
                    <h2 className="changa-one-regular text-center text-3xl tracking-tight md:text-4xl lg:text-5xl">
                        Our journey so far
                    </h2>
                </FadeUp>

                <div className="relative mt-16 lg:mt-24">
                    {/* Mobile spine */}
                    <div className="pointer-events-none absolute bottom-0 left-4 top-0 w-px bg-foreground/50 lg:hidden" />

                    {/* Desktop spine */}
                    <div className="pointer-events-none absolute inset-y-0 left-1/2 hidden w-px -translate-x-1/2 bg-foreground/50 lg:block" />

                    <div className="relative hidden lg:block">
                        <TimelineLoop />
                    </div>

                    {milestones.map((milestone, index) =>
                        milestone.layout === 'center' ? (
                            <CenterMilestoneRow
                                key={milestone.id}
                                milestone={milestone}
                                index={index}
                            />
                        ) : (
                            <AlternatingMilestoneRow
                                key={milestone.id}
                                milestone={milestone}
                                index={index}
                            />
                        ),
                    )}
                </div>
            </div>
        </section>
    );
}
