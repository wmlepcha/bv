import {
    BarChart3,
    BookOpen,
    Calendar,
    Clock,
    FileText,
    MapPin,
} from 'lucide-react';
import { ServiceImagePlaceholder } from '@marketing/components/services/service-item-card';
import { ServiceIntakePeriods } from '@marketing/components/services/service-intake-periods';
import { formatDeliveryModes } from '@marketing/components/services/service-formatters';
import type { ServiceItem } from '@marketing/types/services-page';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';

interface SpecFieldProps {
    icon: LucideIcon;
    label: string;
    children: ReactNode;
}

function SpecField({ icon: Icon, label, children, compact = false }: SpecFieldProps & { compact?: boolean }) {
    return (
        <div className={cn('flex flex-col gap-0.5', !compact && 'gap-1 sm:flex-row sm:gap-3')}>
            <div className={cn('flex shrink-0 items-start gap-2', !compact && 'sm:w-28')}>
                <Icon
                    className={cn('mt-0.5 shrink-0 text-primary', compact ? 'size-3.5' : 'size-4')}
                    aria-hidden
                />
                <span
                    className={cn(
                        'font-medium text-muted-foreground',
                        compact ? 'text-xs' : 'text-sm',
                    )}
                >
                    {label}
                </span>
            </div>
            <div
                className={cn(
                    'min-w-0 flex-1 leading-relaxed text-foreground',
                    compact ? 'pl-5 text-xs' : 'text-sm',
                )}
            >
                {children}
            </div>
        </div>
    );
}

interface ServiceLanguageVariantCardProps {
    item: ServiceItem;
    compact?: boolean;
}

export function ServiceLanguageVariantCard({ item, compact = false }: ServiceLanguageVariantCardProps) {
    const hasIntake = (item.intakePeriods?.length ?? 0) > 0;

    return (
        <article
            className={cn(
                'group relative h-full w-full min-w-0 max-w-full overflow-hidden rounded-2xl border border-primary/25',
                'bg-gradient-to-b from-primary/10 to-foreground/[0.03]',
                'transition-all hover:border-primary/40 hover:bg-foreground/[0.05]',
                compact ? 'p-3' : 'p-4 md:p-6',
            )}
        >
            <div className={cn('flex h-full flex-col', compact ? 'gap-3' : 'gap-6 lg:flex-row lg:items-stretch')}>
                <div className="mx-auto hidden w-full max-w-[8rem] shrink-0 sm:block lg:mx-0 lg:max-w-none lg:w-48 xl:w-56">
                    <ServiceImagePlaceholder
                        imageUrl={item.imageUrl}
                        imageAlt={item.imageAlt}
                        title={item.title}
                        className="aspect-square"
                    />
                </div>

                <div className={cn('min-w-0 flex-1', compact ? 'space-y-2' : 'space-y-4')}>
                    <h4 className={cn('font-semibold', compact ? 'text-sm leading-snug' : 'text-base md:text-lg')}>
                        {item.title}
                    </h4>

                    <dl className={cn(compact ? 'space-y-2' : 'space-y-3')}>
                        {item.deliveryModes && item.deliveryModes.length > 0 && (
                            <SpecField icon={BookOpen} label="Delivery" compact={compact}>
                                {formatDeliveryModes(item.deliveryModes)}
                            </SpecField>
                        )}
                        {item.levelRange && (
                            <SpecField icon={BarChart3} label="Level" compact={compact}>
                                {item.levelRange}
                            </SpecField>
                        )}
                        {item.hours && (
                            <SpecField icon={Clock} label="Hours" compact={compact}>
                                {item.hours}
                            </SpecField>
                        )}
                        {item.durationMonths && (
                            <SpecField icon={Calendar} label="Months" compact={compact}>
                                {item.durationMonths}
                            </SpecField>
                        )}
                        {item.centres && item.centres.length > 0 && (
                            <SpecField icon={MapPin} label="Centre" compact={compact}>
                                <div className="space-y-0.5">
                                    {item.centres.map((centre) => (
                                        <div key={centre}>{centre}</div>
                                    ))}
                                </div>
                            </SpecField>
                        )}
                        {item.notes && (
                            <SpecField icon={FileText} label="Notes" compact={compact}>
                                {item.notes}
                            </SpecField>
                        )}
                    </dl>
                </div>

                {hasIntake && (
                    <ServiceIntakePeriods
                        periods={item.intakePeriods!}
                        className="shrink-0 lg:w-40 xl:w-44"
                        compact={compact}
                    />
                )}
            </div>
        </article>
    );
}
