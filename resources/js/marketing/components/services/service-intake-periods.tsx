import { CalendarDays } from 'lucide-react';
import { cn } from '@/lib/utils';

interface ServiceIntakePeriodsProps {
    periods: string[];
    className?: string;
    pillClassName?: string;
    showLabel?: boolean;
    compact?: boolean;
}

export function ServiceIntakePeriods({
    periods,
    className,
    pillClassName,
    showLabel = true,
    compact = false,
}: ServiceIntakePeriodsProps) {
    if (periods.length === 0) {
        return null;
    }

    const pillClasses = cn(
        'rounded-full border border-primary/20 bg-primary/15 text-center font-medium text-primary',
        pillClassName,
    );

    return (
        <div className={cn('min-w-0 w-full', className)}>
            {showLabel && (
                <div
                    className={cn(
                        'flex items-center gap-2 font-medium text-muted-foreground',
                        compact ? 'text-xs' : 'text-sm',
                    )}
                >
                    <CalendarDays
                        className={cn('text-primary', compact ? 'size-3.5' : 'size-4')}
                        aria-hidden
                    />
                    Intake
                </div>
            )}

            <div
                className={cn(
                    'grid grid-cols-2 gap-2',
                    showLabel && (compact ? 'mt-2' : 'mt-3'),
                )}
            >
                {periods.map((period) => (
                    <span
                        key={period}
                        className={cn(
                            pillClasses,
                            compact ? 'px-2 py-1 text-xs' : 'px-3 py-1.5 text-xs lg:text-sm',
                        )}
                    >
                        {period}
                    </span>
                ))}
            </div>
        </div>
    );
}
