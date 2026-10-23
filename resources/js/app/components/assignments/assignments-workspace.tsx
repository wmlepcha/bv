import {
    DndContext,
    DragOverlay,
    PointerSensor,
    useDraggable,
    useSensor,
    useSensors
    
    
} from '@dnd-kit/core';
import type {DragEndEvent, DragStartEvent} from '@dnd-kit/core';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, FolderPlus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/responsive-dialog';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { AssignmentCard } from '@app/components/assignments/assignment-card';
import {
    AssignmentCreatorCard
    
} from '@app/components/assignments/assignment-creator-card';
import type {AssignmentCreatorCardData} from '@app/components/assignments/assignment-creator-card';
import {
    AssignmentFolderChip,
    AssignmentRootDropZone,
} from '@app/components/assignments/assignment-folder-chip';
import type {
    AssignmentData,
    AssignmentFolder,
} from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

export type AssignmentListTab = 'published' | 'draft';

export type AssignmentCurrentCreator = {
    id: number;
    name: string;
    role: string;
    role_label: string;
    is_mine?: boolean;
};

type Props = {
    creators: AssignmentCreatorCardData[];
    assignments: AssignmentData[];
    folders: AssignmentFolder[];
    currentFolder: { id: number; name: string } | null;
    currentCreator: AssignmentCurrentCreator | null;
    initialTab?: AssignmentListTab;
    tabCounts?: {
        published: number;
        draft: number;
    };
    basePath: string;
    createPath: string;
};

const TABS: { value: AssignmentListTab; label: string }[] = [
    { value: 'published', label: 'Published' },
    { value: 'draft', label: 'Drafts' },
];

function DraggableAssignmentCard({
    assignment,
    basePath,
}: {
    assignment: AssignmentData;
    basePath: string;
}) {
    const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
        id: `assignment-${assignment.id}`,
    });

    return (
        <div ref={setNodeRef}>
            <AssignmentCard
                assignment={assignment}
                basePath={basePath}
                draggable
                dragHandleProps={{ attributes, listeners }}
                isDragging={isDragging}
            />
        </div>
    );
}

