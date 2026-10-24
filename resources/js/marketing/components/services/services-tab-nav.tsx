import { cn } from '@/lib/utils';
import type { ServiceCategory } from '@marketing/types/services-page';

interface ServicesTabNavProps {
    categories: ServiceCategory[];
    activeId: string;
    onChange: (id: string) => void;
}

/** Phone-only iOS-style segmented control, pinned under the site header. Desktop uses the side nav. */
export function ServicesTabNav({
    categories,
    activeId,
    onChange,
}: ServicesTabNavProps) {
    const activeIndex = Math.max(
        0,
        categories.findIndex((category) => category.id === activeId),
    );

    return (
        <nav
            aria-label="Services sections"
            className="sticky top-[var(--marketing-header-height,5.5rem)] z-30 border-b border-border/60 bg-background/85 px-4 py-2.5 backdrop-blur-xl sm:px-6 lg:hidden"
        >
            <div
                role="tablist"
                className="relative mx-auto grid max-w-md auto-cols-fr grid-flow-col rounded-xl bg-muted p-1"
            >
                <span
                    aria-hidden
                    className="absolute inset-y-1 left-1 rounded-lg bg-card shadow-sm transition-transform duration-300 ease-out"
                    style={{
                        width: `calc((100% - 0.5rem) / ${categories.length})`,
                        transform: `translateX(${activeIndex * 100}%)`,
                    }}
                />
                {categories.map((category) => {
                    const isActive = category.id === activeId;

                    return (
                        <button
                            key={category.id}
                            type="button"
                            role="tab"
                            aria-selected={isActive}
                            aria-controls={`services-panel-${category.id}`}
                            id={`services-tab-${category.id}`}
                            onClick={() => onChange(category.id)}
                            className={cn(
                                'relative z-10 h-10 truncate rounded-lg px-2 text-sm font-medium transition-[color,transform] duration-200 active:scale-[0.97]',
                                isActive
                                    ? 'text-foreground'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {category.title}
                        </button>
                    );
                })}
            </div>
        </nav>
    );
}
