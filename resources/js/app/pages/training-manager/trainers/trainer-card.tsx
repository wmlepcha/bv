import { Link } from '@inertiajs/react';
import {
    BadgeCheck,
    ClipboardList,
    Layers,
    Pencil,
    Trash2,
    Users,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

export type TrainerBatchSummary = {
    id: number;
    name: string;
    ends_on_label: string | null;
    is_active: boolean;
};

export type TrainerCardData = {
    id: number;
    name: string;
    email: string;
    photo_url: string | null;
    batches_count: number;
    students_count: number;
    assignments_count: number;
    batches: TrainerBatchSummary[];
};

type Props = {
    trainer: TrainerCardData;
    onDelete: (trainer: TrainerCardData) => void;
};

const AVATAR_PALETTE = [
    'from-[#2A80CC] to-[#0959B5]',
    'from-[#FC5C2E] to-[#EE3D27]',
    'from-[#FBA214] to-[#FC5C2E]',
    'from-[#04A8C8] to-[#2A80CC]',
    'from-[#0959B5] to-[#031835]',
] as const;

function avatarGradient(id: number): string {
    return AVATAR_PALETTE[Math.abs(id) % AVATAR_PALETTE.length];
}

function StatPill({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof Layers;
    label: string;
    value: number;
}) {
    return (
        <div className="rounded-xl border border-border/60 bg-muted/30 px-2.5 py-2 text-center">
            <div className="flex items-center justify-center gap-1 text-muted-foreground">
                <Icon className="size-3.5 shrink-0" />
                <span className="text-[10px] font-medium uppercase tracking-wide">
                    {label}
                </span>
            </div>
            <p className="mt-1 text-base font-semibold tabular-nums text-foreground">
                {value}
            </p>
        </div>
    );
}

export function TrainerCard({ trainer, onDelete }: Props) {
    const getInitials = useInitials();

    return (
        <article
            className={cn(
                'group relative overflow-hidden rounded-2xl border border-border/70 bg-card p-4',
                'shadow-[0_18px_40px_-28px_rgba(0,0,0,0.55)]',
                'transition hover:border-primary/30 hover:shadow-md',
            )}
        >
            <div className="absolute top-3 right-3 z-20 flex items-center gap-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-8 rounded-full text-muted-foreground hover:text-destructive"
                    aria-label={`Delete ${trainer.name}`}
                    onClick={() => onDelete(trainer)}
                >
                    <Trash2 className="size-3.5" />
                </Button>
                <Button
                    asChild
                    variant="secondary"
                    size="icon"
                    className="size-8 rounded-full"
                >
                    <Link
                        href={`/training-manager/trainers/${trainer.id}/edit`}
                        aria-label={`Edit ${trainer.name}`}
                    >
                        <Pencil className="size-3.5" />
                    </Link>
                </Button>
            </div>

            <Link
                href={`/training-manager/trainers/${trainer.id}`}
                className="block space-y-4 pr-16"
            >
                <div className="flex min-w-0 items-start gap-3">
                    <div
                        className={cn(
                            'relative size-14 shrink-0 overflow-hidden rounded-xl sm:size-16',
                            trainer.photo_url
                                ? 'bg-muted'
                                : cn(
                                      'bg-gradient-to-br',
                                      avatarGradient(trainer.id),
                                  ),
                        )}
                    >
                        {trainer.photo_url ? (
                            <img
                                src={trainer.photo_url}
                                alt=""
                                className="size-full object-cover"
                            />
                        ) : (
                            <>
                                <div className="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.22),transparent_55%)]" />
                                <div className="flex size-full items-center justify-center">
                                    <span className="text-lg font-semibold text-white/95">
                                        {getInitials(trainer.name)}
                                    </span>
                                </div>
                            </>
                        )}
                    </div>

                    <div className="min-w-0 flex-1 pt-0.5">
                        <div className="flex min-w-0 items-center gap-1.5">
                            <h2 className="truncate text-base font-semibold tracking-tight">
                                {trainer.name}
                            </h2>
                            <BadgeCheck
                                className="size-4 shrink-0 fill-emerald-500 text-white dark:text-[#0f1419]"
                                aria-label="Verified trainer"
                            />
                        </div>
                        <p className="mt-0.5 truncate text-sm text-muted-foreground">
                            {trainer.email}
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-3 gap-2">
                    <StatPill
                        icon={Layers}
                        label="Batches"
                        value={trainer.batches_count}
                    />
                    <StatPill
                        icon={Users}
                        label="Students"
                        value={trainer.students_count}
                    />
                    <StatPill
                        icon={ClipboardList}
                        label="Tasks"
                        value={trainer.assignments_count}
                    />
                </div>
            </Link>
        </article>
    );
}
