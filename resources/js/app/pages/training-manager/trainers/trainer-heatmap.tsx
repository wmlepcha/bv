import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/responsive-dialog';
import { cn } from '@/lib/utils';
import type { TrainerCardData } from './trainer-card';

// Heatmap ramp: white (no batches) -> green (light load) -> red (heaviest
// load). One continuous scale — low workload reads as calm, high as urgent.
const INTENSITY_STEPS = [
    {
        max: 0.25,
        bg: 'bg-green-100 dark:bg-green-950/50',
        border: 'border-green-200 dark:border-green-900',
        text: 'text-foreground',
    },
    {
        max: 0.5,
        bg: 'bg-green-400 dark:bg-green-700',
        border: 'border-green-500 dark:border-green-600',
        text: 'text-white',
    },
    {
        max: 0.75,
        bg: 'bg-orange-400 dark:bg-orange-600',
        border: 'border-orange-500 dark:border-orange-500',
        text: 'text-white',
    },
    {
        max: 1,
        bg: 'bg-red-500 dark:bg-red-600',
        border: 'border-red-600 dark:border-red-500',
        text: 'text-white',
    },
] as const;

const EMPTY_STEP = {
    bg: 'bg-card',
    border: 'border-border/70',
    text: 'text-foreground',
} as const;

function intensityFor(count: number, maxCount: number) {
    if (count <= 0 || maxCount <= 0) {
        return EMPTY_STEP;
    }

    const ratio = count / maxCount;

    return (
        INTENSITY_STEPS.find((step) => ratio <= step.max) ??
        INTENSITY_STEPS[INTENSITY_STEPS.length - 1]
    );
}

type Props = {
    trainers: TrainerCardData[];
};

export function TrainerHeatmap({ trainers }: Props) {
    const [openTrainer, setOpenTrainer] = useState<TrainerCardData | null>(
        null,
    );
    const maxCount = Math.max(
        0,
        ...trainers.map((trainer) => trainer.batches_count),
    );

    return (
        <>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                {trainers.map((trainer) => {
                    const intensity = intensityFor(
                        trainer.batches_count,
                        maxCount,
                    );

                    return (
                        <button
                            key={trainer.id}
                            type="button"
                            onClick={() => setOpenTrainer(trainer)}
                            className={cn(
                                'flex aspect-square flex-col items-center justify-center gap-1 rounded-2xl border p-3 text-center shadow-[0_10px_24px_-20px_rgba(0,0,0,0.5)] transition-transform hover:scale-[1.03]',
                                intensity.bg,
                                intensity.border,
                            )}
                        >
                            <span
                                className={cn(
                                    'w-full truncate text-sm font-medium',
                                    intensity.text,
                                )}
                            >
                                {trainer.name}
                            </span>
                            <span
                                className={cn(
                                    'text-3xl font-bold tabular-nums',
                                    intensity.text,
                                )}
                            >
                                {trainer.batches_count}
                            </span>
                            <span
                                className={cn(
                                    'text-[11px] tracking-wide uppercase opacity-80',
                                    intensity.text,
                                )}
                            >
                                batch{trainer.batches_count === 1 ? '' : 'es'}
                            </span>
                        </button>
                    );
                })}
            </div>

            <Dialog
                open={openTrainer !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setOpenTrainer(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{openTrainer?.name}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-1">
                        {openTrainer && openTrainer.batches.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                No batches assigned.
                            </p>
                        )}
                        {openTrainer?.batches.map((batch) => (
                            <div
                                key={batch.id}
                                className="flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 text-sm"
                            >
                                <span className="min-w-0 truncate">
                                    {batch.name}
                                </span>
                                <span className="shrink-0 text-xs tabular-nums text-muted-foreground">
                                    {batch.ends_on_label
                                        ? `${batch.is_active ? 'Ends' : 'Ended'} ${batch.ends_on_label}`
                                        : 'No end date'}
                                </span>
                            </div>
                        ))}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
