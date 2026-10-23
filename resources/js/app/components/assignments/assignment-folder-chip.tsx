import { useDroppable } from '@dnd-kit/core';
import { Folder } from 'lucide-react';
import { cn } from '@/lib/utils';
import { folderColor } from '@app/components/material-folder-card';
import type { AssignmentFolder } from '@app/types/assignments';

type Props = {
    folder: AssignmentFolder;
    isActive: boolean;
    onSelect: () => void;
    onRename?: () => void;
    onDelete?: () => void;
};

export function AssignmentFolderChip({
    folder,
    isActive,
    onSelect,
    onRename,
    onDelete,
}: Props) {
    const { setNodeRef, isOver } = useDroppable({
        id: `folder-${folder.id}`,
    });
    const colors = folderColor(folder.id);

    return (
        <div
            ref={setNodeRef}
            className={cn(
                'group relative shrink-0',
                isOver && 'scale-[1.02]',
            )}
        >
            <button
                type="button"
                onClick={onSelect}
                className={cn(
                    'flex min-w-[120px] max-w-[180px] flex-col rounded-xl border px-3 py-2.5 text-left transition',
                    colors.body,
                    colors.text,
                    isActive
                        ? 'ring-2 ring-primary ring-offset-2 ring-offset-background'
                        : 'hover:-translate-y-0.5 hover:shadow-sm',
                    isOver && 'ring-2 ring-primary/60',
                )}
            >
                <div className="flex items-center gap-1.5">
                    <Folder className={cn('size-3.5 shrink-0', colors.muted)} />
                    <span className="truncate text-sm font-medium">
                        {folder.name}
                    </span>
                </div>
                <span className={cn('mt-1 text-xs', colors.muted)}>
                    {folder.assignments_count} in tab
                </span>
            </button>
            {(onRename || onDelete) && (
                <div className="absolute -top-1 -right-1 hidden gap-0.5 group-hover:flex">
                    {onRename && (
                        <button
                            type="button"
                            onClick={(event) => {
                                event.stopPropagation();
                                onRename();
                            }}
                            className="rounded bg-background px-1.5 py-0.5 text-[10px] shadow-sm"
                        >
                            Rename
                        </button>
                    )}
                    {onDelete && (
                        <button
                            type="button"
                            onClick={(event) => {
                                event.stopPropagation();
                                onDelete();
                            }}
                            className="rounded bg-background px-1.5 py-0.5 text-[10px] text-destructive shadow-sm"
                        >
                            Delete
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

export function AssignmentRootDropZone({
    isActive,
    onSelect,
}: {
    isActive: boolean;
    onSelect: () => void;
}) {
    const { setNodeRef, isOver } = useDroppable({
        id: 'folder-root',
    });

    return (
        <button
            ref={setNodeRef}
            type="button"
            onClick={onSelect}
            className={cn(
                'shrink-0 rounded-xl border border-dashed border-border px-3 py-2.5 text-left transition',
                isActive && 'border-primary bg-primary/5 ring-2 ring-primary ring-offset-2 ring-offset-background',
                isOver && 'border-primary bg-primary/10',
                !isActive && 'hover:border-primary/40 hover:bg-muted/30',
            )}
        >
            <span className="text-sm font-medium">All assignments</span>
            <span className="mt-0.5 block text-xs text-muted-foreground">
                Unfiled
            </span>
        </button>
    );
}
