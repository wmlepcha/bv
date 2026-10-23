import { cn } from '@/lib/utils';
import { ServicesGroupBlock } from '@marketing/components/services/services-group-block';
import type { ServiceCategory } from '@marketing/types/services-page';

interface ServicesCategorySectionProps {
    category: ServiceCategory;
    stickyTopOffset?: number;
}

export function ServicesCategorySection({ category, stickyTopOffset }: ServicesCategorySectionProps) {
    return (
        <section
            id={`services-panel-${category.id}`}
            role="tabpanel"
            aria-labelledby={`services-tab-${category.id}`}
            className="px-4 pt-8 pb-16 sm:px-6 sm:py-24 md:py-32"
        >
            <div className="mx-auto w-full min-w-0 max-w-7xl 2xl:max-w-[90rem]">
                <div className="grid min-w-0 gap-10 lg:grid-cols-[minmax(0,220px)_1fr] lg:gap-16 xl:grid-cols-[minmax(0,260px)_1fr]">
                    <div
                        className="lg:sticky lg:z-10 lg:self-start"
                        style={{ top: `${stickyTopOffset ?? 112}px` }}
                    >
                        <div
                            className={cn(
                                'inline-flex rounded-xl bg-brand-cool-dark px-6 py-4 text-center text-lg font-semibold text-white shadow-lg',
                                'lg:block lg:w-full lg:px-8 lg:py-6 lg:text-xl',
                            )}
                        >
                            {category.title}
                        </div>
                    </div>

                    <div className="min-w-0 space-y-12 lg:space-y-16 lg:border-l lg:border-foreground/10 lg:pl-12">
                        {category.groups.map((group) => (
                            <ServicesGroupBlock key={group.id} group={group} />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
