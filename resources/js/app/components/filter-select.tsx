import type { LucideIcon } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

const ALL = '__all__';

export default function FilterSelect({
    label,
    icon: Icon,
    value,
    allLabel,
    options,
    onChange,
}: {
    label: string;
    icon: LucideIcon;
    value: string | null;
    allLabel: string;
    options: { value: string; label: string }[];
    onChange: (value: string) => void;
}) {
    const active = Boolean(value);

    return (
        <Select
            value={value ?? ALL}
            onValueChange={(next) => onChange(next === ALL ? '' : next)}
        >
            <SelectTrigger
                aria-label={label}
                className={cn(
                    'h-10 rounded-full border-border bg-card px-4 shadow-sm transition-colors hover:bg-muted/60',
                    active &&
                        'border-primary/40 bg-primary/10 font-medium text-foreground',
                )}
            >
                <Icon className="size-4" />
                <SelectValue />
            </SelectTrigger>
            <SelectContent
                align="start"
                className="min-w-56 rounded-2xl border-border/60 p-1.5 shadow-xl"
            >
                <SelectItem value={ALL} className="rounded-xl py-2">
                    {allLabel}
                </SelectItem>
                {options.map((option) => (
                    <SelectItem
                        key={option.value}
                        value={option.value}
                        className="rounded-xl py-2"
                    >
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
