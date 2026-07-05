import { FadeUp, StaggerChildren } from '@marketing/components/animations/primitives';
import { ServiceItemCard } from '@marketing/components/services/service-item-card';
import { ServiceLanguageFamily } from '@marketing/components/services/service-language-family';
import type { ServiceGroup as ServiceGroupType, ServiceItem } from '@marketing/types/services-page';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

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

export function ServicesGroupBlock({ group }: ServicesGroupBlockProps) {
    const families = groupItemsByFamily(group.items);
    const isMobile = useIsMobile();

    return (
        <div className="space-y-6">
            <FadeUp disabled={isMobile}>
                <h3 className="text-lg font-semibold tracking-tight md:text-xl">{group.title}</h3>
            </FadeUp>

            <StaggerChildren
                disabled={isMobile}
                className="grid min-w-0 gap-6 lg:grid-cols-2"
            >
                {families.map((family) => {
                    if (family.label) {
                        return (
                            <div
                                key={family.label}
                                className={cn(
                                    'min-w-0',
                                    family.items.length > 1 ? 'lg:col-span-2' : undefined,
                                )}
                            >
                                <ServiceLanguageFamily label={family.label} items={family.items} />
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
