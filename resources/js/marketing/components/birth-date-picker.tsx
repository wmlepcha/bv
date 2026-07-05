import {
    format,
    isValid,
    parseISO,
    setMonth as setMonthDate,
    setYear as setYearDate,
    subDays,
    subYears,
} from 'date-fns';
import { useEffect, useMemo, useState } from 'react';
import { Calendar } from '@/components/ui/calendar';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { useIsExtraSmallPhone, useIsMobile } from '@shared/hooks/use-media-query';

interface BirthDatePickerProps {
    value: string;
    onChange: (value: string) => void;
    className?: string;
}

const selectContentClassName = 'z-[60] max-h-60';

function parseDate(value: string): Date | undefined {
    if (!value) {
        return undefined;
    }

    const parsed = parseISO(value);

    return isValid(parsed) ? parsed : undefined;
}

export function BirthDatePicker({ value, onChange, className }: BirthDatePickerProps) {
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
        <div className={cn('space-y-3 xs-short:space-y-1', className)}>
            {selected ? (
                <p className="text-sm text-muted-foreground xs-short:hidden">
                    Selected:{' '}
                    <span className="font-medium text-foreground">{format(selected, 'd MMMM yyyy')}</span>
                </p>
            ) : (
                <p className="text-sm text-muted-foreground xs-short:hidden">Select your date of birth below.</p>
            )}

            <div className="rounded-xl border border-border bg-card/50 p-2 xs-short:rounded-lg xs-short:p-1">
                <div className="grid grid-cols-2 gap-3 px-2 pt-2 pb-3 xs-short:gap-1 xs-short:px-1 xs-short:pt-1 xs-short:pb-1">
                    <div className="space-y-1.5 xs-short:space-y-0">
                        <span className="text-xs text-muted-foreground xs-short:sr-only">Month</span>
                        <Select
                            value={String(displayMonth.getMonth())}
                            onValueChange={(monthValue) => {
                                setDisplayMonth(setMonthDate(displayMonth, parseInt(monthValue, 10)));
                            }}
                        >
                            <SelectTrigger className="w-full xs-short:h-8 xs-short:text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent className={selectContentClassName}>
                                {months.map((month) => (
                                    <SelectItem key={month.value} value={month.value}>
                                        {month.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1.5 xs-short:space-y-0">
                        <span className="text-xs text-muted-foreground xs-short:sr-only">Year</span>
                        <Select
                            value={String(displayMonth.getFullYear())}
                            onValueChange={(yearValue) => {
                                setDisplayMonth(setYearDate(displayMonth, parseInt(yearValue, 10)));
                            }}
                        >
                            <SelectTrigger className="w-full xs-short:h-8 xs-short:text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent className={selectContentClassName} scrollInterval={150}>
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
