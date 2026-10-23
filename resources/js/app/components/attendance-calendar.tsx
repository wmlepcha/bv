import { Check, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { cn } from '@/lib/utils';

export type DayStatus = 'present' | 'excused' | 'absent';
export type StatusMap = Record<string, DayStatus>;

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export function toKey(date: Date): string {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
}

export function fromKey(key: string): Date {
    return new Date(`${key}T00:00:00`);
}

function addDays(date: Date, days: number): Date {
    const next = new Date(date);
    next.setDate(next.getDate() + days);

    return next;
}

export function statusText(status: DayStatus | undefined): string {
    return status === 'present'
        ? 'you were present'
        : status === 'excused'
          ? 'you were on leave'
          : status === 'absent'
            ? 'you were absent'
            : 'there was no class';
}

function StatusCircle({
    status,
    selected,
}: {
    status: DayStatus | undefined;
    selected: boolean;
}) {
    return (
        <span
            className={cn(
                'flex size-9 items-center justify-center rounded-full border-2 text-xs font-bold transition-colors',
                status === 'present' &&
                    (selected
                        ? 'border-white bg-white text-primary'
                        : 'border-primary bg-primary text-primary-foreground'),
                status === 'excused' &&
                    (selected
                        ? 'border-white bg-white text-sky-600'
                        : 'border-sky-500 bg-sky-500 text-white'),
                status === 'absent' &&
                    (selected
                        ? 'border-white bg-white text-muted-foreground'
                        : 'border-muted-foreground/30 bg-muted text-muted-foreground'),
                status === undefined &&
                    (selected
                        ? 'border-white/70 bg-white/20'
                        : 'border-border bg-card'),
            )}
        >
            {status === 'present' && (
                <Check className="size-4" strokeWidth={3} />
            )}
            {status === 'excused' && 'L'}
            {status === 'absent' && <X className="size-4" strokeWidth={3} />}
        </span>
    );
}

export function WeekStrip({
    selected,
    onSelect,
    statuses,
}: {
    selected: Date;
    onSelect: (date: Date) => void;
    statuses: StatusMap;
}) {
    const start = addDays(selected, -selected.getDay());
    const days = Array.from({ length: 7 }, (_, index) => addDays(start, index));
    const label = selected.toLocaleDateString('en-GB', {
        month: 'long',
        year: 'numeric',
    });

    return (
        <section className="rounded-3xl border border-border bg-card/60 p-4 shadow-sm">
            <div className="mb-3 flex items-center justify-between gap-3">
                <h2 className="text-sm font-semibold">{label}</h2>
                <div className="flex gap-1">
                    <button
                        type="button"
                        aria-label="Previous week"
                        onClick={() => onSelect(addDays(selected, -7))}
                        className="flex size-8 items-center justify-center rounded-full bg-muted active:scale-90"
                    >
                        <ChevronLeft className="size-4" />
                    </button>
                    <button
                        type="button"
                        aria-label="Next week"
                        onClick={() => onSelect(addDays(selected, 7))}
                        className="flex size-8 items-center justify-center rounded-full bg-muted active:scale-90"
                    >
                        <ChevronRight className="size-4" />
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-7 gap-1">
                {days.map((day) => {
                    const key = toKey(day);
                    const isSelected = key === toKey(selected);

                    return (
                        <button
                            key={key}
                            type="button"
                            onClick={() => onSelect(day)}
                            aria-pressed={isSelected}
                            className={cn(
                                'flex flex-col items-center gap-1.5 rounded-full px-1 py-2 transition-colors',
                                isSelected
                                    ? 'bg-primary text-primary-foreground shadow-md'
                                    : 'text-foreground',
                            )}
                        >
                            <StatusCircle
                                status={statuses[key]}
                                selected={isSelected}
                            />
                            <span className="text-xs font-medium">
                                {WEEKDAYS[day.getDay()]}
                            </span>
                            <span className="text-lg leading-none font-semibold tabular-nums">
                                {day.getDate()}
                            </span>
                        </button>
                    );
                })}
            </div>

            <p className="mt-4 text-sm text-muted-foreground">
                On this day,{' '}
                <span className="font-semibold text-foreground">
                    {statusText(statuses[toKey(selected)])}
                </span>
            </p>
        </section>
    );
}

export function MonthCalendar({
    selected,
    onSelect,
    statuses,
}: {
    selected: Date;
    onSelect: (date: Date) => void;
    statuses: StatusMap;
}) {
    const monthStart = new Date(selected.getFullYear(), selected.getMonth(), 1);
    const gridStart = addDays(monthStart, -monthStart.getDay());
    const cells = Array.from({ length: 42 }, (_, index) =>
        addDays(gridStart, index),
    );
    const todayKey = toKey(new Date());

    const shiftMonth = (delta: number) => {
        const target = new Date(
            selected.getFullYear(),
            selected.getMonth() + delta,
            1,
        );
        const lastDay = new Date(
            target.getFullYear(),
            target.getMonth() + 1,
            0,
        ).getDate();
        target.setDate(Math.min(selected.getDate(), lastDay));
        onSelect(target);
    };

    return (
        <section className="rounded-3xl border border-border bg-card/60 p-4 shadow-sm">
            <div className="mb-4 flex items-center justify-between gap-3">
                <h2 className="text-xl font-semibold">Select Date</h2>
                <div className="flex items-center gap-1 rounded-full bg-muted px-1 py-1">
                    <button
                        type="button"
                        aria-label="Previous month"
                        onClick={() => shiftMonth(-1)}
                        className="flex size-7 items-center justify-center rounded-full active:scale-90"
                    >
                        <ChevronLeft className="size-4" />
                    </button>
                    <span className="min-w-20 text-center text-xs font-medium">
                        {selected.toLocaleDateString('en-GB', {
                            month: 'short',
                            year: 'numeric',
                        })}
                    </span>
                    <button
                        type="button"
                        aria-label="Next month"
                        onClick={() => shiftMonth(1)}
                        className="flex size-7 items-center justify-center rounded-full active:scale-90"
                    >
                        <ChevronRight className="size-4" />
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-7 gap-y-1 text-center">
                {WEEKDAYS.map((weekday) => (
                    <span
                        key={weekday}
                        className="pb-2 text-xs font-medium text-muted-foreground"
                    >
                        {weekday}
                    </span>
                ))}
                {cells.map((day) => {
                    const key = toKey(day);
                    const status = statuses[key];
                    const inMonth = day.getMonth() === selected.getMonth();
                    const isSelected = key === toKey(selected);

                    return (
                        <button
                            key={key}
                            type="button"
                            onClick={() => onSelect(day)}
                            aria-label={`${day.toDateString()}${status ? `, ${status === 'excused' ? 'leave' : status}` : ''}`}
                            aria-pressed={isSelected}
                            className="flex items-center justify-center py-0.5"
                        >
                            <span
                                className={cn(
                                    'flex size-9 items-center justify-center rounded-full text-sm tabular-nums transition-colors',
                                    !inMonth && 'text-muted-foreground/40',
                                    inMonth && !status && 'text-foreground',
                                    status === 'present' &&
                                        'bg-emerald-500/20 font-semibold text-emerald-600',
                                    status === 'excused' &&
                                        'bg-sky-500/20 font-semibold text-sky-600',
                                    status === 'absent' &&
                                        'bg-destructive/15 font-semibold text-destructive',
                                    key === todayKey &&
                                        !isSelected &&
                                        'ring-2 ring-primary/60',
                                    isSelected &&
                                        'bg-primary font-semibold text-primary-foreground shadow',
                                )}
                            >
                                {day.getDate()}
                            </span>
                        </button>
                    );
                })}
            </div>

            <ul className="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-muted-foreground">
                {(
                    [
                        ['Present', 'bg-emerald-500'],
                        ['Leave', 'bg-sky-500'],
                        ['Absent', 'bg-destructive'],
                    ] as const
                ).map(([label, color]) => (
                    <li key={label} className="flex items-center gap-1.5">
                        <span className={cn('size-2.5 rounded-full', color)} />
                        {label}
                    </li>
                ))}
            </ul>
        </section>
    );
}
