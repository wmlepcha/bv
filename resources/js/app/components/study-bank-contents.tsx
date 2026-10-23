import { router } from '@inertiajs/react';
import {
    destroy as destroyLearningItem,
    store as storeLearningItem,
} from '@/routes/student/my-learning';
import { download as studyBankDownload } from '@/routes/student/study-bank/files';
import { show as studyBankFolder } from '@/routes/student/study-bank/folders';
import { MaterialFileCard } from '@app/components/material-file-card';
import { MaterialFolderCard } from '@app/components/material-folder-card';

export type StudyBankFolder = {
    id: number;
    name: string;
    type: string;
};

export type StudyBankFile = {
    id: number;
    name: string;
    extension: string;
    kind?: 'file' | 'youtube' | 'youtube_playlist' | 'link';
    url?: string | null;
    embed_url?: string | null;
    playlist_items?: Array<{ video_id: string; title: string }> | null;
    preview_url?: string | null;
    folder_name?: string | null;
    created_at: string | null;
    learning_item_id?: number | null;
    type: string;
};

export function StudyBankContents({
    folders,
    files,
}: {
    folders: StudyBankFolder[];
    files: StudyBankFile[];
}) {
    return (
        <div className="space-y-8">
            {folders.length > 0 && (
                <section className="space-y-3">
                    <h2 className="text-sm font-medium text-muted-foreground">
                        Folders
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {folders.map((folder) => (
                            <MaterialFolderCard
                                key={`folder-${folder.id}`}
                                name={folder.name}
                                href={studyBankFolder.url(folder.id)}
                                itemLabel="Folder"
                                colorIndex={folder.id}
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
                                playlistItems={file.playlist_items}
                                playlistItemsUrl={
                                    (file.kind ?? 'file') === 'youtube_playlist'
                                        ? `/student/study-bank/files/${file.id}/playlist-items`
                                        : null
                                }
                                uploadedAt={file.created_at}
                                categoryLabel={file.folder_name ?? undefined}
                                downloadHref={
                                    (file.kind ?? 'file') === 'file'
                                        ? studyBankDownload.url(file.id)
                                        : null
                                }
                                isSavedToLearning={
                                    file.learning_item_id != null
                                }
                                onToggleLearning={() => {
                                    if (file.learning_item_id != null) {
                                        router.delete(
                                            destroyLearningItem.url(
                                                file.learning_item_id,
                                            ),
                                            {
                                                preserveScroll: true,
                                                onSuccess: () =>
                                                    router.flushAll(),
                                            },
                                        );

                                        return;
                                    }

                                    router.post(
                                        storeLearningItem.url(),
                                        { material_file_id: file.id },
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => router.flushAll(),
                                        },
                                    );
                                }}
                            />
                        ))}
                    </div>
                </section>
            )}
        </div>
    );
}
