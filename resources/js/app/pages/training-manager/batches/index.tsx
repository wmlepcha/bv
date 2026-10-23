import { Link, router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarRange,
    GraduationCap,
    MapPin,
    MoreVertical,
    Pencil,
    Trash2,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/responsive-dialog';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Batch = {
    id: number;
    name: string;
    center_name: string | null;
    language_label: string;
    level: string;
    teacher_name: string | null;
    is_active: boolean;
    enrollments_count: number;
    starts_on: string | null;
    ends_on: string | null;
    starts_on_label: string | null;
    ends_on_label: string | null;
    setup_percent: number;
};

type Props = {
    batches: Batch[];
};

const BATCH_PALETTE = [
    {
        body: 'bg-[#2A80CC]',
        soft: 'bg-white/15',
        bar: 'bg-white/25',
        fill: 'bg-white',
    },
    {
        body: 'bg-[#FC5C2E]',
        soft: 'bg-white/15',
        bar: 'bg-white/25',
        fill: 'bg-white',
    },
    {
        body: 'bg-[#FBA214]',
        soft: 'bg-white/20',
        bar: 'bg-white/30',
        fill: 'bg-white',
    },
    {
        body: 'bg-[#0959B5]',
        soft: 'bg-white/15',
        bar: 'bg-white/25',
        fill: 'bg-white',
    },
    {
        body: 'bg-[#EE3D27]',
        soft: 'bg-white/15',
        bar: 'bg-white/25',
        fill: 'bg-white',
    },
    {
        body: 'bg-[#04A8C8]',
        soft: 'bg-white/15',
        bar: 'bg-white/25',
        fill: 'bg-white',
    },
] as const;

function batchColor(id: number) {
    return BATCH_PALETTE[Math.abs(id) % BATCH_PALETTE.length];
}

/** Progress from batch setup completeness. */
function setupFill(percent: number): number {
    return Math.max(8, Math.min(100, percent));
}

function formatCardDates(batch: Batch): string | null {
    if (batch.starts_on_label && batch.ends_on_label) {
        return `${batch.starts_on_label} – ${batch.ends_on_label}`;
    }

    if (batch.starts_on_label) {
        return `Starts ${batch.starts_on_label}`;
    }

    if (batch.ends_on_label) {
        return `Ends ${batch.ends_on_label}`;
    }

    return null;
}

