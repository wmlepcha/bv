import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';

export type SheetFilters = {
    profession: string;
    experience: string;
    level: string;
};

type Option = { value: string; label: string };

function Group({
    title,
    value,
    options,
    onChange,
}: {
    title: string;
    value: string;
    options: Option[];
    onChange: (value: string) => void;
}) {
    return (
        <div
            role="radiogroup"
            aria-label={title}
            className="flex flex-col gap-3"
        >
            <h3 className="text-sm font-semibold">{title}</h3>
            <div className="flex flex-wrap gap-2">
                {[{ value: '', label: 'Any' }, ...options].map((option) => {
                    const selected = value === option.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            role="radio"
                            aria-checked={selected}
                            onClick={() => onChange(option.value)}
                            className={cn(
                                'rounded-full border px-4 py-2 text-sm transition-all active:scale-95',
                                selected
                                    ? 'border-primary bg-primary font-medium text-primary-foreground shadow-sm'
                                    : 'border-border bg-card text-foreground',
                            )}
                        >
                            {option.label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

export default function StudentsFilterSheet({
    open,
    onOpenChange,
    current,
    professions,
    levels,
    onApply,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    current: SheetFilters;
    professions: Option[];
    levels: Option[];
    onApply: (filters: SheetFilters) => void;
}) {
    const [draft, setDraft] = useState<SheetFilters>(current);

    const handleOpenChange = (next: boolean) => {
        if (next) {
            setDraft(current);
        }

        onOpenChange(next);
    };

    return (
        <Sheet open={open} onOpenChange={handleOpenChange}>
            <SheetContent
                side="right"
                className="w-[88vw] gap-0 p-0 pt-[env(safe-area-inset-top)] sm:max-w-sm"
            >
                <SheetHeader className="border-b border-border/60 p-5">
                    <SheetTitle>Filters</SheetTitle>
                    <SheetDescription>
                        Narrow down the candidate list.
                    </SheetDescription>
                </SheetHeader>

                <div className="flex flex-1 flex-col gap-7 overflow-y-auto p-5">
                    <Group
                        title="Profession"
                        value={draft.profession}
                        options={professions}
                        onChange={(profession) =>
                            setDraft((d) => ({ ...d, profession }))
                        }
                    />
                    <Group
                        title="Experience"
                        value={draft.experience}
                        options={[
                            { value: 'fresher', label: 'Fresher' },
                            { value: 'experienced', label: 'Experienced' },
                        ]}
                        onChange={(experience) =>
                            setDraft((d) => ({ ...d, experience }))
                        }
                    />
                    <Group
                        title="Language level"
                        value={draft.level}
                        options={levels}
                        onChange={(level) => setDraft((d) => ({ ...d, level }))}
                    />
                </div>

                <SheetFooter className="flex-row gap-3 border-t border-border/60 p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
                    <Button
                        type="button"
                        variant="outline"
                        className="h-11 flex-1 rounded-full"
                        onClick={() =>
                            setDraft({
                                profession: '',
                                experience: '',
                                level: '',
                            })
                        }
                    >
                        Reset
                    </Button>
                    <Button
                        type="button"
                        className="h-11 flex-[2] rounded-full"
                        onClick={() => {
                            onApply(draft);
                            onOpenChange(false);
                        }}
                    >
                        Show results
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
