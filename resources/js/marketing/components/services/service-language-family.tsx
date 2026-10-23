import { ChevronRight } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import { FadeUp, StaggerChildren } from '@marketing/components/animations/primitives';
import { ServiceLanguagePlaceholderCard } from '@marketing/components/services/service-language-placeholder-card';
import { ServiceLanguageVariantCard } from '@marketing/components/services/service-language-variant-card';
import { HorizontalSnapScroll } from '@marketing/components/ui/horizontal-snap-scroll';
import type { ServiceItem } from '@marketing/types/services-page';

interface ServiceLanguageFamilyProps {
    label: string;
    items: ServiceItem[];
}

function getFamilyDescription(items: ServiceItem[]): string | null {
    for (const item of items) {
        if (item.familyDescription) {
            return item.familyDescription;
        }
    }

    return null;
}

export function ServiceLanguageFamily({ label, items }: ServiceLanguageFamilyProps) {
    const isMultiVariant = items.length > 1;
    const familyDescription = getFamilyDescription(items);
    const isMobile = useIsMobile();
    const scrollRef = useRef<HTMLDivElement>(null);
    const slideRefs = useRef<(HTMLDivElement | null)[]>([]);
    const [activeIndex, setActiveIndex] = useState(0);

    useEffect(() => {
        if (!isMultiVariant) {
            return;
        }

        const root = scrollRef.current;

        if (!root) {
            return;
        }

        const slides = slideRefs.current.filter(Boolean) as HTMLDivElement[];

        if (slides.length === 0) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

                if (!visible) {
                    return;
                }

                const index = slides.indexOf(visible.target as HTMLDivElement);

                if (index >= 0) {
                    setActiveIndex(index);
                }
            },
            {
                root,
                threshold: [0.55, 0.75, 1],
            },
        );

        slides.forEach((slide) => observer.observe(slide));

        return () => observer.disconnect();
    }, [isMultiVariant, items.length]);

    if (!isMultiVariant) {
        return <ServiceLanguagePlaceholderCard item={items[0]} />;
    }

    return (
        <div className="min-w-0 space-y-8">
            <FadeUp className="text-center" disabled={isMobile}>
                <p className="mb-4 hidden items-center gap-2 rounded-full border border-primary/25 bg-primary/10 px-4 py-1.5 text-xs font-medium tracking-wide text-foreground/80 sm:inline-flex">
                    <span className="h-1.5 w-1.5 rounded-full bg-primary" />
                    Language
                </p>
                <h4 className="text-2xl font-semibold tracking-tight md:text-3xl">
                    {label} Language Programs
                </h4>
                {familyDescription && (
                    <p className="mx-auto mt-3 max-w-2xl px-2 text-sm leading-relaxed text-foreground/80 md:text-base">
                        {familyDescription}
                    </p>
                )}
            </FadeUp>

            <div className="min-w-0 lg:hidden">
                {items.length > 1 && (
                    <p className="mb-3 flex items-center gap-1.5 text-xs text-muted-foreground">
                        <span>Swipe for more formats</span>
                        <ChevronRight className="size-3.5 animate-pulse text-primary" aria-hidden />
                    </p>
                )}

                <div className="overflow-hidden">
                    <HorizontalSnapScroll
                        ref={scrollRef}
                        ariaLabel={`${label} language program variants`}
                        trackClassName="gap-3"
                    >
                        {items.map((item, index) => (
                            <div
                                key={item.id}
                                ref={(element) => {
                                    slideRefs.current[index] = element;
                                }}
                                className={cn(
                                    'w-[72%] max-w-[15.5rem] shrink-0 snap-start transition-[transform,opacity] duration-300',
                                    activeIndex === index
                                        ? 'scale-100 opacity-100'
                                        : 'scale-[0.97] opacity-80',
                                )}
                            >
                                <ServiceLanguageVariantCard item={item} compact />
                            </div>
                        ))}
                    </HorizontalSnapScroll>
                </div>

                {items.length > 1 && (
                    <div
                        className="mt-4 flex justify-center gap-2"
                        role="tablist"
                        aria-label={`${label} program slides`}
                    >
                        {items.map((item, index) => (
                            <button
                                key={item.id}
                                type="button"
                                role="tab"
                                aria-selected={activeIndex === index}
                                aria-label={`Slide ${index + 1}: ${item.title}`}
                                className={cn(
                                    'h-2 rounded-full transition-all duration-300',
                                    activeIndex === index
                                        ? 'w-6 bg-primary'
                                        : 'w-2 bg-primary/30',
                                )}
                                onClick={() => {
                                    slideRefs.current[index]?.scrollIntoView({
                                        behavior: 'smooth',
                                        inline: 'start',
                                        block: 'nearest',
                                    });
                                }}
                            />
                        ))}
                    </div>
                )}
            </div>

            <StaggerChildren className="hidden space-y-4 lg:block">
                {items.map((item) => (
                    <ServiceLanguageVariantCard key={item.id} item={item} />
                ))}
            </StaggerChildren>
        </div>
    );
}
