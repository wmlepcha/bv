import { cn } from '@/lib/utils';
import { ServicesGroupBlock } from '@marketing/components/services/services-group-block';
import type { ServiceCategory } from '@marketing/types/services-page';

interface ServicesCategorySectionProps {
    category: ServiceCategory;
    categories: ServiceCategory[];
    activeId: string;
    onChange: (id: string) => void;
}

export function ServicesCategorySection({
    category,
    categories,
    activeId,
    onChange,
}: ServicesCategorySectionProps) {
    return (
        <section
            id={`services-panel-${category.id}`}
            role="tabpanel"
            aria-labelledby={`services-tab-${category.id}`}
            className="px-4 pt-6 pb-16 sm:px-6 sm:pt-8 sm:pb-24 md:pb-32 lg:py-24"
        >
            <div className="mx-auto w-full max-w-7xl min-w-0 2xl:max-w-[90rem]">
                <div className="grid min-w-0 gap-10 lg:grid-cols-[minmax(0,220px)_1fr] lg:gap-16 xl:grid-cols-[minmax(0,260px)_1fr]">
                    <h2 className="sr-only">{category.title}</h2>

                    <nav
                        aria-label="Services categories"
                        className="hidden lg:sticky lg:top-[calc(var(--marketing-header-height,5.5rem)+1.5rem)] lg:z-10 lg:block lg:self-start"
                    >
                        <div
                            role="tablist"
                            aria-orientation="vertical"
                            className="flex flex-col gap-1"
                        >
                            {categories.map((item) => {
                                const isActive = item.id === activeId;

                                return (
                                    <button
                                        key={item.id}
                                        type="button"
                                        role="tab"
                                        aria-selected={isActive}
                                        aria-controls={`services-panel-${item.id}`}
                                        id={`services-side-tab-${item.id}`}
                                        onClick={() => onChange(item.id)}
                                        className={cn(
                                            'relative rounded-xl px-5 py-3 text-left text-base font-medium transition-colors',
                                            isActive
                                                ? 'bg-primary/10 text-primary'
                                                : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground',
                                        )}
                                    >
                                        {isActive && (
                                            <span
                                                aria-hidden
                                                className="absolute top-3 right-0 bottom-3 w-1 rounded-full bg-primary"
                                            />
                                        )}
                                        {item.title}
                                    </button>
                                );
                            })}
                        </div>
                    </nav>

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
