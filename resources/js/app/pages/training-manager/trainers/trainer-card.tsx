import { Link } from '@inertiajs/react';
import { BadgeCheck, Layers, Pencil, Trash2 } from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type TrainerCardData = {
    id: number;
    name: string;
    email: string;
    photo_url: string | null;
    batches_count: number;
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

export function TrainerCard({ trainer, onDelete }: Props) {
    const getInitials = useInitials();

    return (
        <article
            className={cn(
                'flex flex-col overflow-hidden rounded-[2rem] border border-border/70 bg-card p-3',
                'shadow-[0_18px_40px_-28px_rgba(0,0,0,0.55)]',
                'transition-transform duration-200 hover:-translate-y-0.5',
            )}
        >
            <div
                className={cn(
                    'relative aspect-[4/5] overflow-hidden rounded-[1.5rem]',
                    trainer.photo_url
                        ? 'bg-muted'
                        : cn('bg-gradient-to-br', avatarGradient(trainer.id)),
                )}
                aria-hidden
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
                            <span className="text-5xl font-semibold tracking-tight text-white/95 sm:text-6xl">
                                {getInitials(trainer.name)}
                            </span>
                        </div>
                    </>
                )}
            </div>

            <div className="flex flex-1 flex-col px-3 pt-4 pb-2">
                <div className="flex min-w-0 items-center gap-1.5">
                    <h2 className="truncate text-lg font-semibold tracking-tight text-foreground">
                        {trainer.name}
                    </h2>
                    <BadgeCheck
                        className="size-5 shrink-0 fill-emerald-500 text-white dark:text-[#0f1419]"
                        aria-label="Verified trainer"
                    />
                </div>

                <p className="mt-1 line-clamp-2 text-sm leading-relaxed text-muted-foreground">
                    {trainer.email}
                </p>

                <div className="mt-auto flex items-center justify-between gap-3 pt-5">
                    <div className="flex items-center gap-3 text-sm text-muted-foreground">
                        <span className="inline-flex items-center gap-1.5">
                            <Layers className="size-4 shrink-0 opacity-80" />
                            <span className="tabular-nums font-medium text-foreground">
                                {trainer.batches_count}
                            </span>
                        </span>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-9 rounded-full text-muted-foreground hover:text-destructive"
                            aria-label={`Delete ${trainer.name}`}
                            onClick={() => onDelete(trainer)}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                        <Button
                            asChild
                            variant="secondary"
                            size="sm"
                            className="rounded-full px-4 font-semibold"
                        >
                            <Link
                                href={`/training-manager/trainers/${trainer.id}/edit`}
                            >
                                <Pencil className="size-3.5" />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </article>
    );
}
