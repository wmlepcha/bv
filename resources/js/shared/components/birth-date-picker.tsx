import {
    format,
    isValid,
    parseISO,
    setMonth as setMonthDate,
    setYear as setYearDate,
    subDays,
    subYears,
} from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Calendar } from '@/components/ui/calendar';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/responsive-select';
import { cn } from '@/lib/utils';
import { useIsExtraSmallPhone, useIsMobile } from '@shared/hooks/use-media-query';

interface BirthDatePickerProps {
    value: string;
    onChange: (value: string) => void;
    className?: string;
    /** Compact trigger + popover panel for dense form grids */
    variant?: 'default' | 'compact';
}

const selectContentClassName = 'z-[60] max-h-60';

function parseDate(value: string): Date | undefined {
    if (!value) {
        return undefined;
    }

    const parsed = parseISO(value);

    return isValid(parsed) ? parsed : undefined;
}

function BirthDateCalendarPanel({
    value,
    onChange,
    showSelectedLabel = true,
    selectContentClassName: contentZ = selectContentClassName,
}: {
    value: string;
    onChange: (value: string) => void;
    showSelectedLabel?: boolean;
    selectContentClassName?: string;
}) {
    const isMobile = useIsMobile();
    const isExtraSmallPhone = useIsExtraSmallPhone();
    const selected = useMemo(() => parseDate(value), [value]);
    const maxDate = subDays(new Date(), 1);
    const minDate = subYears(new Date(), 100);
    const defaultMonth = selected ?? subYears(new Date(), 25);

    const [displayMonth, setDisplayMonth] = useState(defaultMonth);

    useEffect(() => {
        if (selected) {
            setDisplayMonth(selected);
        }
    }, [selected]);

    const months = useMemo(
        () =>
            Array.from({ length: 12 }, (_, index) => ({
                value: String(index),
                label: format(new Date(2024, index, 1), isMobile ? 'MMM' : 'MMMM'),
            })),
        [isMobile],
    );

    const years = useMemo(() => {
        const result: number[] = [];

        for (let year = maxDate.getFullYear(); year >= minDate.getFullYear(); year--) {
            result.push(year);
        }

        return result;
    }, [maxDate, minDate]);

    return (
        <div className="space-y-3 xs-short:space-y-1">
            {showSelectedLabel &&
                (selected ? (
                    <p className="text-sm text-muted-foreground xs-short:hidden">
                        Selected:{' '}
                        <span className="font-medium text-foreground">
                            {format(selected, 'd MMMM yyyy')}
                        </span>
                    </p>
                ) : (
                    <p className="text-sm text-muted-foreground xs-short:hidden">
                        Select your date of birth below.
                    </p>
                ))}

            <div className="rounded-xl border border-border bg-card/50 p-2 xs-short:rounded-lg xs-short:p-1">
                <div className="grid grid-cols-2 gap-3 px-2 pt-2 pb-3 xs-short:gap-1 xs-short:px-1 xs-short:pt-1 xs-short:pb-1">
                    <div className="space-y-1.5 xs-short:space-y-0">
                        <span className="text-xs text-muted-foreground xs-short:sr-only">
                            Month
                        </span>
                        <Select
                            value={String(displayMonth.getMonth())}
                            onValueChange={(monthValue) => {
                                setDisplayMonth(
                                    setMonthDate(displayMonth, parseInt(monthValue, 10)),
                                );
                            }}
                        >
                            <SelectTrigger className="w-full xs-short:h-8 xs-short:text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent className={contentZ}>
                                {months.map((month) => (
                                    <SelectItem key={month.value} value={month.value}>
                                        {month.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1.5 xs-short:space-y-0">
                        <span className="text-xs text-muted-foreground xs-short:sr-only">
                            Year
                        </span>
                        <Select
                            value={String(displayMonth.getFullYear())}
                            onValueChange={(yearValue) => {
                                setDisplayMonth(
                                    setYearDate(displayMonth, parseInt(yearValue, 10)),
                                );
                            }}
                        >
                            <SelectTrigger className="w-full xs-short:h-8 xs-short:text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent className={contentZ} scrollInterval={150}>
                                {years.map((year) => (
                                    <SelectItem key={year} value={String(year)}>
                                        {year}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <Calendar
                    mode="single"
                    month={displayMonth}
                    onMonthChange={setDisplayMonth}
                    hideNavigation
                    selected={selected}
                    onSelect={(date) => {
                        if (date) {
                            onChange(format(date, 'yyyy-MM-dd'));
                        }
                    }}
                    disabled={{ after: maxDate, before: minDate }}
                    showOutsideDays={!isExtraSmallPhone}
                    className="mx-auto w-full bg-transparent p-2 pt-0 [--cell-size:2rem] max-sm:[--cell-size:2.125rem] xs-short:p-1 xs-short:pt-0 xs-short:[--cell-size:1.5rem]"
                    classNames={{
                        weekdays: 'hidden',
                        week: 'mt-1.5 grid w-full grid-cols-7 xs-short:mt-0.5',
                        month_caption: 'hidden',
                        month: 'mx-auto w-full max-w-[calc(var(--cell-size)*7)] gap-2 xs-short:gap-0',
                        months: 'flex w-full justify-center',
                        month_grid: 'w-full',
                        root: 'mx-auto flex w-full justify-center',
                        day: 'flex items-center justify-center xs-short:[&_button]:text-[11px]',
                    }}
                />
            </div>
        </div>
    );
}

export function BirthDatePicker({
    value,
    onChange,
    className,
    variant = 'default',
}: BirthDatePickerProps) {
    const [open, setOpen] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);
    const selected = useMemo(() => parseDate(value), [value]);

    useEffect(() => {
        if (!open || variant !== 'compact') {
            return;
        }

        const isInsidePicker = (target: EventTarget | null): boolean => {
            if (!(target instanceof Element)) {
                return false;
            }

            if (rootRef.current?.contains(target)) {
                return true;
            }

            // Month/year SelectContent is portaled to <body>; treat it as inside the picker.
            return Boolean(
                target.closest(
                    '[data-slot="select-content"], [data-radix-select-viewport], [data-radix-popper-content-wrapper]',
                ),
            );
        };

        const onPointerDown = (event: MouseEvent | TouchEvent) => {
            if (!isInsidePicker(event.target)) {
                setOpen(false);
            }
        };

        // Defer so the opening click does not immediately dismiss the panel.
        const frame = window.requestAnimationFrame(() => {
            document.addEventListener('mousedown', onPointerDown);
            document.addEventListener('touchstart', onPointerDown);
        });

        return () => {
            window.cancelAnimationFrame(frame);
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('touchstart', onPointerDown);
        };
    }, [open, variant]);

    if (variant === 'compact') {
        return (
            <div ref={rootRef} className={cn('relative', className)}>
                <button
                    type="button"
                    onClick={() => setOpen((prev) => !prev)}
                    className={cn(
                        'border-input flex h-9 w-full items-center justify-between gap-2 rounded-md border bg-transparent px-3 text-left text-sm shadow-xs transition-[color,box-shadow] outline-none',
                        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                        'dark:bg-input/30 dark:hover:bg-input/50',
                        !selected && 'text-muted-foreground',
                    )}
                    aria-expanded={open}
                    aria-haspopup="dialog"
                >
                    <span className="truncate">
                        {selected ? format(selected, 'd MMMM yyyy') : 'Select date'}
                    </span>
                    <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
                </button>

                {open && (
                    <div
                        className="absolute top-full left-0 z-50 mt-2 w-[min(100vw-2rem,22rem)] rounded-xl border border-border bg-popover p-3 shadow-lg"
                        onMouseDown={(event) => event.stopPropagation()}
                        onTouchStart={(event) => event.stopPropagation()}
                    >
                        <BirthDateCalendarPanel
                            value={value}
                            showSelectedLabel={false}
                            selectContentClassName="z-[70] max-h-60"
                            onChange={(next) => {
                                onChange(next);
                                setOpen(false);
                            }}
                        />
                    </div>
                )}
            </div>
        );
    }

    return (
        <div className={cn(className)}>
            <BirthDateCalendarPanel value={value} onChange={onChange} />
        </div>
    );
}
