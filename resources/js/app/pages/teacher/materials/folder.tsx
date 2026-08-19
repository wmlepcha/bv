import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { MaterialFileCard } from '@app/components/material-file-card';
import { MaterialFolderCard } from '@app/components/material-folder-card';
import {
    ShareToBatchesModal,
    type ShareBatchOption,
} from '@app/components/share-to-batches-modal';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { Button } from '@/components/ui/button';

type Props = {
    folder: {
        id: number;
        name: string;
        parent_id: number | null;
        shared_batch_ids?: number[];
    };
    breadcrumbs: Array<{ id: number; name: string }>;
    folders: Array<{
        id: number;
        name: string;
        type: string;
        shared_batch_ids?: number[];
    }>;
    files: Array<{
        id: number;
        name: string;
        extension: string;
        kind?: 'file' | 'youtube' | 'link';
        url?: string | null;
        embed_url?: string | null;
        created_at: string | null;
        type: string;
        shared_batch_ids?: number[];
    }>;
    batches: ShareBatchOption[];
};

type ShareTarget = {
    type: 'folder' | 'file';
    id: number;
    name: string;
    selected: number[];
};

export default function TeacherMaterialFolder({
    folder,
    breadcrumbs,
    folders,
    files,
    batches,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };
    const [shareTarget, setShareTarget] = useState<ShareTarget | null>(null);

    const backHref =
        folder.parent_id !== null
            ? `/teacher/materials/folders/${folder.parent_id}`
            : '/teacher/materials';

    const shareActionUrl =
        shareTarget === null
            ? null
            : shareTarget.type === 'folder'
              ? `/teacher/materials/folders/${shareTarget.id}/share`
              : `/teacher/materials/files/${shareTarget.id}/share`;

    return (
        <>
            <PrivateSeoHead title={folder.name} />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {folder.name}
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Browse assigned training materials. Share folders or
                            files with your batches.
                        </p>
                        {flash?.success && (
                            <p className="mt-2 text-sm text-emerald-700 dark:text-emerald-400">
                                {flash.success}
                            </p>
                        )}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() =>
                                setShareTarget({
                                    type: 'folder',
                                    id: folder.id,
                                    name: folder.name,
                                    selected: folder.shared_batch_ids ?? [],
                                })
                            }
                        >
                            Share folder
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={backHref}>
                                {folder.parent_id !== null
                                    ? 'Up one level'
                                    : 'Back to materials'}
                            </Link>
                        </Button>
                    </div>
                </div>

                <nav className="flex flex-wrap items-center gap-2 text-sm">
                    <Link
                        href="/teacher/materials"
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
                                href={`/teacher/materials/folders/${crumb.id}`}
                                className="hover:text-foreground"
                            >
                                {crumb.name}
                            </Link>
                        </span>
                    ))}
                </nav>

                {folders.length === 0 && files.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        This folder is empty.
                    </div>
                ) : (
                    <div className="space-y-8">
                        {folders.length > 0 && (
                            <section className="space-y-3">
                                <h2 className="text-sm font-medium text-muted-foreground">
                                    Folders
                                </h2>
                                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {folders.map((child) => (
                                        <MaterialFolderCard
                                            key={`folder-${child.id}`}
                                            name={child.name}
                                            href={`/teacher/materials/folders/${child.id}`}
                                            itemLabel="Folder"
                                            colorIndex={child.id}
                                            onShare={() =>
                                                setShareTarget({
                                                    type: 'folder',
                                                    id: child.id,
                                                    name: child.name,
                                                    selected:
                                                        child.shared_batch_ids ??
                                                        [],
                                                })
                                            }
                                        />
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
                                        <MaterialFileCard
                                            key={`file-${file.id}`}
                                            name={file.name}
                                            extension={file.extension}
                                            kind={file.kind ?? 'file'}
                                            url={file.url}
                                            embedUrl={file.embed_url}
                                            uploadedAt={file.created_at}
                                            downloadHref={
                                                (file.kind ?? 'file') === 'file'
                                                    ? `/teacher/materials/files/${file.id}/download`
                                                    : null
                                            }
                                            onShare={() =>
                                                setShareTarget({
                                                    type: 'file',
                                                    id: file.id,
                                                    name: file.name,
                                                    selected:
                                                        file.shared_batch_ids ??
                                                        [],
                                                })
                                            }
                                        />
                                    ))}
                                </div>
                            </section>
                        )}
                    </div>
                )}
            </div>

            <ShareToBatchesModal
                open={shareTarget !== null}
                batches={batches}
                target={shareTarget}
                actionUrl={shareActionUrl}
                onClose={() => setShareTarget(null)}
                facultyOnly
            />
        </>
    );
}

TeacherMaterialFolder.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'My Materials',
            href: '/teacher/materials',
        },
        ...props.breadcrumbs.map((crumb) => ({
            title: crumb.name,
            href: `/teacher/materials/folders/${crumb.id}`,
        })),
    ],
});
