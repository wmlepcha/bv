import { useRef, useState } from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import {
    FadeUp,
    StaggerChildren,
} from '@marketing/components/animations/primitives';
import { ServiceItemCard } from '@marketing/components/services/service-item-card';
import { ServiceLanguageFamily } from '@marketing/components/services/service-language-family';
import type {
    ServiceGroup as ServiceGroupType,
    ServiceItem,
} from '@marketing/types/services-page';

interface ServicesGroupBlockProps {
    group: ServiceGroupType;
}

type ItemFamily = {
    label: string | null;
    items: ServiceItem[];
};

function groupItemsByFamily(items: ServiceItem[]): ItemFamily[] {
    const families: ItemFamily[] = [];
    const familyIndex = new Map<string, number>();

    for (const item of items) {
        const key = item.familyLabel ?? `__single_${item.id}`;

        if (familyIndex.has(key)) {
            families[familyIndex.get(key)!].items.push(item);
            continue;
        }

        familyIndex.set(key, families.length);
        families.push({
            label: item.familyLabel ?? null,
            items: [item],
        });
    }

    return families;
}

/** Phone-only swipeable row of cards (one snap point per card) with page dots. */
function ServiceItemSlider({
    items,
    label,
}: {
    items: ServiceItem[];
    label: string;
}) {
    const trackRef = useRef<HTMLDivElement>(null);
    const [activeIndex, setActiveIndex] = useState(0);
    const hasMany = items.length > 1;

    const handleScroll = () => {
        const track = trackRef.current;
        const first = track?.firstElementChild as
            HTMLElement | null | undefined;

        if (!track || !first) {
            return;
        }

        const step = first.offsetWidth + 12;
        setActiveIndex(
            Math.min(
                items.length - 1,
                Math.max(0, Math.round(track.scrollLeft / step)),
            ),
        );
    };

    return (
        <div className="min-w-0 lg:hidden">
            <div
                ref={trackRef}
                role="region"
                aria-label={label}
                onScroll={handleScroll}
                className={cn(
                    '-mx-4 flex snap-x snap-mandatory scrollbar-none gap-3 overflow-x-auto overscroll-x-contain px-4 pb-1 sm:-mx-6 sm:px-6',
                    !hasMany && 'overflow-x-visible',
                )}
            >
                {items.map((item) => (
                    <div
                        key={item.id}
                        className={cn(
                            'min-w-0 shrink-0 snap-start scroll-ml-4 sm:scroll-ml-6',
                            hasMany ? 'w-[85%] max-w-sm' : 'w-full',
                        )}
                    >
                        <ServiceItemCard item={item} />
                    </div>
                ))}
            </div>

            {hasMany && (
                <div className="mt-3 flex justify-center gap-2" aria-hidden>
                    {items.map((item, index) => (
                        <span
                            key={item.id}
                            className={cn(
                                'h-2 rounded-full transition-all duration-300',
                                activeIndex === index
                                    ? 'w-6 bg-primary'
                                    : 'w-2 bg-primary/30',
                            )}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

export function ServicesGroupBlock({ group }: ServicesGroupBlockProps) {
    const families = groupItemsByFamily(group.items);
    const isMobile = useIsMobile();
    // Language families carry their own headings, so the group title would only repeat them.
    const showTitle = families.some((family) => !family.label);
    // Groups with no language families get a swipeable row on phones; families scroll on their own.
    const slideOnPhones = families.every((family) => !family.label);

    return (
        <div className="space-y-6">
            {(showTitle || group.description) && (
                <FadeUp disabled={isMobile} className="space-y-2">
                    {showTitle && (
                        <h3 className="text-lg font-semibold tracking-tight md:text-xl">
                            {group.title}
                        </h3>
                    )}
                    {group.description && (
                        <p className="max-w-2xl text-sm leading-relaxed text-muted-foreground md:text-base">
                            {group.description}
                        </p>
                    )}
                </FadeUp>
            )}

            {slideOnPhones && (
                <ServiceItemSlider
                    items={group.items}
                    label={`${group.title} options`}
                />
            )}

            <StaggerChildren
                disabled={isMobile}
                className={cn(
                    'min-w-0 gap-6 lg:grid-cols-2',
                    slideOnPhones ? 'hidden lg:grid' : 'grid',
                )}
            >
                {families.map((family) => {
                    if (family.label) {
                        return (
                            <div
                                key={family.label}
                                className={cn(
                                    'min-w-0',
                                    family.items.length > 1
                                        ? 'lg:col-span-2'
                                        : undefined,
                                )}
                            >
                                <ServiceLanguageFamily
                                    label={family.label}
                                    items={family.items}
                                />
                            </div>
                        );
                    }

                    return family.items.map((item) => (
                        <ServiceItemCard key={item.id} item={item} />
                    ));
                })}
            </StaggerChildren>
        </div>
    );
}
