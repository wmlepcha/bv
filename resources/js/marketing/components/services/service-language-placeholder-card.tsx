import { ServiceImagePlaceholder } from '@marketing/components/services/service-item-card';
import type { ServiceItem } from '@marketing/types/services-page';
import { cn } from '@/lib/utils';

interface ServiceLanguagePlaceholderCardProps {
    item: ServiceItem;
}

export function ServiceLanguagePlaceholderCard({ item }: ServiceLanguagePlaceholderCardProps) {
    return (
        <div className="min-w-0 w-full space-y-3">
            <h4 className="text-base font-semibold text-secondary">{item.title}</h4>

            <article
                className={cn(
                    'w-full overflow-hidden rounded-2xl border border-white/10',
                    'bg-gradient-to-b from-primary/10 to-white/[0.03]',
                    'p-4 md:p-6',
                )}
            >
                <ServiceImagePlaceholder
                    imageUrl={item.imageUrl}
                    imageAlt={item.imageAlt}
                    title={item.title}
                    className="aspect-[4/3]"
                />
            </article>
        </div>
    );
}
