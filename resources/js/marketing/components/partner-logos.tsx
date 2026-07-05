import type { SiteLayoutPartner } from '@/types/global';
import { cn } from '@/lib/utils';

interface PartnerLogosProps {
    partners: SiteLayoutPartner[];
    partnerLabel: string;
    className?: string;
    logoClassName?: string;
    labelClassName?: string;
    showLabel?: boolean;
    layout?: 'inline' | 'stacked';
}

/** Government of Sikkim and NSDC International partner logos. */
export function PartnerLogos({
    partners,
    partnerLabel,
    className,
    logoClassName,
    labelClassName,
    showLabel = true,
    layout = 'inline',
}: PartnerLogosProps) {
    const isStacked = layout === 'stacked';

    return (
        <div
            className={cn(
                'flex items-center gap-3',
                isStacked && 'flex-col items-center gap-2 text-center',
                className,
            )}
        >
            {showLabel && (
                <p
                    className={cn(
                        'max-w-[200px] text-right text-xs leading-snug text-foreground/75',
                        isStacked && 'max-w-xs text-center',
                        labelClassName,
                    )}
                >
                    {partnerLabel}
                </p>
            )}
            <div className={cn('flex shrink-0 items-center gap-2 sm:gap-3', isStacked && 'justify-center')}>
                {partners.map((partner) => (
                    <div
                        key={partner.name}
                        className={cn(
                            'rounded-md bg-white/95 shadow-sm',
                            isStacked ? 'p-1.5' : 'p-1 sm:p-1.5',
                        )}
                    >
                        <img
                            src={partner.imageUrl}
                            alt={partner.altText}
                            className={cn('h-11 w-auto object-contain', logoClassName)}
                        />
                    </div>
                ))}
            </div>
        </div>
    );
}
