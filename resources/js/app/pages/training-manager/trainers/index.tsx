import { Link, router, usePage } from '@inertiajs/react';
import { Flame, LayoutGrid, Users } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
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
import { PrivateSeoHead } from '@shared/components/seo-head';
import {
    TrainerCard
    
} from './trainer-card';
import type {TrainerCardData} from './trainer-card';
import { TrainerHeatmap } from './trainer-heatmap';

type Props = {
    trainers: TrainerCardData[];
};

export default function TrainersIndex({ trainers }: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };
    const [deleteTarget, setDeleteTarget] = useState<TrainerCardData | null>(
        null,
    );
    const [deleting, setDeleting] = useState(false);
    const [view, setView] = useState<'grid' | 'heatmap'>('grid');

    function confirmDelete() {
        if (!deleteTarget || deleting) {
            return;
        }

        setDeleting(true);
        router.delete(`/training-manager/trainers/${deleteTarget.id}`, {
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    return (
        <>
            <PrivateSeoHead title="Trainers" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Trainers
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Create and manage trainers assigned to batches.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-full"
                            onClick={() =>
                                setView((current) =>
                                    current === 'grid' ? 'heatmap' : 'grid',
                                )
                            }
                        >
                            {view === 'grid' ? (
                                <>
                                    <Flame className="size-4" />
                                    View as heatmap
                                </>
                            ) : (
                                <>
                                    <LayoutGrid className="size-4" />
                                    View as grid
                                </>
                            )}
                        </Button>
                        <Button asChild className="rounded-full">
                            <Link href="/training-manager/trainers/create">
                                Add trainer
                            </Link>
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <p className="text-sm font-medium text-green-600 dark:text-emerald-400">
                        {flash.success}
                    </p>
                )}
                {flash?.error && (
                    <p className="text-sm font-medium text-destructive">
                        {flash.error}
                    </p>
                )}

                {trainers.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-border bg-card/40 p-12 text-center">
                        <div className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Users className="size-7" />
                        </div>
                        <p className="mt-4 text-sm font-medium">
                            No trainers yet
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create one to assign them to batches.
                        </p>
                        <Button asChild className="mt-5 rounded-full">
                            <Link href="/training-manager/trainers/create">
                                Add trainer
                            </Link>
                        </Button>
                    </div>
                ) : view === 'heatmap' ? (
                    <TrainerHeatmap trainers={trainers} />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        {trainers.map((trainer) => (
                            <TrainerCard
                                key={trainer.id}
                                trainer={trainer}
                                onDelete={setDeleteTarget}
                            />
                        ))}
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
                        <DialogTitle>Delete trainer?</DialogTitle>
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
                            Delete trainer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

TrainersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Trainers',
            href: '/training-manager/trainers',
        },
    ],
};
