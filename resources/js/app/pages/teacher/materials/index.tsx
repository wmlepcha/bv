import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { MaterialFileCard } from '@app/components/material-file-card';
import { MaterialFolderCard } from '@app/components/material-folder-card';
import {
    ShareToBatchesModal
    
} from '@app/components/share-to-batches-modal';
import type {ShareBatchOption} from '@app/components/share-to-batches-modal';
import { PrivateSeoHead } from '@shared/components/seo-head';

type MaterialFolder = {
    id: number;
    name: string;
    type: string;
    shared_batch_ids?: number[];
};

type MaterialFile = {
    id: number;
    name: string;
    extension: string;
    kind?: 'file' | 'youtube' | 'youtube_playlist' | 'link';
    url?: string | null;
    embed_url?: string | null;
    playlist_items?: Array<{ video_id: string; title: string }> | null;
    folder_name: string | null;
    created_at: string | null;
    type: string;
    shared_batch_ids?: number[];
};

type Props = {
    materials: {
        folders: MaterialFolder[];
        files: MaterialFile[];
    };
    batches: ShareBatchOption[];
};

type ShareTarget = {
    type: 'folder' | 'file';
    id: number;
    name: string;
    selected: number[];
};

export default function TeacherMaterialsIndex({
    materials,
    batches,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };
    const [shareTarget, setShareTarget] = useState<ShareTarget | null>(null);

    const isEmpty =
        materials.folders.length === 0 && materials.files.length === 0;

    const shareActionUrl =
        shareTarget === null
            ? null
            : shareTarget.type === 'folder'
              ? `/teacher/materials/folders/${shareTarget.id}/share`
              : `/teacher/materials/files/${shareTarget.id}/share`;

    return (
        <>
            <PrivateSeoHead title="My Materials" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        My Materials
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Folders and files assigned to you by a training manager.
                        Share them with your batches — not individual students.
                    </p>
                    {flash?.success && (
                        <p className="mt-2 text-sm text-emerald-700 dark:text-emerald-400">
                            {flash.success}
                        </p>
                    )}
                </div>

                <nav className="flex flex-wrap items-center gap-2 text-sm">
                    <span className="text-foreground">Root</span>
                </nav>

                {isEmpty ? (
                    <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        No materials have been assigned to you yet.
                    </div>
                ) : (
                    <div className="space-y-8">
                        {materials.folders.length > 0 && (
                            <section className="space-y-3">
                                <h2 className="text-sm font-medium text-muted-foreground">
                                    Folders
                                </h2>
                                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {materials.folders.map((folder) => (
                                        <MaterialFolderCard
                                            key={`folder-${folder.id}`}
                                            name={folder.name}
                                            href={`/teacher/materials/folders/${folder.id}`}
                                            itemLabel="Folder"
                                            colorIndex={folder.id}
                                            onShare={() =>
                                                setShareTarget({
                                                    type: 'folder',
                                                    id: folder.id,
                                                    name: folder.name,
                                                    selected:
                                                        folder.shared_batch_ids ??
                                                        [],
                                                })
                                            }
                                        />
                                    ))}
                                </div>
                            </section>
                        )}

                        {materials.files.length > 0 && (
                            <section className="space-y-3">
                                <h2 className="text-sm font-semibold tracking-tight">
                                    Materials
                                </h2>
                                <div className="rounded-2xl border border-border/60 bg-muted/20 p-2 sm:p-3">
                                    {materials.files.map((file) => (
                                        <MaterialFileCard
                                            key={`file-${file.id}`}
                                            name={file.name}
                                            extension={file.extension}
                                            kind={file.kind ?? 'file'}
                                            url={file.url}
                                            embedUrl={file.embed_url}
                                            playlistItems={file.playlist_items}
                                            playlistItemsUrl={
                                                (file.kind ?? 'file') ===
                                                'youtube_playlist'
                                                    ? `/teacher/materials/files/${file.id}/playlist-items`
                                                    : null
                                            }
                                            uploadedAt={file.created_at}
                                            categoryLabel={
                                                file.folder_name ?? undefined
                                            }
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

TeacherMaterialsIndex.layout = {
    breadcrumbs: [
        {
            title: 'My Materials',
            href: '/teacher/materials',
        },
    ],
};
