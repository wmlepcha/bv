import { Form, Link, router, usePage } from '@inertiajs/react';
import {
    FileUp,
    FolderPlus,
    Link2,
    MoreVertical,
    Upload,
    X,
    Youtube,
} from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import type { ChangeEvent, DragEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { MaterialFileCard } from '@app/components/material-file-card';
import type { MaterialKind } from '@app/components/material-file-card';
import { MaterialFolderCard } from '@app/components/material-folder-card';
import { MoveCopyMaterialModal } from '@app/components/move-copy-material-modal';
import type {
    FolderOption,
    RelocateTarget,
} from '@app/components/move-copy-material-modal';
import { ShareToBatchesModal } from '@app/components/share-to-batches-modal';
import type { ShareBatchOption } from '@app/components/share-to-batches-modal';
import InputError from '@shared/components/input-error';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Trainer = { id: number; name: string; email: string };

type AssignedTrainer = { id: number; name: string };

type FolderRow = {
    id: number;
    name: string;
    children_count: number;
    files_count: number;
    assigned_trainers: AssignedTrainer[];
    assigned_trainer_ids: number[];
    shared_batch_ids: number[];
};

type FileRow = {
    id: number;
    display_name: string;
    extension: string;
    kind: MaterialKind;
    url: string | null;
    embed_url: string | null;
    playlist_items?: Array<{ video_id: string; title: string }> | null;
    size: number;
    created_at: string | null;
    assigned_trainers: AssignedTrainer[];
    assigned_trainer_ids: number[];
    shared_batch_ids: number[];
};

type UploadMode = 'file' | 'youtube' | 'link';

type YoutubeMode = 'video' | 'playlist';

type DeleteTarget =
    | { type: 'folder'; id: number; name: string }
    | { type: 'file'; id: number; name: string };

type ShareTarget = {
    type: 'folder' | 'file';
    id: number;
    name: string;
    selected: number[];
};

type Props = {
    currentFolder: {
        id: number;
        name: string;
        parent_id: number | null;
    } | null;
    breadcrumbs: Array<{ id: number; name: string }>;
    folders: FolderRow[];
    files: FileRow[];
    folderOptions: FolderOption[];
    trainers: Trainer[];
    batches: ShareBatchOption[];
};

export default function LibraryIndex({
    currentFolder,
    breadcrumbs,
    folders,
    files,
    folderOptions,
    trainers,
    batches,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };
    const [assignTarget, setAssignTarget] = useState<{
        type: 'folder' | 'file';
        id: number;
        name: string;
        selected: number[];
    } | null>(null);
    const [shareTarget, setShareTarget] = useState<ShareTarget | null>(null);
    const [renameFile, setRenameFile] = useState<FileRow | null>(null);
    const [relocateTarget, setRelocateTarget] = useState<RelocateTarget | null>(
        null,
    );
    const [deleteTarget, setDeleteTarget] = useState<DeleteTarget | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [removingTrainerKey, setRemovingTrainerKey] = useState<string | null>(
        null,
    );
    const [uploadMode, setUploadMode] = useState<UploadMode>('file');
    const [youtubeMode, setYoutubeMode] = useState<YoutubeMode>('video');
    const [selectedFileName, setSelectedFileName] = useState<string | null>(
        null,
    );
    const [isDragging, setIsDragging] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const parentHref = useMemo(() => {
        if (currentFolder === null) {
            return null;
        }

        if (currentFolder.parent_id === null) {
            return '/training-manager/library';
        }

        return `/training-manager/library?folder=${currentFolder.parent_id}`;
    }, [currentFolder]);

    const folderChildrenByParent = useMemo(() => {
        const map = new Map<number | 'root', number[]>();

        for (const folder of folderOptions) {
            const key = folder.parent_id ?? 'root';
            const list = map.get(key) ?? [];
            list.push(folder.id);
            map.set(key, list);
        }

        return map;
    }, [folderOptions]);

    function descendantFolderIds(folderId: number): number[] {
        const ids: number[] = [];
        const stack = [...(folderChildrenByParent.get(folderId) ?? [])];

        while (stack.length > 0) {
            const id = stack.pop()!;
            ids.push(id);
            const children = folderChildrenByParent.get(id) ?? [];

            for (const childId of children) {
                stack.push(childId);
            }
        }

        return ids;
    }

    function confirmDelete() {
        if (!deleteTarget || deleting) {
            return;
        }

        setDeleting(true);

        const url =
            deleteTarget.type === 'folder'
                ? `/training-manager/library/folders/${deleteTarget.id}`
                : `/training-manager/library/files/${deleteTarget.id}`;

        router.delete(url, {
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    }

    function removeTrainerAssignment(
        type: 'folder' | 'file',
        id: number,
        trainerId: number,
        currentTrainers: AssignedTrainer[],
    ) {
        const key = `${type}-${id}-${trainerId}`;

        if (removingTrainerKey !== null) {
            return;
        }

        const remainingIds = currentTrainers
            .map((trainer) => trainer.id)
            .filter((currentId) => currentId !== trainerId);

        const url =
            type === 'folder'
                ? `/training-manager/library/folders/${id}/assign`
                : `/training-manager/library/files/${id}/assign`;

        setRemovingTrainerKey(key);
        router.post(
            url,
            { trainer_ids: remainingIds },
            {
                preserveScroll: true,
                onFinish: () => setRemovingTrainerKey(null),
            },
        );
    }

    return (
        <>
            <PrivateSeoHead title="Library" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Library
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Create folders, add PDF/Word/Excel/MP3/MP4 files,
                        YouTube videos, or website links, and assign them to
                        trainers.
                    </p>
                </div>

                {flash?.success && (
                    <p className="text-sm font-medium text-green-600">
                        {flash.success}
                    </p>
                )}

                <nav className="flex flex-wrap items-center gap-2 text-sm">
                    <Link
                        href="/training-manager/library"
                        className="text-muted-foreground hover:text-foreground"
                    >
                        Root
                    </Link>
                    {breadcrumbs.map((crumb) => (
                        <span
                            key={crumb.id}
                            className="flex items-center gap-2"
                        >
                            <span className="text-muted-foreground">/</span>
                            <Link
                                href={`/training-manager/library?folder=${crumb.id}`}
                                className="hover:text-foreground"
                            >
                                {crumb.name}
                            </Link>
                        </span>
                    ))}
                </nav>

                <div className="grid gap-4 lg:grid-cols-[minmax(280px,320px)_1fr]">
                    <aside className="flex flex-col gap-5 rounded-2xl border border-border/70 bg-card p-5 shadow-[0_12px_40px_-18px_rgba(3,24,53,0.35)]">
                        <Form
                            action="/training-manager/library/folders"
                            method="post"
                            className="flex flex-col gap-3"
                            resetOnSuccess
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="flex items-center gap-2">
                                        <span className="flex size-8 items-center justify-center rounded-full bg-primary/10 text-primary">
                                            <FolderPlus className="size-4" />
                                        </span>
                                        <Label
                                            htmlFor="folder-name"
                                            className="text-sm font-semibold"
                                        >
                                            New{' '}
                                            {currentFolder
                                                ? 'subfolder'
                                                : 'folder'}
                                        </Label>
                                    </div>
                                    <input
                                        type="hidden"
                                        name="parent_id"
                                        value={currentFolder?.id ?? ''}
                                    />
                                    <Input
                                        id="folder-name"
                                        name="name"
                                        required
                                        placeholder="Folder name"
                                        className="h-11 rounded-xl"
                                    />
                                    <InputError message={errors.name} />
                                    <Button
                                        type="submit"
                                        className="h-11 rounded-full"
                                        disabled={processing}
                                    >
                                        {processing && <Spinner />}
                                        Create folder
                                    </Button>
                                </>
                            )}
                        </Form>

                        {currentFolder && (
                            <div className="flex flex-col gap-4 border-t border-border/70 pt-5">
                                <div className="space-y-3">
                                    <p className="text-sm font-semibold">
                                        Add material
                                    </p>
                                    <div className="grid grid-cols-3 gap-1 rounded-full bg-muted/70 p-1">
                                        {(
                                            [
                                                {
                                                    value: 'file',
                                                    label: 'File',
                                                    icon: FileUp,
                                                },
                                                {
                                                    value: 'youtube',
                                                    label: 'YouTube',
                                                    icon: Youtube,
                                                },
                                                {
                                                    value: 'link',
                                                    label: 'Link',
                                                    icon: Link2,
                                                },
                                            ] as const
                                        ).map((mode) => {
                                            const Icon = mode.icon;
                                            const active =
                                                uploadMode === mode.value;

                                            return (
                                                <button
                                                    key={mode.value}
                                                    type="button"
                                                    onClick={() =>
                                                        setUploadMode(
                                                            mode.value,
                                                        )
                                                    }
                                                    className={cn(
                                                        'inline-flex items-center justify-center gap-1.5 rounded-full px-2 py-2 text-xs font-medium transition-all',
                                                        active
                                                            ? 'bg-primary text-primary-foreground shadow-sm'
                                                            : 'text-muted-foreground hover:bg-background/70 hover:text-foreground',
                                                    )}
                                                    aria-pressed={active}
                                                >
                                                    <Icon className="size-3.5 shrink-0" />
                                                    <span className="truncate">
                                                        {mode.label}
                                                    </span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                {uploadMode === 'file' && (
                                    <Form
                                        action={`/training-manager/library/folders/${currentFolder.id}/files`}
                                        method="post"
                                        encType="multipart/form-data"
                                        className="flex flex-col gap-4"
                                        resetOnSuccess
                                        onSuccess={() =>
                                            setSelectedFileName(null)
                                        }
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div
                                                    role="button"
                                                    tabIndex={0}
                                                    onKeyDown={(event) => {
                                                        if (
                                                            event.key ===
                                                                'Enter' ||
                                                            event.key === ' '
                                                        ) {
                                                            event.preventDefault();
                                                            fileInputRef.current?.click();
                                                        }
                                                    }}
                                                    onClick={() =>
                                                        fileInputRef.current?.click()
                                                    }
                                                    onDragEnter={(
                                                        event: DragEvent,
                                                    ) => {
                                                        event.preventDefault();
                                                        setIsDragging(true);
                                                    }}
                                                    onDragOver={(
                                                        event: DragEvent,
                                                    ) => {
                                                        event.preventDefault();
                                                        setIsDragging(true);
                                                    }}
                                                    onDragLeave={(
                                                        event: DragEvent,
                                                    ) => {
                                                        event.preventDefault();
                                                        setIsDragging(false);
                                                    }}
                                                    onDrop={(
                                                        event: DragEvent,
                                                    ) => {
                                                        event.preventDefault();
                                                        setIsDragging(false);
                                                        const file =
                                                            event.dataTransfer
                                                                .files?.[0];

                                                        if (
                                                            !file ||
                                                            !fileInputRef.current
                                                        ) {
                                                            return;
                                                        }

                                                        const transfer =
                                                            new DataTransfer();
                                                        transfer.items.add(
                                                            file,
                                                        );
                                                        fileInputRef.current.files =
                                                            transfer.files;
                                                        setSelectedFileName(
                                                            file.name,
                                                        );
                                                    }}
                                                    className={cn(
                                                        'flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border border-dashed px-4 py-8 text-center transition-all',
                                                        isDragging
                                                            ? 'border-primary bg-primary/10'
                                                            : 'border-primary/35 bg-primary/[0.04] hover:border-primary/60 hover:bg-primary/[0.07]',
                                                    )}
                                                >
                                                    <span className="flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                                        <Upload className="size-5" />
                                                    </span>
                                                    <div className="space-y-1">
                                                        <p className="text-sm leading-snug font-semibold">
                                                            Drag & drop{' '}
                                                            <span className="text-primary">
                                                                PDF
                                                            </span>
                                                            ,{' '}
                                                            <span className="text-primary">
                                                                Word
                                                            </span>
                                                            ,{' '}
                                                            <span className="text-primary">
                                                                Excel
                                                            </span>
                                                            ,{' '}
                                                            <span className="text-primary">
                                                                MP3
                                                            </span>
                                                            , or{' '}
                                                            <span className="text-primary">
                                                                MP4
                                                            </span>
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            or{' '}
                                                            <span className="font-medium text-primary underline underline-offset-2">
                                                                browse files
                                                            </span>{' '}
                                                            on your computer
                                                        </p>
                                                    </div>
                                                    {selectedFileName && (
                                                        <p className="max-w-full truncate rounded-full bg-background/80 px-3 py-1 text-xs font-medium text-foreground shadow-sm">
                                                            {selectedFileName}
                                                        </p>
                                                    )}
                                                    <input
                                                        ref={fileInputRef}
                                                        id="file"
                                                        type="file"
                                                        name="file"
                                                        required
                                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.mp3,.mp4"
                                                        className="sr-only"
                                                        onChange={(
                                                            event: ChangeEvent<HTMLInputElement>,
                                                        ) => {
                                                            setSelectedFileName(
                                                                event.target
                                                                    .files?.[0]
                                                                    ?.name ??
                                                                    null,
                                                            );
                                                        }}
                                                    />
                                                </div>
                                                <InputError
                                                    message={errors.file}
                                                />
                                                <Button
                                                    type="submit"
                                                    className="h-11 rounded-full"
                                                    disabled={processing}
                                                >
                                                    {processing && <Spinner />}
                                                    Upload
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                )}

                                {uploadMode === 'youtube' && (
                                    <div className="flex flex-col gap-4">
                                        <div className="grid grid-cols-2 gap-1 rounded-full bg-muted/70 p-1">
                                            {(
                                                [
                                                    {
                                                        value: 'video',
                                                        label: 'Video',
                                                    },
                                                    {
                                                        value: 'playlist',
                                                        label: 'Playlist',
                                                    },
                                                ] as const
                                            ).map((mode) => {
                                                const active =
                                                    youtubeMode === mode.value;

                                                return (
                                                    <button
                                                        key={mode.value}
                                                        type="button"
                                                        onClick={() =>
                                                            setYoutubeMode(
                                                                mode.value,
                                                            )
                                                        }
                                                        className={cn(
                                                            'rounded-full px-2 py-1.5 text-xs font-medium transition-all',
                                                            active
                                                                ? 'bg-primary text-primary-foreground shadow-sm'
                                                                : 'text-muted-foreground hover:bg-background/70 hover:text-foreground',
                                                        )}
                                                        aria-pressed={active}
                                                    >
                                                        {mode.label}
                                                    </button>
                                                );
                                            })}
                                        </div>

                                        {youtubeMode === 'video' ? (
                                            <Form
                                                action={`/training-manager/library/folders/${currentFolder.id}/youtube`}
                                                method="post"
                                                className="flex flex-col gap-4"
                                                resetOnSuccess
                                            >
                                                {({ processing, errors }) => (
                                                    <>
                                                        <div className="rounded-2xl border border-dashed border-primary/35 bg-primary/[0.04] p-4">
                                                            <div className="mb-4 flex items-center gap-2">
                                                                <span className="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                                                    <Youtube className="size-4" />
                                                                </span>
                                                                <div>
                                                                    <p className="text-sm font-semibold">
                                                                        YouTube
                                                                        video
                                                                    </p>
                                                                    <p className="text-xs text-muted-foreground">
                                                                        Paste a
                                                                        watch,
                                                                        share,
                                                                        or
                                                                        Shorts
                                                                        link
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div className="grid gap-3">
                                                                <div className="grid gap-1.5">
                                                                    <Label htmlFor="youtube-url">
                                                                        YouTube
                                                                        URL
                                                                    </Label>
                                                                    <Input
                                                                        id="youtube-url"
                                                                        name="url"
                                                                        type="url"
                                                                        required
                                                                        placeholder="https://www.youtube.com/watch?v=..."
                                                                        className="h-11 rounded-xl bg-background"
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors.url
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="grid gap-1.5">
                                                                    <Label htmlFor="youtube-title">
                                                                        Title{' '}
                                                                        <span className="font-normal text-muted-foreground">
                                                                            (optional)
                                                                        </span>
                                                                    </Label>
                                                                    <Input
                                                                        id="youtube-title"
                                                                        name="title"
                                                                        placeholder="Video title"
                                                                        className="h-11 rounded-xl bg-background"
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors.title
                                                                        }
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <Button
                                                            type="submit"
                                                            className="h-11 rounded-full"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            {processing && (
                                                                <Spinner />
                                                            )}
                                                            Add video
                                                        </Button>
                                                    </>
                                                )}
                                            </Form>
                                        ) : (
                                            <Form
                                                action={`/training-manager/library/folders/${currentFolder.id}/youtube-playlist`}
                                                method="post"
                                                className="flex flex-col gap-4"
                                                resetOnSuccess
                                            >
                                                {({ processing, errors }) => (
                                                    <>
                                                        <div className="rounded-2xl border border-dashed border-primary/35 bg-primary/[0.04] p-4">
                                                            <div className="mb-4 flex items-center gap-2">
                                                                <span className="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                                                    <Youtube className="size-4" />
                                                                </span>
                                                                <div>
                                                                    <p className="text-sm font-semibold">
                                                                        YouTube
                                                                        playlist
                                                                    </p>
                                                                    <p className="text-xs text-muted-foreground">
                                                                        Paste a
                                                                        playlist
                                                                        link
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div className="grid gap-3">
                                                                <div className="grid gap-1.5">
                                                                    <Label htmlFor="youtube-playlist-url">
                                                                        Playlist
                                                                        URL
                                                                    </Label>
                                                                    <Input
                                                                        id="youtube-playlist-url"
                                                                        name="url"
                                                                        type="url"
                                                                        required
                                                                        placeholder="https://www.youtube.com/playlist?list=..."
                                                                        className="h-11 rounded-xl bg-background"
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors.url
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="grid gap-1.5">
                                                                    <Label htmlFor="youtube-playlist-title">
                                                                        Title{' '}
                                                                        <span className="font-normal text-muted-foreground">
                                                                            (optional)
                                                                        </span>
                                                                    </Label>
                                                                    <Input
                                                                        id="youtube-playlist-title"
                                                                        name="title"
                                                                        placeholder="Playlist title"
                                                                        className="h-11 rounded-xl bg-background"
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors.title
                                                                        }
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <Button
                                                            type="submit"
                                                            className="h-11 rounded-full"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            {processing && (
                                                                <Spinner />
                                                            )}
                                                            Add playlist
                                                        </Button>
                                                    </>
                                                )}
                                            </Form>
                                        )}
                                    </div>
                                )}

                                {uploadMode === 'link' && (
                                    <Form
                                        action={`/training-manager/library/folders/${currentFolder.id}/links`}
                                        method="post"
                                        className="flex flex-col gap-4"
                                        resetOnSuccess
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div className="rounded-2xl border border-dashed border-secondary/40 bg-secondary/[0.06] p-4">
                                                    <div className="mb-4 flex items-center gap-2">
                                                        <span className="flex size-9 items-center justify-center rounded-xl bg-secondary/15 text-secondary">
                                                            <Link2 className="size-4" />
                                                        </span>
                                                        <div>
                                                            <p className="text-sm font-semibold">
                                                                Website link
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                Opens in a new
                                                                tab for trainers
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="grid gap-3">
                                                        <div className="grid gap-1.5">
                                                            <Label htmlFor="link-url">
                                                                Website URL
                                                            </Label>
                                                            <Input
                                                                id="link-url"
                                                                name="url"
                                                                type="url"
                                                                required
                                                                placeholder="https://example.com"
                                                                className="h-11 rounded-xl bg-background"
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.url
                                                                }
                                                            />
                                                        </div>
                                                        <div className="grid gap-1.5">
                                                            <Label htmlFor="link-title">
                                                                Title{' '}
                                                                <span className="font-normal text-muted-foreground">
                                                                    (optional)
                                                                </span>
                                                            </Label>
                                                            <Input
                                                                id="link-title"
                                                                name="title"
                                                                placeholder="Link title"
                                                                className="h-11 rounded-xl bg-background"
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.title
                                                                }
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                                <Button
                                                    type="submit"
                                                    className="h-11 rounded-full"
                                                    disabled={processing}
                                                >
                                                    {processing && <Spinner />}
                                                    Add link
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                )}
                            </div>
                        )}
                    </aside>

                    <div className="min-w-0 flex-1 space-y-4">
                        {parentHref && (
                            <Link
                                href={parentHref}
                                className="inline-flex text-sm text-muted-foreground hover:text-foreground"
                            >
                                ← Up one level
                            </Link>
                        )}

                        {folders.length === 0 && files.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                                {currentFolder
                                    ? 'This folder is empty.'
                                    : 'No folders yet. Create one to start organizing materials.'}
                            </div>
                        ) : (
                            <div className="space-y-8">
                                {folders.length > 0 && (
                                    <section className="space-y-3">
                                        <h2 className="text-sm font-medium text-muted-foreground">
                                            Folders
                                        </h2>
                                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                            {folders.map((folder) => (
                                                <div
                                                    key={`folder-${folder.id}`}
                                                    className="space-y-2"
                                                >
                                                    <MaterialFolderCard
                                                        name={folder.name}
                                                        href={`/training-manager/library?folder=${folder.id}`}
                                                        itemLabel={`${folder.children_count} folders · ${folder.files_count} files`}
                                                        colorIndex={folder.id}
                                                        assignees={
                                                            folder.assigned_trainers
                                                        }
                                                        onShare={() =>
                                                            setShareTarget({
                                                                type: 'folder',
                                                                id: folder.id,
                                                                name: folder.name,
                                                                selected:
                                                                    folder.shared_batch_ids,
                                                            })
                                                        }
                                                        menu={
                                                            <DropdownMenu>
                                                                <DropdownMenuTrigger
                                                                    asChild
                                                                >
                                                                    <Button
                                                                        type="button"
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        className="size-8 rounded-full bg-black/10 text-inherit hover:bg-black/20 hover:text-inherit"
                                                                    >
                                                                        <MoreVertical className="size-4" />
                                                                        <span className="sr-only">
                                                                            Folder
                                                                            actions
                                                                        </span>
                                                                    </Button>
                                                                </DropdownMenuTrigger>
                                                                <DropdownMenuContent align="end">
                                                                    <DropdownMenuItem
                                                                        onSelect={() =>
                                                                            setAssignTarget(
                                                                                {
                                                                                    type: 'folder',
                                                                                    id: folder.id,
                                                                                    name: folder.name,
                                                                                    selected:
                                                                                        folder.assigned_trainer_ids,
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        {folder
                                                                            .assigned_trainers
                                                                            .length >
                                                                        0
                                                                            ? 'Manage assignments'
                                                                            : 'Assign trainers'}
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem
                                                                        onSelect={() =>
                                                                            setRelocateTarget(
                                                                                {
                                                                                    type: 'folder',
                                                                                    id: folder.id,
                                                                                    name: folder.name,
                                                                                    currentParentId:
                                                                                        currentFolder?.id ??
                                                                                        null,
                                                                                    folderId:
                                                                                        folder.id,
                                                                                    disabledFolderIds:
                                                                                        descendantFolderIds(
                                                                                            folder.id,
                                                                                        ),
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        Move or
                                                                        copy
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem
                                                                        variant="destructive"
                                                                        onSelect={() =>
                                                                            setDeleteTarget(
                                                                                {
                                                                                    type: 'folder',
                                                                                    id: folder.id,
                                                                                    name: folder.name,
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        Delete
                                                                        folder
                                                                    </DropdownMenuItem>
                                                                </DropdownMenuContent>
                                                            </DropdownMenu>
                                                        }
                                                    />
                                                    <AssignmentList
                                                        trainers={
                                                            folder.assigned_trainers
                                                        }
                                                        removingKey={
                                                            removingTrainerKey
                                                        }
                                                        itemKeyPrefix={`folder-${folder.id}`}
                                                        onRemove={(trainerId) =>
                                                            removeTrainerAssignment(
                                                                'folder',
                                                                folder.id,
                                                                trainerId,
                                                                folder.assigned_trainers,
                                                            )
                                                        }
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    </section>
                                )}

                                {files.length > 0 && (
                                    <section className="space-y-3">
                                        <h2 className="text-sm font-semibold tracking-tight">
                                            Materials
                                        </h2>
                                        <div className="rounded-2xl border border-border/60 bg-muted/20 p-2 sm:p-3">
                                            {files.map((file) => (
                                                <div
                                                    key={`file-${file.id}`}
                                                    className="space-y-1"
                                                >
                                                    <MaterialFileCard
                                                        name={file.display_name}
                                                        extension={
                                                            file.extension
                                                        }
                                                        kind={
                                                            file.kind ?? 'file'
                                                        }
                                                        url={file.url}
                                                        embedUrl={
                                                            file.embed_url
                                                        }
                                                        playlistItems={
                                                            file.playlist_items
                                                        }
                                                        playlistItemsUrl={
                                                            (file.kind ??
                                                                'file') ===
                                                            'youtube_playlist'
                                                                ? `/training-manager/library/files/${file.id}/playlist-items`
                                                                : null
                                                        }
                                                        uploadedAt={
                                                            file.created_at
                                                        }
                                                        downloadHref={
                                                            (file.kind ??
                                                                'file') ===
                                                            'file'
                                                                ? `/training-manager/library/files/${file.id}/download`
                                                                : null
                                                        }
                                                        onRename={() =>
                                                            setRenameFile(file)
                                                        }
                                                        onMove={() =>
                                                            setRelocateTarget({
                                                                type: 'file',
                                                                id: file.id,
                                                                name: file.display_name,
                                                                currentParentId:
                                                                    currentFolder!
                                                                        .id,
                                                            })
                                                        }
                                                        onShare={() =>
                                                            setShareTarget({
                                                                type: 'file',
                                                                id: file.id,
                                                                name: file.display_name,
                                                                selected:
                                                                    file.shared_batch_ids,
                                                            })
                                                        }
                                                        onDelete={() =>
                                                            setDeleteTarget({
                                                                type: 'file',
                                                                id: file.id,
                                                                name: file.display_name,
                                                            })
                                                        }
                                                    />
                                                    <div className="px-3 pb-2 sm:px-4">
                                                        <AssignmentList
                                                            trainers={
                                                                file.assigned_trainers
                                                            }
                                                            removingKey={
                                                                removingTrainerKey
                                                            }
                                                            itemKeyPrefix={`file-${file.id}`}
                                                            onRemove={(
                                                                trainerId,
                                                            ) =>
                                                                removeTrainerAssignment(
                                                                    'file',
                                                                    file.id,
                                                                    trainerId,
                                                                    file.assigned_trainers,
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </section>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {assignTarget && (
                <AssignModal
                    trainers={trainers}
                    target={assignTarget}
                    onClose={() => setAssignTarget(null)}
                />
            )}

            <ShareToBatchesModal
                open={shareTarget !== null}
                batches={batches}
                target={shareTarget}
                actionUrl={
                    shareTarget === null
                        ? null
                        : shareTarget.type === 'folder'
                          ? `/training-manager/library/folders/${shareTarget.id}/share`
                          : `/training-manager/library/files/${shareTarget.id}/share`
                }
                onClose={() => setShareTarget(null)}
            />

            <MoveCopyMaterialModal
                open={relocateTarget !== null}
                target={relocateTarget}
                folderOptions={folderOptions}
                onClose={() => setRelocateTarget(null)}
            />

            {renameFile && (
                <RenameModal
                    file={renameFile}
                    onClose={() => setRenameFile(null)}
                />
            )}

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
                        <DialogTitle>
                            {deleteTarget?.type === 'folder'
                                ? 'Delete folder?'
                                : 'Delete material?'}
                        </DialogTitle>
                        <DialogDescription>
                            {deleteTarget?.type === 'folder'
                                ? `“${deleteTarget.name}” and all of its subfolders and files will be permanently deleted. This cannot be undone.`
                                : `“${deleteTarget?.name}” will be permanently deleted. This cannot be undone.`}
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
                            {deleteTarget?.type === 'folder'
                                ? 'Delete folder'
                                : 'Delete'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function AssignmentList({
    trainers,
    removingKey,
    itemKeyPrefix,
    onRemove,
}: {
    trainers: AssignedTrainer[];
    removingKey: string | null;
    itemKeyPrefix: string;
    onRemove: (trainerId: number) => void;
}) {
    if (trainers.length === 0) {
        return <p className="text-xs text-muted-foreground">Not assigned</p>;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            <span className="text-xs text-muted-foreground">Assigned to</span>
            {trainers.map((trainer) => {
                const chipKey = `${itemKeyPrefix}-${trainer.id}`;
                const isRemoving = removingKey === chipKey;

                return (
                    <span
                        key={trainer.id}
                        className="inline-flex items-center gap-1 rounded-full border border-border bg-muted/50 py-0.5 pr-1 pl-2.5 text-xs"
                    >
                        <span>{trainer.name}</span>
                        <button
                            type="button"
                            className="inline-flex size-5 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive disabled:opacity-50"
                            aria-label={`Remove ${trainer.name}`}
                            title="Remove assignment"
                            disabled={removingKey !== null}
                            onClick={() => onRemove(trainer.id)}
                        >
                            {isRemoving ? (
                                <Spinner className="size-3" />
                            ) : (
                                <X className="size-3" />
                            )}
                        </button>
                    </span>
                );
            })}
        </div>
    );
}

function AssignModal({
    trainers,
    target,
    onClose,
}: {
    trainers: Trainer[];
    target: {
        type: 'folder' | 'file';
        id: number;
        name: string;
        selected: number[];
    };
    onClose: () => void;
}) {
    const action =
        target.type === 'folder'
            ? `/training-manager/library/folders/${target.id}/assign`
            : `/training-manager/library/files/${target.id}/assign`;
    const hasAssignments = target.selected.length > 0;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md rounded-xl border border-border bg-background p-5 shadow-lg">
                <h2 className="text-lg font-semibold">
                    {hasAssignments
                        ? 'Manage assignments'
                        : `Assign ${target.name}`}
                </h2>
                <p className="mt-1 text-sm text-muted-foreground">
                    {hasAssignments
                        ? `Update which trainers can access “${target.name}”. Uncheck all to remove every assignment.`
                        : `Select trainers who should receive this ${target.type}.`}
                </p>
                <Form
                    action={action}
                    method="post"
                    className="mt-4 flex flex-col gap-4"
                    onSuccess={onClose}
                >
                    {({ processing }) => (
                        <>
                            <div className="max-h-64 space-y-2 overflow-y-auto">
                                {trainers.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No trainers available.
                                    </p>
                                ) : (
                                    trainers.map((trainer) => (
                                        <label
                                            key={trainer.id}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <input
                                                type="checkbox"
                                                name="trainer_ids[]"
                                                value={trainer.id}
                                                defaultChecked={target.selected.includes(
                                                    trainer.id,
                                                )}
                                            />
                                            <span>
                                                {trainer.name}{' '}
                                                <span className="text-muted-foreground">
                                                    ({trainer.email})
                                                </span>
                                            </span>
                                        </label>
                                    ))
                                )}
                            </div>
                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={onClose}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Save assignments
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </div>
    );
}

function RenameModal({
    file,
    onClose,
}: {
    file: FileRow;
    onClose: () => void;
}) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md rounded-xl border border-border bg-background p-5 shadow-lg">
                <h2 className="text-lg font-semibold">Rename</h2>
                <Form
                    action={`/training-manager/library/files/${file.id}`}
                    method="put"
                    className="mt-4 flex flex-col gap-4"
                    onSuccess={onClose}
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="display_name">
                                    Display name
                                </Label>
                                <Input
                                    id="display_name"
                                    name="display_name"
                                    required
                                    defaultValue={file.display_name}
                                />
                                <InputError message={errors.display_name} />
                            </div>
                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={onClose}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Rename
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </div>
    );
}

LibraryIndex.layout = {
    breadcrumbs: [
        {
            title: 'Library',
            href: '/training-manager/library',
        },
    ],
};