export default function BatchesIndex({ batches }: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };
    const [deleteTarget, setDeleteTarget] = useState<Batch | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState<number | null>(null);

    function confirmDelete() {
        if (!deleteTarget || deleting) {
            return;
        }

        setDeleting(true);
        router.delete(`/training-manager/batches/${deleteTarget.id}`, {
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    function toggleActive(batch: Batch, nextActive: boolean) {
        if (togglingId !== null || batch.is_active === nextActive) {
            return;
        }

        setTogglingId(batch.id);
        router.patch(
            `/training-manager/batches/${batch.id}/active`,
            { is_active: nextActive },
            {
                preserveScroll: true,
                onFinish: () => setTogglingId(null),
            },
        );
    }

    return (
        <>
            <PrivateSeoHead title="Batches" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Batches
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Create batches, assign trainers, and enroll
                            students.
                        </p>
                    </div>
                    <Button asChild className="rounded-full">
                        <Link href="/training-manager/batches/create">
                            Add batch
                        </Link>
                    </Button>
                </div>

                {flash?.success && (
                    <p className="text-sm font-medium text-green-600 dark:text-emerald-400">
                        {flash.success}
                    </p>
                )}

                {batches.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-border bg-card/40 p-12 text-center">
                        <div className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <GraduationCap className="size-7" />
                        </div>
                        <p className="mt-4 text-sm font-medium">
                            No batches yet
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create a batch to assign trainers and enroll
                            students.
                        </p>
                        <Button asChild className="mt-5 rounded-full">
                            <Link href="/training-manager/batches/create">
                                Add batch
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {batches.map((batch) => {
                            const colors = batchColor(batch.id);
                            const fill = setupFill(batch.setup_percent);
                            const dateLabel = formatCardDates(batch);

                            return (
                                <article
                                    key={batch.id}
                                    className={cn(
                                        'group relative flex min-h-[210px] flex-col overflow-hidden rounded-[1.75rem] p-5 text-white shadow-[0_18px_40px_-24px_rgba(3,24,53,0.55)] transition-transform duration-200 hover:-translate-y-0.5',
                                        colors.body,
                                    )}
                                >
                                    <Link
                                        href={`/training-manager/batches/${batch.id}`}
                                        className="absolute inset-0 z-0 rounded-[1.75rem]"
                                        aria-label={`View ${batch.name}`}
                                    />
                                    <div className="relative z-10 flex items-start justify-between gap-3">
                                        <div className="flex min-w-0 items-start gap-3">
                                            <span
                                                className={cn(
                                                    'flex size-12 shrink-0 items-center justify-center rounded-2xl',
                                                    colors.soft,
                                                )}
                                            >
                                                <GraduationCap className="size-6" />
                                            </span>
                                            <div className="min-w-0">
                                                <h2 className="truncate text-lg font-semibold tracking-tight">
                                                    {batch.name}
                                                </h2>
                                                <p className="mt-0.5 text-sm text-white/80">
                                                    {batch.language_label}{' '}
                                                    {batch.level}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex shrink-0 items-center gap-2">
                                            <button
                                                type="button"
                                                role="switch"
                                                aria-checked={batch.is_active}
                                                aria-label={
                                                    batch.is_active
                                                        ? 'Deactivate batch'
                                                        : 'Activate batch'
                                                }
                                                disabled={
                                                    togglingId === batch.id
                                                }
                                                onClick={(event) => {
                                                    event.preventDefault();
                                                    event.stopPropagation();
                                                    toggleActive(
                                                        batch,
                                                        !batch.is_active,
                                                    );
                                                }}
                                                className={cn(
                                                    'inline-flex h-8 items-center gap-1.5 rounded-full px-1.5 transition-colors',
                                                    'bg-white/20 hover:bg-white/30 disabled:opacity-60',
                                                )}
                                            >
                                                <span
                                                    className={cn(
                                                        'relative h-5 w-9 rounded-full transition-colors',
                                                        batch.is_active
                                                            ? 'bg-white'
                                                            : 'bg-white/35',
                                                    )}
                                                >
                                                    <span
                                                        className={cn(
                                                            'absolute top-0.5 size-4 rounded-full transition-all',
                                                            batch.is_active
                                                                ? 'left-4 bg-[#031835]'
                                                                : 'left-0.5 bg-white',
                                                        )}
                                                    />
                                                </span>
                                                <span className="pr-1.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                                                    {togglingId === batch.id
                                                        ? '…'
                                                        : batch.is_active
                                                          ? 'Active'
                                                          : 'Off'}
                                                </span>
                                            </button>
                                            <span className="inline-flex h-8 min-w-10 items-center justify-center rounded-xl bg-white px-2 text-sm font-semibold tabular-nums text-[#031835]">
                                                {batch.enrollments_count}
                                            </span>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <button
                                                        type="button"
                                                        className="relative z-20 inline-flex size-8 items-center justify-center rounded-full bg-white/15 text-white transition-colors hover:bg-white/25"
                                                        aria-label="Batch actions"
                                                        onClick={(event) =>
                                                            event.stopPropagation()
                                                        }
                                                    >
                                                        <MoreVertical className="size-4" />
                                                    </button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={`/training-manager/batches/${batch.id}/edit`}
                                                        >
                                                            <Pencil className="size-4" />
                                                            Edit
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onSelect={() =>
                                                            setDeleteTarget(
                                                                batch,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                        Delete
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    </div>

                                    <div className="relative z-10 mt-5 space-y-2 text-sm text-white/85">
                                        {dateLabel && (
                                            <p className="flex items-center gap-2">
                                                <CalendarRange className="size-3.5 shrink-0 opacity-80" />
                                                <span className="truncate">
                                                    {dateLabel}
                                                </span>
                                            </p>
                                        )}
                                        <p className="flex items-center gap-2">
                                            <MapPin className="size-3.5 shrink-0 opacity-80" />
                                            <span className="truncate">
                                                {batch.center_name ??
                                                    'No center'}
                                            </span>
                                        </p>
                                        <p className="flex items-center gap-2">
                                            <Users className="size-3.5 shrink-0 opacity-80" />
                                            <span className="truncate">
                                                {batch.teacher_name ??
                                                    'No trainer assigned'}
                                            </span>
                                        </p>
                                    </div>

                                    <div className="relative z-10 mt-auto pt-6">
                                        <div
                                            className={cn(
                                                'h-1.5 overflow-hidden rounded-full',
                                                colors.bar,
                                            )}
                                            aria-hidden
                                        >
                                            <div
                                                className={cn(
                                                    'h-full rounded-full transition-all',
                                                    colors.fill,
                                                )}
                                                style={{ width: `${fill}%` }}
                                            />
                                        </div>

                                        <div className="mt-4 flex items-center justify-between gap-3">
                                            <p className="text-xs text-white/75">
                                                {batch.enrollments_count}{' '}
                                                {batch.enrollments_count === 1
                                                    ? 'student'
                                                    : 'students'}
                                            </p>
                                            <Link
                                                href={`/training-manager/batches/${batch.id}/students`}
                                                className="relative z-20 inline-flex items-center gap-1.5 text-sm font-semibold text-white transition-opacity hover:opacity-90"
                                                onClick={(event) =>
                                                    event.stopPropagation()
                                                }
                                            >
                                                Students
                                                <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                            </Link>
                                        </div>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}
            </div>

            <Dialog
                open={deleteTarget !== null}
                onOpenChange={(open) => {
                    if (!open && !deleting) {
                        setDeleteTarget(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete batch?</DialogTitle>
                        <DialogDescription>
                            “{deleteTarget?.name}” will be permanently deleted.
                            This cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" disabled={deleting}>
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            disabled={deleting}
                            onClick={confirmDelete}
                        >
                            {deleting && <Spinner />}
                            Delete batch
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

BatchesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Batches',
            href: '/training-manager/batches',
        },
    ],
};
