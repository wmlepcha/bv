import { router } from '@inertiajs/react';
import { Check, Folder, Home } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

export type FolderOption = {
    id: number;
    name: string;
    parent_id: number | null;
    depth: number;
};

export type RelocateTarget = {
    type: 'file' | 'folder';
    id: number;
    name: string;
    /** Parent folder id for materials, or current parent for folders (null = root). */
    currentParentId: number | null;
    /** Folder being relocated — used to disable self + descendants. */
    folderId?: number;
    disabledFolderIds?: number[];
};

type MoveCopyMaterialModalProps = {
    open: boolean;
    target: RelocateTarget | null;
    folderOptions: FolderOption[];
    onClose: () => void;
};

type DestinationKey = number | 'root';

export function MoveCopyMaterialModal({
    open,
    target,
    folderOptions,
    onClose,
}: MoveCopyMaterialModalProps) {
    const [selected, setSelected] = useState<DestinationKey | null>(null);
    const [submitting, setSubmitting] = useState<'move' | 'copy' | null>(null);

    useEffect(() => {
        if (!open) {
            setSelected(null);
            setSubmitting(null);
        }
    }, [open]);

    const disabledIds = useMemo(() => {
        if (!target || target.type !== 'folder') {
            return new Set<number>();
        }

        const ids = new Set<number>(target.disabledFolderIds ?? []);
        if (target.folderId !== undefined) {
            ids.add(target.folderId);
        }

        return ids;
    }, [target]);

    const hasSelectableDestination = useMemo(() => {
        if (!target) {
            return false;
        }

        if (target.type === 'file') {
            return folderOptions.some(
                (folder) => folder.id !== target.currentParentId,
            );
        }

        const canUseRoot = target.currentParentId !== null;
        const canUseFolder = folderOptions.some(
            (folder) =>
                !disabledIds.has(folder.id) &&
                folder.id !== target.currentParentId,
        );

        return canUseRoot || canUseFolder;
    }, [disabledIds, folderOptions, target]);

    if (!target) {
        return null;
    }

    const isCurrentRoot =
        target.type === 'folder' && target.currentParentId === null;
    const isRootSelected = selected === 'root';
    const selectedFolderId =
        selected === null || selected === 'root' ? null : selected;

    const canSubmit =
        selected !== null &&
        submitting === null &&
        !(
            target.type === 'file' &&
            selectedFolderId === target.currentParentId
        ) &&
        !(
            target.type === 'folder' &&
            selected === 'root' &&
            target.currentParentId === null
        ) &&
        !(
            target.type === 'folder' &&
            selectedFolderId !== null &&
            selectedFolderId === target.currentParentId
        );

    function submit(action: 'move' | 'copy') {
        if (!canSubmit || selected === null) {
            return;
        }

        setSubmitting(action);

        if (target.type === 'file') {
            if (selected === 'root' || selectedFolderId === null) {
                setSubmitting(null);
                return;
            }

            const url =
                action === 'move'
                    ? `/training-manager/library/files/${target.id}/move`
                    : `/training-manager/library/files/${target.id}/copy`;

            router.post(
                url,
                { folder_id: selectedFolderId },
                {
                    preserveScroll: true,
                    onSuccess: () => onClose(),
                    onFinish: () => setSubmitting(null),
                },
            );

            return;
        }

        const url =
            action === 'move'
                ? `/training-manager/library/folders/${target.id}/move`
                : `/training-manager/library/folders/${target.id}/copy`;

        router.post(
            url,
            {
                parent_id: selected === 'root' ? null : selectedFolderId,
            },
            {
                preserveScroll: true,
                onSuccess: () => onClose(),
                onFinish: () => setSubmitting(null),
            },
        );
    }

    const noun = target.type === 'folder' ? 'folder' : 'material';

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (!nextOpen && submitting === null) {
                    onClose();
                }
            }}
        >
            <DialogContent className="gap-0 overflow-hidden p-0 sm:max-w-md">
                <DialogHeader className="space-y-1.5 border-b border-border/70 px-5 py-4">
                    <DialogTitle className="pr-8 text-lg">
                        Move or copy “{target.name}”
                    </DialogTitle>
                    <DialogDescription>
                        {target.type === 'folder'
                            ? 'Choose a destination. Move relocates this folder and everything inside it; Copy creates a full duplicate without assignments.'
                            : 'Choose a destination folder. Move relocates this material; Copy leaves the original in place.'}
                    </DialogDescription>
                </DialogHeader>

                <div className="px-5 py-4">
                    {!hasSelectableDestination ? (
                        <div className="rounded-2xl border border-dashed border-border bg-muted/30 p-5 text-center">
                            <p className="text-sm font-medium">
                                Nowhere else to go
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Create another folder to move or copy this{' '}
                                {noun}.
                            </p>
                        </div>
                    ) : (
                        <div className="max-h-72 space-y-1 overflow-y-auto rounded-2xl border border-border/70 bg-muted/20 p-2">
                            {target.type === 'folder' && (
                                <button
                                    type="button"
                                    disabled={
                                        isCurrentRoot || submitting !== null
                                    }
                                    onClick={() => setSelected('root')}
                                    className={cn(
                                        'flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-sm transition-colors',
                                        isCurrentRoot &&
                                            'cursor-not-allowed opacity-50',
                                        !isCurrentRoot &&
                                            !isRootSelected &&
                                            'hover:bg-background/80',
                                        isRootSelected &&
                                            'bg-primary text-primary-foreground shadow-sm',
                                    )}
                                >
                                    <Home
                                        className={cn(
                                            'size-4 shrink-0',
                                            isRootSelected
                                                ? 'text-primary-foreground'
                                                : 'text-muted-foreground',
                                        )}
                                    />
                                    <span className="min-w-0 flex-1 truncate font-medium">
                                        Library root
                                    </span>
                                    {isCurrentRoot && (
                                        <span className="shrink-0 text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                                            Current
                                        </span>
                                    )}
                                    {isRootSelected && (
                                        <Check className="size-4 shrink-0" />
                                    )}
                                </button>
                            )}

                            {folderOptions.map((folder) => {
                                const isDisabled =
                                    target.type === 'file'
                                        ? folder.id === target.currentParentId
                                        : disabledIds.has(folder.id);
                                const isCurrentParent =
                                    folder.id === target.currentParentId;
                                const isSelected = selected === folder.id;

                                return (
                                    <button
                                        key={folder.id}
                                        type="button"
                                        disabled={
                                            isDisabled || submitting !== null
                                        }
                                        onClick={() => setSelected(folder.id)}
                                        className={cn(
                                            'flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-sm transition-colors',
                                            isDisabled &&
                                                'cursor-not-allowed opacity-50',
                                            !isDisabled &&
                                                !isSelected &&
                                                'hover:bg-background/80',
                                            isSelected &&
                                                'bg-primary text-primary-foreground shadow-sm',
                                        )}
                                        style={{
                                            paddingLeft: `${0.75 + (target.type === 'folder' ? folder.depth + 1 : folder.depth) * 0.85}rem`,
                                        }}
                                    >
                                        <Folder
                                            className={cn(
                                                'size-4 shrink-0',
                                                isSelected
                                                    ? 'text-primary-foreground'
                                                    : 'text-muted-foreground',
                                            )}
                                        />
                                        <span className="min-w-0 flex-1 truncate font-medium">
                                            {folder.name}
                                        </span>
                                        {isCurrentParent && (
                                            <span className="shrink-0 text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                                                Current
                                            </span>
                                        )}
                                        {target.type === 'folder' &&
                                            folder.id === target.folderId && (
                                                <span className="shrink-0 text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                                                    This folder
                                                </span>
                                            )}
                                        {isSelected && (
                                            <Check className="size-4 shrink-0" />
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </div>

                <DialogFooter className="gap-2 border-t border-border/70 bg-muted/20 px-5 py-4 sm:justify-between">
                    <DialogClose asChild>
                        <Button
                            type="button"
                            variant="secondary"
                            className="rounded-full"
                            disabled={submitting !== null}
                        >
                            Cancel
                        </Button>
                    </DialogClose>
                    <div className="flex flex-col-reverse gap-2 sm:flex-row">
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-full"
                            disabled={!canSubmit}
                            onClick={() => submit('copy')}
                        >
                            {submitting === 'copy' && <Spinner />}
                            Copy here
                        </Button>
                        <Button
                            type="button"
                            className="rounded-full"
                            disabled={!canSubmit}
                            onClick={() => submit('move')}
                        >
                            {submitting === 'move' && <Spinner />}
                            Move here
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
