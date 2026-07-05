import { ServiceSpecRow } from '@marketing/components/services/service-spec-row';
import { ServiceIntakePeriods } from '@marketing/components/services/service-intake-periods';
import { formatDeliveryModes } from '@marketing/components/services/service-formatters';
import type { ServiceItem } from '@marketing/types/services-page';
import { cn } from '@/lib/utils';

interface ServiceImagePlaceholderProps {
    imageUrl?: string | null;
    imageAlt?: string | null;
    title: string;
    className?: string;
}

export function ServiceImagePlaceholder({
    imageUrl,
    imageAlt,
    title,
    className,
}: ServiceImagePlaceholderProps) {
    if (imageUrl) {
        return (
            <img
                src={imageUrl}
                alt={imageAlt ?? title}
                className={cn('aspect-video w-full rounded-xl object-cover', className)}
            />
        );
    }

    return (
        <div
            className={cn(
                'flex aspect-video w-full items-center justify-center rounded-xl border border-dashed border-white/15 bg-white/[0.04] text-sm text-muted-foreground',
                className,
            )}
        >
            Image coming soon
        </div>
    );
}

interface ServiceItemCardProps {
    item: ServiceItem;
    compact?: boolean;
}

export function ServiceItemCard({ item, compact = false }: ServiceItemCardProps) {
    const hasSpecs =
        (item.deliveryModes?.length ?? 0) > 0 ||
        item.levelRange ||
        item.hours ||
        item.durationMonths ||
        (item.centres?.length ?? 0) > 0 ||
        (item.intakePeriods?.length ?? 0) > 0 ||
        (item.venues?.length ?? 0) > 0 ||
        item.timeline ||
        (item.certificates?.length ?? 0) > 0 ||
        (item.sectors?.length ?? 0) > 0 ||
        item.notes;

    return (
        <article
            className={cn(
                'group relative overflow-hidden rounded-2xl border border-white/10',
                'bg-gradient-to-b from-primary/10 to-white/[0.03]',
                'transition-all hover:border-primary/30 hover:bg-white/[0.05]',
                compact ? 'p-4 md:p-5' : 'p-6 md:p-8',
            )}
        >
            {!compact && (
                <span
                    aria-hidden
                    className="pointer-events-none absolute -left-2 -top-2 h-16 w-16 rounded-full bg-primary/20 blur-2xl transition-opacity group-hover:bg-primary/30"
                />
            )}

            <div className="relative">
                {!compact && (
                    <ServiceImagePlaceholder
                        imageUrl={item.imageUrl}
                        imageAlt={item.imageAlt}
                        title={item.title}
                        className="mb-4"
                    />
                )}

                <h4 className={cn('font-semibold', compact ? 'text-base' : 'text-lg')}>
                    {item.title}
                </h4>

                {hasSpecs && (
                    <dl className="mt-4 space-y-3">
                        {item.deliveryModes && item.deliveryModes.length > 0 && (
                            <ServiceSpecRow
                                label="Delivery"
                                value={formatDeliveryModes(item.deliveryModes)}
                            />
                        )}
                        {item.levelRange && <ServiceSpecRow label="Level" value={item.levelRange} />}
                        {item.hours && <ServiceSpecRow label="Hours" value={item.hours} />}
                        {item.durationMonths && (
                            <ServiceSpecRow label="Months" value={item.durationMonths} />
                        )}
                        {item.centres && item.centres.length > 0 && (
                            <div className="flex flex-col gap-1 sm:flex-row sm:gap-3">
                                <dt className="shrink-0 text-sm font-medium text-muted-foreground sm:w-28">
                                    Centre
                                </dt>
                                <dd className="space-y-1 text-sm leading-relaxed">
                                    {item.centres.map((centre) => (
                                        <div key={centre}>{centre}</div>
                                    ))}
                                </dd>
                            </div>
                        )}
                        {item.intakePeriods && item.intakePeriods.length > 0 && (
                            <div className="flex flex-col gap-2 sm:flex-row sm:gap-3">
                                <dt className="shrink-0 text-sm font-medium text-muted-foreground sm:w-28">
                                    Intake
                                </dt>
                                <dd className="min-w-0 flex-1">
                                    <ServiceIntakePeriods
                                        periods={item.intakePeriods}
                                        showLabel={false}
                                    />
                                </dd>
                            </div>
                        )}
                        {item.venues && item.venues.length > 0 && (
                            <ServiceSpecRow label="Venues" value={item.venues.join(' | ')} />
                        )}
                        {item.timeline && <ServiceSpecRow label="Timeline" value={item.timeline} />}
                        {item.certificates && item.certificates.length > 0 && (
                            <ServiceSpecRow label="Certificate" value={item.certificates.join(' | ')} />
                        )}
                        {item.sectors && item.sectors.length > 0 && (
                            <ServiceSpecRow label="Sectors" value={item.sectors.join(' | ')} />
                        )}
                        {item.notes && <ServiceSpecRow label="Notes" value={item.notes} />}
                    </dl>
                )}

            </div>
        </article>
    );
}