export default function AssignmentsWorkspace({
    creators,
    assignments,
    folders,
    currentFolder,
    currentCreator,
    initialTab = 'published',
    tabCounts,
    basePath,
    createPath,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };
    const [tab, setTab] = useState<AssignmentListTab>(initialTab);
    const [activeAssignmentId, setActiveAssignmentId] = useState<number | null>(
        null,
    );
    const [folderDialogOpen, setFolderDialogOpen] = useState(false);
    const [renameFolder, setRenameFolder] = useState<AssignmentFolder | null>(
        null,
    );
    const [deleteFolder, setDeleteFolder] = useState<AssignmentFolder | null>(
        null,
    );

    const createFolderForm = useForm({ name: '' });
    const renameFolderForm = useForm({ name: '' });

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: { distance: 8 },
        }),
    );

    useEffect(() => {
        setTab(initialTab);
    }, [initialTab]);

    const counts = tabCounts ?? {
        published: 0,
        draft: 0,
    };

    const creatorQuery = currentCreator
        ? { creator: currentCreator.id }
        : {};

    const selectTab = (nextTab: AssignmentListTab) => {
        setTab(nextTab);
        router.get(
            basePath,
            {
                ...creatorQuery,
                status: nextTab,
                ...(currentFolder ? { folder: currentFolder.id } : {}),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const selectFolder = (folderId: number | null) => {
        router.get(
            basePath,
            folderId === null
                ? { ...creatorQuery, status: tab }
                : { ...creatorQuery, status: tab, folder: folderId },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const handleDragStart = (event: DragStartEvent) => {
        const id = event.active.id.toString();

        if (id.startsWith('assignment-')) {
            setActiveAssignmentId(Number(id.replace('assignment-', '')));
        }
    };

    const handleDragEnd = (event: DragEndEvent) => {
        setActiveAssignmentId(null);

        const { active, over } = event;

        if (!over) {
            return;
        }

        const assignmentId = Number(
            active.id.toString().replace('assignment-', ''),
        );
        const overId = over.id.toString();

        let folderId: number | null = null;

        if (overId.startsWith('folder-') && overId !== 'folder-root') {
            folderId = Number(overId.replace('folder-', ''));
        }

        const assignment = assignments.find((item) => item.id === assignmentId);

        if (assignment?.folder_id === folderId) {
            return;
        }

        router.patch(
            `${basePath}/${assignmentId}/folder`,
            { folder_id: folderId },
            { preserveScroll: true },
        );
    };

    const submitCreateFolder = (event: React.FormEvent) => {
        event.preventDefault();
        createFolderForm.post(`${basePath}/folders`, {
            preserveScroll: true,
            onSuccess: () => {
                createFolderForm.reset();
                setFolderDialogOpen(false);
            },
        });
    };

    const submitRenameFolder = (event: React.FormEvent) => {
        event.preventDefault();

        if (!renameFolder) {
            return;
        }

        renameFolderForm.put(`${basePath}/folders/${renameFolder.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                renameFolderForm.reset();
                setRenameFolder(null);
            },
        });
    };

    const confirmDeleteFolder = () => {
        if (!deleteFolder) {
            return;
        }

        router.delete(`${basePath}/folders/${deleteFolder.id}`, {
            data: creatorQuery,
            preserveScroll: true,
            onSuccess: () => setDeleteFolder(null),
        });
    };

    const activeAssignment = assignments.find(
        (item) => item.id === activeAssignmentId,
    );

    const emptyMessage =
        currentFolder !== null
            ? `No ${tab} assignments in this folder.`
            : tab === 'published'
              ? 'No published assignments yet.'
              : 'No draft assignments yet.';

    if (currentCreator === null) {
        return (
            <>
                <PrivateSeoHead title="Assignments" />
                <div className="flex flex-col gap-5 p-2 sm:px-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                Assignments
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Browse assignments by Training Manager or
                                Faculty.
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={createPath}>Create assignment</Link>
                        </Button>
                    </div>

                    {flash?.success && (
                        <p className="text-sm font-medium text-green-600 dark:text-emerald-400">
                            {flash.success}
                        </p>
                    )}

                    {creators.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No assignments yet. Create one to get started.
                        </p>
                    ) : (
                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {creators.map((creator) => (
                                <AssignmentCreatorCard
                                    key={creator.id}
                                    creator={creator}
                                    href={`${basePath}?creator=${creator.id}`}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </>
        );
    }

    return (
        <>
            <PrivateSeoHead title={`${currentCreator.name} · Assignments`} />
            <DndContext
                sensors={sensors}
                onDragStart={handleDragStart}
                onDragEnd={handleDragEnd}
            >
                <div className="flex flex-col gap-5 p-2 sm:px-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <Button
                                type="button"
                                variant="ghost"
                                className="-ml-2 mb-1 h-8 px-2 text-muted-foreground"
                                asChild
                            >
                                <Link href={basePath}>
                                    <ArrowLeft className="mr-1.5 size-4" />
                                    All creators
                                </Link>
                            </Button>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {currentCreator.name}
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {currentCreator.role_label} · Organize, review,
                                and track their assignments.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    createFolderForm.reset();
                                    setFolderDialogOpen(true);
                                }}
                            >
                                <FolderPlus className="mr-2 size-4" />
                                New folder
                            </Button>
                            <Button asChild>
                                <Link href={createPath}>Create assignment</Link>
                            </Button>
                        </div>
                    </div>

                    {flash?.success && (
                        <p className="text-sm font-medium text-green-600 dark:text-emerald-400">
                            {flash.success}
                        </p>
                    )}

                    <div
                        className="flex gap-1 overflow-x-auto border-b border-border"
                        role="tablist"
                    >
                        {TABS.map((entry) => (
                            <button
                                key={entry.value}
                                type="button"
                                role="tab"
                                aria-selected={tab === entry.value}
                                className={cn(
                                    'shrink-0 border-b-2 px-4 py-2 text-sm font-medium transition',
                                    tab === entry.value
                                        ? 'border-primary text-foreground'
                                        : 'border-transparent text-muted-foreground hover:text-foreground',
                                )}
                                onClick={() => selectTab(entry.value)}
                            >
                                {entry.label}
                                <span className="ml-1.5 tabular-nums text-muted-foreground">
                                    ({counts[entry.value]})
                                </span>
                            </button>
                        ))}
                    </div>

                    <div className="flex gap-2 overflow-x-auto pb-1">
                        <AssignmentRootDropZone
                            isActive={currentFolder === null}
                            onSelect={() => selectFolder(null)}
                        />
                        {folders.map((folder) => (
                            <AssignmentFolderChip
                                key={folder.id}
                                folder={folder}
                                isActive={currentFolder?.id === folder.id}
                                onSelect={() => selectFolder(folder.id)}
                                onRename={() => {
                                    renameFolderForm.setData(
                                        'name',
                                        folder.name,
                                    );
                                    setRenameFolder(folder);
                                }}
                                onDelete={() => setDeleteFolder(folder)}
                            />
                        ))}
                    </div>

                    {assignments.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {emptyMessage}
                        </p>
                    ) : (
                        <div
                            className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
                            role="tabpanel"
                        >
                            {assignments.map((assignment) => (
                                <DraggableAssignmentCard
                                    key={assignment.id}
                                    assignment={assignment}
                                    basePath={basePath}
                                />
                            ))}
                        </div>
                    )}
                </div>

                <DragOverlay>
                    {activeAssignment ? (
                        <AssignmentCard
                            assignment={activeAssignment}
                            basePath={basePath}
                            isDragging
                        />
                    ) : null}
                </DragOverlay>
            </DndContext>

            <Dialog open={folderDialogOpen} onOpenChange={setFolderDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>New folder</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitCreateFolder} className="grid gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="folder-name">Folder name</Label>
                            <Input
                                id="folder-name"
                                value={createFolderForm.data.name}
                                onChange={(e) =>
                                    createFolderForm.setData(
                                        'name',
                                        e.target.value,
                                    )
                                }
                                autoFocus
                            />
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                disabled={createFolderForm.processing}
                            >
                                {createFolderForm.processing && (
                                    <Spinner className="mr-2" />
                                )}
                                Create
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={renameFolder !== null}
                onOpenChange={(open) => !open && setRenameFolder(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Rename folder</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitRenameFolder} className="grid gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="rename-folder">Folder name</Label>
                            <Input
                                id="rename-folder"
                                value={renameFolderForm.data.name}
                                onChange={(e) =>
                                    renameFolderForm.setData(
                                        'name',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                disabled={renameFolderForm.processing}
                            >
                                Save
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={deleteFolder !== null}
                onOpenChange={(open) => !open && setDeleteFolder(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete folder?</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Assignments in &ldquo;{deleteFolder?.name}&rdquo; will
                        move back to All assignments. Nothing is deleted.
                    </p>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmDeleteFolder}
                        >
                            Delete folder
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
